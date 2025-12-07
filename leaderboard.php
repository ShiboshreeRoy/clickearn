<?php
/**
 * Leaderboard & Top Earners
 * -------------------------
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

// Fetch Current User Data (For Sidebar)
$user = $pdo->query("SELECT * FROM users WHERE id=$user_id")->fetch(PDO::FETCH_ASSOC);
$plan = $user['membership_level'] ?? 'free';

// --- LEADERBOARD LOGIC ---
// We calculate: Total Earned = Current Balance + Total Approved Withdrawals
$sql = "SELECT u.username, u.membership_level, u.balance, 
        COALESCE(SUM(w.amount), 0) as total_withdrawn,
        (u.balance + COALESCE(SUM(w.amount), 0)) as total_earned
        FROM users u
        LEFT JOIN withdrawals w ON u.id = w.user_id AND w.status = 'approved'
        GROUP BY u.id
        ORDER BY total_earned DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for Plan Styles
$plan_styles = [
    'free' => 'bg-gray-100 text-gray-600',
    'gold' => 'bg-yellow-100 text-yellow-700',
    'platinum' => 'bg-purple-100 text-purple-700'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex h-screen overflow-hidden">

    <aside class="w-72 bg-white border-r border-slate-200 hidden md:flex flex-col z-20 shadow-lg">
        <div class="h-20 flex items-center px-8 border-b border-slate-100">
            <div class="text-2xl font-bold text-indigo-600">ClickEarn</div>
        </div>
        
        <div class="p-6 text-center border-b border-gray-100 bg-gray-50/50">
            <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 mx-auto mb-3 flex items-center justify-center text-2xl text-white shadow-lg">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <h4 class="font-bold text-slate-900"><?= htmlspecialchars($user['username']) ?></h4>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition"><i class="fas fa-home w-5"></i> Dashboard</a>
            <a href="leaderboard.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-bold transition"><i class="fas fa-trophy w-5"></i> Leaderboard</a>
            <a href="upgrade.php" class="flex items-center gap-3 px-4 py-3 text-yellow-600 hover:bg-yellow-50 rounded-xl font-medium transition"><i class="fas fa-crown w-5"></i> Upgrade</a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition"><i class="fas fa-cog w-5"></i> Settings</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white border-b border-slate-200 flex md:hidden items-center justify-between px-4">
            <div class="font-bold text-lg text-indigo-600">Leaderboard</div>
            <a href="dashboard.php" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-12 bg-slate-50">
            
            <div class="text-center mb-10">
                <h1 class="text-3xl font-extrabold text-slate-900">Top Earners</h1>
                <p class="text-slate-500 mt-2">The highest earning members of all time.</p>
            </div>

            <?php if (count($leaders) >= 3): ?>
            <div class="flex flex-col md:flex-row justify-center items-end gap-6 mb-12">
                
                <div class="bg-white p-6 rounded-3xl shadow-lg border-b-4 border-slate-300 w-full md:w-64 text-center order-2 md:order-1 transform hover:-translate-y-2 transition duration-300">
                    <div class="w-16 h-16 rounded-full bg-slate-200 mx-auto -mt-10 mb-4 flex items-center justify-center text-3xl shadow-md border-4 border-white">🥈</div>
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($leaders[1]['username']) ?></h3>
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-2">Silver</p>
                    <p class="text-2xl font-bold text-slate-700">$<?= number_format($leaders[1]['total_earned'], 2) ?></p>
                </div>

                <div class="bg-gradient-to-b from-yellow-50 to-white p-8 rounded-t-3xl rounded-b-3xl shadow-xl border-b-4 border-yellow-400 w-full md:w-72 text-center order-1 md:order-2 z-10 transform scale-110">
                    <div class="w-20 h-20 rounded-full bg-yellow-400 mx-auto -mt-14 mb-4 flex items-center justify-center text-4xl shadow-lg border-4 border-white text-white">👑</div>
                    <h3 class="font-bold text-xl text-slate-900"><?= htmlspecialchars($leaders[0]['username']) ?></h3>
                    <p class="text-xs text-yellow-600 uppercase font-bold tracking-wider mb-3">Champion</p>
                    <p class="text-3xl font-extrabold text-slate-900">$<?= number_format($leaders[0]['total_earned'], 2) ?></p>
                    <div class="mt-4 inline-block px-3 py-1 bg-yellow-200 text-yellow-800 rounded-full text-xs font-bold uppercase">
                        <?= $leaders[0]['membership_level'] ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-lg border-b-4 border-amber-600 w-full md:w-64 text-center order-3 transform hover:-translate-y-2 transition duration-300">
                    <div class="w-16 h-16 rounded-full bg-amber-600 mx-auto -mt-10 mb-4 flex items-center justify-center text-3xl shadow-md border-4 border-white text-white">🥉</div>
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($leaders[2]['username']) ?></h3>
                    <p class="text-xs text-amber-700 uppercase font-bold tracking-wider mb-2">Bronze</p>
                    <p class="text-2xl font-bold text-slate-700">$<?= number_format($leaders[2]['total_earned'], 2) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden max-w-4xl mx-auto">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase text-slate-500 font-bold">
                            <tr>
                                <th class="px-6 py-4">Rank</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Level</th>
                                <th class="px-6 py-4 text-right">Total Earned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            // Skip first 3 if displayed in podium, else show all
                            $start_index = (count($leaders) >= 3) ? 3 : 0; 
                            for ($i = $start_index; $i < count($leaders); $i++): 
                                $row = $leaders[$i];
                                $rank = $i + 1;
                                $is_me = ($row['username'] === $user['username']);
                            ?>
                            <tr class="hover:bg-slate-50 transition <?= $is_me ? 'bg-indigo-50 hover:bg-indigo-100' : '' ?>">
                                <td class="px-6 py-4 font-bold text-slate-400">#<?= $rank ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                            <?= strtoupper(substr($row['username'], 0, 1)) ?>
                                        </div>
                                        <span class="font-bold text-slate-700 <?= $is_me ? 'text-indigo-700' : '' ?>">
                                            <?= htmlspecialchars($row['username']) ?> <?= $is_me ? '(You)' : '' ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs uppercase font-bold <?= $plan_styles[$row['membership_level']] ?? 'bg-gray-100' ?>">
                                        <?= $row['membership_level'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-800">
                                    $<?= number_format($row['total_earned'], 2) ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <?php if(empty($leaders)) echo "<div class='p-8 text-center text-slate-400'>No data available yet.</div>"; ?>
            </div>

        </div>
    </main>
</body>
</html>