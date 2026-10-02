<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $stmt = $pdo->prepare("SELECT p.permission_level, p.can_promote FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userRow = $stmt->fetch();
    $perms = (int)$userRow['permission_level'];
    $canPromote = (bool)$userRow['can_promote'];
    
    if ($perms < 5 && $action !== 'mark_paid') {
        header('Location: index.php');
        exit;
    }

    function getPosLevel($pdo, $posId) {
        $stmt = $pdo->prepare("SELECT permission_level FROM positions WHERE id = ?");
        $stmt->execute([$posId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : 999;
    }

    function getStaffLevel($pdo, $staffId) {
        $stmt = $pdo->prepare("SELECT p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
        $stmt->execute([$staffId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : 999;
    }

    // ---- STAFF MANAGEMENT (add, change_position, delete) ----
    if ($action === 'add_staff') {
        $discordId = $_POST['discord_id'];
        $username = $_POST['username'];
        $posId = (int)$_POST['position_id'];
        
        $reqLevel = getPosLevel($pdo, $posId);
        
        if (!($canPromote || $perms === 6)) {
            header('Location: admin.php?error=CannotPromote');
            exit;
        }
        if ($reqLevel >= $perms) {
            header('Location: admin.php?error=HierarchyViolation');
            exit;
        }
        
        $insert = $pdo->prepare("INSERT INTO staff (discord_id, username, position_id) VALUES (?, ?, ?)");
        $insert->execute([$discordId, $username, $posId]);
    }
    elseif ($action === 'change_position') {
        $staffId = (int)$_POST['staff_id'];
        $newPosId = (int)$_POST['new_position_id'];
        
        $currentLevel = getStaffLevel($pdo, $staffId);
        $newLevel = getPosLevel($pdo, $newPosId);
        
        if (!($canPromote || $perms === 6)) {
            header('Location: admin.php?error=CannotPromote');
            exit;
        }
        if ($currentLevel >= $perms || $newLevel >= $perms) {
            header('Location: admin.php?error=HierarchyViolation');
            exit;
        }
        
        $pdo->prepare("UPDATE staff SET position_id = ? WHERE id = ?")->execute([$newPosId, $staffId]);
    }
    elseif ($action === 'delete_staff') {
        $staffId = (int)$_POST['staff_id'];
        $targetLevel = getStaffLevel($pdo, $staffId);
        
        if (!($canPromote || $perms === 6)) {
            header('Location: admin.php?error=CannotPromote');
            exit;
        }
        if ($targetLevel >= $perms) {
            header('Location: admin.php?error=HierarchyViolation');
            exit;
        }
        $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$staffId]);
    }
    elseif ($action === 'set_sp') {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $spLevel = (int)($_POST['sp_level'] ?? 0);
        $spReason = $_POST['sp_reason'] ?? '';

        if (!($canPromote || $perms >= 6)) {
            header('Location: admin.php?error=CannotPromote');
            exit;
        }

        $targetLevel = getStaffLevel($pdo, $staffId);
        if ($targetLevel >= $perms) {
            header('Location: admin.php?error=HierarchyViolation');
            exit;
        }

        if ($spLevel < 0) $spLevel = 0;
        if ($spLevel > 10) $spLevel = 10;

        $pdo->prepare("UPDATE staff SET sp_level = ? WHERE id = ?")->execute([$spLevel, $staffId]);

        if ($spLevel > 0) {
            $pdo->prepare("INSERT INTO staff_sp (staff_id, reason, issued_by) VALUES (?, ?, ?)")
                ->execute([$staffId, $spReason, $_SESSION['user_id']]);

            $notifTitle = "You received an SP Level " . $spLevel;
            $notifMessage = "Reason: " . ($spReason ? $spReason : 'No reason provided');
            $pdo->prepare("INSERT INTO notifications (staff_id, type, title, message, created_at) VALUES (?, 'SP', ?, ?, NOW())")
                ->execute([$staffId, $notifTitle, $notifMessage]);
        }
    }
    
    // ---- POSITIONS MANAGEMENT (add, edit, delete, role tabs) ----
    elseif ($action === 'add_position') {
        if ($perms < 6) { header('Location: admin.php?error=Unauthorized'); exit; }
        $name = $_POST['name'];
        $base = (float)$_POST['base_salary'];
        $hourly = (float)$_POST['hourly_rate'];
        $canCook = isset($_POST['can_cook']) ? 1 : 0;
        $canPromotePos = isset($_POST['can_promote']) ? 1 : 0;
        $permLevel = (int)$_POST['permission_level'];
        $weeklyTarget = (int)($_POST['weekly_target'] ?? 40);
        $showWeekly = isset($_POST['show_weekly_target']) ? 1 : 0;

        $insert = $pdo->prepare("INSERT INTO positions (name, base_salary, hourly_rate, can_cook, can_promote, permission_level, weekly_target_hours, show_weekly_target) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$name, $base, $hourly, $canCook, $canPromotePos, $permLevel, $weeklyTarget, $showWeekly]);
    }
    elseif ($action === 'edit_position') {
        if ($perms < 6) { header('Location: admin.php?error=Unauthorized'); exit; }
        $posId = (int)$_POST['position_id'];
        $name = $_POST['name'];
        $base = (float)$_POST['base_salary'];
        $hourly = (float)$_POST['hourly_rate'];
        $canCook = isset($_POST['can_cook']) ? 1 : 0;
        $canPromotePos = isset($_POST['can_promote']) ? 1 : 0;
        $permLevel = (int)$_POST['permission_level'];
        $weeklyTarget = (int)($_POST['weekly_target'] ?? 40);
        $showWeekly = isset($_POST['show_weekly_target']) ? 1 : 0;
        
        if (getPosLevel($pdo, $posId) === 6) {
            header('Location: admin.php?error=CannotEditSuperadmin');
            exit;
        }
        $update = $pdo->prepare("UPDATE positions SET name = ?, base_salary = ?, hourly_rate = ?, can_cook = ?, can_promote = ?, permission_level = ?, weekly_target_hours = ?, show_weekly_target = ? WHERE id = ?");
        $update->execute([$name, $base, $hourly, $canCook, $canPromotePos, $permLevel, $weeklyTarget, $showWeekly, $posId]);
    }
    elseif ($action === 'delete_position') {
        if ($perms < 6) { header('Location: admin.php?error=Unauthorized'); exit; }
        $posId = (int)$_POST['position_id'];
        if (getPosLevel($pdo, $posId) === 6) {
            header('Location: admin.php?error=CannotDeleteSuperadmin');
            exit;
        }
        
        $staffCount = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE position_id = ?");
        $staffCount->execute([$posId]);
        if ($staffCount->fetchColumn() > 0) {
            header('Location: admin.php?error=PositionHasStaff');
            exit;
        }
        $pdo->prepare("DELETE FROM role_tabs WHERE position_id = ?")->execute([$posId]);
        $pdo->prepare("DELETE FROM positions WHERE id = ?")->execute([$posId]);
    }
    elseif ($action === 'update_role_tabs') {
        if ($perms < 6) { header('Location: admin.php?error=Unauthorized'); exit; }
        $posId = (int)$_POST['position_id'];
        if (getPosLevel($pdo, $posId) === 6) {
            header('Location: admin.php?error=CannotEditSuperadmin');
            exit;
        }
        $tabs = $_POST['tabs'] ?? [];
        
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM role_tabs WHERE position_id = ?")->execute([$posId]);
            $insert = $pdo->prepare("INSERT INTO role_tabs (position_id, tab_name) VALUES (?, ?)");
            foreach ($tabs as $tab) {
                $insert->execute([$posId, $tab]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: admin.php?error=TabUpdateFailed');
            exit;
        }
    }

    header('Location: admin.php?msg=Success');
    exit;
}
