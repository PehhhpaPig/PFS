<?php
/** Minimal single‑file TOTP helper (RFC 6238). ©2025 MIT */
final class Totp
{
    public static function generateRandomSecret(int $len = 20): string
    { return self::base32Encode(random_bytes($len)); }

    public static function getUri(string $secret, string $account, string $issuer): string
    { return 'otpauth://totp/'.rawurlencode("$issuer:$account")."?secret=$secret&issuer=".rawurlencode($issuer); }

    public static function now(string $secret): string
    { return self::code($secret, time()); }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = str_pad(trim($code), 6, '0', STR_PAD_LEFT);
        if (!ctype_digit($code)) return false;
        $t = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($code, self::code($secret, $t + 30*$i))) return true;
        }
        return false;
    }

    /* ---- internals ---- */
    private const DIGITS = 6;
    private static function code(string $secret, int $ts): string
    {
        $ctr = intdiv($ts, 30);
        $key = self::base32Decode($secret);
        $binCtr = pack('N*', 0).pack('N*', $ctr);
        $hash = hash_hmac('sha1', $binCtr, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $chunk  = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        return str_pad((string)($chunk % 10**self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }
    private static function base32Encode(string $bin): string
    {
        $a='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';
        foreach(str_split($bin) as $c) $bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);
        $enc=''; foreach(str_split($bits,5) as $ch){$enc.=$a[bindec(str_pad($ch,5,'0',STR_PAD_RIGHT))];}
        return $enc; }
    private static function base32Decode(string $str): string
    {
        $a='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';
        foreach(str_split(strtoupper($str)) as $c){$bits.=str_pad(decbin(strpos($a,$c)),5,'0',STR_PAD_LEFT);} $bin='';
        foreach(str_split($bits,8) as $b){ if(strlen($b)==8) $bin.=chr(bindec($b)); }
        return $bin; }
}