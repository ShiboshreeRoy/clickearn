<?php
/**
 * Advanced Admin Dashboard
 * ------------------------
 * Central hub for managing the earning platform.
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// 1. Strict Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";

// 2. Handle Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action: Add New Task
    if (isset($_POST['add_task'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, url, reward) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['title'], $_POST['url'], $_POST['reward']]);
            $msg = "Task '{$_POST['title']}' published successfully!";
        } catch (PDOException $e) {
            $error = "Error adding task: " . $e->getMessage();
        }
    }

    // Action: Delete Task
    if (isset($_POST['delete_task'])) {
        $id = $_POST['task_id'];
        // Cascade delete is handled by DB constraints usually, but manual cleanup ensures safety
        $pdo->prepare("DELETE FROM task_logs WHERE task_id = ?")->execute([$id]); 
        $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
        $msg = "Task deleted successfully.";
    }
}

// 3. Fetch System Statistics
try {
    // Basic Counts
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $total_tasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $total_completed = $pdo->query("SELECT COUNT(*) FROM task_logs")->fetchColumn();
    
    // Financials
    $pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();
    $total_paid = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='approved'")->fetchColumn() ?: 0.00;

    // Support Tickets (NEW)
    // Check if table exists to prevent errors if you haven't run the SQL yet
    $open_tickets = 0;
    try {
        $open_tickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status='open'")->fetchColumn();
    } catch (Exception $e) {
        // Table likely doesn't exist yet, ignore error
    }

    // Recent Users (Last 5)
    $recent_users = $pdo->query("SELECT username, email, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Existing Tasks (For management list)
    $all_tasks = $pdo->query("SELECT * FROM tasks ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-64 bg-gray-900 text-gray-300 hidden md:flex flex-col flex-shrink-0 transition-all duration-300">
            <div class="h-16 flex items-center justify-center border-b border-gray-800 bg-gray-900">
                <span class="text-xl font-bold text-white tracking-wider"><i class="fas fa-shield-alt text-blue-500 mr-2"></i> ADMIN</span>
            </div>

            <div class="p-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">A</div>
                <div>
                    <p class="text-sm font-bold text-white">Super Admin</p>
                    <p class="text-xs text-green-400">● Online</p>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1">
                    <li>
                        <a href="admin_dashboard.php" class="flex items-center gap-3 px-6 py-3 bg-blue-600 text-white border-r-4 border-blue-400">
                            <i class="fas fa-tachometer-alt w-5"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="admin_withdrawals.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
                            <i class="fas fa-money-bill-wave w-5"></i> Withdrawals 
                            <?php if($pending_withdrawals > 0): ?>
                                <span class="bg-yellow-500 text-gray-900 font-bold text-xs px-2 py-0.5 rounded-full ml-auto"><?= $pending_withdrawals ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="admin_support.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
                            <i class="fas fa-headset w-5"></i> Support
                            <?php if($open_tickets > 0): ?>
                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full ml-auto"><?= $open_tickets ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
                            <i class="fas fa-users w-5"></i> Users
                        </a>
                    </li>
                    <li>
                        <a href="admin_settings.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
                            <i class="fas fa-cogs w-5"></i> Settings
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <a href="logout.php" class="flex items-center gap-2 text-red-400 hover:text-red-300 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-16 bg-white border-b border-gray-200 flex md:hidden items-center justify-between px-4">
                <div class="font-bold text-lg text-gray-800">Admin Panel</div>
                <button class="text-gray-600"><i class="fas fa-bars"></i></button>
            </header>

            <div class="flex-1 overflow-y-auto p-6 md:p-8">
                
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
                        <p class="text-gray-500 mt-1">Welcome back, Admin. Here's what's happening today.</p>
                    </div>
                    <div class="hidden md:block">
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide">
                            System Healthy
                        </span>
                    </div>
                </div>

                <?php if($msg): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                        <p class="font-bold">Success</p>
                        <p><?= $msg ?></p>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
                        <p class="font-bold">Error</p>
                        <p><?= $error ?></p>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-500 uppercase">Total Users</h3>
                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600"><i class="fas fa-users"></i></div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($total_users) ?></p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-500 uppercase">Open Tickets</h3>
                            <div class="p-2 bg-red-50 rounded-lg text-red-600"><i class="fas fa-envelope-open-text"></i></div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($open_tickets) ?></p>
                        <?php if($open_tickets > 0): ?>
                            <a href="admin_support.php" class="text-xs text-red-600 hover:underline mt-2 block">Respond Now →</a>
                        <?php else: ?>
                            <span class="text-xs text-green-500 mt-2 block">All clear!</span>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-500 uppercase">Pending Withdrawals</h3>
                            <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600"><i class="fas fa-clock"></i></div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($pending_withdrawals) ?></p>
                        <a href="admin_withdrawals.php" class="text-xs text-blue-600 hover:underline mt-2 block">Review Requests →</a>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-500 uppercase">Tasks Done</h3>
                            <div class="p-2 bg-green-50 rounded-lg text-green-600"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($total_completed) ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-8">
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                <h2 class="font-bold text-gray-800"><i class="fas fa-plus-circle mr-2"></i> Create New Task</h2>
                            </div>
                            <div class="p-6">
                                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-600 mb-1">Task Title</label>
                                        <input type="text" name="title" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="e.g. Visit our Sponsor" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-600 mb-1">Target URL</label>
                                        <input type="url" name="url" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://example.com" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-600 mb-1">Reward Amount ($)</label>
                                        <input type="number" step="0.0001" name="reward" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" value="0.0500" required>
                                    </div>
                                    <div class="md:col-span-2 mt-2">
                                        <button type="submit" name="add_task" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg transition shadow-md">
                                            Publish Task
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="font-bold text-gray-800"><i class="fas fa-list mr-2"></i> Active Tasks</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold">
                                        <tr>
                                            <th class="px-6 py-3">ID</th>
                                            <th class="px-6 py-3">Title</th>
                                            <th class="px-6 py-3">Reward</th>
                                            <th class="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($all_tasks as $task): ?>
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-6 py-3 text-gray-500">#<?= $task['id'] ?></td>
                                                <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($task['title']) ?></td>
                                                <td class="px-6 py-3 text-green-600 font-bold">$<?= number_format($task['reward'], 4) ?></td>
                                                <td class="px-6 py-3 text-right">
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                        <button type="submit" name="delete_task" class="text-red-500 hover:text-red-700 font-medium text-xs bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if(empty($all_tasks)): ?>
                                    <div class="p-6 text-center text-gray-400">No active tasks found.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <div class="space-y-8">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="font-bold text-gray-800"><i class="fas fa-user-clock mr-2"></i> Recent Registrations</h2>
                            </div>
                            <ul class="divide-y divide-gray-100">
                                <?php foreach ($recent_users as $u): ?>
                                    <li class="px-6 py-4 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($u['username']) ?></p>
                                            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($u['email']) ?></p>
                                        </div>
                                        <span class="text-xs text-gray-400"><?= date('M j', strtotime($u['created_at'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if(empty($recent_users)): ?>
                                    <li class="px-6 py-4 text-center text-gray-400 text-sm">No users yet.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-lg p-6 text-white">
                            <h3 class="font-bold text-lg mb-2">Need Help?</h3>
                            <p class="text-gray-400 text-sm mb-4">Check database logs or contact support.</p>
                            <a href="phpmyadmin" target="_blank" class="block text-center w-full bg-white/10 hover:bg-white/20 py-2 rounded text-sm transition">
                                Open Database
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

</body>
</html>