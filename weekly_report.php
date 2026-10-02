<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.username, p.name as pos_name, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$statsStmt = $pdo->query("
    SELECT 
        s.id, s.username, p.name as pos_name, p.base_salary, p.hourly_rate,
        (SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, d.clock_in, IFNULL(d.clock_out, NOW())) / 60), 0) FROM duty_logs d WHERE d.staff_id = s.id AND YEARWEEK(d.clock_in, 1) = YEARWEEK(CURDATE(), 1)) as weekly_hours,
        (SELECT COALESCE(SUM(c.quantity), 0) FROM cook_logs c WHERE c.staff_id = s.id AND YEARWEEK(c.created_at, 1) = YEARWEEK(CURDATE(), 1)) as food_cooked
    FROM staff s
    JOIN positions p ON s.position_id = p.id
    ORDER BY weekly_hours DESC
");
$staffStats = $statsStmt->fetchAll();

$pageTitle = 'Weekly Report';
$pageSubtitle = 'View staff duty hours, salaries, and kitchen production for the week';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly Report - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'header.php'; ?>

        <div class="p-4 fade-in">
            <div class="row g-4">
                
                <!-- Leaderboard Section -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header border-bottom border-secondary bg-transparent py-3">
                            <h6 class="text-white m-0 fw-bold">🏆 Weekly Duty Hour Leaderboard</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php 
                                $rank = 1;
                                foreach($staffStats as $stat): 
                                    if ($stat['weekly_hours'] <= 0) continue;
                                    
                                    $badgeClass = 'bg-secondary';
                                    if ($rank == 1) $badgeClass = 'bg-warning text-dark';
                                    else if ($rank == 2) $badgeClass = 'bg-light text-dark';
                                    else if ($rank == 3) $badgeClass = 'bg-primary text-white';
                                ?>
                                <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge <?php echo $badgeClass; ?> rounded-circle me-3 d-flex justify-content-center align-items-center" style="width: 30px; height: 30px; font-size: 14px;">
                                            <?php echo $rank; ?>
                                        </span>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($stat['username']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($stat['pos_name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="fw-bold fs-5 text-accent">
                                        <?php echo number_format($stat['weekly_hours'], 1); ?>h
                                    </div>
                                </li>
                                <?php 
                                $rank++;
                                endforeach; 
                                if ($rank == 1): ?>
                                    <li class="list-group-item bg-transparent text-muted text-center py-4">No duty hours recorded this week yet.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Report -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header border-bottom border-secondary bg-transparent py-3">
                            <h6 class="text-white m-0 fw-bold">📊 Comprehensive Staff Report</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive px-4 pb-3 mt-3">
                                <table class="table table-hover table-borderless">
                                    <thead>
                                        <tr>
                                            <th>Staff Name</th>
                                            <th>Position</th>
                                            <th class="text-end">Duty Hours</th>
                                            <th class="text-end">Dishes Cooked</th>
                                            <th class="text-end">Est. Salary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($staffStats as $stat): 
                                            $hours = round((float)$stat['weekly_hours'], 1);
                                            $baseSalary = (float)$stat['base_salary'];
                                            $hourlyRate = (float)$stat['hourly_rate'];
                                            if ($hours >= 21) {
                                                $estSalary = $baseSalary + (($hours - 21) * $hourlyRate);
                                            } else {
                                                $estSalary = ($baseSalary / 21) * $hours;
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-white"><?php echo htmlspecialchars($stat['username']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-dark border border-secondary text-light">
                                                    <?php echo htmlspecialchars($stat['pos_name']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?php echo number_format($hours, 1); ?>h
                                            </td>
                                            <td class="text-end text-success fw-bold">
                                                <?php echo (int)$stat['food_cooked']; ?>
                                            </td>
                                            <td class="text-end text-warning fw-bold">
                                                $<?php echo number_format($estSalary, 2); ?>
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
</body>
</html>
