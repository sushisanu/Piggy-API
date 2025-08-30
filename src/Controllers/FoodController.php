<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
class FoodController {
    private Database $db;
    public function __construct(Database $db){ $this->db = $db; }
    public function catalog(): void {
        $pdo = $this->db->pdo();
        $rows = $pdo->query('SELECT id, name, category FROM foods ORDER BY id')->fetchAll();
        Response::json(['catalog'=>$rows]);
    }
    public function inventory(int $player_id): void {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('SELECT uf.food_id, f.name, uf.qty FROM user_food uf JOIN foods f ON uf.food_id = f.id WHERE uf.player_id = ?');
        $stmt->execute([$player_id]);
        $rows = $stmt->fetchAll();
        Response::json(['inventory'=>$rows]);
    }
    public function addInventory(int $player_id): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $food_id = (int)($input['food_id'] ?? 0);
        $qty = (int)($input['qty'] ?? 0);
        if(!$food_id || $qty <= 0) Response::json(['error'=>'food_id and positive qty required'],400);
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('INSERT INTO user_food (player_id, food_id, qty) VALUES (?,?,?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)');
        $stmt->execute([$player_id, $food_id, $qty]);
        Response::json(['message'=>'Inventory updated']);
    }
}
