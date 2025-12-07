<?php
/**
 * ClickEarn Premium Dashboard
 * ---------------------------
 */

// 1. System Initialization
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Configuration & Auth
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$error = null;

// --- DAILY BONUS LOGIC (Placed at top for processing) ---
$bonus_amount = 0.0100; // $0.01 Daily
if (isset($_POST['claim_bonus'])) {
    $checkBonus = $pdo->prepare("SELECT last_daily_bonus FROM users WHERE id = ?");
    $checkBonus->execute([$user_id]);
    $last_date = $checkBonus->fetchColumn();

    if ($last_date != $today) {
        $pdo->prepare("UPDATE users SET balance = balance + ?, last_daily_bonus = ? WHERE id = ?")->execute([$bonus_amount, $today, $user_id]);
        header("Location: dashboard.php?msg=bonus_claimed");
        exit;
    }
}

try {
    // 3. Fetch User Data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // 4. Referral Code Logic
    if (empty($user['referral_code'])) {
        $new_code = strtoupper(substr(md5(uniqid($user_id, true)), 0, 8));
        $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$new_code, $user_id]);
        $user['referral_code'] = $new_code;
    }

    // 5. Fetch Tasks
    $taskSql = "SELECT t.* FROM tasks t 
                WHERE t.id NOT IN (SELECT task_id FROM task_logs WHERE user_id = ? AND completed_at = ?)
                LIMIT 50";
    $taskStmt = $pdo->prepare($taskSql);
    $taskStmt->execute([$user_id, $today]);
    $tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Fetch History
    $histSql = "SELECT tl.*, t.title, t.reward FROM task_logs tl JOIN tasks t ON tl.task_id = t.id WHERE tl.user_id = ? ORDER BY tl.id DESC LIMIT 5";
    $histStmt = $pdo->prepare($histSql);
    $histStmt->execute([$user_id]);
    $history_tasks = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    $withSql = "SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 5";
    $withStmt = $pdo->prepare($withSql);
    $withStmt->execute([$user_id]);
    $history_withdrawals = $withStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "System update in progress.";
}

