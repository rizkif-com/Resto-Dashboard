<?php
// Initialize Database and Seed Data

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'spill_the_tea';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    $schema = file_get_contents('schema.sql');
    if ($schema) {
        $pdo->exec($schema);
        echo "<p>Schema imported successfully.</p>";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM positions");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO positions (name, base_salary, hourly_rate, permission_level) VALUES 
            ('Cook', 500.00, 15.00, 1),
            ('Manager', 700.00, 20.00, 2),
            ('Director', 1000.00, 30.00, 3)
        ");
        
        $pdo->exec("INSERT INTO recipes (name, cooking_bonus) VALUES 
            ('Burger', 2.00),
            ('Fries', 0.50),
            ('Special Tea', 1.50)
        ");

        $pdo->exec("INSERT INTO ingredients (name, stock_quantity) VALUES 
            ('Patty', 100),
            ('Buns', 100),
            ('Potato', 200),
            ('Tea Leaves', 50)
        ");

        $pdo->exec("INSERT INTO staff (discord_id, username, position_id, sp_level) VALUES 
            ('test_user_123', 'TestDirector', 3, 0),
            ('cook_user_456', 'CookJane', 1, 1)
        ");

        echo "<p>Seed data inserted.</p>";
    } else {
        echo "<p>Database already seeded.</p>";
    }

    echo "<h3>Setup Complete!</h3>";
    echo "<a href='login.php'>Click here to Login</a>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage() . "<br><br>Make sure MySQL is running on localhost with user 'root' and no password.");
}
?>
