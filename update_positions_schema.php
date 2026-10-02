<?php
require_once 'config.php';

echo "<h2>Positions Schema Migration</h2>";

try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM positions LIKE 'can_promote'");
    if ($colCheck->rowCount() > 0) {
        echo "<p style='color:green;'>positions table already has 'can_promote' column. No changes needed.</p>";
    } else {
        $pdo->exec("ALTER TABLE positions ADD COLUMN can_promote BOOLEAN NOT NULL DEFAULT 0");
        echo "<p style='color:green;'>Successfully added 'can_promote' column to positions table.</p>";
    }

    echo "<p><a href='admin.php'>Go back to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
