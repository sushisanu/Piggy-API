<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../src/Core/Database.php';
require __DIR__ . '/../src/Controllers/QuestController.php';
use App\Core\Database;
use App\Controllers\QuestController;
$config = require __DIR__ . '/../config.php';
$db = new Database($config['db']);
$qc = new QuestController($db);
$qc->resetAll();
echo PHP_EOL;
