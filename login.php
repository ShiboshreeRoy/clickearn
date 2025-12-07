<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if($user['role'] == 'admin') header("Location: admin_dashboard.php");
        else header("Location: dashboard.php");
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl mb-4 font-bold text-center text-green-600">Login</h2>
        <?php if(isset($error)) echo "<p class='text-red-500 mb-2'>$error</p>"; ?>
        <input type="email" name="email" placeholder="Email" class="w-full mb-3 p-2 border rounded" required>
        <input type="password" name="password" placeholder="Password" class="w-full mb-4 p-2 border rounded" required>
        <p class="mt-4 text-center"><a href="forgot_password.php" class="text-green-500">Forgotten Password !</a></p>
        <button class="w-full bg-green-500 text-white p-2 rounded hover:bg-green-600">Login</button>
        <p class="mt-4 text-center">No account? <a href="register.php" class="text-green-500">Register</a></p>
    </form>
</body>
</html>