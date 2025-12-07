<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// 1. Get Admin Payment Numbers (Ideally fetch from settings table, hardcoded here for simplicity)
$admin_bkash = "01700000000"; 
$admin_nagad = "01800000000";

// 2. Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan = $_POST['plan']; // gold or platinum
    $method = $_POST['method'];
    $trx_id = trim($_POST['trx_id']);
    $sender = trim($_POST['sender']);
    
    // Set Prices
    $amount = ($plan === 'gold') ? 19.00 : 49.00; // Gold $19, Platinum $49

    // Check if TrxID already used
    $check = $pdo->prepare("SELECT id FROM payments WHERE transaction_id = ?");
    $check->execute([$trx_id]);

    if ($check->rowCount() > 0) {
        $error = "This Transaction ID is already used!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, transaction_id, sender_number, plan_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $method, $trx_id, $sender, $plan]);
            $msg = "Payment submitted! Admin will verify and upgrade your account within 24 hours.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Membership | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <nav class="bg-white shadow-sm p-4">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="text-xl font-bold text-indigo-600"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 md:p-12">
        
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Choose Your Plan</h1>
            <p class="text-lg text-gray-500">Unlock higher earnings and more daily tasks.</p>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded shadow-sm text-center font-bold text-lg">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded shadow-sm text-center">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 flex flex-col">
                <h3 class="text-xl font-bold text-gray-500 uppercase tracking-wide">Basic</h3>
                <div class="mt-4 flex items-baseline text-gray-900">
                    <span class="text-5xl font-extrabold tracking-tight">$0</span>
                    <span class="ml-1 text-xl font-semibold text-gray-500">/forever</span>
                </div>
                <ul class="mt-6 space-y-4 flex-1">
                    <li class="flex"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> 5 Tasks Daily</li>
                    <li class="flex"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> Standard Support</li>
                    <li class="flex text-gray-400"><i class="fas fa-times mt-1 mr-3"></i> No Referral Bonus</li>
                </ul>
                <button disabled class="mt-8 block w-full bg-gray-100 text-gray-400 font-bold py-3 rounded-lg cursor-not-allowed">Current Plan</button>
            </div>

            <div class="bg-white rounded-2xl shadow-xl border-2 border-indigo-500 p-8 flex flex-col relative transform scale-105 z-10">
                <span class="absolute top-0 right-0 bg-indigo-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg uppercase">Best Value</span>
                <h3 class="text-xl font-bold text-indigo-600 uppercase tracking-wide">Gold</h3>
                <div class="mt-4 flex items-baseline text-gray-900">
                    <span class="text-5xl font-extrabold tracking-tight">$19</span>
                    <span class="ml-1 text-xl font-semibold text-gray-500">/one-time</span>
                </div>
                <ul class="mt-6 space-y-4 flex-1">
                    <li class="flex"><i class="fas fa-check text-indigo-500 mt-1 mr-3"></i> 50 Tasks Daily</li>
                    <li class="flex"><i class="fas fa-check text-indigo-500 mt-1 mr-3"></i> Priority Support</li>
                    <li class="flex"><i class="fas fa-check text-indigo-500 mt-1 mr-3"></i> 2x Task Rewards</li>
                </ul>
                <a href="#payment-form" onclick="selectPlan('gold', 19)" class="mt-8 block w-full bg-indigo-600 text-white text-center font-bold py-3 rounded-lg hover:bg-indigo-700 transition">Buy Gold</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 flex flex-col">
                <h3 class="text-xl font-bold text-purple-600 uppercase tracking-wide">Platinum</h3>
                <div class="mt-4 flex items-baseline text-gray-900">
                    <span class="text-5xl font-extrabold tracking-tight">$49</span>
                    <span class="ml-1 text-xl font-semibold text-gray-500">/one-time</span>
                </div>
                <ul class="mt-6 space-y-4 flex-1">
                    <li class="flex"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> 100 Tasks Daily</li>
                    <li class="flex"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> 24/7 Support</li>
                    <li class="flex"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> 3x Task Rewards</li>
                </ul>
                <a href="#payment-form" onclick="selectPlan('platinum', 49)" class="mt-8 block w-full bg-purple-100 text-purple-700 text-center font-bold py-3 rounded-lg hover:bg-purple-200 transition">Buy Platinum</a>
            </div>
        </div>

        <div id="payment-form" class="hidden bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden max-w-3xl mx-auto">
            <div class="bg-gray-900 text-white p-6 text-center">
                <h3 class="text-2xl font-bold">Complete Your Payment</h3>
                <p>Send Money to our agent number and fill the form below.</p>
            </div>
            
            <div class="p-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p class="font-bold text-yellow-800">Instructions:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 mt-1">
                        <li>Go to your Bkash/Nagad/Rocket App.</li>
                        <li>Select <strong>"Send Money"</strong> (Not Cash Out).</li>
                        <li>Send <strong id="pay-amount">$19 (approx 2200 BDT)</strong> to:</li>
                        <li class="font-mono font-bold mt-1">Bkash/Nagad: <?= $admin_bkash ?></li>
                        <li>Copy the <strong>Transaction ID</strong> and paste it below.</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="plan" id="selected-plan">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block font-bold mb-2">Payment Method</label>
                            <select name="method" class="w-full border p-3 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="rocket">Rocket</option>
                                <option value="upay">Upay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold mb-2">Sender Number</label>
                            <input type="text" name="sender" placeholder="017xxxxxxxx" class="w-full border p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block font-bold mb-2">Transaction ID (TrxID)</label>
                        <input type="text" name="trx_id" placeholder="e.g. 8N7A6D5..." class="w-full border p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-4 rounded-lg hover:bg-green-700 transition text-lg shadow-lg">
                        Submit Payment Details
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
        function selectPlan(plan, price) {
            // Rate: 1 USD = 120 BDT (Example)
            const rate = 120; 
            const bdtPrice = price * rate;

            document.getElementById('payment-form').classList.remove('hidden');
            document.getElementById('selected-plan').value = plan;
            document.getElementById('pay-amount').innerText = `$${price} (${bdtPrice} BDT)`;
            
            // Smooth scroll
            document.getElementById('payment-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>