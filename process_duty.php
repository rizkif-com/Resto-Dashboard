<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'];

    if ($action === 'clock_in') {
        $stmt = $pdo->prepare("SELECT id FROM duty_logs WHERE staff_id = ? AND clock_out IS NULL");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO duty_logs (staff_id, clock_in) VALUES (?, NOW())");
            $insert->execute([$userId]);
        }
        header('Location: index.php');
        exit;
    } elseif ($action === 'clock_out') {
        $stmt = $pdo->prepare("SELECT id, clock_in FROM duty_logs WHERE staff_id = ? AND clock_out IS NULL");
        $stmt->execute([$userId]);
        $open = $stmt->fetch();
        if (!$open) {
            header('Location: index.php?error=No open duty');
            exit;
        }

        $clockIn = strtotime($open['clock_in']);
        $now = time();
        $durationHours = ($now - $clockIn) / 3600.0;

        if ($durationHours > 24) {
            header('Location: index.php?error=Shift exceeds 24 hours — contact admin');
            exit;
        }

        if ($durationHours > 21) {
            header('Location: index.php?error=Shift too long; use manual off-duty entry');
            exit;
        }

        $update = $pdo->prepare("UPDATE duty_logs SET clock_out = NOW() WHERE id = ?");
        $update->execute([$open['id']]);
        header('Location: index.php?msg=Clocked out');
        exit;
    }

    if ($action === 'manual_clock_in') {
        $clockInTime = $_POST['clock_in_time'] ?? '';
        $ts = strtotime($clockInTime);
        if (!$ts) {
            header('Location: attendance.php?error=Invalid+clock+in+time');
            exit;
        }

        $insert = $pdo->prepare("INSERT INTO duty_logs (staff_id, clock_in) VALUES (?, ?)");
        $insert->execute([$userId, date('Y-m-d H:i:s', $ts)]);
        $newId = $pdo->lastInsertId();
        $_SESSION['manual_duty_id'] = $newId;
        header('Location: attendance.php?msg=Manual+clock+in+saved');
        exit;
    }

    if ($action === 'manual_clock_out') {
        $manualDutyId = $_POST['manual_duty_id'] ?? '';
        $clockOutTime = $_POST['clock_out_time'] ?? '';
        $ts = strtotime($clockOutTime);
        if (empty($manualDutyId) || !$ts) {
            header('Location: attendance.php?error=Missing+manual+duty+or+invalid+time');
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, staff_id, clock_in FROM duty_logs WHERE id = ? AND staff_id = ? AND clock_out IS NULL");
        $stmt->execute([$manualDutyId, $userId]);
        $open = $stmt->fetch();
        if (!$open) {
            header('Location: attendance.php?error=No+matching+open+manual+duty');
            exit;
        }

        $clockIn = strtotime($open['clock_in']);
        $clockOut = $ts;

        if ($clockOut <= $clockIn) {
            $inDate = date('Y-m-d', $clockIn);
            $outDate = date('Y-m-d', $clockOut);
            if ($inDate === $outDate) {
                header('Location: attendance.php?error=Off-duty+time+must+be+after+on-duty+time');
                exit;
            }
        }

        $durationHours = ($clockOut - $clockIn) / 3600.0;
        if ($durationHours > 24) {
            header('Location: attendance.php?error=Shift+exceeds+24+hours');
            exit;
        }

        $update = $pdo->prepare("UPDATE duty_logs SET clock_out = ? WHERE id = ?");
        $update->execute([date('Y-m-d H:i:s', $clockOut), $manualDutyId]);
        unset($_SESSION['manual_duty_id']);
        header('Location: attendance.php?msg=Manual+clock+out+saved');
        exit;
    }

    header('Location: index.php');
    exit;
}