// Helpers
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$referral_link = $protocol . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . ($user['referral_code'] ?? '');
$plan = $user['membership_level'] ?? 'free';
$can_claim = ($user['last_daily_bonus'] != $today);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ClickEarn Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-72 bg-white border-r border-slate-200 hidden md:flex flex-col z-20 shadow-lg">
            <div class="h-24 flex items-center px-8">
                <div class="text-2xl font-bold text-indigo-600 tracking-tight flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white text-sm"><i class="fas fa-bolt"></i></div>
                    ClickEarn
                </div>
            </div>
            
            <div class="px-6 mb-6">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-xl text-white font-bold shadow-md">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($user['username']) ?></h4>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wider"><?= ucfirst($plan) ?> Plan</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Menu</p>
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition-all">
                    <i class="fas fa-grid-2 w-5"></i> Dashboard
                </a>
                <a href="upgrade.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 rounded-xl font-medium transition-all">
                    <i class="fas fa-rocket w-5"></i> Upgrade Plan
                </a>
                <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 rounded-xl font-medium transition-all">
                    <i class="fas fa-wallet w-5"></i> Withdraw
                </a>
                
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mt-6 mb-2">Help & Settings</p>
                <a href="support.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 rounded-xl font-medium transition-all">
                    <i class="fas fa-headset w-5"></i> Support Center
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 rounded-xl font-medium transition-all">
                    <i class="fas fa-cog w-5"></i> Settings
                </a>
                
            </nav>

            <div class="p-4 border-t border-slate-100">
                <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-3 text-red-600 bg-red-50 hover:bg-red-100 rounded-xl font-bold transition-all text-sm">
                    <i class="fas fa-power-off"></i> Sign Out
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-16 bg-white border-b border-slate-200 flex md:hidden items-center justify-between px-4 z-30">
                <div class="font-bold text-lg text-indigo-600">ClickEarn</div>
                <button onclick="document.querySelector('aside').classList.toggle('hidden')" class="text-slate-500 p-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-10">
                
                <?php if(isset($_GET['msg']) && $_GET['msg']=='bonus_claimed'): ?>
                    <div id="bonus-alert" class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl mb-6 font-bold flex items-center gap-3 shadow-sm">
                        <i class="fas fa-check-circle text-xl"></i> Daily Bonus of $<?= $bonus_amount ?> Added!
                    </div>
                    <script>setTimeout(() => document.getElementById('bonus-alert').remove(), 3000);</script>
                <?php endif; ?>

                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-3xl p-8 text-white shadow-xl shadow-indigo-200 mb-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 opacity-10 transform translate-x-10 -translate-y-10">
                        <i class="fas fa-coins text-9xl"></i>
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">Welcome back, <?= htmlspecialchars($user['username']) ?>! 👋</h1>
                            <p class="text-indigo-100">You have <span class="font-bold text-white"><?= count($tasks) ?> tasks</span> waiting for you today.</p>
                        </div>
                        
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 p-2 rounded-2xl flex items-center gap-4 pr-6">
                            <div class="bg-white text-indigo-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl shadow-lg">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-100 uppercase font-bold">Daily Reward</p>
                                <?php if ($can_claim): ?>
                                    <form method="POST">
                                        <button type="submit" name="claim_bonus" class="text-sm font-bold text-white hover:text-yellow-300 transition underline decoration-dashed underline-offset-4">
                                            Claim $0.01 Now
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-sm font-bold text-emerald-300"><i class="fas fa-check"></i> Claimed Today</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fas fa-wallet"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Balance</p>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">$<?= number_format($user['balance'], 4) ?></p>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-mouse-pointer"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Tasks</p>
                        </div>
                        <p class="text-2xl font-bold text-slate-800"><?= count($tasks) ?></p>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-10 h-10 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center"><i class="fas fa-crown"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Plan</p>
                        </div>
                        <p class="text-2xl font-bold text-slate-800 uppercase"><?= $plan ?></p>
                        <?php if($plan === 'free'): ?>
                            <a href="upgrade.php" class="text-xs text-indigo-600 font-bold hover:underline absolute top-6 right-6">Upgrade -></a>
                        <?php endif; ?>
                    </div>

                    <a href="support.php" class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow group cursor-pointer">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-10 h-10 rounded-full bg-pink-50 text-pink-500 flex items-center justify-center"><i class="fas fa-life-ring"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider group-hover:text-pink-500 transition-colors">Help</p>
                        </div>
                        <p class="text-lg font-bold text-slate-800">Need Support?</p>
                        <p class="text-xs text-slate-400">Open a ticket</p>
                    </a>

                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm mb-10">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                        <div class="flex items-start gap-5">
                            <div class="bg-amber-100 p-4 rounded-2xl text-amber-600 shrink-0">
                                <i class="fas fa-gift text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Invite Friends & Earn 10%</h3>
                                <p class="text-slate-500 text-sm mt-1 max-w-xl leading-relaxed">
                                    Share your link. You earn 10% commission on every withdrawal your referred friends make. Lifetime passive income.
                                </p>
                            </div>
                        </div>
                        <div class="w-full lg:w-auto bg-slate-50 p-2 rounded-2xl border border-slate-200 flex items-center gap-3 pl-4">
                            <i class="fas fa-link text-slate-400"></i>
                            <input type="text" readonly value="<?= $referral_link ?>" id="ref-input" 
                                class="bg-transparent border-none focus:ring-0 text-sm text-slate-600 font-mono w-full lg:w-64 truncate">
                            <button onclick="copyLink()" id="copy-btn" 
                                class="bg-slate-900 hover:bg-indigo-600 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg active:scale-95">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-slate-800">Available Tasks</h2>
                    <span class="text-xs font-bold bg-slate-200 text-slate-600 px-3 py-1 rounded-full">Refreshes 00:00 UTC</span>
                </div>
                
                <?php if (empty($tasks)): ?>
                    <div class="flex flex-col items-center justify-center bg-white rounded-3xl border-2 border-dashed border-slate-200 p-12 text-center mb-10">
                        <div class="bg-emerald-50 p-4 rounded-full mb-4">
                            <i class="fas fa-check text-3xl text-emerald-500"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">All Caught Up!</h3>
                        <p class="text-slate-500 mt-1">Great job! Come back tomorrow for new earning opportunities.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-10">
                        <?php foreach ($tasks as $task): ?>
                            <div class="group bg-white rounded-3xl p-5 border border-slate-100 hover:border-indigo-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="bg-indigo-50 p-3 rounded-xl text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        +$<?= number_format($task['reward'], 4) ?>
                                    </span>
                                </div>
                                <h3 class="font-bold text-slate-800 mb-1 line-clamp-1" title="<?= htmlspecialchars($task['title']) ?>">
                                    <?= htmlspecialchars($task['title']) ?>
                                </h3>
                                <p class="text-xs text-slate-400 mb-6 truncate font-mono bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                                    <?= htmlspecialchars($task['url']) ?>
                                </p>
                                <div class="mt-auto">
                                    <button onclick="openTask(<?= $task['id'] ?>)" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3 rounded-xl transition-all shadow-md flex items-center justify-center gap-2 group-hover:shadow-indigo-200">
                                        Start Task <i class="fas fa-arrow-right text-xs opacity-50"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="history" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700">Recent Activity</h3>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            <?php foreach($history_tasks as $h): ?>
                                <li class="p-5 flex justify-between items-center hover:bg-slate-50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xs"><i class="fas fa-check"></i></div>
                                        <div>
                                            <p class="font-bold text-sm text-slate-800"><?= htmlspecialchars($h['title']) ?></p>
                                            <p class="text-xs text-slate-400"><?= date('M j, H:i', strtotime($h['completed_at'])) ?></p>
                                        </div>
                                    </div>
                                    <span class="text-emerald-600 font-bold text-sm">+$<?= number_format($h['reward'], 4) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700">Payouts</h3>
                        </div>
                        <ul class="divide-y divide-slate-50">
                            <?php foreach($history_withdrawals as $hw): ?>
                                <li class="p-5 flex justify-between items-center hover:bg-slate-50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center text-xs"><i class="fas fa-university"></i></div>
                                        <div>
                                            <p class="font-bold text-sm text-slate-800"><?= htmlspecialchars($hw['method']) ?></p>
                                            <p class="text-xs text-slate-400"><?= date('M j, Y', strtotime($hw['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-slate-800 text-sm">$<?= number_format($hw['amount'], 2) ?></p>
                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full
                                            <?= $hw['status']=='approved' ? 'bg-emerald-100 text-emerald-600' : ($hw['status']=='rejected' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600') ?>">
                                            <?= $hw['status'] ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>

            </div>
        </main>
    </div>
    <?php
// Fetch latest announcement
$news = $pdo->query("SELECT * FROM announcements ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($news):
    $colors = [
        'info' => 'bg-blue-100 border-blue-500 text-blue-900',
        'success' => 'bg-green-100 border-green-500 text-green-900',
        'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-900',
        'danger' => 'bg-red-100 border-red-500 text-red-900',
    ];
    $style = $colors[$news['type']] ?? $colors['info'];
?>
<div class="<?= $style ?> border-l-4 p-4 rounded-r-xl mb-6 shadow-sm flex items-start gap-3">
    <i class="fas fa-bullhorn mt-1"></i>
    <div>
        <strong class="block font-bold"><?= htmlspecialchars($news['title']) ?></strong>
        <p class="text-sm"><?= htmlspecialchars($news['message']) ?></p>
    </div>
</div>
<?php endif; ?>
    <div id="toast" class="fixed bottom-6 right-6 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl transform translate-y-20 opacity-0 transition-all duration-300 flex items-center gap-3 z-50">
        <i class="fas fa-check-circle text-emerald-400 text-xl"></i>
        <div>
            <p class="font-bold text-sm">Success</p>
            <p class="text-xs text-slate-300">Link copied to clipboard</p>
        </div>
    </div>

    <script>
        function openTask(taskId) {
            const w = 1024; const h = 768;
            const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
            const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
            const win = window.open(`view_task.php?id=${taskId}`, `TaskWindow_${taskId}`, `toolbar=no,scrollbars=yes,resizable=yes,width=${w},height=${h},top=${y},left=${x}`);
            const timer = setInterval(function() { if(win.closed) { clearInterval(timer); window.location.reload(); } }, 500);
        }

        function copyLink() {
            const input = document.getElementById('ref-input');
            const btn = document.getElementById('copy-btn');
            input.select(); input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
            
            const originalText = btn.innerText;
            btn.innerText = "Copied!";
            btn.classList.add("bg-emerald-600", "hover:bg-emerald-700");
            
            const toast = document.getElementById('toast');
            toast.classList.remove("translate-y-20", "opacity-0");
            
            setTimeout(() => {
                btn.innerText = originalText;
                btn.classList.remove("bg-emerald-600", "hover:bg-emerald-700");
                toast.classList.add("translate-y-20", "opacity-0");
            }, 2500);
        }
    </script>
</body>
</html>