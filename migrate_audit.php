<?php
require_once 'config.php';

echo "<h2>Audit log migration</h2>";
try {
    $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetchColumn();
    if ($check) {
        echo "<p style='color:green;'>audit_logs already exists.</p>";
    } else {
        $pdo->exec("CREATE TABLE audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p style='color:green;'>Created audit_logs table.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin.php'>Back to Admin</a></p>";

?>
