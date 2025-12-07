<?php
/**
 * Premium Withdrawal Page
 * -----------------------
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// 1. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// 2. Fetch User Data (For Sidebar & Balance)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Get Settings
$min_withdraw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='min_withdraw'")->fetchColumn();
if(!$min_withdraw) $min_withdraw = 10.00; // Default fallback

// 4. Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $method = $_POST['method'];
    $details = trim($_POST['details']);
    
    // Refresh Balance Check
    $current_balance = $pdo->query("SELECT balance FROM users WHERE id=$user_id")->fetchColumn();
    
    if (!$amount || $amount < $min_withdraw) {
        $error = "Minimum withdrawal amount is $$min_withdraw";
    } elseif ($amount > $current_balance) {
        $error = "Insufficient balance! You only have $$current_balance";
    } elseif (empty($details)) {
        $error = "Please provide your payment account details.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Deduct Balance
            $deduct = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $deduct->execute([$amount, $user_id]);
            
            // Create Record
            $ins = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, method, account_details) VALUES (?, ?, ?, ?)");
            $ins->execute([$user_id, $amount, $method, $details]);
            
            $pdo->commit();
            $msg = "Withdrawal request of $$amount submitted successfully!";
            
            // Refresh local user variable to update sidebar balance immediately
            $user['balance'] -= $amount; 
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// 5. Fetch Withdrawal History
$history = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$history->execute([$user_id]);
$tx_history = $history->fetchAll(PDO::FETCH_ASSOC);

// Helper for Plan Style (Sidebar)
$plan = $user['membership_level'] ?? 'free';
$plan_style = [
    'free' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => 'fa-user', 'label' => 'Standard'],
    'gold' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'fa-crown', 'label' => 'Gold'],
    'platinum' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'fa-gem', 'label' => 'Platinum']
];
$current_style = $plan_style[$plan] ?? $plan_style['free'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdraw Funds | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 text-slate-800 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-72 bg-white border-r border-gray-200 hidden md:flex flex-col z-20 shadow-sm">
            <div class="h-20 flex items-center px-8 border-b border-gray-100">
                <div class="text-2xl font-bold text-indigo-600 tracking-tight flex items-center gap-2">
                    <i class="fas fa-layer-group"></i> ClickEarn
                </div>
            </div>
            
            <div class="p-6 text-center border-b border-gray-100 bg-gray-50/50">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 mx-auto mb-3 flex items-center justify-center text-2xl text-white shadow-lg">
                    <span class="font-bold"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                </div>
                <h4 class="font-bold text-gray-900"><?= htmlspecialchars($user['username']) ?></h4>
                <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase <?= $current_style['bg'] ?> <?= $current_style['text'] ?>">
                    <i class="fas <?= $current_style['icon'] ?> mr-1"></i> <?= $current_style['label'] ?>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl transition-colors font-medium">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>
                <a href="upgrade.php" class="flex items-center gap-3 px-4 py-3 text-yellow-600 hover:bg-yellow-50 rounded-xl transition-colors font-medium">
                    <i class="fas fa-arrow-up w-5"></i> Upgrade Plan
                </a>
                <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-semibold transition-colors">
                    <i class="fas fa-wallet w-5"></i> Withdraw Funds
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl transition-colors font-medium">
                    <i class="fas fa-cog w-5"></i> Settings
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-16 bg-white border-b border-gray-200 flex md:hidden items-center justify-between px-4 z-30">
                <div class="font-bold text-xl text-indigo-600">ClickEarn</div>
                <a href="dashboard.php" class="text-gray-600"><i class="fas fa-arrow-left"></i></a>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-12">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Withdraw Funds</h1>
                        <p class="text-gray-500 mt-1">Transfer your earnings to your preferred account.</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-2xl shadow-lg flex items-center gap-4 w-full md:w-auto">
                        <div class="bg-white/20 p-3 rounded-xl">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-green-100 font-medium uppercase tracking-wider">Available Balance</p>
                            <p class="text-3xl font-bold">$<?= number_format($user['balance'], 4) ?></p>
                        </div>
                    </div>
                </div>

                <?php if($msg): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
                        <i class="fas fa-check-circle text-xl"></i>
                        <div><p class="font-bold">Success</p><p><?= $msg ?></p></div>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <div><p class="font-bold">Error</p><p><?= $error ?></p></div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
                            
                            <form method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    
                                    <div class="col-span-1">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Amount (USD)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 font-bold">$</span>
                                            <input type="number" step="0.01" name="amount" id="amountInput" oninput="calculateBDT()"
                                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition font-bold text-gray-800 text-lg" 
                                                placeholder="0.00" required>
                                        </div>
                                        <div class="flex justify-between mt-2 text-xs">
                                            <span class="text-gray-400">Min: $<?= $min_withdraw ?></span>
                                            <span class="text-indigo-600 font-bold" id="bdtPreview">≈ 0 BDT</span>
                                        </div>
                                    </div>

                                    <div class="col-span-1">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Payment Method</label>
                                        <div class="relative">
                                            <select name="method" class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white">
                                                <option value="bkash">bKash (Personal)</option>
                                                <option value="nagad">Nagad (Personal)</option>
                                                <option value="rocket">Rocket</option>
                                                <option value="upay">Upay</option>
                                                <option value="binance">Binance (USDT)</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-1 md:col-span-2">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Account Details</label>
                                        <div class="relative">
                                            <span class="absolute top-3 left-4 text-gray-400"><i class="fas fa-address-card"></i></span>
                                            <textarea name="details" rows="2" 
                                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition" 
                                                placeholder="Enter Wallet Number, Email, or ID..." required></textarea>
                                        </div>
                                    </div>

                                </div>

                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-500/30 transition transform active:scale-95 text-lg">
                                    Request Payout
                                </button>
                                <p class="text-center text-xs text-gray-400 mt-4">
                                    <i class="fas fa-lock"></i> Payments are processed within 24-48 hours.
                                </p>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden h-full">
                            <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                <h3 class="font-bold text-gray-700"><i class="fas fa-history mr-2"></i> Recent History</h3>
                            </div>
                            <div class="overflow-y-auto max-h-[500px]">
                                <?php if(empty($tx_history)): ?>
                                    <div class="p-8 text-center text-gray-400">
                                        <i class="fas fa-receipt text-4xl mb-2 opacity-30"></i>
                                        <p class="text-sm">No withdrawals yet.</p>
                                    </div>
                                <?php else: ?>
                                    <ul class="divide-y divide-gray-100">
                                        <?php foreach($tx_history as $tx): ?>
                                            <li class="p-5 hover:bg-gray-50 transition">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="font-bold text-gray-800 text-lg">$<?= number_format($tx['amount'], 2) ?></span>
                                                    <?php 
                                                        $statusClass = match($tx['status']) {
                                                            'approved' => 'bg-green-100 text-green-700',
                                                            'rejected' => 'bg-red-100 text-red-700',
                                                            default => 'bg-yellow-100 text-yellow-700'
                                                        };
                                                    ?>
                                                    <span class="text-xs px-2 py-1 rounded font-bold uppercase <?= $statusClass ?>">
                                                        <?= $tx['status'] ?>
                                                    </span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs text-gray-500">
                                                    <span class="uppercase tracking-wide font-semibold"><i class="fas fa-university mr-1"></i> <?= $tx['method'] ?></span>
                                                    <span><?= date('M d, Y', strtotime($tx['created_at'])) ?></span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        function calculateBDT() {
            const usd = document.getElementById('amountInput').value;
            const rate = 120; // Example Rate: 1 USD = 120 BDT
            const bdt = (usd * rate).toFixed(2);
            document.getElementById('bdtPreview').innerText = `≈ ${bdt} BDT`;
        }
    </script>
</body>
</html>