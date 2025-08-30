<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
class QuestController {
    private Database $db;
    public function __construct(Database $db){ $this->db = $db; }
    private function ensureTodayQuests(int $player_id){
        $pdo = $this->db->pdo();
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM user_quests WHERE player_id = ? AND date = ?');
        $stmt->execute([$player_id, $today]);
        $c = (int)$stmt->fetchColumn();
        if($c === 0){
            $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                ->execute([$player_id,'feed_5',$today,0,5,10,0]);
            $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                ->execute([$player_id,'feed_10',$today,0,10,25,0]);
        }
    }
    public function daily(int $player_id): void {
        $this->ensureTodayQuests($player_id);
        $pdo = $this->db->pdo();
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT id, quest_key, progress, goal, reward_coin, claimed FROM user_quests WHERE player_id = ? AND date = ?');
        $stmt->execute([$player_id, $today]);
        $rows = $stmt->fetchAll();
        Response::json(['date'=>$today,'quests'=>$rows]);
    }
    public function claim(int $player_id): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $quest_id = (int)($input['quest_id'] ?? 0);
        if(!$quest_id) Response::json(['error'=>'quest_id required'],400);
        $pdo = $this->db->pdo();
        try{
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, progress, goal, reward_coin, claimed FROM user_quests WHERE id = ? AND player_id = ? FOR UPDATE');
            $stmt->execute([$quest_id, $player_id]);
            $q = $stmt->fetch();
            if(!$q){ $pdo->rollBack(); Response::json(['error'=>'Quest not found'],404); }
            if((int)$q['claimed']===1){ $pdo->rollBack(); Response::json(['error'=>'Already claimed'],400); }
            if((int)$q['progress'] < (int)$q['goal']){ $pdo->rollBack(); Response::json(['error'=>'Quest not completed'],400); }
            $pdo->prepare('UPDATE user_quests SET claimed = 1 WHERE id = ?')->execute([$quest_id]);
            $pdo->prepare('UPDATE players SET coin = coin + ? WHERE id = ?')->execute([(int)$q['reward_coin'], $player_id]);
            $pdo->commit();
            Response::json(['message'=>'Reward claimed','reward_coin'=>(int)$q['reward_coin']]);
        }catch(\Throwable $e){
            $pdo->rollBack();
            Response::json(['error'=>'Claim failed','detail'=>$e->getMessage()],500);
        }
    }
    // helper for cron: reset all players' quests (truncate and create new for today)
    public function resetAll(): void {
        $pdo = $this->db->pdo();
        $today = date('Y-m-d');
        $pdo->beginTransaction();
        try{
            // delete today's existing for safety? We'll create new per player
            $players = $pdo->query('SELECT id FROM players')->fetchAll();
            foreach($players as $p){
                $pid = (int)$p['id'];
                $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$pid,'feed_5',$today,0,5,10,0]);
                $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$pid,'feed_10',$today,0,10,25,0]);
            }
            $pdo->commit();
            echo "OK";
        }catch(\Throwable $e){
            $pdo->rollBack();
            echo 'ERROR: '.$e->getMessage();
        }
    }
}
