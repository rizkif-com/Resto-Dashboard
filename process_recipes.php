<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Permission check
$permStmt = $pdo->prepare("SELECT p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$permStmt->execute([$userId]);
$permLevel = (int)$permStmt->fetchColumn();
if ($permLevel < 4) {
    http_response_code(403);
    echo "<p style='color:red;'>Forbidden</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recipes.php');
    exit;
}

$action = $_POST['action'] ?? '';

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    header('Location: recipes.php?error=invalid_csrf');
    exit;
}

try {
    if ($action === 'add_recipe') {
        $name = trim($_POST['name'] ?? '');
        $bonus = (float)($_POST['cooking_bonus'] ?? 0.0);
        if ($name === '') throw new Exception('Name required');
        $stmt = $pdo->prepare("INSERT INTO recipes (name, cooking_bonus) VALUES (?, ?)");
        $stmt->execute([$name, $bonus]);
        $newId = $pdo->lastInsertId();
        log_audit($pdo, 'recipe.add', json_encode(['id'=>$newId,'name'=>$name,'bonus'=>$bonus]), $userId);
        header('Location: recipes.php?msg=added');
        exit;
    }

    if ($action === 'edit_recipe') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $bonus = (float)($_POST['cooking_bonus'] ?? 0.0);
        if (!$id || $name === '') throw new Exception('Invalid');
        $stmt = $pdo->prepare("UPDATE recipes SET name = ?, cooking_bonus = ? WHERE id = ?");
        $stmt->execute([$name, $bonus, $id]);
        log_audit($pdo, 'recipe.edit', json_encode(['id'=>$id,'name'=>$name,'bonus'=>$bonus]), $userId);
        header('Location: recipes.php?msg=edited');
        exit;
    }

    if ($action === 'delete_recipe') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Invalid id');
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM recipes WHERE id = ?")->execute([$id]);
        log_audit($pdo, 'recipe.delete', json_encode(['id'=>$id]), $userId);
        header('Location: recipes.php?msg=deleted');
        exit;
    }

    if ($action === 'add_ing') {
        $recipe_id = (int)($_POST['recipe_id'] ?? 0);
        $ing_id = (int)($_POST['ingredient_id'] ?? 0);
        $qty = (int)($_POST['quantity_required'] ?? 1);
        if (!$recipe_id || !$ing_id) throw new Exception('Invalid');
        $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity_required) VALUES (?, ?, ?)");
        $stmt->execute([$recipe_id, $ing_id, $qty]);
        log_audit($pdo, 'recipe.ing.add', json_encode(['recipe_id'=>$recipe_id,'ing_id'=>$ing_id,'qty'=>$qty]), $userId);
        header('Location: recipes.php?msg=ing_added');
        exit;
    }

    if ($action === 'remove_ing') {
        $recipe_id = (int)($_POST['recipe_id'] ?? 0);
        $ing_id = (int)($_POST['ingredient_id'] ?? 0);
        if (!$recipe_id || !$ing_id) throw new Exception('Invalid');
        $stmt = $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ? AND ingredient_id = ?");
        $stmt->execute([$recipe_id, $ing_id]);
        log_audit($pdo, 'recipe.ing.remove', json_encode(['recipe_id'=>$recipe_id,'ing_id'=>$ing_id]), $userId);
        header('Location: recipes.php?msg=ing_removed');
        exit;
    }

    if ($action === 'update_ing_qty') {
        $recipe_id = (int)($_POST['recipe_id'] ?? 0);
        $ing_id = (int)($_POST['ingredient_id'] ?? 0);
        $qty = (int)($_POST['quantity_required'] ?? 1);
        if (!$recipe_id || !$ing_id) throw new Exception('Invalid');
        $stmt = $pdo->prepare("UPDATE recipe_ingredients SET quantity_required = ? WHERE recipe_id = ? AND ingredient_id = ?");
        $stmt->execute([$qty, $recipe_id, $ing_id]);
        log_audit($pdo, 'recipe.ing.update', json_encode(['recipe_id'=>$recipe_id,'ing_id'=>$ing_id,'qty'=>$qty]), $userId);
        header('Location: recipes.php?msg=ing_updated');
        exit;
    }

    throw new Exception('Unknown action');

} catch (Exception $e) {
    header('Location: recipes.php?error=' . urlencode($e->getMessage()));
    exit;
}

?>
