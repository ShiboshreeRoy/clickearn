<?php
require 'config.php';
$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pin = $_POST['pin'];
    $new_pass = $_POST['new_password'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND security_pin = ?");
    $stmt->execute([$email, $pin]);
    
    if ($stmt->rowCount() > 0) {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $email]);
        $msg = "Password reset successfully! You can now login.";
    } else {
        $error = "Invalid Email or Security PIN.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-lock-open"></i>
            </div>
            <h2 class="text-2xl font-bold">Recover Account</h2>
            <p class="text-gray-500 text-sm">Enter your email and 4-digit PIN.</p>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center"><?= $msg ?> <a href="login.php" class="underline font-bold">Login</a></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="Email Address" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
            <input type="text" name="pin" maxlength="4" placeholder="Security PIN (4 Digits)" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
            <input type="password" name="new_password" placeholder="New Password" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
            
            <button class="w-full bg-gray-900 text-white font-bold py-3 rounded-lg hover:bg-gray-800 transition">Reset Password</button>
        </form>
        
        <p class="mt-6 text-center text-sm text-gray-500">
            Remembered it? <a href="login.php" class="text-blue-600 font-bold">Back to Login</a>
        </p>
    </div>
</body>
</html>