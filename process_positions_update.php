<?php
require_once 'config.php';

// Simple endpoint to update weekly_target_hours and show_weekly_target for a position
$method = $_SERVER['REQUEST_METHOD'];

// Use $_REQUEST so both GET and POST are supported; require params to exist.
$positionId = isset($_REQUEST['position_id']) ? (int)$_REQUEST['position_id'] : 0;
$weekly = isset($_REQUEST['weekly_target']) ? (int)$_REQUEST['weekly_target'] : null;
$show = isset($_REQUEST['show_weekly_target']) ? 1 : 0;

if (!$positionId || $weekly === null) {
    if ($method !== 'GET' && $method !== 'POST') {
        http_response_code(405);
        echo "<p style='color:red;'>Method not allowed.</p>";
        exit;
    }

    echo "<h3>Usage</h3><p>Provide <strong>position_id</strong> and <strong>weekly_target</strong>.</p>";
    echo "<p>Example (browser): <a href=\"?position_id=2&weekly_target=35&show_weekly_target=1\">?position_id=2&weekly_target=35&show_weekly_target=1</a></p>";
    echo "<p>Example (curl): <code>curl -X POST -d 'position_id=2&weekly_target=35&show_weekly_target=1' ./process_positions_update.php</code></p>";
    echo "<p><a href='admin.php'>Back to Admin</a></p>";
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE positions SET weekly_target_hours = ?, show_weekly_target = ? WHERE id = ?");
    $stmt->execute([$weekly, $show, $positionId]);

    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>Position updated successfully.</p>";
    } else {
        echo "<p style='color:orange;'>No changes made or position not found.</p>";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin.php'>Back to Admin</a></p>";

?>
