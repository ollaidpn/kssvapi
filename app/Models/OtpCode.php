<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'type',
        'pending_data',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'pending_data' => 'array',
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Check if the OTP is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Mark the OTP as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Generate a new 6-digit OTP code
     */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for registration
     */
    public static function createForRegistration(string $email, array $pendingData): self
    {
        // Invalidate any existing OTPs for this email and type
        self::where('email', $email)
            ->where('type', 'registration')
            ->where('used', false)
            ->update(['used' => true]);

        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'type' => 'registration',
            'pending_data' => $pendingData,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Create a new OTP for password reset
     */
    public static function createForPasswordReset(string $email): self
    {
        // Invalidate any existing OTPs for this email and type
        self::where('email', $email)
            ->where('type', 'password_reset')
            ->where('used', false)
            ->update(['used' => true]);

        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Find a valid OTP by email, code and type
     */
    public static function findValid(string $email, string $code, string $type): ?self
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
