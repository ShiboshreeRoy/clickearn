<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];
$msg = "";

// 1. Handle New Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if($subject && $message) {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $subject, $message]);
        $msg = "Ticket created successfully! Admin will reply soon.";
    }
}

// 2. Fetch User's Tickets
$tickets = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY id DESC");
$tickets->execute([$user_id]);
$my_tickets = $tickets->fetchAll(PDO::FETCH_ASSOC);

// Helper for Status Colors
function getStatusColor($status) {
    return match($status) {
        'open' => 'bg-yellow-100 text-yellow-800',
        'replied' => 'bg-green-100 text-green-800',
        'closed' => 'bg-gray-100 text-gray-800',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Support | ClickEarn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 font-sans text-gray-800 flex h-screen overflow-hidden">

    <?php include 'admin_sidebar.php'; // Reuse sidebar if possible, or copy sidebar code here ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">Support Center</h1>

        <?php if($msg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6"><?= $msg ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-lg mb-4">Open New Ticket</h3>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Subject</label>
                            <select name="subject" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>Payment Issue</option>
                                <option>Task Error</option>
                                <option>Account Upgrade</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
                            <textarea name="message" rows="5" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Describe your issue..."></textarea>
                        </div>
                        <button class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700 transition">Submit Ticket</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-200 font-bold">My Tickets</div>
                    <div class="divide-y divide-gray-100">
                        <?php foreach($my_tickets as $t): ?>
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold text-lg"><?= htmlspecialchars($t['subject']) ?></h4>
                                    <span class="text-xs px-2 py-1 rounded font-bold uppercase <?= getStatusColor($t['status']) ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4 bg-gray-50 p-3 rounded">
                                    <?= htmlspecialchars($t['message']) ?>
                                </p>
                                
                                <?php if($t['admin_reply']): ?>
                                    <div class="ml-4 pl-4 border-l-4 border-blue-500">
                                        <p class="text-xs text-blue-600 font-bold mb-1"><i class="fas fa-user-shield"></i> Admin Reply:</p>
                                        <p class="text-gray-800 text-sm"><?= htmlspecialchars($t['admin_reply']) ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-xs text-gray-400 italic">Waiting for admin reply...</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($my_tickets)) echo "<p class='p-6 text-gray-500 text-center'>No tickets found.</p>"; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>