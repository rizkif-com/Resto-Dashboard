<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebarUserId = $_SESSION['user_id'] ?? null;
$user = null;
if ($sidebarUserId) {
    $sidebarStmt = $pdo->prepare("SELECT s.username, s.position_id, p.name as pos_name, p.can_cook, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
    $sidebarStmt->execute([$sidebarUserId]);
    $user = $sidebarStmt->fetch(PDO::FETCH_ASSOC);
}
require_once 'utils.php';
?>
<div id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">🍵</div>
        Resto
    </div>
<?php if ($sidebarUserId && $user): ?>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="index.php" class="sidebar-nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="attendance.php" class="sidebar-nav-link <?php echo $currentPage == 'attendance.php' ? 'active' : ''; ?>">
                🕒 Attendance
            </a>
        </li>
        <?php $positionId = $user['position_id'] ?? null; ?>
        <?php if ($positionId && hasTabAccess($pdo, $positionId, 'weekly_report.php')): ?>
        <li class="sidebar-nav-item">
            <a href="weekly_report.php" class="sidebar-nav-link <?php echo $currentPage == 'weekly_report.php' ? 'active' : ''; ?>">
                📈 Weekly Report
            </a>
        </li>
        <?php endif; ?>
        <?php if ($positionId && hasTabAccess($pdo, $positionId, 'kitchen.php')): ?>
        <li class="sidebar-nav-item">
            <a href="kitchen.php" class="sidebar-nav-link <?php echo $currentPage == 'kitchen.php' ? 'active' : ''; ?>">
                🍳 Kitchen
            </a>
        </li>
        <?php endif; ?>
        <?php if ($positionId && hasTabAccess($pdo, $positionId, 'inventory.php')): ?>
        <li class="sidebar-nav-item">
            <a href="inventory.php" class="sidebar-nav-link <?php echo $currentPage == 'inventory.php' ? 'active' : ''; ?>">
                📦 Inventory
            </a>
        </li>
        <?php endif; ?>
        <?php if ($positionId && hasTabAccess($pdo, $positionId, 'admin.php')): ?>
        <li class="sidebar-nav-item">
            <a href="admin.php" class="sidebar-nav-link <?php echo $currentPage == 'admin.php' ? 'active' : ''; ?>">
                🛡️ Director Panel
            </a>
        </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>
    <div class="sidebar-bottom">
        <a href="login.php" class="sidebar-nav-link text-muted">
            🚪 Logout
        </a>
    </div>
</div>
