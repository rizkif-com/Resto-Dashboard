<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h3>Step 1: Testing config.php include...</h3>";
try {
    require_once 'config.php';
    echo "<p style='color:green;'>config.php loaded OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>config.php error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h3>Step 2: Testing PDO connection...</h3>";
try {
    $test = $pdo->query("SELECT 1")->fetchColumn();
    echo "<p style='color:green;'>Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h3>Step 3: Checking tables...</h3>";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables found: " . implode(', ', $tables) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Step 4: Checking role_tabs table...</h3>";
try {
    $check = $pdo->query("SHOW TABLES LIKE 'role_tabs'")->fetchColumn();
    if ($check) {
        echo "<p>role_tabs exists. Columns:</p>";
        $cols = $pdo->query("SHOW COLUMNS FROM role_tabs")->fetchAll();
        echo "<pre>";
        foreach ($cols as $c) {
            echo $c['Field'] . " - " . $c['Type'] . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p style='color:orange;'>role_tabs table does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Step 5: Checking positions table...</h3>";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM positions")->fetchAll();
    echo "<pre>";
    foreach ($cols as $c) {
        echo $c['Field'] . " - " . $c['Type'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Step 6: Testing utils.php...</h3>";
try {
    require_once 'utils.php';
    echo "<p style='color:green;'>utils.php loaded OK</p>";
    $result = hasTabAccess($pdo, 1, 'attendance.php');
    echo "<p style='color:green;'>hasTabAccess() works. Result for position 1 + attendance.php: " . ($result ? 'true' : 'false') . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>utils.php error: " . $e->getMessage() . "</p>";
}

echo "<h3>Step 7: Testing sidebar query...</h3>";
try {
    $stmt = $pdo->query("SELECT s.username, s.position_id, p.name as pos_name, p.can_cook, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "<p style='color:green;'>Sidebar query OK. Sample user: " . htmlspecialchars($row['username']) . "</p>";
    } else {
        echo "<p style='color:orange;'>No staff found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Sidebar query error: " . $e->getMessage() . "</p>";
}

echo "<hr><p>Debug complete.</p>";
?>
