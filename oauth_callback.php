<?php
require_once 'config.php';

$error_msg = '';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $tokenUrl = "https://discord.com/api/oauth2/token";
    $data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => DISCORD_REDIRECT_URI
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($tokenUrl, false, $context);

    if ($response === false) {
        $error_msg = "Error exchanging code for token. Check credentials.";
    } else {
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'];

        $userUrl = "https://discord.com/api/users/@me";
        $userOptions = [
            'http' => [
                'header' => "Authorization: Bearer {$accessToken}\r\n",
                'method' => 'GET'
            ]
        ];
        $userContext = stream_context_create($userOptions);
        $userResponse = @file_get_contents($userUrl, false, $userContext);
        
        if ($userResponse === false) {
            $error_msg = "Error fetching user data from Discord.";
        } else {
            $userData = json_decode($userResponse, true);
            $discordId = $userData['id'];

            $stmt = $pdo->prepare("SELECT id FROM staff WHERE discord_id = ?");
            $stmt->execute([$discordId]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit;
            } else {
                $error_msg = "Unauthorized. You are not a registered staff member. Ask a Director to add your Discord ID: <strong>" . htmlspecialchars($discordId) . "</strong>";
            }
        }
    }
} else {
    header('Location: login.php');
    exit;
}

if (!empty($error_msg)):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication Error - Resto</title>
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
        .error-card {
            background-color: #161b22;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <h2 class="text-danger fw-bold mb-4">Authentication Error</h2>
        <div class="alert alert-danger bg-dark border-danger text-danger text-start">
            <?php echo $error_msg; ?>
        </div>
        <a href="login.php" class="btn btn-outline-light w-100 fw-bold py-2 mt-3">Back to Login</a>
    </div>
</body>
</html>
<?php endif; ?>
