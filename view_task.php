<?php
require 'config.php';
if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) die("Unauthorized Access");

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Task Info
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) die("Task not found.");

// Check if already completed today
$today = date('Y-m-d');
$check = $pdo->prepare("SELECT * FROM task_logs WHERE user_id = ? AND task_id = ? AND completed_at = ?");
$check->execute([$user_id, $task_id, $today]);
if ($check->rowCount() > 0) die("Task already completed today.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Viewing Ad...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Progress Bar Animation */
        .progress-bar { transition: width 1s linear; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">

    <div class="h-16 bg-gray-900 text-white flex items-center justify-between px-6 shadow-lg z-50">
        <div class="font-bold text-lg">Ad Viewer</div>
        
        <div id="timer-container" class="flex items-center gap-4">
            <span class="text-sm text-gray-300">Wait for reward:</span>
            <div class="text-2xl font-mono font-bold text-yellow-400" id="countdown">10</div>
        </div>

        <div id="success-msg" class="hidden flex items-center gap-2 text-green-400 font-bold">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>$<?= $task['reward'] ?> Added!</span>
            <button onclick="window.close()" class="ml-4 bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded text-xs text-white">Close</button>
        </div>
    </div>

    <iframe src="<?= htmlspecialchars($task['url']) ?>" class="flex-1 w-full border-none bg-white"></iframe>

    <script>
        let timeLeft = 10; // Seconds to wait
        const taskId = <?= $task_id ?>;
        const reward = <?= $task['reward'] ?>;

        const timerDisplay = document.getElementById('countdown');
        const successMsg = document.getElementById('success-msg');
        const timerContainer = document.getElementById('timer-container');

        const interval = setInterval(() => {
            timeLeft--;
            timerDisplay.innerText = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(interval);
                claimReward();
            }
        }, 1000);

        function claimReward() {
            // AJAX Request to Backend to securely add money
            fetch('ajax_claim.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `task_id=${taskId}&token=<?= $_SESSION['csrf_token'] ?? '' ?>`
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    timerContainer.classList.add('hidden');
                    successMsg.classList.remove('hidden');
                } else {
                    alert("Error: " + data.message);
                }
            });
        }
    </script>
</body>
</html>