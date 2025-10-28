<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserSecurity extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_security';

    protected $fillable = [
        'user_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_failed_attempts',
        'last_2fa_at',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'last_2fa_at' => 'datetime',
    ];

    public function consumeRecoveryCode(string $plain): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $now = now()->toISOString();
        foreach ($codes as $idx => $code) {
            if (!is_array($code)) continue;
            if (($code['used_at'] ?? null) !== null) continue;
            $value = (string) ($code['value'] ?? '');
            // constant-time compare
            if (hash_equals($value, $plain)) {
                $codes[$idx]['used_at'] = $now;
                $this->two_factor_recovery_codes = $codes;
                $this->save();
                return true;
            }
        }
        return false;
    }
}
