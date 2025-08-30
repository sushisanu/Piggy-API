<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
class PigController {
    private Database $db;
    private int $feedCooldown;
    public function __construct(Database $db, int $feedCooldown) { $this->db=$db; $this->feedCooldown=$feedCooldown; }
    public function list($player_id): void {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('SELECT id, name, last_fed_at FROM pigs WHERE player_id = ? ORDER BY id');
        $stmt->execute([$player_id]);
        $rows = $stmt->fetchAll();
        Response::json(['pigs'=>$rows]);
    }
    public function feed(int $player_id): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pig_id = (int)($input['pig_id'] ?? 0);
        $food_id = (int)($input['food_id'] ?? 0);
        if(!$pig_id || !$food_id) Response::json(['error'=>'pig_id and food_id required'],400);
        $pdo = $this->db->pdo();
        try{
            $pdo->beginTransaction();
            // check pig belongs to player and lock
            $stmt = $pdo->prepare('SELECT id, player_id, last_fed_at FROM pigs WHERE id = ? FOR UPDATE');
            $stmt->execute([$pig_id]);
            $pig = $stmt->fetch();
            if(!$pig || (int)$pig['player_id'] !== $player_id) { $pdo->rollBack(); Response::json(['error'=>'Pig not found'],404); }
            // global user cooldown (users table)
            $stmt = $pdo->prepare('SELECT last_feed_at FROM players WHERE id = ? FOR UPDATE');
            $stmt->execute([$player_id]);
            $pl = $stmt->fetch();
            $last_feed = $pl['last_feed_at'] ?? null;
            if($last_feed){
                $since = time() - strtotime($last_feed);
                if($since < $this->feedCooldown){ $pdo->rollBack(); Response::json(['error'=>'Cooldown: wait '.($this->feedCooldown-$since).'s'],429); }
            }
            // per-pig cooldown
            if($pig['last_fed_at']){
                $sincePig = time() - strtotime($pig['last_fed_at']);
                if($sincePig < $this->feedCooldown){ $pdo->rollBack(); Response::json(['error'=>'Pig cooldown: wait '.($this->feedCooldown-$sincePig).'s'],429); }
            }
            // check inventory
            $stmt = $pdo->prepare('SELECT qty FROM user_food WHERE player_id = ? AND food_id = ? FOR UPDATE');
            $stmt->execute([$player_id, $food_id]);
            $inv = $stmt->fetch();
            if(!$inv || (int)$inv['qty'] <= 0){ $pdo->rollBack(); Response::json(['error'=>'No food in inventory'],400); }
            // consume food
            $pdo->prepare('UPDATE user_food SET qty = qty - 1 WHERE player_id = ? AND food_id = ?')->execute([$player_id, $food_id]);
            // update pig last_fed_at
            $pdo->prepare('UPDATE pigs SET last_fed_at = NOW() WHERE id = ?')->execute([$pig_id]);
            // update player exp and maybe coin
            $pdo->prepare('UPDATE players SET exp = exp + 5, last_feed_at = NOW() WHERE id = ?')->execute([$player_id]);
            // log feed
            $pdo->prepare('INSERT INTO feed_logs (player_id, pig_id, food_id, created_at) VALUES (?,?,?, NOW())')->execute([$player_id, $pig_id, $food_id]);
            // update daily quests progress for today
            $today = date('Y-m-d');
            $pdo->prepare('UPDATE user_quests SET progress = LEAST(progress + 1, goal) WHERE player_id = ? AND date = ?')->execute([$player_id, $today]);
            $pdo->commit();
            Response::json(['message'=>'Fed pig successfully','pig_id'=>$pig_id]);
        }catch(\Throwable $e){
            $pdo->rollBack();
            Response::json(['error'=>'Feed failed','detail'=>$e->getMessage()],500);
        }
    }
}
