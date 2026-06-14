<?php

namespace Core;

use PDO;

class Database
{
    // Luu ket noi database dung chung cho moi lan goi Database::connection().
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        // Chi tao ket noi moi neu truoc do chua co ket noi nao.
        if (self::$connection === null) {
            // Lay thong tin ket noi tu config/database.php, config nay doc tu file .env.
            $config = require __DIR__ . '/../config/database.php';

            // DSN cho PDO: chon driver mysql, host, port, ten database va charset.
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";

            self::$connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    // Khi database co loi, PDO se throw exception de index.php bat bang try/catch.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // Mac dinh fetch du lieu dang associative array: ['id' => 1, 'name' => '...'].
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        // Tra ve ket noi dang co de controller/model su dung.
        return self::$connection;
    }
}
