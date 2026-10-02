<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipeId = (int)$_POST['recipe_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];

    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        header('Location: kitchen.php?error=invalid_csrf');
        exit;
    }

    $photoPath = null;
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (isset($_FILES['proof_photo']) && $_FILES['proof_photo']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['proof_photo']['tmp_name'];
        $ext = pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION);
        $photoPath = $uploadDir . 'proof_' . time() . '.' . $ext;
        move_uploaded_file($tmpName, $photoPath);
    }

    if ($quantity > 0) {
        $stmt = $pdo->prepare(
            "SELECT ri.ingredient_id, ri.quantity_required, i.stock_quantity " .
            "FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id " .
            "WHERE ri.recipe_id = ?"
        );
        $stmt->execute([$recipeId]);
        $ingredients = $stmt->fetchAll();

        foreach ($ingredients as $ing) {
            $needed = $ing['quantity_required'] * $quantity; // total needed for all dishes
            if ($ing['stock_quantity'] < $needed) {
                header('Location: kitchen.php?error=insufficient_stock&ing=' . urlencode($ing['ingredient_id']));
                exit;
            }
        }

        $deductStmt = $pdo->prepare(
            "UPDATE ingredients SET stock_quantity = stock_quantity - ? WHERE id = ?"
        );
        foreach ($ingredients as $ing) {
            $needed = $ing['quantity_required'] * $quantity;
            $deductStmt->execute([$needed, $ing['ingredient_id']]);
            log_audit($pdo, 'cook.deduct', json_encode(['recipe_id'=>$recipeId,'ingredient_id'=>$ing['ingredient_id'],'qty'=>$needed,'quantity'=>$quantity]), $userId);
        }

        $insert = $pdo->prepare(
            "INSERT INTO cook_logs (staff_id, recipe_id, quantity, photo_proof_path) VALUES (?, ?, ?, ?)"
        );
        $insert->execute([$userId, $recipeId, $quantity, $photoPath]);
    }

    header('Location: kitchen.php?msg=CookLogSubmitted');
    exit;
}
?>
