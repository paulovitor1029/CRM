<?php

namespace App\Support;

class Totp
{
    public static function generateCode(string $secret, int $timeStep = 30, int $digits = 6, int $t0 = 0): string
    {
        $counter = (int) floor((time() - $t0) / $timeStep);
        return self::hotp($secret, $counter, $digits);
    }

    public static function verifyCode(string $secret, string $code, int $window = 1, int $timeStep = 30, int $digits = 6, int $t0 = 0): bool
    {
        $currentCounter = (int) floor((time() - $t0) / $timeStep);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($secret, $currentCounter + $i, $digits), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function hotp(string $secret, int $counter, int $digits = 6): string
    {
        $key = Base32::decode($secret);
        // 64-bit big-endian counter; current epoch fits in lower 32 bits for TOTP
        $binCounter = pack('N2', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        $mod = 10 ** $digits;
        return str_pad((string) ($truncated % $mod), $digits, '0', STR_PAD_LEFT);
    }
}
