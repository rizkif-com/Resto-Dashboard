<?php
require_once 'config.php';

try {
    $pdo->beginTransaction();

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE duty_logs");
    $pdo->exec("TRUNCATE TABLE cook_logs");
    $pdo->exec("TRUNCATE TABLE staff_sp");
    $pdo->exec("TRUNCATE TABLE payroll_reports");
    $pdo->exec("TRUNCATE TABLE staff");
    $pdo->exec("TRUNCATE TABLE positions");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("INSERT INTO positions (id, name, base_salary, hourly_rate, permission_level, can_cook) VALUES 
        (1, 'Trainee', 300.00, 10.00, 1, 0),
        (2, 'Staff', 400.00, 12.00, 2, 0),
        (3, 'Chef', 600.00, 18.00, 3, 1),
        (4, 'Manager', 800.00, 22.00, 4, 1),
        (5, 'Director', 1200.00, 35.00, 5, 1),
        (6, 'Superadmin', 2000.00, 50.00, 6, 1)
    ");

    $pdo->exec("INSERT INTO staff (discord_id, username, position_id, sp_level) VALUES 
        ('trainee_123', 'TestTrainee', 1, 0),
        ('staff_123', 'TestStaff', 2, 0),
        ('chef_123', 'TestChef', 3, 0),
        ('manager_123', 'TestManager', 4, 0),
        ('director_123', 'TestDirector', 5, 0),
        ('superadmin_123', 'TestSuperadmin', 6, 0)
    ");

    $pdo->commit();
    echo "<h1>Database Roles Updated Successfully!</h1>";
    echo "<p>The positions (Trainee -> Superadmin) have been created, along with a test user for each role.</p>";
    echo "<ul>";
    echo "<li><b>Trainee ID:</b> 1</li>";
    echo "<li><b>Staff ID:</b> 2</li>";
    echo "<li><b>Chef ID:</b> 3</li>";
    echo "<li><b>Manager ID:</b> 4</li>";
    echo "<li><b>Director ID:</b> 5</li>";
    echo "<li><b>Superadmin ID:</b> 6</li>";
    echo "</ul>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error updating roles: " . $e->getMessage());
}
?>
