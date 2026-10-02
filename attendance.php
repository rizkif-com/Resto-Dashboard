<?php
require_once 'config.php';
requireLogin();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$__php_error_log = __DIR__ . '/php_error.log';
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($__php_error_log) {
    $msg = sprintf("[%s] ERROR %d: %s in %s on line %d\n", date('c'), $errno, $errstr, $errfile, $errline);
    error_log($msg, 3, $__php_error_log);
    return false;
});
set_exception_handler(function($e) use ($__php_error_log) {
    $msg = sprintf("[%s] EXCEPTION: %s in %s on line %d\nStack: %s\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    error_log($msg, 3, $__php_error_log);
    http_response_code(500);
    echo "<p style='color:red;'>Internal server error. Check php_error.log for details.</p>";
    exit;
});

$userId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT s.username, s.sp_level, p.name as pos_name FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    $user = ['username' => 'Unknown', 'sp_level' => 0, 'pos_name' => ''];
}

$spLevel = isset($user['sp_level']) ? (int)$user['sp_level'] : 0;
$userName = $user['username'] ?? 'Unknown';
$posName = $user['pos_name'] ?? '';

$dutyStmt = $pdo->prepare("SELECT id, clock_in FROM duty_logs WHERE staff_id = ? AND clock_out IS NULL");
$dutyStmt->execute([$userId]);
$currentDuty = $dutyStmt->fetch();
$isOnDuty = $currentDuty !== false;


try {
    $pdo->beginTransaction();
    $todayStart = date('Y-m-d 00:00:00');
    $rollStmt = $pdo->prepare("SELECT id, staff_id, clock_in FROM duty_logs WHERE clock_out IS NULL AND clock_in < ?");
    $rollStmt->execute([$todayStart]);
    $toRoll = $rollStmt->fetchAll();
    if (!empty($toRoll)) {
        $closeStmt = $pdo->prepare("UPDATE duty_logs SET clock_out = ? WHERE id = ?");
        $insStmt = $pdo->prepare("INSERT INTO duty_logs (staff_id, clock_in) VALUES (?, ?)");
        foreach ($toRoll as $r) {
            $closeStmt->execute([$todayStart, $r['id']]);
            $insStmt->execute([$r['staff_id'], $todayStart]);
        }
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

$dutyStmt = $pdo->prepare("SELECT id, clock_in FROM duty_logs WHERE staff_id = ? AND clock_out IS NULL");
$dutyStmt->execute([$userId]);
$currentDuty = $dutyStmt->fetch();
$isOnDuty = $currentDuty !== false;

$hoursStmt = $pdo->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW())) / 60) as total_hours 
                            FROM duty_logs WHERE staff_id = ? AND status = 'PENDING'");
$hoursStmt->execute([$userId]);
$unpaidHours = round((float)$hoursStmt->fetchColumn(), 1);

