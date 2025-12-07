<?php
// config.php

$host = 'localhost';
$dbname = 'earning_platform'; // Make sure this matches your DB name
$user = 'root';
$pass = '';




try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Enable Exceptions for Real World Error Handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Use real prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Secure Session Start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    // In production, log this to a file, don't show the user the raw error
    die("Database Connection Error. Please try again later.");
}
?>