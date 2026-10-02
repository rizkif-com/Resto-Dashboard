<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE staff_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    $unreadCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, type, title, message, created_at, is_read FROM notifications WHERE staff_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'unreadCount' => $unreadCount,
        'notifications' => $notifications
    ]);
}
elseif ($action === 'mark_read') {
    $notifId = (int)($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND staff_id = ?");
        $stmt->execute([$notifId, $userId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    }
}
elseif ($action === 'mark_all_read') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE staff_id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
