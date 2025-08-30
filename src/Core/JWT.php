<?php
declare(strict_types=1);
namespace App\Core;
class JWT {
    private string $secret;
    public function __construct(string $secret){ $this->secret=$secret; }
    private function b64url(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
    private function b64url_decode(string $d): string { $rem = strlen($d) % 4; if($rem) $d .= str_repeat('=', 4-$rem); return base64_decode(strtr($d, '-_', '+/')); }
    public function sign(array $payload, int $exp=86400): string {
        $header=['alg'=>'HS256','typ'=>'JWT'];
        $payload['iat']=time(); $payload['exp']=time()+$exp;
        $segments=[ $this->b64url(json_encode($header)), $this->b64url(json_encode($payload)) ];
        $sig = hash_hmac('sha256', implode('.', $segments), $this->secret, true);
        $segments[] = $this->b64url($sig);
        return implode('.', $segments);
    }
    public function verify(string $token): array {
        $parts = explode('.', $token);
        if(count($parts)!==3) throw new \Exception('Invalid token');
        [$h,$p,$s]=$parts;
        $expected = $this->b64url(hash_hmac('sha256', "$h.$p", $this->secret, true));
        if(!hash_equals($expected,$s)) throw new \Exception('Signature mismatch');
        $payload = json_decode($this->b64url_decode($p), true);
        if(!is_array($payload)) throw new \Exception('Invalid payload');
        if(($payload['exp']??0) < time()) throw new \Exception('Token expired');
        return $payload;
    }
}
