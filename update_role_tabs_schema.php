<?php
require_once 'config.php';

echo "<h2>Role Tabs Migration</h2>";

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'role_tabs'");
    if ($tableCheck->rowCount() > 0) {
        $cols = $pdo->query("SHOW COLUMNS FROM role_tabs LIKE 'role_id'");
        if ($cols->rowCount() > 0) {
            echo "<p>Found old role_tabs table with 'role_id' column. Dropping and recreating...</p>";
            $pdo->exec("DROP TABLE role_tabs");
        } else {
            echo "<p>role_tabs table already has correct schema. No changes needed.</p>";
            exit;
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS role_tabs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        position_id INT NOT NULL,
        tab_name VARCHAR(50) NOT NULL,
        UNIQUE KEY uniq_role_tab (position_id, tab_name),
        FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "<p style='color:green;'>role_tabs table created successfully with 'position_id' column.</p>";
    echo "<p><a href='attendance.php'>Go back to app</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
