<?php

namespace App;

use PDO;
use PDOException;

class db 
{
    public function getPDO() : PDO {
        $host = 'localhost';
        $db   = 'medical_record_db';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'DB Connection Failed: ' . $e->getMessage()]);
            exit;
        }
    }  
}
