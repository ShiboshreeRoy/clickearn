<?php
/**
 * Admin Users Management
 * ----------------------
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// 1. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";

// 2. Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action: Update Balance
    if (isset($_POST['update_balance'])) {
        $uid = $_POST['user_id'];
        $new_bal = $_POST['balance'];
        $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$new_bal, $uid]);
        $msg = "User #$uid balance updated.";
    }

    // Action: Delete User
    if (isset($_POST['delete_user'])) {
        $uid = $_POST['user_id'];
        // Delete related data first (Logs, Withdrawals) to avoid database errors
        $pdo->prepare("DELETE FROM task_logs WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM withdrawals WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $msg = "User #$uid deleted permanently.";
    }
}

// 3. Search & Fetch Users
$search = $_GET['search'] ?? '';
$params = [];
$sql = "SELECT * FROM users WHERE role = 'user'";

if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        
        <?php include 'admin_sidebar.php'; ?>
        
        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-16 bg-white border-b border-gray-200 flex md:hidden items-center justify-between px-4">
                <div class="font-bold text-lg text-gray-800">Admin Users</div>
                <a href="logout.php" class="text-red-500"><i class="fas fa-sign-out-alt"></i></a>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                    
                    <form class="flex gap-2 w-full md:w-auto">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name/email..." class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <?php if($msg): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase text-xs font-semibold">
                                <tr>
                                    <th class="p-4">ID</th>
                                    <th class="p-4">User Details</th>
                                    <th class="p-4">Level</th>
                                    <th class="p-4">Balance ($)</th>
                                    <th class="p-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php foreach ($users as $u): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4 text-gray-400">#<?= $u['id'] ?></td>
                                        <td class="p-4">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($u['username']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                                            <?php if(!empty($u['referral_code'])): ?>
                                                <div class="text-xs text-indigo-500 mt-1 font-mono bg-indigo-50 inline-block px-1 rounded">Ref: <?= $u['referral_code'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs uppercase font-bold border border-gray-200">
                                                <?= $u['membership_level'] ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <form method="POST" class="flex items-center gap-2">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="number" step="0.0001" name="balance" value="<?= $u['balance'] ?>" class="w-24 border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 outline-none">
                                                <button type="submit" name="update_balance" class="text-blue-600 hover:text-blue-800 text-xs font-bold uppercase">Save</button>
                                            </form>
                                        </td>
                                        <td class="p-4">
                                            <form method="POST" onsubmit="return confirm('WARNING: This will permanently delete the user and their history. Continue?');">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" name="delete_user" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded text-xs font-medium transition">
                                                    <i class="fas fa-trash mr-1"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(empty($users)) echo "<div class='p-12 text-center text-gray-400'>No users found matching your search.</div>"; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>