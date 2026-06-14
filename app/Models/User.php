<?php

namespace App\Models;

use Core\Database;

class User
{
    /**
     * Tim user theo email.
     *
     * Input:
     * - $email: email can tim trong bang users
     *
     * Output:
     * - array neu tim thay user
     * - null neu email chua ton tai
     */
    public function findByEmail(string $email): ?array
    {
        // Lay ket noi PDO dung chung cua ung dung.
        $db = Database::connection();

        // Dung prepared statement de tranh SQL injection khi email den tu request.
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Tao user moi.
     *
     * Input:
     * - $username: ten hien thi cua user
     * - $email: email da validate va chua ton tai
     * - $passwordHash: mat khau da hash bang password_hash()
     *
     * Output:
     * - array thong tin public cua user vua tao
     * - khong tra ve password_hash de tranh lo thong tin nhay cam
     */
    public function create(string $username, string $email, string $passwordHash): array
    {
        // Lay ket noi PDO dung chung cua ung dung.
        $db = Database::connection();

        // Insert user moi vao database.
        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash, created_at)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => $db->lastInsertId(),  //lastInsertId() hàm của PDO
            'username' => $username,
            'email' => $email,
        ];
    }
}
