<?php
/**
 * Checks whether a given role (position) has access to a specific tab.
 * Uses permission_level from positions table as the primary mechanism.
 * If role_tabs table exists and has mappings, those take priority.
 */
function hasTabAccess($pdo, $positionId, $tabName) {
    if (empty($positionId)) {
        return false;
    }

    $permLevel = 0;
    try {
        $permStmt = $pdo->prepare("SELECT permission_level FROM positions WHERE id = ?");
        $permStmt->execute([$positionId]);
        $permLevel = (int) $permStmt->fetchColumn();
    } catch (Exception $e) {
        return true;
    }

    try {
        $tableExists = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'role_tabs'")->fetchColumn();
        
        if ($tableExists) {
            $colCheck = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'role_tabs' AND column_name = 'position_id'")->fetchColumn();
            
            if ($colCheck) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM role_tabs WHERE position_id = ?");
                $checkStmt->execute([$positionId]);
                $count = (int) $checkStmt->fetchColumn();

                if ($count > 0) {
                    $stmt = $pdo->prepare("SELECT 1 FROM role_tabs WHERE position_id = ? AND tab_name = ? LIMIT 1");
                    $stmt->execute([$positionId, $tabName]);
                    return (bool) $stmt->fetchColumn();
                }
            }
        }
    } catch (Exception $e) {
    }

    switch ($tabName) {
        case 'kitchen.php':
        case 'inventory.php':
            return $permLevel >= 4;
        case 'admin.php':
            return $permLevel >= 5;
        case 'weekly_report.php':
            return $permLevel >= 3;
        default:
            return true; 
    }
}
