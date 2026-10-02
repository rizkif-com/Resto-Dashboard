<?php
require_once 'config.php';

try {
    $pdo->exec("ALTER TABLE positions ADD COLUMN IF NOT EXISTS can_cook BOOLEAN NOT NULL DEFAULT FALSE");
    
    $pdo->exec("ALTER TABLE ingredients ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT 'assets/img/default.png'");
    $pdo->exec("ALTER TABLE ingredients ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) NOT NULL DEFAULT 0.00");

    $pdo->exec("UPDATE positions SET can_cook = 1 WHERE name = 'Cook'");
    $pdo->exec("UPDATE positions SET can_cook = 1 WHERE name = 'Director'");

    $pdo->exec("UPDATE ingredients SET cost = 1.50, image_url = 'https://cdn-icons-png.flaticon.com/512/3014/3014519.png' WHERE name = 'Patty'");
    $pdo->exec("UPDATE ingredients SET cost = 0.50, image_url = 'https://cdn-icons-png.flaticon.com/512/3014/3014526.png' WHERE name = 'Buns'");
    $pdo->exec("UPDATE ingredients SET cost = 0.20, image_url = 'https://cdn-icons-png.flaticon.com/512/6122/6122971.png' WHERE name = 'Potato'");
    $pdo->exec("UPDATE ingredients SET cost = 0.80, image_url = 'https://cdn-icons-png.flaticon.com/512/3014/3014502.png' WHERE name = 'Tea Leaves'");

    $stmt = $pdo->query("SELECT COUNT(*) FROM recipe_ingredients");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity_required) VALUES 
            (1, 1, 1), -- Burger needs 1 Patty
            (1, 2, 2), -- Burger needs 2 Buns
            (2, 3, 3), -- Fries needs 3 Potatoes
            (3, 4, 1)  -- Special Tea needs 1 Tea Leaves
        ");
    }

    echo "Schema updated successfully!";
} catch (PDOException $e) {
    die("Error updating schema: " . $e->getMessage());
}
?>
