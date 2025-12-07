<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') header("Location: login.php");
$msg = "";

// Handle Reply
if (isset($_POST['reply_ticket'])) {
    $id = $_POST['ticket_id'];
    $reply = $_POST['reply'];
    
    $pdo->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'replied' WHERE id = ?")->execute([$reply, $id]);
    $msg = "Reply sent successfully.";
}

// Fetch Open Tickets
$tickets = $pdo->query("SELECT t.*, u.username FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.status != 'closed' ORDER BY t.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans text-gray-800 flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">Support Tickets</h1>
        <?php if($msg) echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>$msg</div>"; ?>
        
        <div class="grid gap-6">
            <?php foreach($tickets as $t): ?>
                <div class="bg-white p-6 rounded-xl shadow border border-gray-200">
                    <div class="flex justify-between mb-2">
                        <span class="font-bold text-lg text-blue-600">User: <?= htmlspecialchars($t['username']) ?></span>
                        <span class="text-xs text-gray-500"><?= $t['created_at'] ?></span>
                    </div>
                    <h4 class="font-bold mb-2">Subject: <?= htmlspecialchars($t['subject']) ?></h4>
                    <p class="bg-gray-50 p-3 rounded text-sm text-gray-700 mb-4"><?= htmlspecialchars($t['message']) ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                        <textarea name="reply" class="w-full border p-2 rounded mb-2 text-sm" placeholder="Write reply..." required><?= $t['admin_reply'] ?></textarea>
                        <button name="reply_ticket" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">Send Reply</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if(empty($tickets)) echo "<p>No open tickets.</p>"; ?>
        </div>
    </main>
</body>
</html>