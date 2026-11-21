<?php

namespace App\Services\Security;

use App\Models\Exam;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate a cryptographic-safe 6-character examination token.
     */
    public function generateToken(int $length = 6): string
    {
        // Avoid easily confused characters: 0, O, 1, I, L
        $characters = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $charLength = strlen($characters);
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, $charLength - 1)];
        }

        return $token;
    }

    /**
     * Activate or regenerate token for an exam.
     */
    public function activateToken(Exam $exam, ?int $durationMinutes = 15): Exam
    {
        $exam->update([
            'token' => $this->generateToken(6),
            'token_aktif' => true,
            'token_expired_at' => $durationMinutes ? now()->addMinutes($durationMinutes) : null,
        ]);

        return $exam;
    }

    /**
     * Deactivate exam token.
     */
    public function deactivateToken(Exam $exam): Exam
    {
        $exam->update([
            'token_aktif' => false,
            'token' => null,
            'token_expired_at' => null,
        ]);

        return $exam;
    }

    /**
     * Rotate token if expired. Returns true if rotated.
     */
    public function rotateExpiredToken(Exam $exam): bool
    {
        if ($exam->token_aktif && $exam->token_expired_at && $exam->token_expired_at->lte(now())) {
            $this->activateToken($exam, 15);
            return true;
        }

        return false;
    }

    /**
     * Verify student submitted token against active exam token.
     */
    public function verifyToken(Exam $exam, string $submittedToken): bool
    {
        $this->rotateExpiredToken($exam);

        if (! $exam->token_aktif || ! $exam->token) {
            return false;
        }

        $cleanToken = strtoupper(trim($submittedToken));
        return hash_equals(strtoupper((string) $exam->token), $cleanToken);
    }
}
