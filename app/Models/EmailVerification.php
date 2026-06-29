<?php

namespace App\Models;

use Core\Database;

class EmailVerification
{
    /**
     * Tao ban ghi xac thuc email moi.
     */
    public function create(
        int $userId,
        string $email,
        string $codeHash,
        string $expiresAt,
        string $createdAt,
        string $updatedAt
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare(
            'INSERT INTO email_verifications (user_id, email, code_hash, expires_at, attempt_count, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $email,
            $codeHash,
            $expiresAt,
            $createdAt,
            $updatedAt,
        ]);
    }

    /**
     * Lay ma xac thuc moi nhat theo email, kem thong tin user.
     */
    public function findByEmailWithUser(string $email): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            'SELECT
                email_verifications.id AS verification_id,
                email_verifications.code_hash,
                email_verifications.expires_at,
                email_verifications.attempt_count,
                email_verifications.verified_at,
                users.id AS user_id,
                users.username,
                users.email,
                users.email_verified_at
             FROM email_verifications
             INNER JOIN users ON users.id = email_verifications.user_id
             WHERE email_verifications.email = ?
             ORDER BY email_verifications.id DESC
             LIMIT 1'
        );
        $stmt->execute([$email]);

        $verification = $stmt->fetch();

        return $verification ?: null;
    }

    /**
     * Cap nhat so lan nhap sai ma xac thuc.
     */
    public function updateAttemptCount(int $verificationId, int $attemptCount, string $updatedAt): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('UPDATE email_verifications SET attempt_count = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([
            $attemptCount,
            $updatedAt,
            $verificationId,
        ]);
    }

    /**
     * Danh dau ma xac thuc da duoc su dung.
     */
    public function markVerified(int $verificationId, string $verifiedAt, string $updatedAt): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('UPDATE email_verifications SET verified_at = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([
            $verifiedAt,
            $updatedAt,
            $verificationId,
        ]);
    }
}