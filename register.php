<?php
require 'config.php';
$error = "";

// Capture Referral
$ref_code = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $pin = $_POST['pin']; // New Field
    $posted_ref = trim($_POST['referral_code']);

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        $error = "Email already registered.";
    } else {
        // Find Referrer
        $referrer_id = NULL;
        if (!empty($posted_ref)) {
            $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $refStmt->execute([$posted_ref]);
            $refRow = $refStmt->fetch();
            if($refRow) $referrer_id = $refRow['id'];
        }

        // Insert User with PIN
        $sql = "INSERT INTO users (username, email, password, referrer_id, security_pin) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), $referrer_id, $pin]);
        
        header("Location: login.php?msg=registered");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Create Account</h2>
        <?php if($error): ?><div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="referral_code" value="<?= $ref_code ?>">
            
            <div class="space-y-4">
                <input type="text" name="username" placeholder="Username" class="w-full border p-3 rounded-lg" required>
                <input type="email" name="email" placeholder="Email" class="w-full border p-3 rounded-lg" required>
                <input type="password" name="password" placeholder="Password" class="w-full border p-3 rounded-lg" required>
                
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Security PIN (4 Digits)</label>
                    <input type="text" name="pin" maxlength="4" pattern="\d{4}" placeholder="e.g. 1234" class="w-full border p-3 rounded-lg bg-gray-50 tracking-widest font-bold text-center" required>
                    <p class="text-xs text-gray-400 mt-1">You will need this to reset your password.</p>
                </div>

                <button class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700">Register</button>
            </div>
        </form>
        <p class="mt-4 text-center text-sm">Have an account? <a href="login.php" class="text-blue-600 font-bold">Login</a></p>
    </div>
</body>
</html>