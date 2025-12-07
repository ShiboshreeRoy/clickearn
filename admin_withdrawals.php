<?php
/**
 * Admin Withdrawal Management
 * ---------------------------
 * Handle user payout requests securely.
 */
ob_start();
session_start();
require_once 'config.php';

// 1. Admin Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. Handle Actions (Approve/Reject)
if (isset($_POST['action'])) {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    // Fetch the request details first
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request && $request['status'] === 'pending') {
        if ($action === 'approve') {
            // Mark as approved (Money is already deducted from balance upon request)
            $update = $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
            $update->execute([$requestId]);
            $message = "Request #$requestId Approved & Marked Paid.";
        } elseif ($action === 'reject') {
            // Reject: REFUND the money back to user's balance
            $pdo->beginTransaction();
            try {
                // 1. Update status to rejected
                $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
                
                // 2. Refund User
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$request['amount'], $request['user_id']]);
                
                $pdo->commit();
                $message = "Request #$requestId Rejected. Funds refunded to user.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}

// 3. Fetch Requests (Pending first, then history)
$pendingSql = "SELECT w.*, u.username, u.email 
               FROM withdrawals w 
               JOIN users u ON w.user_id = u.id 
               WHERE w.status = 'pending' 
               ORDER BY w.created_at ASC";
$pendingRequests = $pdo->query($pendingSql)->fetchAll(PDO::FETCH_ASSOC);

$historySql = "SELECT w.*, u.username 
               FROM withdrawals w 
               JOIN users u ON w.user_id = u.id 
               WHERE w.status != 'pending' 
               ORDER BY w.created_at DESC LIMIT 20";
$historyRequests = $pdo->query($historySql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Withdrawals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-900 text-gray-100 font-sans">

    <div class="flex h-screen">
        <aside class="w-64 bg-gray-800 border-r border-gray-700 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-700">
                <span class="text-xl font-bold text-blue-400">AdminPanel</span>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="admin_dashboard.php" class="block px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded">Dashboard</a>
                <a href="admin_withdrawals.php" class="block px-4 py-2 bg-blue-600 text-white rounded">Withdrawals</a>
                <a href="admin_users.php" class="block px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded">Users</a>
            </nav>
        </aside>

        <main class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-3xl font-bold mb-6">Withdrawal Requests</h1>

            <?php if ($message): ?>
                <div class="bg-blue-600 text-white p-4 rounded mb-6 shadow-lg animate-pulse">
                    <i class="fas fa-info-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 mb-8 overflow-hidden">
                <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-yellow-400"><i class="fas fa-clock mr-2"></i> Pending Requests</h2>
                    <span class="bg-gray-700 px-3 py-1 rounded-full text-sm"><?= count($pendingRequests) ?> Pending</span>
                </div>
                
                <?php if (empty($pendingRequests)): ?>
                    <div class="p-8 text-center text-gray-500">No pending requests.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="p-4">User</th>
                                    <th class="p-4">Amount</th>
                                    <th class="p-4">Method</th>
                                    <th class="p-4">Details (Wallet/Email)</th>
                                    <th class="p-4">Date</th>
                                    <th class="p-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($pendingRequests as $req): ?>
                                    <tr class="hover:bg-gray-750 transition">
                                        <td class="p-4">
                                            <div class="font-bold"><?= htmlspecialchars($req['username']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($req['email']) ?></div>
                                        </td>
                                        <td class="p-4 font-bold text-green-400 text-lg">$<?= number_format($req['amount'], 2) ?></td>
                                        <td class="p-4">
                                            <span class="bg-gray-600 px-2 py-1 rounded text-xs"><?= htmlspecialchars($req['method']) ?></span>
                                        </td>
                                        <td class="p-4 font-mono text-sm text-yellow-100 select-all bg-gray-900/50 rounded px-2">
                                            <?= htmlspecialchars($req['account_details']) ?>
                                        </td>
                                        <td class="p-4 text-sm text-gray-400"><?= date('M j, H:i', strtotime($req['created_at'])) ?></td>
                                        <td class="p-4 text-center">
                                            <form method="POST" class="flex gap-2 justify-center">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                
                                                <button type="submit" name="action" value="approve" onclick="return confirm('Confirm payment sent?')" 
                                                    class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-sm shadow hover:shadow-lg transition">
                                                    <i class="fas fa-check"></i> Pay
                                                </button>
                                                
                                                <button type="submit" name="action" value="reject" onclick="return confirm('Reject and Refund balance?')"
                                                    class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-sm shadow hover:shadow-lg transition">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="text-xl font-bold mb-4 text-gray-400">Recent History</h2>
            <div class="bg-gray-800 rounded-xl shadow border border-gray-700 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-400">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="p-3">User</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($historyRequests as $hist): ?>
                            <tr>
                                <td class="p-3"><?= htmlspecialchars($hist['username']) ?></td>
                                <td class="p-3">$<?= number_format($hist['amount'], 2) ?></td>
                                <td class="p-3">
                                    <?php if($hist['status'] == 'approved'): ?>
                                        <span class="text-green-500 font-bold">Paid</span>
                                    <?php else: ?>
                                        <span class="text-red-500 font-bold">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3"><?= date('M j', strtotime($hist['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>
</html>