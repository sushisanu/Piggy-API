<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

require __DIR__ . '/../config.php';
require __DIR__ . '/../src/Core/Database.php';
require __DIR__ . '/../src/Core/JWT.php';
require __DIR__ . '/../src/Core/Response.php';
require __DIR__ . '/../src/Middleware/Auth.php';
require __DIR__ . '/../src/Controllers/AuthController.php';
require __DIR__ . '/../src/Controllers/PigController.php';
require __DIR__ . '/../src/Controllers/FoodController.php';
require __DIR__ . '/../src/Controllers/QuestController.php';
require __DIR__ . '/../src/Controllers/AdminController.php';

use App\Core\Database;
use App\Core\JWT;
use App\Core\Response;
use App\Middleware\ensureAuth;
use App\Controllers\AuthController;
use App\Controllers\PigController;
use App\Controllers\FoodController;
use App\Controllers\QuestController;
use App\Controllers\AdminController;

$config = require __DIR__ . '/../config.php';
$db = new Database($config['db']);
$jwt = new JWT($config['jwt_secret']);
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// simple router
if($uri === '/auth/register' && $method === 'POST'){
    $c = new AuthController($db);
    $c->register();
}
if($uri === '/auth/login' && $method === 'POST'){
    $c = new AuthController($db);
    $c->login($jwt);
}

// protected routes
function authUser(){ global $db, $jwt; return App\Middleware\ensureAuth($db, $jwt); }

if($uri === '/pigs' && $method === 'GET'){
    $user = authUser(); // returns user row with id=username? users.id
    // find player_id from users.id
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new PigController($db, (int)$config['feed_cooldown']);
    $c->list((int)$player['id']);
}

if($uri === '/pigs/feed' && $method === 'POST'){
    $user = authUser();
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new PigController($db, (int)$config['feed_cooldown']);
    $c->feed((int)$player['id']);
}

if($uri === '/foods/catalog' && $method === 'GET'){
    $c = new FoodController($db);
    $c->catalog();
}
if($uri === '/foods/inventory' && $method === 'GET'){
    $user = authUser();
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new FoodController($db);
    $c->inventory((int)$player['id']);
}
if($uri === '/inventory/add' && $method === 'POST'){
    $user = authUser();
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new FoodController($db);
    $c->addInventory((int)$player['id']);
}

if($uri === '/quests/daily' && $method === 'GET'){
    $user = authUser();
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new QuestController($db);
    $c->daily((int)$player['id']);
}
if($uri === '/quests/claim' && $method === 'POST'){
    $user = authUser();
    $stmt = $db->pdo()->prepare('SELECT id FROM players WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $player = $stmt->fetch();
    if(!$player) Response::json(['error'=>'Player not found'],404);
    $c = new QuestController($db);
    $c->claim((int)$player['id']);
}

// admin ban (admin must exist as user with role='admin')
if($uri === '/admin/ban' && $method === 'POST'){
    $user = authUser();
    if(($user['role'] ?? 'user') !== 'admin') Response::json(['error'=>'Admin only'],403);
    $c = new AdminController($db);
    $c->ban();
}

Response::json(['error'=>'Not Found'],404);
