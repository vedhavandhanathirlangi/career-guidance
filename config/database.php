<?php
declare(strict_types=1);

$host     = 'localhost';
$dbname   = 'career_guidance_db';
$username = 'root';
$password = ''; // Typical local XAMPP setup only. Use a real password in production.

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check that MySQL is running and config/database.php is correct.');
}
