<?php
// simple env loader

if (!function_exists('env')) {
    function env($k, $d=null){
        static $cache=null;
        if($cache===null){
            $cache=[];
            $p=__DIR__.'/.env';
            if(file_exists($p)){
                foreach(explode("\n",file_get_contents($p)) as $line){
                    if(trim($line)==='' || str_starts_with(trim($line),'#')) continue;
                    [$kk,$vv]=array_map('trim',explode('=', $line, 2)+[1=>null]);
                    $cache[$kk]=$vv;
                }
            }
        }
        return $cache[$k] ?? $d;
    }
}

return [
    'db'=>[
        'host'=>env('DB_HOST','127.0.0.1'),
        'port'=>env('DB_PORT','3306'),
        'name'=>env('DB_NAME','piggyquest'),
        'user'=>env('DB_USER','root'),
        'pass'=>env('DB_PASS','root'),
        'charset'=>'utf8',
    ],
    'jwt_secret'=>env('JWT_SECRET','change_this_to_secret'),
    'feed_cooldown'=> (int) (env('FEED_COOLDOWN_SECONDS','10')),
];