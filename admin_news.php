<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') header("Location: login.php");
$msg = "";

// Handle Post
if (isset($_POST['post_news'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $type = $_POST['type'];
    
    $pdo->prepare("INSERT INTO announcements (title, message, type) VALUES (?, ?, ?)")
        ->execute([$title, $message, $type]);
    $msg = "Announcement posted!";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: admin_news.php");
    exit;
}

$news = $pdo->query("SELECT * FROM announcements ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage News</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">Announcements</h1>
        <?php if($msg) echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>$msg</div>"; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl shadow h-fit">
                <h3 class="font-bold mb-4">Post New Update</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="block text-sm font-bold mb-1">Title</label>
                        <input type="text" name="title" class="w-full border p-2 rounded" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-bold mb-1">Type</label>
                        <select name="type" class="w-full border p-2 rounded">
                            <option value="info">Info (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Alert (Red)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-1">Message</label>
                        <textarea name="message" rows="4" class="w-full border p-2 rounded" required></textarea>
                    </div>
                    <button name="post_news" class="w-full bg-blue-600 text-white font-bold py-2 rounded">Post News</button>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <?php foreach($news as $n): ?>
                    <div class="bg-white p-4 rounded-xl shadow border-l-4 border-<?= ($n['type']=='danger'?'red':($n['type']=='warning'?'yellow':($n['type']=='success'?'green':'blue'))) ?>-500 flex justify-between items-start">
                        <div>
                            <h4 class="font-bold"><?= htmlspecialchars($n['title']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                            <p class="text-xs text-gray-400 mt-2"><?= $n['created_at'] ?></p>
                        </div>
                        <a href="?delete=<?= $n['id'] ?>" class="text-red-500 hover:bg-red-50 p-2 rounded"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>