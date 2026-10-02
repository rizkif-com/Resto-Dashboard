<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.username, p.name as pos_name, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$perms = (int)$user['permission_level'];
if ($perms < 4) {
    header('Location: kitchen.php');
    exit;
}

$recipes = $pdo->query("SELECT * FROM recipes ORDER BY name ASC")->fetchAll();
$ingredients = $pdo->query("SELECT id, name, stock_quantity FROM ingredients ORDER BY name ASC")->fetchAll();

$riStmt = $pdo->query("SELECT ri.recipe_id, ri.ingredient_id, ri.quantity_required, i.name as ing_name FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id");
$recipeMap = [];
while ($r = $riStmt->fetch()) {
    $recipeMap[$r['recipe_id']][] = $r;
}

$pageTitle = 'Recipes';
$pageSubtitle = 'Manage recipes and their ingredients';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Recipes - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div id="main-content">
        <?php include 'header.php'; ?>
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white">Recipes</h4>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRecipeModal">+ Add Recipe</button>
            </div>

            <?php if(!empty($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if(!empty($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="row">
                <?php foreach($recipes as $r): ?>
                <div class="col-md-6 mb-3">
                    <div class="card bg-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="text-white"><?php echo htmlspecialchars($r['name']); ?></h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-light me-1" data-bs-toggle="modal" data-bs-target="#editRecipeModal" data-id="<?php echo $r['id']; ?>" data-name="<?php echo htmlspecialchars($r['name']); ?>" data-bonus="<?php echo $r['cooking_bonus']; ?>">Edit</button>
                                    <form method="POST" action="process_recipes.php" style="display:inline;" onsubmit="return confirm('Delete recipe?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete_recipe">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-muted small">Bonus: $<?php echo number_format($r['cooking_bonus'],2); ?></p>
                            <h6 class="text-warning">Ingredients:</h6>
                            <ul>
                                <?php if(!empty($recipeMap[$r['id']])): foreach($recipeMap[$r['id']] as $ri): ?>
                                    <li><?php echo htmlspecialchars($ri['ing_name']); ?> — <?php echo intval($ri['quantity_required']); ?> units
                                        <form method="POST" action="process_recipes.php" style="display:inline; margin-left:10px;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="remove_ing">
                                            <input type="hidden" name="recipe_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="ingredient_id" value="<?php echo $ri['ingredient_id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </li>
                                <?php endforeach; else: ?>
                                    <li class="text-muted">No ingredients assigned.</li>
                                <?php endif; ?>
                            </ul>

                            <form method="POST" action="process_recipes.php" class="row g-2 align-items-center">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="add_ing">
                                <input type="hidden" name="recipe_id" value="<?php echo $r['id']; ?>">
                                <div class="col-auto">
                                    <select name="ingredient_id" class="form-select form-select-sm">
                                        <?php foreach($ingredients as $ing): ?>
                                            <option value="<?php echo $ing['id']; ?>"><?php echo htmlspecialchars($ing['name']); ?> (<?php echo $ing['stock_quantity']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input name="quantity_required" type="number" min="1" value="1" class="form-control form-control-sm" />
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-success">Add Ingredient</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Recipe Modal -->
    <div class="modal fade" id="addRecipeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Add Recipe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="process_recipes.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                        <input type="hidden" name="action" value="add_recipe">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input class="form-control bg-dark text-white" name="name" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cooking Bonus</label>
                            <input class="form-control bg-dark text-white" name="cooking_bonus" type="number" step="0.01" value="0.00" />
                        </div>
                        <button class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Recipe Modal -->
    <div class="modal fade" id="editRecipeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Recipe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="process_recipes.php" id="editRecipeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                        <input type="hidden" name="action" value="edit_recipe">
                        <input type="hidden" name="id" id="edit_recipe_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input class="form-control bg-dark text-white" name="name" id="edit_recipe_name" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cooking Bonus</label>
                            <input class="form-control bg-dark text-white" name="cooking_bonus" id="edit_recipe_bonus" type="number" step="0.01" />
                        </div>
                        <button class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.querySelectorAll('[data-bs-target="#editRecipeModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_recipe_id').value = btn.dataset.id;
        document.getElementById('edit_recipe_name').value = btn.dataset.name;
        document.getElementById('edit_recipe_bonus').value = btn.dataset.bonus;
    });
});
</script>
</body>
</html>
