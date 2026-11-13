<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function conexao(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/config.php';
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['db_host'],
                $config['db_port'],
                $config['db_name']
            );
            self::$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Alinha o CURDATE() do MySQL com o fuso do PHP (America/Sao_Paulo, UTC-3).
            self::$pdo->exec("SET time_zone = '-03:00'");
        }

        return self::$pdo;
    }
}
