<?php
require 'config.php';

// Check if user is already logged in
$is_logged_in = isset($_SESSION['user_id']);

// --- FETCH PUBLIC LEADERBOARD (Top 5) ---
$leaders = [];
try {
    // Calculate Total Earned (Balance + Withdrawals)
    $sql = "SELECT u.username, 
            (u.balance + COALESCE(SUM(w.amount), 0)) as total_earned
            FROM users u
            LEFT JOIN withdrawals w ON u.id = w.user_id AND w.status = 'approved'
            GROUP BY u.id
            ORDER BY total_earned DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent fail if DB not ready
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StarClone - Earn Money Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .hero-pattern {
            background-color: #ffffff;
            background-image: radial-gradient(#3b82f6 0.5px, transparent 0.5px), radial-gradient(#3b82f6 0.5px, #ffffff 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center gap-2">
                        <div class="bg-blue-600 text-white p-2 rounded-lg">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-gray-900">StarClone</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="#how-it-works" class="text-gray-500 hover:text-blue-600 font-medium transition">How it Works</a>
                    <a href="#pricing" class="text-gray-500 hover:text-blue-600 font-medium transition">Pricing</a>
                    <a href="#leaderboard" class="text-yellow-600 hover:text-yellow-700 font-bold transition flex items-center gap-1">
                        <i class="fas fa-trophy"></i> Leaderboard
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-full font-medium transition shadow-lg shadow-blue-500/30">
                            Dashboard <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-blue-600 font-medium hidden sm:block">Log In</a>
                        <a href="register.php" class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-2 rounded-full font-medium transition shadow-lg">
                            Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="relative overflow-hidden bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">Get paid to</span>
                            <span class="block text-blue-600 xl:inline">browse websites</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Join thousands of members earning real money daily. Simply view ads, complete simple tasks, and get paid directly to your wallet via bKash, Nagad, or Rocket.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="register.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg">
                                    Start Earning Now
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#leaderboard" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg">
                                    See Top Earners
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 hero-pattern opacity-100 flex items-center justify-center">
             <div class="hidden lg:block">
                <i class="fas fa-coins text-9xl text-blue-100 animate-bounce" style="animation-duration: 3s;"></i>
             </div>
        </div>
    </div>

    <div class="bg-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-blue-400 mb-2">10,000+</div>
                    <div class="text-gray-400">Active Members</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-green-400 mb-2">$50,000+</div>
                    <div class="text-gray-400">Paid Out</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-purple-400 mb-2">24/7</div>
                    <div class="text-gray-400">Support</div>
                </div>
            </div>
        </div>
    </div>

    <div id="leaderboard" class="py-16 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-yellow-600 font-semibold tracking-wide uppercase">Hall of Fame</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Top Earners This Month
                </p>
            </div>

            <div class="relative max-w-4xl mx-auto">
                <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none">
                    <i class="fas fa-trophy text-9xl text-yellow-500"></i>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative z-10">
                    <div class="grid grid-cols-1 divide-y divide-gray-100">
                        <?php if(empty($leaders)): ?>
                            <div class="p-8 text-center text-gray-500">Leaderboard updating...</div>
                        <?php else: ?>
                            <?php foreach($leaders as $index => $leader): ?>
                                <div class="flex items-center justify-between p-6 hover:bg-gray-50 transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg 
                                            <?= $index === 0 ? 'bg-yellow-100 text-yellow-700' : 
                                               ($index === 1 ? 'bg-gray-200 text-gray-700' : 
                                               ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-600')) ?>">
                                            <?php if($index === 0) echo '👑'; else echo '#'.($index+1); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-lg">
                                                <?= htmlspecialchars($leader['username']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500">Verified Member</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl font-bold text-green-600">$<?= number_format($leader['total_earned'], 2) ?></p>
                                        <p class="text-xs text-gray-400 uppercase tracking-wider">Total Earned</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-gray-50 p-4 text-center border-t border-gray-100">
                        <a href="register.php" class="text-indigo-600 font-semibold hover:text-indigo-800 text-sm">
                            Join them on the leaderboard →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pricing" class="py-16 bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Membership Plans</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Invest in Your Earnings
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                    Upgrade to unlock more daily tasks and multiply your rewards.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-900">Basic</h3>
                    <p class="mt-4 flex items-baseline text-gray-900">
                        <span class="text-5xl font-extrabold tracking-tight">$0</span>
                        <span class="ml-1 text-xl font-semibold text-gray-500">/forever</span>
                    </p>
                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> 5 Tasks Daily</li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> Standard Payouts</li>
                        <li class="flex items-start opacity-50"><i class="fas fa-times text-gray-400 mt-1 mr-3"></i> No Referral Bonus</li>
                    </ul>
                    <div class="mt-8">
                        <a href="register.php" class="block w-full bg-gray-100 border border-gray-200 rounded-lg py-3 px-6 text-center font-bold text-gray-700 hover:bg-gray-200 transition">Start Free</a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border-2 border-indigo-600 p-8 transform md:-translate-y-4 relative z-10">
                    <div class="absolute top-0 right-0 -mt-3 -mr-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white uppercase tracking-wide">Popular</span>
                    </div>
                    <h3 class="text-xl font-semibold text-indigo-600">Gold</h3>
                    <p class="mt-4 flex items-baseline text-gray-900">
                        <span class="text-5xl font-extrabold tracking-tight">$19</span>
                        <span class="ml-1 text-xl font-semibold text-gray-500">/one-time</span>
                    </p>
                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start"><i class="fas fa-check text-indigo-600 mt-1 mr-3"></i> 50 Tasks Daily</li>
                        <li class="flex items-start"><i class="fas fa-check text-indigo-600 mt-1 mr-3"></i> 2x Task Rewards</li>
                        <li class="flex items-start"><i class="fas fa-check text-indigo-600 mt-1 mr-3"></i> Priority Support</li>
                    </ul>
                    <div class="mt-8">
                        <a href="register.php" class="block w-full bg-indigo-600 text-white rounded-lg py-3 px-6 text-center font-bold hover:bg-indigo-700 transition">Get Started</a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-900">Platinum</h3>
                    <p class="mt-4 flex items-baseline text-gray-900">
                        <span class="text-5xl font-extrabold tracking-tight">$49</span>
                        <span class="ml-1 text-xl font-semibold text-gray-500">/one-time</span>
                    </p>
                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> 100 Tasks Daily</li>
                        <li class="flex items-start"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> 3x Task Rewards</li>
                        <li class="flex items-start"><i class="fas fa-check text-purple-500 mt-1 mr-3"></i> Instant Withdrawals</li>
                    </ul>
                    <div class="mt-8">
                        <a href="register.php" class="block w-full bg-purple-600 text-white rounded-lg py-3 px-6 text-center font-bold hover:bg-purple-700 transition">Go Platinum</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="how-it-works" class="py-16 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Process</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Three Simple Steps to Earn
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6 rounded-xl border border-gray-100">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-user-plus"></i></div>
                    <h3 class="text-xl font-bold mb-2">1. Register</h3>
                    <p class="text-gray-500">Sign up for a free account in seconds.</p>
                </div>
                <div class="text-center p-6 rounded-xl border border-gray-100">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-eye"></i></div>
                    <h3 class="text-xl font-bold mb-2">2. View Ads</h3>
                    <p class="text-gray-500">Browse websites and complete simple tasks.</p>
                </div>
                <div class="text-center p-6 rounded-xl border border-gray-100">
                    <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-wallet"></i></div>
                    <h3 class="text-xl font-bold mb-2">3. Get Paid</h3>
                    <p class="text-gray-500">Withdraw earnings to your mobile wallet.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center gap-2 mb-4 md:mb-0">
                <i class="fas fa-mouse-pointer text-blue-500"></i>
                <span class="font-bold text-white text-lg">StarClone</span>
            </div>
            <div class="flex space-x-6 text-sm">
                <a href="#" class="hover:text-white transition">Privacy Policy</a>
                <a href="#" class="hover:text-white transition">Terms of Service</a>
                <a href="#" class="hover:text-white transition">Contact Us</a>
            </div>
            <p class="mt-4 md:mt-0 text-sm">&copy; <?= date('Y') ?> StarClone. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>