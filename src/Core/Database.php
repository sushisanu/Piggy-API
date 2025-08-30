<?php
declare(strict_types=1);
namespace App\Core;
use PDO, PDOException;
class Database {
    private PDO $pdo;
    public function __construct(array $cfg){
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],$cfg['port'],$cfg['name'],$cfg['charset']);
        try{
            $this->pdo = new PDO($dsn,$cfg['user'],$cfg['pass'], [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
            ]);
        }catch(PDOException $e){
            http_response_code(500);
            echo json_encode(['error'=>'DB connection failed','detail'=>$e->getMessage()]);
            exit;
        }
    }
    public function pdo(): PDO { return $this->pdo; }
}
