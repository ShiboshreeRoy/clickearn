<?php
/**
 * User Settings & Profile
 * -----------------------
 */
ob_start();
session_start();
require_once 'config.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// 1. Handle Profile Update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $country = trim($_POST['country']);
    
    try {
        $pdo->prepare("UPDATE users SET full_name=?, phone=?, country=? WHERE id=?")
            ->execute([$name, $phone, $country, $user_id]);
        $msg = "Profile updated successfully!";
    } catch (Exception $e) {
        $error = "Update failed.";
    }
}

// 2. Handle Password Change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $real_pass = $stmt->fetchColumn();

    if (password_verify($current, $real_pass)) {
        if ($new === $confirm) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user_id]);
            $msg = "Password changed successfully!";
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Fetch User Data
$user = $pdo->query("SELECT * FROM users WHERE id=$user_id")->fetch(PDO::FETCH_ASSOC);
$plan = $user['membership_level'] ?? 'free';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex h-screen overflow-hidden">

    <aside class="w-72 bg-white border-r border-slate-200 hidden md:flex flex-col z-20">
        <div class="h-20 flex items-center px-8 border-b border-slate-100">
            <div class="text-2xl font-bold text-indigo-600">ClickEarn</div>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium"><i class="fas fa-home w-5"></i> Dashboard</a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-semibold"><i class="fas fa-cog w-5"></i> Settings</a>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl font-medium"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white border-b border-slate-200 flex md:hidden items-center justify-between px-4">
            <div class="font-bold text-lg text-indigo-600">Settings</div>
            <a href="dashboard.php" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-12">
            
            <h1 class="text-3xl font-bold text-slate-900 mb-8">Account Settings</h1>

            <?php if($msg): ?>
                <div class="bg-emerald-100 text-emerald-700 p-4 rounded-xl mb-6 font-bold flex items-center gap-2"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold flex items-center gap-2"><i class="fas fa-times-circle"></i> <?= $error ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm text-center">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto flex items-center justify-center text-4xl text-white font-bold mb-4 shadow-lg">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($user['username']) ?></h2>
                        <p class="text-slate-500 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <div class="mt-6 flex justify-center gap-2">
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold uppercase tracking-wide">
                                <?= ucfirst($plan) ?> Plan
                            </span>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold uppercase tracking-wide">
                                Verified
                            </span>
                        </div>
                    </div>

                    <div class="bg-indigo-600 p-6 rounded-3xl shadow-lg text-white">
                        <h3 class="font-bold text-lg mb-2">Upgrade Membership</h3>
                        <p class="text-indigo-100 text-sm mb-4">Unlock more daily tasks and priority support.</p>
                        <a href="upgrade.php" class="block w-full bg-white text-indigo-600 text-center font-bold py-2 rounded-xl hover:bg-indigo-50 transition">View Plans</a>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Personal Information</h3>
                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Full Name</label>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50" placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Phone Number</label>
                                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50" placeholder="+880 17...">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Email Address</label>
                                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border border-slate-200 rounded-xl px-4 py-3 bg-slate-100 text-slate-400 cursor-not-allowed" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Country</label>
                                    <select name="country" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50">
                                        <option value="Bangladesh" <?= ($user['country'] ?? '') == 'Bangladesh' ? 'selected' : '' ?>>Bangladesh</option>
                                        <option value="India" <?= ($user['country'] ?? '') == 'India' ? 'selected' : '' ?>>India</option>
                                        <option value="Pakistan" <?= ($user['country'] ?? '') == 'Pakistan' ? 'selected' : '' ?>>Pakistan</option>
                                        <option value="Other" <?= ($user['country'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="bg-slate-900 text-white font-bold py-3 px-6 rounded-xl hover:bg-slate-800 transition">Save Changes</button>
                        </form>
                    </div>

                    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Security</h3>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-slate-600 mb-2">Current Password</label>
                                <input type="password" name="current_password" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">New Password</label>
                                    <input type="password" name="new_password" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="bg-red-50 text-red-600 border border-red-100 font-bold py-3 px-6 rounded-xl hover:bg-red-100 transition">Update Password</button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </main>
</body>
</html>