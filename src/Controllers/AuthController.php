<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
class AuthController {
    private Database $db;
    public function __construct(Database $db){ $this->db=$db; }
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if(!$username || !$password) Response::json(['error'=>'username and password required'],400);
        $pdo = $this->db->pdo();
        // check exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if($stmt->fetch()) Response::json(['error'=>'username already exists'],400);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->beginTransaction();
        try{
            $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?,?)');
            $stmt->execute([$username,$hash]);
            $uid = (int)$pdo->lastInsertId();
            // create player
            $stmt = $pdo->prepare('INSERT INTO players (user_id, level, exp, coin) VALUES (?,1,0,0)');
            $stmt->execute([$uid]);
            $player_id = (int)$pdo->lastInsertId();
            // seed pigs (10)
            $stmt = $pdo->prepare('INSERT INTO pigs (player_id, name) VALUES (?,?)');
            for($i=1;$i<=10;$i++){
                $stmt->execute([$player_id, "Pig #$i"]);
            }
            // seed some food (Food catalog is in foods_table; user inventory in user_food)
            $pdo->prepare('INSERT INTO user_food (player_id, food_id, qty) VALUES (?,?,?)')->execute([$player_id,1,10]);
            $pdo->prepare('INSERT INTO user_food (player_id, food_id, qty) VALUES (?,?,?)')->execute([$player_id,2,10]);
            $pdo->prepare('INSERT INTO user_food (player_id, food_id, qty) VALUES (?,?,?)')->execute([$player_id,3,10]);
            // create daily quests for today
            $today = date('Y-m-d');
            $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                ->execute([$player_id,'feed_5',$today,0,5,10,0]);
            $pdo->prepare('INSERT INTO user_quests (player_id, quest_key, date, progress, goal, reward_coin, claimed) VALUES (?,?,?,?,?,?,?)')
                ->execute([$player_id,'feed_10',$today,0,10,25,0]);
            $pdo->commit();
            Response::json(['message'=>'registered','user_id'=>$uid]);
        }catch(\Throwable $e){
            $pdo->rollBack();
            Response::json(['error'=>'Registration failed','detail'=>$e->getMessage()],500);
        }
    }
    public function login($jwt): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if(!$username || !$password) Response::json(['error'=>'username and password required'],400);
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if(!$row || !password_verify($password, $row['password'])) Response::json(['error'=>'Invalid credentials'],401);
        $token = $jwt->sign(['uid'=>(int)$row['id']]);
        Response::json(['token'=>$token]);
    }
}
