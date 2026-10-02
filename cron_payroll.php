<?php
require_once 'config.php';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT id, position_id FROM staff");
    $allStaff = $stmt->fetchAll();

    foreach ($allStaff as $staff) {
        $staffId = $staff['id'];
        $positionId = $staff['position_id'];

        $posStmt = $pdo->prepare("SELECT base_salary, hourly_rate FROM positions WHERE id = ?");
        $posStmt->execute([$positionId]);
        $position = $posStmt->fetch();
        
        if (!$position) continue;

        $baseSalary = (float)$position['base_salary'];
        $hourlyRate = (float)$position['hourly_rate'];

        $dutyStmt = $pdo->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW())) / 60) as total_hours 
                                    FROM duty_logs 
                                    WHERE staff_id = ? AND status = 'PENDING'");
        $dutyStmt->execute([$staffId]);
        $hoursWorked = (float)$dutyStmt->fetchColumn();

        $cookStmt = $pdo->prepare("SELECT SUM(c.quantity * r.cooking_bonus) as total_bonus
                                    FROM cook_logs c
                                    JOIN recipes r ON c.recipe_id = r.id
                                    WHERE c.staff_id = ? AND c.status = 'PENDING'");
        $cookStmt->execute([$staffId]);
        $bonusPay = (float)$cookStmt->fetchColumn();

        if ($hoursWorked <= 0 && $bonusPay <= 0) {
            continue;
        }

        $overtimeHours = 0;
        $actualBasePay = $baseSalary;

        if ($hoursWorked < 21) {
            $actualBasePay = ($baseSalary / 21) * $hoursWorked;
        } else {
            $overtimeHours = $hoursWorked - 21;
        }

        $overtimePay = $overtimeHours * $hourlyRate;
        $totalPay = $actualBasePay + $overtimePay + $bonusPay;

        $insertStmt = $pdo->prepare("INSERT INTO payroll_reports (staff_id, week_start, week_end, base_salary, overtime_pay, bonus_pay, total_pay, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, 'UNPAID')");
        $insertStmt->execute([
            $staffId,
            date('Y-m-d', strtotime('-1 week')),
            date('Y-m-d'),
            $actualBasePay,
            $overtimePay,
            $bonusPay,
            $totalPay
        ]);

        $updateDuty = $pdo->prepare("UPDATE duty_logs SET status = 'PROCESSED' WHERE staff_id = ? AND status = 'PENDING'");
        $updateDuty->execute([$staffId]);

        $updateCook = $pdo->prepare("UPDATE cook_logs SET status = 'PROCESSED' WHERE staff_id = ? AND status = 'PENDING'");
        $updateCook->execute([$staffId]);
    }

    $pdo->commit();
    echo "Payroll processed successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error processing payroll: " . $e->getMessage());
}
?>
