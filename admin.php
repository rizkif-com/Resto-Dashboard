<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.username, p.name as pos_name, p.permission_level, p.can_promote FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$perms = (int)$user['permission_level'];
$canPromote = (bool)$user['can_promote'];

if ($perms < 5) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['mark_paid'])) {
    $reportId = (int)$_POST['report_id'];
    
    $stmt = $pdo->prepare("SELECT staff_id, total_pay FROM payroll_reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    
    if ($report) {
        $pdo->prepare("UPDATE payroll_reports SET status = 'PAID' WHERE id = ?")->execute([$reportId]);
        
        $notifTitle = "Your Salary Has Been Paid!";
        $notifMessage = "Your paycheck of $" . number_format($report['total_pay'], 2) . " has been processed and paid.";
        $pdo->prepare("INSERT INTO notifications (staff_id, type, title, message, reference_id, created_at) VALUES (?, 'SALARY_PAID', ?, ?, ?, NOW())")
            ->execute([$report['staff_id'], $notifTitle, $notifMessage, $reportId]);
    }
    
    header('Location: admin.php');
    exit;
}

$totalStaff = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$pendingSp = $pdo->query("SELECT COUNT(*) FROM staff WHERE sp_level > 0")->fetchColumn();
$unpaidPayrollsCount = $pdo->query("SELECT COUNT(*) FROM payroll_reports WHERE status = 'UNPAID'")->fetchColumn();
$kitchenAudits = $pdo->query("SELECT COUNT(*) FROM cook_logs WHERE status = 'PENDING'")->fetchColumn();

$unpaidPayrolls = $pdo->query("SELECT p.*, s.username FROM payroll_reports p JOIN staff s ON p.staff_id = s.id WHERE p.status = 'UNPAID' ORDER BY p.id DESC")->fetchAll();
$pendingCooks = $pdo->query("SELECT c.*, s.username, r.name as recipe_name FROM cook_logs c JOIN staff s ON c.staff_id = s.id JOIN recipes r ON c.recipe_id = r.id WHERE c.status = 'PENDING' ORDER BY c.id DESC")->fetchAll();
$allStaff = $pdo->query("SELECT s.*, p.name as pos_name, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id ORDER BY s.id ASC")->fetchAll();
$allPositions = $pdo->query("SELECT * FROM positions ORDER BY permission_level DESC, name ASC")->fetchAll();

$roleTabs = [];
try {
    $tabsStmt = $pdo->query("SELECT position_id, tab_name FROM role_tabs");
    while ($row = $tabsStmt->fetch()) {
        $roleTabs[$row['position_id']][] = $row['tab_name'];
    }
} catch (Exception $e) {}
$availableTabs = ['attendance.php', 'kitchen.php', 'inventory.php', 'admin.php', 'weekly_report.php'];

$pageTitle = 'Director Panel';
$pageSubtitle = 'Manage operations, staff, and payroll';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Director Panel - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'header.php'; ?>

        <div class="p-4 fade-in">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 h-100">
                        <div class="metric-title">Total Staff</div>
                        <div class="metric-value mt-2"><?php echo $totalStaff; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 border-danger">
                        <div class="metric-title">Pending SP Logs</div>
                        <div class="metric-value mt-2 text-danger"><?php echo $pendingSp; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 border-warning">
                        <div class="metric-title">Unpaid Payrolls</div>
                        <div class="metric-value mt-2 text-warning"><?php echo $unpaidPayrollsCount; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 border-primary">
                        <div class="metric-title">Kitchen Audits</div>
                        <div class="metric-value mt-2 text-primary"><?php echo $kitchenAudits; ?></div>
                    </div>
                </div>
            </div>

            <!-- Management Buttons -->
            <div class="mb-4 d-flex gap-2">
                <?php if ($canPromote || $perms >= 6): ?>
                <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">+ Add Staff</button>
                <?php endif; ?>
                <?php if ($perms >= 6): ?>
                <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPosModal">+ Add Position</button>
                <?php endif; ?>
            </div>

            <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active btn-sm me-2 rounded-pill px-4 text-white border border-secondary bg-dark" data-bs-toggle="pill" data-bs-target="#payroll" style="opacity:0.8;">Payroll Mgmt</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link btn-sm me-2 rounded-pill px-4 text-white border border-secondary bg-dark" data-bs-toggle="pill" data-bs-target="#audit" style="opacity:0.8;">Kitchen Audit</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link btn-sm me-2 rounded-pill px-4 text-white border border-secondary bg-dark" data-bs-toggle="pill" data-bs-target="#staff" style="opacity:0.8;">Staff & SP</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link btn-sm me-2 rounded-pill px-4 text-white border border-secondary bg-dark" data-bs-toggle="pill" data-bs-target="#positions_tab" style="opacity:0.8;">Positions</button></li>
                <?php if ($perms >= 6): ?>
                <li class="nav-item" role="presentation"><button class="nav-link btn-sm me-2 rounded-pill px-4 text-white border border-secondary bg-dark" data-bs-toggle="pill" data-bs-target="#role_tabs_tab" style="opacity:0.8;">Role Permissions</button></li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="adminTabsContent">
                <!-- Payroll Tab -->
                <div class="tab-pane fade show active" id="payroll">
                    <div class="card">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Pending Payrolls</h6>
                            <div class="table-responsive px-4 pb-3">
                                <table class="table table-hover table-borderless">
                                    <thead><tr><th>Staff Member</th><th>Period</th><th>Total Pay</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($unpaidPayrolls)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">No unpaid payrolls.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach($unpaidPayrolls as $pr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pr['username']); ?></td>
                                            <td><?php echo $pr['week_start'] . ' to ' . $pr['week_end']; ?></td>
                                            <td class="text-warning fw-bold">$<?php echo number_format($pr['total_pay'], 2); ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="report_id" value="<?php echo $pr['id']; ?>">
                                                    <button type="submit" name="mark_paid" class="btn btn-sm btn-success">Mark as Paid</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kitchen Audit Tab -->
                <div class="tab-pane fade" id="audit">
                    <div class="card">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Recent Cook Logs</h6>
                            <div class="table-responsive px-4 pb-3">
                                <table class="table table-hover table-borderless">
                                    <thead><tr><th>Staff Member</th><th>Item</th><th>Qty</th><th>Proof</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($pendingCooks)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">No pending cook logs.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach($pendingCooks as $cook): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cook['username']); ?></td>
                                            <td><?php echo htmlspecialchars($cook['recipe_name']); ?></td>
                                            <td><?php echo htmlspecialchars($cook['quantity']); ?></td>
                                            <td><a href="#" class="text-primary">View Photo</a></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger">Reject</button>
                                                <button class="btn btn-sm btn-success">Accept</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Tab -->
                <div class="tab-pane fade" id="staff">
                    <div class="card">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Staff Overview</h6>
                            <div class="table-responsive px-4 pb-3">
                                <table class="table table-hover table-borderless">
                                    <thead><tr><th>ID</th><th>Name</th><th>Position</th><th>SP Level</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($allStaff as $st): ?>
                                        <tr>
                                            <td><?php echo $st['id']; ?></td>
                                            <td><?php echo htmlspecialchars($st['username']); ?></td>
                                            <td><?php echo htmlspecialchars($st['pos_name']); ?></td>
                                            <td>
                                                <?php if($st['sp_level'] > 0): ?>
                                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">Level <?php echo $st['sp_level']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(($canPromote || $perms >= 6) && $st['permission_level'] < $perms): ?>
                                                <button class="btn btn-sm btn-outline-info manage-staff-btn" 
                                                        data-id="<?php echo $st['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($st['username']); ?>"
                                                    data-pos="<?php echo $st['position_id']; ?>"
                                                    data-splevel="<?php echo $st['sp_level']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#manageStaffModal">Manage Role</button>
                                                <?php else: ?>
                                                    <span class="text-muted small">Protected</span>
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

                <!-- Positions Tab -->
                <div class="tab-pane fade" id="positions_tab">
                    <div class="card">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Positions Management</h6>
                            <div class="table-responsive px-4 pb-3">
                                <table class="table table-hover table-borderless">
                                    <thead><tr><th>ID</th><th>Name</th><th>Base Salary</th><th>Hourly Rate</th><th>Weekly Target</th><th>Show Target</th><th>Can Cook</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($allPositions as $p): ?>
                                        <tr>
                                            <td><?php echo $p['id']; ?></td>
                                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                                            <td>$<?php echo number_format($p['base_salary'], 2); ?></td>
                                            <td>$<?php echo number_format($p['hourly_rate'], 2); ?></td>
                                            <td><?php echo intval($p['weekly_target_hours']); ?>h</td>
                                            <td><?php echo $p['show_weekly_target'] ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>'; ?></td>
                                            <td><?php echo $p['can_cook'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></td>
                                            <td>
                                                <?php if($perms >= 6 && $p['permission_level'] != 6): ?>
                                                <button class="btn btn-sm btn-outline-light edit-pos-btn" 
                                                    data-id="<?php echo $p['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                                    data-base="<?php echo $p['base_salary']; ?>"
                                                    data-hourly="<?php echo $p['hourly_rate']; ?>"
                                                    data-cancook="<?php echo $p['can_cook']; ?>"
                                                    data-canpromote="<?php echo $p['can_promote'] ?? 0; ?>"
                                                    data-level="<?php echo $p['permission_level']; ?>"
                                                    data-weekly="<?php echo $p['weekly_target_hours'] ?? 40; ?>"
                                                    data-showweekly="<?php echo $p['show_weekly_target'] ? 1 : 0; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editPosModal">Edit</button>
                                                <form method="POST" action="process_admin.php" style="display:inline;" onsubmit="return confirm('Delete this position? It must not have any staff assigned.');">
                                                    <input type="hidden" name="action" value="delete_position">
                                                    <input type="hidden" name="position_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                                <?php elseif($p['permission_level'] == 6): ?>
                                                    <span class="text-muted small">Protected Superadmin</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Superadmin Only</span>
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

                <!-- Role Permissions Tab -->
                <?php if ($perms >= 6): ?>
                <div class="tab-pane fade" id="role_tabs_tab">
                    <div class="card">
                        <div class="card-body p-0">
                            <h6 class="text-white fw-bold p-4 pb-0 mb-3">Manage Tab Access per Role</h6>
                            <div class="px-4 pb-4">
                                <p class="text-muted small">Select which tabs each role is allowed to access. (The Superadmin always has access to everything).</p>
                                <div class="accordion" id="rolesAccordion">
                                    <?php foreach($allPositions as $p): ?>
                                    <div class="accordion-item bg-dark border-secondary">
                                        <h2 class="accordion-header" id="heading_<?php echo $p['id']; ?>">
                                            <button class="accordion-button collapsed bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $p['id']; ?>">
                                                <?php echo htmlspecialchars($p['name']); ?> <?php if($p['permission_level'] == 6) echo '<span class="badge bg-danger ms-2">Superadmin</span>'; ?>
                                            </button>
                                        </h2>
                                        <div id="collapse_<?php echo $p['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#rolesAccordion">
                                            <div class="accordion-body">
                                                <?php if($p['permission_level'] == 6): ?>
                                                    <div class="alert alert-warning py-2 mb-0">The Superadmin role has unrestricted access and cannot be modified.</div>
                                                <?php else: ?>
                                                    <form method="POST" action="process_admin.php">
                                                        <input type="hidden" name="action" value="update_role_tabs">
                                                        <input type="hidden" name="position_id" value="<?php echo $p['id']; ?>">
                                                        <div class="d-flex flex-wrap gap-3 mb-3">
                                                            <?php 
                                                            $currentTabs = $roleTabs[$p['id']] ?? []; 
                                                            foreach($availableTabs as $tab): 
                                                                $isChecked = in_array($tab, $currentTabs);
                                                            ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="tabs[]" value="<?php echo htmlspecialchars($tab); ?>" id="chk_<?php echo $p['id'] . '_' . md5($tab); ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                                                <label class="form-check-label text-white" for="chk_<?php echo $p['id'] . '_' . md5($tab); ?>">
                                                                    <?php echo htmlspecialchars($tab); ?>
                                                                </label>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary">Save Permissions for <?php echo htmlspecialchars($p['name']); ?></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-card); border-color: var(--border-color);">
          <div class="modal-header border-bottom border-secondary">
            <h5 class="modal-title text-white">Add Staff Member</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="process_admin.php">
              <div class="modal-body">
                <input type="hidden" name="action" value="add_staff">
                <div class="mb-3">
                    <label class="form-label text-muted">Discord ID</label>
                    <input type="text" class="form-control" name="discord_id" required>
                    <small class="text-muted">Used for Discord OAuth Login.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Position</label>
                    <select class="form-select" name="position_id" required>
                        <?php foreach($allPositions as $p): ?>
                            <?php if($p['permission_level'] < $perms): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
              </div>
              <div class="modal-footer border-top border-secondary">
                <button type="submit" class="btn btn-success w-100">Add Staff</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Add Position Modal -->
    <div class="modal fade" id="addPosModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-card); border-color: var(--border-color);">
          <div class="modal-header border-bottom border-secondary">
            <h5 class="modal-title text-white">Add Position</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="process_admin.php">
              <div class="modal-body">
                <input type="hidden" name="action" value="add_position">
                <div class="mb-3">
                    <label class="form-label text-muted">Position Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Base Salary ($)</label>
                        <input type="number" step="0.01" class="form-control" name="base_salary" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Hourly Rate ($)</label>
                        <input type="number" step="0.01" class="form-control" name="hourly_rate" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label text-muted">Permission Level (1-5)</label>
                        <input type="number" min="1" max="5" class="form-control" name="permission_level" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Weekly Target (hours)</label>
                        <input type="number" min="0" class="form-control" name="weekly_target" value="40">
                    </div>
                    <div class="col-6 d-flex align-items-center">
                        <div class="form-check form-switch ms-auto">
                            <input class="form-check-input" type="checkbox" name="show_weekly_target" id="add_show_weekly_target" checked>
                            <label class="form-check-label text-white" for="add_show_weekly_target">Show Weekly Target</label>
                        </div>
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="can_cook" id="canCookCheck">
                    <label class="form-check-label text-white" for="canCookCheck">Has Cooking Access</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="can_promote" id="canPromoteCheck">
                    <label class="form-check-label text-white" for="canPromoteCheck">Can Promote/Fire Staff</label>
                </div>
              </div>
              <div class="modal-footer border-top border-secondary">
                <button type="submit" class="btn btn-success w-100">Add Position</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Position Modal -->
    <div class="modal fade" id="editPosModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-card); border-color: var(--border-color);">
          <div class="modal-header border-bottom border-secondary">
            <h5 class="modal-title text-white">Edit Position</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="process_admin.php">
              <div class="modal-body">
                <input type="hidden" name="action" value="edit_position">
                <input type="hidden" name="position_id" id="edit_position_id">
                <div class="mb-3">
                    <label class="form-label text-muted">Position Name</label>
                    <input type="text" class="form-control" name="name" id="edit_name" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Base Salary ($)</label>
                        <input type="number" step="0.01" class="form-control" name="base_salary" id="edit_base_salary" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Hourly Rate ($)</label>
                        <input type="number" step="0.01" class="form-control" name="hourly_rate" id="edit_hourly_rate" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label text-muted">Permission Level (1-5)</label>
                        <input type="number" min="1" max="5" class="form-control" name="permission_level" id="edit_permission_level" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Weekly Target (hours)</label>
                        <input type="number" min="0" class="form-control" name="weekly_target" id="edit_weekly_target" value="40">
                    </div>
                    <div class="col-6 d-flex align-items-center">
                        <div class="form-check form-switch ms-auto">
                            <input class="form-check-input" type="checkbox" name="show_weekly_target" id="edit_show_weekly">
                            <label class="form-check-label text-white" for="edit_show_weekly">Show Weekly Target</label>
                        </div>
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="can_cook" id="edit_can_cook">
                    <label class="form-check-label text-white" for="edit_can_cook">Has Cooking Access</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="can_promote" id="edit_can_promote">
                    <label class="form-check-label text-white" for="edit_can_promote">Can Promote/Fire Staff</label>
                </div>
              </div>
              <div class="modal-footer border-top border-secondary">
                <button type="submit" class="btn btn-primary w-100">Save Changes</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Manage Staff Modal -->
    <div class="modal fade" id="manageStaffModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-card); border-color: var(--border-color);">
          <div class="modal-header border-bottom border-secondary">
            <h5 class="modal-title text-white">Manage Staff</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="process_admin.php" class="mb-4">
                <input type="hidden" name="action" value="change_position">
                <input type="hidden" name="staff_id" id="manage_staff_id">
                <div class="mb-3">
                    <label class="form-label text-muted">Change Role for <span id="manage_staff_name" class="text-white fw-bold"></span></label>
                    <select class="form-select" name="new_position_id" id="manage_staff_pos" required>
                        <?php foreach($allPositions as $p): ?>
                            <?php if($p['permission_level'] < $perms): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Save New Role</button>
            </form>
            <hr class="border-secondary">
            <form method="POST" action="process_admin.php" class="mb-3">
                <input type="hidden" name="action" value="set_sp">
                <input type="hidden" name="staff_id" id="manage_staff_id_sp">
                <div class="mb-3">
                    <label class="form-label text-muted">SP Level (0 = none)</label>
                    <input type="number" min="0" max="10" name="sp_level" id="manage_sp_level" class="form-control" />
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Reason for SP</label>
                    <textarea name="sp_reason" id="manage_sp_reason" class="form-control" rows="3" placeholder="Enter the reason for issuing this SP..."></textarea>
                </div>
                <button type="submit" class="btn btn-warning w-100">Save SP Level</button>
            </form>
            <hr class="border-secondary">
            <form method="POST" action="process_admin.php" onsubmit="return confirm('Are you sure you want to fire this staff member? This action cannot be undone.');">
                <input type="hidden" name="action" value="delete_staff">
                <input type="hidden" name="staff_id" id="fire_staff_id">
                <button type="submit" class="btn btn-outline-danger w-100">Fire Staff Member</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script>
        document.querySelectorAll('#adminTabs .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('#adminTabs .nav-link').forEach(l => {
                    l.classList.remove('active');
                    l.classList.replace('border-accent', 'border-secondary');
                });
                this.classList.add('active');
                this.classList.replace('border-secondary', 'border-accent');
            });
        });

        document.querySelectorAll('.edit-pos-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_position_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_base_salary').value = this.dataset.base;
                document.getElementById('edit_hourly_rate').value = this.dataset.hourly;
                document.getElementById('edit_can_cook').checked = this.dataset.cancook == 1;
                document.getElementById('edit_can_promote').checked = this.dataset.canpromote == 1;
                document.getElementById('edit_permission_level').value = this.dataset.level;
                document.getElementById('edit_weekly_target').value = this.dataset.weekly || 40;
                document.getElementById('edit_show_weekly').checked = this.dataset.showweekly == 1;
            });
        });

        document.querySelectorAll('.manage-staff-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('manage_staff_id').value = this.dataset.id;
                document.getElementById('fire_staff_id').value = this.dataset.id;
                document.getElementById('manage_staff_name').innerText = this.dataset.name;
                document.getElementById('manage_staff_pos').value = this.dataset.pos;
                // sync SP modal fields
                document.getElementById('manage_staff_id_sp').value = this.dataset.id;
                document.getElementById('manage_sp_level').value = this.dataset.splevel || '';
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
