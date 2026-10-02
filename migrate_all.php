<?php
require_once 'config.php';

echo "<h2>Run All Migrations</h2>";
try {
    $col = $pdo->query("SHOW COLUMNS FROM positions LIKE 'weekly_target_hours'")->rowCount();
    if ($col) {
        echo "<p style='color:green;'>positions.weekly_target_hours already present.</p>";
    } else {
        $pdo->exec("ALTER TABLE positions ADD COLUMN weekly_target_hours INT NOT NULL DEFAULT 40");
        echo "<p style='color:green;'>Added weekly_target_hours to positions.</p>";
    }

    $col2 = $pdo->query("SHOW COLUMNS FROM positions LIKE 'show_weekly_target'")->rowCount();
    if ($col2) {
        echo "<p style='color:green;'>positions.show_weekly_target already present.</p>";
    } else {
        $pdo->exec("ALTER TABLE positions ADD COLUMN show_weekly_target BOOLEAN NOT NULL DEFAULT TRUE");
        echo "<p style='color:green;'>Added show_weekly_target to positions.</p>";
    }

    $notif = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetchColumn();
    if ($notif) {
        echo "<p style='color:green;'>notifications table exists.</p>";
    } else {
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            type ENUM('SP','SALARY_PAID','OTHER') DEFAULT 'OTHER',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p style='color:green;'>Created notifications table.</p>";
    }

    $col3 = $pdo->query("SHOW COLUMNS FROM staff_sp LIKE 'reason'")->rowCount();
    if ($col3) {
        echo "<p style='color:green;'>staff_sp.reason already present.</p>";
    } else {
        $pdo->exec("ALTER TABLE staff_sp ADD COLUMN reason TEXT NOT NULL");
        echo "<p style='color:green;'>Added reason to staff_sp.</p>";
    }

    $audit = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetchColumn();
    if ($audit) {
        echo "<p style='color:green;'>audit_logs exists.</p>";
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

    echo "<p><a href='admin.php'>Back to Admin</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Migration error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
