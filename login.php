<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $_SESSION['user_id'] = $_POST['staff_id'];
    header('Location: index.php');
    exit;
}

$discordUrl = '';
if (!empty(DISCORD_CLIENT_ID)) {
    $discordUrl = "https://discord.com/api/oauth2/authorize?client_id=" . DISCORD_CLIENT_ID . "&redirect_uri=" . urlencode(DISCORD_REDIRECT_URI) . "&response_type=code&scope=identify";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #0d1117;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background-color: #161b22;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .btn-discord {
            background-color: #5865F2;
            color: white;
            border: none;
        }
        .btn-discord:hover {
            background-color: #4752C4;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-white fw-bold mb-4">Resto</h2>
        
        <?php if (!empty($discordUrl)): ?>
            <p class="text-muted mb-4">Sign in using your Discord account.</p>
            <a href="<?php echo $discordUrl; ?>" class="btn btn-discord w-100 fw-bold py-2 mb-3">
                Login with Discord
            </a>
        <?php else: ?>
            <div class="alert alert-warning text-start" style="font-size: 0.9rem;">
                Discord OAuth is not configured. Use the local test login.
            </div>
        <?php endif; ?>

        <hr class="border-secondary my-4">
        
        <p class="text-muted small">Local Test Login (Bypass)</p>
        <form method="POST">
            <select name="staff_id" class="form-select bg-dark text-white border-secondary mb-3">
                <?php
                try {
                    $stmt = $pdo->query("SELECT s.id, s.username, p.name as pos_name FROM staff s JOIN positions p ON s.position_id = p.id");
                    while ($row = $stmt->fetch()) {
                        echo "<option value='{$row['id']}'>{$row['username']} ({$row['pos_name']})</option>";
                    }
                } catch (Exception $e) {
                    echo "<option disabled>Error loading staff.</option>";
                }
                ?>
            </select>
            <button type="submit" name="test_login" class="btn btn-outline-light w-100 fw-bold py-2">Test Login</button>
        </form>
    </div>
</body>
</html>
