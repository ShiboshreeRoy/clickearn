<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'];
$today = date('Y-m-d');

try {
    $pdo->beginTransaction();

    // 1. Verify Task Exists & Get Reward
    $stmt = $pdo->prepare("SELECT reward FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) throw new Exception("Invalid Task");

    // 2. Check if already done today (Double Check)
    $check = $pdo->prepare("SELECT id FROM task_logs WHERE user_id = ? AND task_id = ? AND completed_at = ?");
    $check->execute([$user_id, $task_id, $today]);
    if ($check->rowCount() > 0) throw new Exception("Already completed today");

    // 3. Add Money to User
    $update = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $update->execute([$task['reward'], $user_id]);

    // 4. Log the Task
    $log = $pdo->prepare("INSERT INTO task_logs (user_id, task_id, completed_at) VALUES (?, ?, ?)");
    $log->execute([$user_id, $task_id, $today]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'new_balance' => '...']); // Can return new balance

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>