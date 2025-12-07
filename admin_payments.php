<?php
ob_start();
session_start();
require_once 'config.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";

// Handle Approval/Rejection
if (isset($_POST['action'])) {
    $pay_id = $_POST['pay_id'];
    $action = $_POST['action'];

    // Get Payment Details
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$pay_id]);
    $pay = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pay && $pay['status'] == 'pending') {
        if ($action == 'approve') {
            $pdo->beginTransaction();
            try {
                // 1. Mark Payment Approved
                $pdo->prepare("UPDATE payments SET status = 'approved' WHERE id = ?")->execute([$pay_id]);
                
                // 2. Upgrade User
                $pdo->prepare("UPDATE users SET membership_level = ? WHERE id = ?")->execute([$pay['plan_type'], $pay['user_id']]);
                
                $pdo->commit();
                $msg = "Payment Approved! User upgraded to " . ucfirst($pay['plan_type']);
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "Error: " . $e->getMessage();
            }
        } elseif ($action == 'reject') {
            $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?")->execute([$pay_id]);
            $msg = "Payment Rejected.";
        }
    }
}

// Fetch Pending Payments
$payments = $pdo->query("SELECT p.*, u.username, u.email FROM payments p JOIN users u ON p.user_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans text-gray-800 flex h-screen overflow-hidden">

    <?php include 'admin_sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">Payment Approvals</h1>
        
        <?php if($msg): ?>
            <div class="bg-blue-100 text-blue-800 p-4 rounded mb-6 font-bold"><?= $msg ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-bold border-b">
                    <tr>
                        <th class="p-4">User</th>
                        <th class="p-4">Method</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">TrxID / Sender</th>
                        <th class="p-4">Plan</th>
                        <th class="p-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-sm">
                    <?php foreach($payments as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4">
                                <div class="font-bold"><?= htmlspecialchars($p['username']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($p['email']) ?></div>
                            </td>
                            <td class="p-4">
                                <span class="bg-pink-100 text-pink-600 px-2 py-1 rounded text-xs uppercase font-bold">
                                    <?= $p['method'] ?>
                                </span>
                            </td>
                            <td class="p-4 font-bold text-green-600">$<?= $p['amount'] ?></td>
                            <td class="p-4">
                                <div class="font-mono font-bold"><?= $p['transaction_id'] ?></div>
                                <div class="text-xs text-gray-500">From: <?= $p['sender_number'] ?></div>
                            </td>
                            <td class="p-4 uppercase font-bold text-indigo-600"><?= $p['plan_type'] ?></td>
                            <td class="p-4 text-center">
                                <form method="POST" class="flex justify-center gap-2">
                                    <input type="hidden" name="pay_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded shadow text-xs">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded shadow text-xs">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($payments)) echo "<div class='p-8 text-center text-gray-400'>No pending payments.</div>"; ?>
        </div>
    </main>

</body>
</html>