<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
class AdminController {
    private Database $db;
    public function __construct(Database $db){ $this->db=$db; }
    public function ban(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $user_id = (int)($input['user_id'] ?? 0);
        $reason = trim($input['reason'] ?? 'Ban');
        if(!$user_id) Response::json(['error'=>'user_id required'],400);
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('UPDATE users SET banned = 1, ban_reason = ? WHERE id = ?');
        $stmt->execute([$reason, $user_id]);
        Response::json(['message'=>'User banned','user_id'=>$user_id]);
    }
}
