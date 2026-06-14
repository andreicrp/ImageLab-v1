<?php

require_once __DIR__ . '/Config.php';

/**
 * Database class using PDO pattern
 * Gracefully handles db unavailability logging errors to logs/database_error.log
 */
class Database {
    private static ?PDO $connection = null;

    /**
     * Retrieve global PDO Connection instance
     * 
     * @return PDO|null Returns connection or null if database is offline/not initialized
     */
    public static function getConnection(): ?PDO {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=utf8mb4",
                    Config::DB_HOST,
                    Config::DB_NAME
                );
                
                self::$connection = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2 // Avoid hanging request if MySQL is offline
                ]);
            } catch (PDOException $e) {
                self::$connection = null;
                
                // Write error log
                $logMsg = date('[Y-m-d H:i:s] ') . "Connection failed: " . $e->getMessage() . "\n";
                @file_put_contents(Config::LOG_PATH . 'database_error.log', $logMsg, FILE_APPEND);
            }
        }
        return self::$connection;
    }
}
