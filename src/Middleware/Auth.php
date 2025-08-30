<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\JWT;
use App\Core\Response;
use App\Core\Database;
function getBearerToken(): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if(!$h) return null;
    if(str_starts_with($h,'Bearer ')) return substr($h,7);
    return null;
}
function ensureAuth(Database $db, JWT $jwt): array {
    $token = getBearerToken();
    if(!$token) Response::json(['error'=>'Missing Bearer token'],401);
    try{
        $payload = $jwt->verify($token);
    }catch(\Throwable $e){
        Response::json(['error'=>'Invalid token','detail'=>$e->getMessage()],401);
    }
    $uid = (int)($payload['uid'] ?? 0);
    if(!$uid) Response::json(['error'=>'Invalid token payload'],401);
    $pdo = $db->pdo();
    $stmt = $pdo->prepare('SELECT id, username, banned, role FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if(!$user) Response::json(['error'=>'User not found'],401);
    if((int)($user['banned'] ?? 0) === 1) Response::json(['error'=>'User is banned'],403);
    return $user;
}
