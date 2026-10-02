<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Basic permission check: only positions with permission_level >=4 can manage inventory
$permStmt = $pdo->prepare("SELECT p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$permStmt->execute([$userId]);
$permLevel = (int)$permStmt->fetchColumn();
if ($permLevel < 4) {
    http_response_code(403);
    echo "<p style='color:red;'>Forbidden</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$action = $_POST['action'] ?? '';

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    header('Location: inventory.php?error=invalid_csrf');
    exit;
}

try {
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $qty = (int)($_POST['stock_quantity'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 0.0);
        $image = trim($_POST['image_url'] ?? null) ?: null;

        if ($name === '') throw new Exception('Name required');

        $stmt = $pdo->prepare("INSERT INTO ingredients (name, stock_quantity, cost, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $qty, $cost, $image]);
        $newId = $pdo->lastInsertId();
        log_audit($pdo, 'inventory.add', json_encode(['id' => $newId, 'name' => $name, 'qty' => $qty, 'cost' => $cost]), $userId);
        header('Location: inventory.php?msg=added');
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $qty = (int)($_POST['stock_quantity'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 0.0);
        $image = trim($_POST['image_url'] ?? null) ?: null;

        if (!$id || $name === '') throw new Exception('Invalid values');

        $stmt = $pdo->prepare("UPDATE ingredients SET name = ?, stock_quantity = ?, cost = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $qty, $cost, $image, $id]);
        log_audit($pdo, 'inventory.edit', json_encode(['id' => $id, 'name' => $name, 'qty' => $qty, 'cost' => $cost]), $userId);
        header('Location: inventory.php?msg=edited');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("DELETE FROM ingredients WHERE id = ?");
        $stmt->execute([$id]);
        log_audit($pdo, 'inventory.delete', json_encode(['id' => $id]), $userId);
        header('Location: inventory.php?msg=deleted');
        exit;
    }

    if ($action === 'adjust') {
        $id = (int)($_POST['id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        $set = isset($_POST['set_stock']) ? $_POST['set_stock'] : null;

        if (!$id) throw new Exception('Invalid id');

        if ($set !== null) {
            $new = (int)$set;
            $stmt = $pdo->prepare("UPDATE ingredients SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new, $id]);
            log_audit($pdo, 'inventory.set', json_encode(['id' => $id, 'new' => $new]), $userId);
        } else {
            $stmt = $pdo->prepare("UPDATE ingredients SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([$delta, $id]);
            log_audit($pdo, 'inventory.adjust', json_encode(['id' => $id, 'delta' => $delta]), $userId);
        }

        header('Location: inventory.php?msg=adjusted');
        exit;
    }

    throw new Exception('Unknown action');

} catch (Exception $e) {
    $err = urlencode($e->getMessage());
    header("Location: inventory.php?error={$err}");
    exit;
}

?>