try {
    $hasWeeklyCol = (bool)$pdo->query("SHOW COLUMNS FROM positions LIKE 'weekly_target_hours'")->rowCount();
    $hasShowCol = (bool)$pdo->query("SHOW COLUMNS FROM positions LIKE 'show_weekly_target'")->rowCount();

    $selectCols = "p.base_salary, p.hourly_rate";
    if ($hasWeeklyCol) $selectCols .= ", p.weekly_target_hours";
    if ($hasShowCol) $selectCols .= ", p.show_weekly_target";

    $baseSalaryStmt = $pdo->prepare("SELECT $selectCols FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
    $baseSalaryStmt->execute([$userId]);
    $posInfo = $baseSalaryStmt->fetch();
} catch (Exception $e) {
    error_log("[attendance.php] Position query failed: " . $e->getMessage());
    $posInfo = ['base_salary' => 0, 'hourly_rate' => 0];
}

$estSalary = $unpaidHours >= 21 ? ($posInfo['base_salary'] ?? 0) + (($unpaidHours - 21) * ($posInfo['hourly_rate'] ?? 0)) : (($posInfo['base_salary'] ?? 0) / 21) * $unpaidHours;
$weeklyTarget = isset($posInfo['weekly_target_hours']) ? (int)$posInfo['weekly_target_hours'] : 40;
$showWeekly = !empty($posInfo['show_weekly_target']);

$shiftsStmt = $pdo->prepare("SELECT DATE(clock_in) as date, DATE_FORMAT(clock_in, '%H:%i') as start_time, DATE_FORMAT(clock_out, '%H:%i') as end_time, TIMESTAMPDIFF(MINUTE, clock_in, clock_out)/60 as duration, status FROM duty_logs WHERE staff_id = ? ORDER BY id DESC LIMIT 5");
$shiftsStmt->execute([$userId]);
$shifts = $shiftsStmt->fetchAll();

$errorMsg = $_GET['error'] ?? '';
$successMsg = $_GET['msg'] ?? '';

$showOffButton = false;
if ($isOnDuty) {
    $clockIn = strtotime($currentDuty['clock_in']);
    $now = time();
    $durationHours = ($now - $clockIn) / 3600.0;
    $showOffButton = ($durationHours <= 21);
}

$manualDutyId = $_SESSION['manual_duty_id'] ?? null;

$pageTitle = 'Attendance';
$pageSubtitle = 'Track your working hours';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'header.php'; ?>

        <div class="p-4 fade-in">
            <!-- Top Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 h-100">
                        <div class="metric-title">Unpaid Hours</div>
                        <div class="metric-value mt-2"><?php echo number_format($unpaidHours, 1); ?>h</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100">
                        <div class="metric-title">Est. Salary</div>
                        <div class="metric-value mt-2 text-success">$<?php echo number_format($estSalary, 0); ?></div>
                    </div>
                </div>
                <?php if ($showWeekly): ?>
                <div class="col-md-3">
                    <div class="card p-3 h-100">
                        <div class="metric-title">Weekly Target</div>
                        <div class="metric-value mt-2"><?php echo intval($weeklyTarget); ?>h</div>
                        <div class="text-muted" style="font-size: 0.75rem;">100% complete</div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <div class="card p-3 h-100 border-success">
                        <div class="metric-title">Warning Level</div>
                        <div class="metric-value mt-2 <?php echo $spLevel > 0 ? 'text-danger' : 'text-white'; ?>">
                            <?php echo $spLevel > 0 ? 'Level ' . $spLevel : 'Clean'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <?php if ($showWeekly): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="metric-title">Weekly Hours Progress</div>
                        <div class="metric-title text-white"><?php echo number_format($unpaidHours, 1); ?> / <?php echo intval($weeklyTarget); ?>h</div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo intval($weeklyTarget) > 0 ? min(100, ($unpaidHours / $weeklyTarget) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Duty Control -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="text-white m-0 fw-bold">Duty Control</h6>
                                <?php if($isOnDuty): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">● ON DUTY</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2">● OFF DUTY</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($errorMsg)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($successMsg)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="process_duty.php">
                                <?php if(!$isOnDuty): ?>
                                    <button type="submit" name="action" value="clock_in" class="btn btn-success w-100 py-3 fw-bold mb-3">▷ Clock In</button>
                                <?php else: ?>
                                    <?php if($showOffButton): ?>
                                        <button type="submit" name="action" value="clock_out" class="btn btn-danger w-100 py-3 fw-bold mb-3">■ Clock Out</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100 py-3 fw-bold mb-3" disabled>Clock Out (disabled — suspicious duration)</button>
                                        <div class="text-muted small mb-2">Shift exceeds 21 hours; use Manual Entry to record an off-duty time.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </form>

                            <button class="btn btn-outline-secondary w-100 py-2 border-secondary text-white" data-bs-toggle="modal" data-bs-target="#manualEntryModal">+ Manual Entry</button>
                        </div>
                    </div>
                </div>

                <!-- Shift History -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Shift History</h6>
                            <div class="table-responsive px-4">
                                <table class="table table-hover table-borderless">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($shifts as $s): ?>
                                        <tr>
                                            <td><?php echo $s['date']; ?></td>
                                            <td><?php echo $s['start_time']; ?></td>
                                            <td><?php echo $s['end_time'] ?? '-'; ?></td>
                                            <td><?php echo $s['duration'] ? number_format($s['duration'], 2) . 'h' : '-'; ?></td>
                                            <td>
                                                <?php if($s['status'] === 'PAID'): ?>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1">PAID</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2 py-1">UNPAID</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Manual Duty Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">You must first create a manual Clock In before creating a manual Clock Out. Manual Clock Out is only enabled if you previously created a manual Clock In in this session.</p>

                    <form method="POST" action="process_duty.php">
                        <div class="mb-3">
                            <label class="form-label">Manual Clock In (YYYY-MM-DD HH:MM)</label>
                            <input type="datetime-local" name="clock_in_time" class="form-control bg-dark text-white" />
                        </div>
                        <button type="submit" name="action" value="manual_clock_in" class="btn btn-primary mb-3">Save Manual Clock In</button>
                    </form>

                    <hr class="border-secondary">

                    <form method="POST" action="process_duty.php">
                        <div class="mb-3">
                            <label class="form-label">Manual Clock Out (YYYY-MM-DD HH:MM)</label>
                            <input type="datetime-local" name="clock_out_time" class="form-control bg-dark text-white" <?php echo $manualDutyId ? '' : 'disabled'; ?> />
                        </div>
                        <input type="hidden" name="manual_duty_id" value="<?php echo htmlspecialchars($manualDutyId); ?>" />
                        <button type="submit" name="action" value="manual_clock_out" class="btn btn-danger" <?php echo $manualDutyId ? '' : 'disabled title="Create a manual Clock In first"'; ?>>Save Manual Clock Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>
