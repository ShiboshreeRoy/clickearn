<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$msg = "";

// 1. Check if can spin
$stmt = $pdo->prepare("SELECT last_spin, balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$can_spin = ($user['last_spin'] != $today);

// 2. Handle Spin Logic
if (isset($_POST['spin_wheel']) && $can_spin) {
    // Random Reward Logic (Weighted)
    $rand = rand(1, 100);
    if ($rand <= 70) $reward = 0.005;       // 70% chance
    elseif ($rand <= 90) $reward = 0.01;    // 20% chance
    elseif ($rand <= 98) $reward = 0.05;    // 8% chance
    else $reward = 0.10;                    // 2% chance (Jackpot)

    // Update DB
    $pdo->prepare("UPDATE users SET balance = balance + ?, last_spin = ? WHERE id = ?")->execute([$reward, $today, $user_id]);
    
    // Refresh page to show result
    header("Location: spin.php?win=$reward");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spin & Win | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .wheel-outer {
            background: conic-gradient(
                #ef4444 0deg 60deg, 
                #f59e0b 60deg 120deg, 
                #10b981 120deg 180deg, 
                #3b82f6 180deg 240deg, 
                #8b5cf6 240deg 300deg, 
                #ec4899 300deg 360deg
            );
        }
        .spin-animation { animation: spin 2s ease-out forwards; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(1440deg); } }
    </style>
</head>
<body class="bg-gray-900 text-white flex h-screen overflow-hidden">

    <?php include 'admin_sidebar.php'; // Reuse sidebar ?>

    <main class="flex-1 p-8 flex flex-col items-center justify-center relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none" style="background-image: radial-gradient(white 2px, transparent 2px); background-size: 30px 30px;"></div>

        <?php if (isset($_GET['win'])): ?>
            <div class="absolute top-10 bg-green-500 text-white px-8 py-4 rounded-full text-2xl font-bold shadow-xl animate-bounce z-50">
                🎉 You Won $<?= htmlspecialchars($_GET['win']) ?>!
            </div>
        <?php endif; ?>

        <div class="text-center mb-8 relative z-10">
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-pink-500">Daily Lucky Spin</h1>
            <p class="text-gray-400 mt-2">Spin the wheel every 24 hours to win cash!</p>
        </div>

        <div class="relative w-80 h-80">
            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-20 text-4xl text-white">
                <i class="fas fa-caret-down"></i>
            </div>

            <div id="wheel" class="w-full h-full rounded-full border-8 border-white shadow-2xl wheel-outer relative flex items-center justify-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-inner z-10">
                    <span class="text-gray-800 font-bold text-xl">$$$</span>
                </div>
            </div>
        </div>

        <div class="mt-12 z-10">
            <?php if ($can_spin): ?>
                <form method="POST" onsubmit="return spinTheWheel()">
                    <button name="spin_wheel" id="spinBtn" class="bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold py-4 px-12 rounded-full text-xl shadow-lg transform transition hover:scale-105">
                        SPIN NOW
                    </button>
                </form>
            <?php else: ?>
                <button disabled class="bg-gray-700 text-gray-400 font-bold py-4 px-12 rounded-full text-xl cursor-not-allowed">
                    Come Back Tomorrow
                </button>
                <p class="text-gray-500 text-sm mt-3 text-center">Next spin resets at 00:00</p>
            <?php endif; ?>
        </div>

    </main>

    <script>
        function spinTheWheel() {
            const wheel = document.getElementById('wheel');
            const btn = document.getElementById('spinBtn');
            
            // Visual Animation only
            wheel.classList.add('spin-animation');
            btn.disabled = true;
            btn.innerText = "Spinning...";
            
            // Allow form submission after animation
            // In a real app, you'd use AJAX, but here we let the form submit naturally after a delay
            // actually, for this simple version, let the form submit immediately, 
            // the PHP will handle the logic and reload the page with the win.
            // The animation here is just for show before the reload.
            return true; 
        }
    </script>
</body>
</html>