<?php
require_once 'config.php';

echo "<h2>Positions: Weekly Target Migration</h2>";

try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM positions LIKE 'weekly_target_hours'");
    if ($colCheck->rowCount() > 0) {
        echo "<p style='color:green;'>positions table already has weekly_target_hours.</p>";
    } else {
        $pdo->exec("ALTER TABLE positions ADD COLUMN weekly_target_hours INT NOT NULL DEFAULT 40");
        echo "<p style='color:green;'>Added weekly_target_hours to positions.</p>";
    }

    $colCheck2 = $pdo->query("SHOW COLUMNS FROM positions LIKE 'show_weekly_target'");
    if ($colCheck2->rowCount() > 0) {
        echo "<p style='color:green;'>positions table already has show_weekly_target.</p>";
    } else {
        $pdo->exec("ALTER TABLE positions ADD COLUMN show_weekly_target BOOLEAN NOT NULL DEFAULT TRUE");
        echo "<p style='color:green;'>Added show_weekly_target to positions.</p>";
    }

    echo "<p><a href='admin.php'>Back to Admin</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>