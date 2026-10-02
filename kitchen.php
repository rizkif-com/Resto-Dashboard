<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT s.username, p.name as pos_name, p.can_cook, p.permission_level FROM staff s JOIN positions p ON s.position_id = p.id WHERE s.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$isDirector = $user['permission_level'] >= 4;
$canCook = $user['can_cook'];

$recipesStmt = $pdo->query("
    SELECT r.id, r.name, r.cooking_bonus, i.id as ing_id, i.name as ing_name, i.cost, ri.quantity_required 
    FROM recipes r 
    JOIN recipe_ingredients ri ON r.id = ri.recipe_id
    JOIN ingredients i ON ri.ingredient_id = i.id
");
$recipesData = [];
while ($row = $recipesStmt->fetch()) {
    $rId = $row['id'];
    if (!isset($recipesData[$rId])) {
        $recipesData[$rId] = [
            'name' => $row['name'],
            'bonus' => $row['cooking_bonus'],
            'ingredients' => [],
            'cost' => 0
        ];
    }
    $recipesData[$rId]['ingredients'][] = [
        'name' => $row['ing_name'],
        'qty' => $row['quantity_required']
    ];
    $recipesData[$rId]['cost'] += ($row['cost'] * $row['quantity_required']);
}

$pageTitle = 'Kitchen';
$pageSubtitle = 'Cook items and manage logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'header.php'; ?>

        <div class="p-4 fade-in">
            <?php if(!$canCook): ?>
                <div class="alert alert-danger bg-dark border-danger text-danger">
                    <h5 class="fw-bold mb-1">Access Restricted</h5>
                    You do not have the required position permissions to cook items.
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header border-bottom border-secondary bg-transparent py-3">
                                <h6 class="text-white m-0 fw-bold">🍳 Cooking Calculator</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="process_cook.php" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Select Recipe</label>
                                        <select class="form-select" name="recipe_id" id="recipeSelect">
                                            <option value="" disabled selected>Choose...</option>
                                            <?php foreach($recipesData as $id => $r): ?>
                                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($r['name']); ?> (+$<?php echo number_format($r['bonus'], 2); ?> Bonus)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Quantity (Number of Dishes/Packages)</label>
                                        <input type="number" class="form-control" name="quantity" id="quantityInput" value="1" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Upload Proof Photo (or drag & paste)</label>
                                        <div id="dropZone" tabindex="0" class="border border-secondary rounded p-3 text-center" style="background-color: var(--bg-card);">
                                            <p id="dropText" class="text-muted mb-1">Drag & drop image here or click to select</p>
                                            <input class="form-control" type="file" name="proof_photo" id="proofPhotoInput" accept="image/*" style="display:none;">
    <img id="previewImg" class="mt-2" style="max-width:100%; display:none;" alt="Preview" />
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">Submit Cook Log</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header border-bottom border-secondary bg-transparent py-3">
                                <h6 class="text-white m-0 fw-bold">Calculator Summary</h6>
                            </div>
                            <div class="card-body" id="calcSummary">
                                <p class="text-muted">Select a recipe to see required ingredients.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
const recipes = <?php echo json_encode($recipesData); ?>;
const isDirector = <?php echo $isDirector ? 'true' : 'false'; ?>;

const recipeSelect = document.getElementById('recipeSelect');
const quantityInput = document.getElementById('quantityInput');
const calcSummary = document.getElementById('calcSummary');
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('proofPhotoInput');
const dropText = document.getElementById('dropText');

function updateSummary() {
    const rId = recipeSelect.value;
    const quantity = parseInt(quantityInput.value) || 1;
    
    if(!rId) return;
    
    const r = recipes[rId];
    const totalYield = quantity;
    
    let html = `<h5 class="text-white mb-3 fw-bold">Yields: <span class="text-success">${totalYield} Dishes/Packages</span></h5>`;
    html += `<h6 class="text-muted mb-2 text-uppercase" style="font-size: 0.75rem;">Ingredients Required:</h6><ul class="list-group list-group-flush mb-4">`;
    
    r.ingredients.forEach(i => {
        html += `<li class="list-group-item bg-transparent text-white border-secondary px-0 d-flex justify-content-between">
            <span>${i.name}</span>
            <span class="fw-bold">${i.qty * quantity}</span>
        </li>`;
    });
    html += `</ul>`;
    
    if(isDirector) {
        const totalCost = (r.cost * quantity).toFixed(2);
        html += `<div class="p-3 border border-warning rounded bg-warning bg-opacity-10 mt-auto">
            <h6 class="text-warning text-uppercase mb-1" style="font-size: 0.75rem;">Total Preparation Cost (Director)</h6>
            <h3 class="text-warning m-0 fw-bold">$${totalCost}</h3>
        </div>`;
    }
    
    calcSummary.innerHTML = html;
}

if(recipeSelect) {
    recipeSelect.addEventListener('change', updateSummary);
    quantityInput.addEventListener('input', updateSummary);
}

function setPreview(file) {
    const img = document.getElementById('previewImg');
    if (file) {
        img.src = URL.createObjectURL(file);
        img.style.display = 'block';
    } else {
        img.src = '';
        img.style.display = 'none';
    }
}


dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-primary');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-primary');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-primary');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        dropText.textContent = e.dataTransfer.files[0].name;
        setPreview(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length) {
        dropText.textContent = fileInput.files[0].name;
        setPreview(fileInput.files[0]);
    }
});

document.addEventListener('paste', (e) => {
    const items = e.clipboardData.items;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                const container = new DataTransfer();
                container.items.add(blob);
                fileInput.files = container.files;
                dropText.textContent = 'Pasted image: ' + blob.name;
                setPreview(blob);
            }
        }
});
</script>
</body>
</html>
