<?php
/**
 * Admin System Settings
 * ---------------------
 * Configure global platform variables.
 */
ob_start();
session_start();
require_once 'config.php';

// Auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";

// 1. Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        // Upsert (Update if exists, Insert if not)
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $pdo->prepare($sql)->execute([$key, $value]);
    }
    $msg = "Settings saved successfully!";
}

// 2. Fetch Current Settings
// We fetch as key-value pair for easy access
$settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper function to safely get setting
function get_setting($key, $data, $default = '') {
    return isset($data[$key]) ? htmlspecialchars($data[$key]) : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-64 bg-gray-900 text-gray-300 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-800"><span class="font-bold text-white">ADMIN</span></div>
            <nav class="p-4 space-y-2">
                <a href="admin_dashboard.php" class="block px-4 py-2 hover:bg-gray-800 rounded">Dashboard</a>
                <a href="admin_withdrawals.php" class="block px-4 py-2 hover:bg-gray-800 rounded">Withdrawals</a>
                <a href="admin_users.php" class="block px-4 py-2 hover:bg-gray-800 rounded">Users</a>
                <a href="admin_settings.php" class="block px-4 py-2 bg-blue-600 text-white rounded">Settings</a>
            </nav>
        </aside>

        <main class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-3xl font-bold mb-6">System Configuration</h1>

            <?php if($msg): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 border-l-4 border-green-500 font-bold">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="max-w-4xl">
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2"><i class="fas fa-coins text-yellow-500 mr-2"></i> Financials</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Minimum Withdrawal ($)</label>
                            <input type="number" step="0.01" name="min_withdraw" 
                                   value="<?= get_setting('min_withdraw', $settings, '10.00') ?>" 
                                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <p class="text-xs text-gray-400 mt-1">Users cannot withdraw less than this.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Referral Bonus ($)</label>
                            <input type="number" step="0.0001" name="referral_bonus" 
                                   value="<?= get_setting('referral_bonus', $settings, '0.0200') ?>" 
                                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <p class="text-xs text-gray-400 mt-1">One-time bonus when a referral joins.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2"><i class="fas fa-crown text-purple-500 mr-2"></i> Memberships</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Gold Membership Price ($)</label>
                            <input type="number" step="0.01" name="gold_price" 
                                   value="<?= get_setting('gold_price', $settings, '19.00') ?>" 
                                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Platinum Membership Price ($)</label>
                            <input type="number" step="0.01" name="platinum_price" 
                                   value="<?= get_setting('platinum_price', $settings, '49.00') ?>" 
                                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2"><i class="fas fa-globe text-blue-500 mr-2"></i> General</h2>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Site Name</label>
                        <input type="text" name="site_name" 
                               value="<?= get_setting('site_name', $settings, 'ClickEarn') ?>" 
                               class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition transform hover:-translate-y-1">
                        <i class="fas fa-save mr-2"></i> Save Configuration
                    </button>
                </div>

            </form>
        </main>
    </div>
</body>
</html>