<?php
require_once 'config.php';

echo "<h2>Notifications Migration</h2>";

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color:green;'>notifications table already exists. No changes needed.</p>";
    } else {
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            type ENUM('SP', 'SALARY_PAID', 'OTHER') DEFAULT 'OTHER',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES staff(id)
        );");
        echo "<p style='color:green;'>notifications table created successfully.</p>";
    }

    $colCheck = $pdo->query("SHOW COLUMNS FROM staff_sp LIKE 'reason'");
    if ($colCheck->rowCount() > 0) {
        echo "<p style='color:green;'>staff_sp table already has reason column.</p>";
    } else {
        $pdo->exec("ALTER TABLE staff_sp ADD COLUMN reason TEXT NOT NULL DEFAULT ''");
        echo "<p style='color:green;'>reason column added to staff_sp table.</p>";
    }

    echo "<p><a href='admin.php'>Go to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
