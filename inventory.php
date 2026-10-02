<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT s.username, p.name as pos_name, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$perms = (int)$user['permission_level'];

if ($perms < 4) {
    header("Location: index.php");
    exit;
}

$invStmt = $pdo->query("SELECT id, name, stock_quantity, image_url, cost FROM ingredients ORDER BY name ASC");
$inventory = $invStmt->fetchAll();

$pageTitle = 'Inventory';
$pageSubtitle = 'Manage and view ingredient stock levels';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'header.php'; ?>

        <div class="p-4 fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white">Inventory</h4>
                <div>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">+ Add Ingredient</button>
                </div>
            </div>

            <?php if(!empty($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if(!empty($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach($inventory as $item): ?>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background-color: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <?php if($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="max-width: 50px; max-height: 50px; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.5));">
                                <?php else: ?>
                                    <span style="font-size: 2rem;">📦</span>
                                <?php endif; ?>
                            </div>
                            <h6 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <p class="text-muted small mb-1">Unit Cost: $<?php echo number_format($item['cost'], 2); ?></p>
                            
                            <div class="w-100 mt-auto p-2 rounded" style="background-color: rgba(0,0,0,0.2);">
                                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">In Stock</span>
                                <h4 class="text-success m-0 fw-bold"><?php echo $item['stock_quantity']; ?></h4>
                            </div>

                            <div class="mt-3 w-100 d-flex gap-2">
                                <button class="btn btn-sm btn-outline-light w-100 edit-item-btn" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-qty="<?php echo $item['stock_quantity']; ?>"
                                    data-cost="<?php echo $item['cost']; ?>"
                                    data-image="<?php echo htmlspecialchars($item['image_url']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editItemModal">Edit</button>
                                <button class="btn btn-sm btn-outline-danger w-100 delete-item-btn" data-id="<?php echo $item['id']; ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>

<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Add Ingredient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="process_inventory.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control bg-dark text-white" name="name" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input class="form-control bg-dark text-white" name="stock_quantity" type="number" value="0" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost</label>
                        <input class="form-control bg-dark text-white" name="cost" type="number" step="0.01" value="0.00" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input class="form-control bg-dark text-white" name="image_url" />
                    </div>
                    <button class="btn btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Edit Ingredient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="process_inventory.php" id="editItemForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control bg-dark text-white" name="name" id="edit_name" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input class="form-control bg-dark text-white" name="stock_quantity" id="edit_qty" type="number" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost</label>
                        <input class="form-control bg-dark text-white" name="cost" id="edit_cost" type="number" step="0.01" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input class="form-control bg-dark text-white" name="image_url" id="edit_image" />
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <hr class="border-secondary my-3">
                <form method="POST" action="process_inventory.php" id="adjustForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                    <input type="hidden" name="action" value="adjust">
                    <input type="hidden" name="id" id="adjust_id">
                    <div class="mb-3">
                        <label class="form-label">Adjust by (use negative to subtract)</label>
                        <input class="form-control bg-dark text-white" name="delta" id="adjust_delta" type="number" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Or set absolute stock (leave empty to skip)</label>
                        <input class="form-control bg-dark text-white" name="set_stock" id="adjust_set" type="number" />
                    </div>
                    <button class="btn btn-warning">Apply Adjustment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_qty').value = btn.dataset.qty;
        document.getElementById('edit_cost').value = btn.dataset.cost;
        document.getElementById('edit_image').value = btn.dataset.image;

        document.getElementById('adjust_id').value = id;
    });
});

document.querySelectorAll('.delete-item-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Delete this ingredient? This action cannot be undone.')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_inventory.php';
        form.style.display = 'none';
        form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                         '<input type="hidden" name="id" value="' + btn.dataset.id + '">';
        document.body.appendChild(form);
        form.submit();
    });
});
</script>
