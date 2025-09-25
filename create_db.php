<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '12345');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS laravel');
    echo "Database 'laravel' created successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
