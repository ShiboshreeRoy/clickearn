<?php
require 'config.php';

$email = 'admin@platform.com';
$pass = 'admin123'; // <--- This will be your new password

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    // Update existing Admin password
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?")->execute([$hash, $email]);
    echo "<h1>Success!</h1><p>Admin password reset to: <b>$pass</b></p>";
} else {
    // Create new Admin
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute(['SuperAdmin', $email, $hash, 'admin']);
    echo "<h1>Success!</h1><p>New Admin created.</p><p>Email: <b>$email</b></p><p>Password: <b>$pass</b></p>";
}
echo "<br><a href='login.php'>Go to Login</a>";
?>