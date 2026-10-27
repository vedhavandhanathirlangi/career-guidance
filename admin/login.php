<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
send_security_headers();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (too_many_attempts($pdo, 'admin')) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            clear_attempts($pdo, 'admin');
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            header('Location: dashboard.php');
            exit;
        }
        record_failed_attempt($pdo, 'admin');
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login | CareerPath India</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-base="../">
<main class="section">
    <div class="auth-box">
        <h1>Admin login</h1>
        <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <label for="username">Username</label>
            <input id="username" name="username" required autocomplete="username">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="button" type="submit">Log in</button>
        </form>
        <p><a href="../index.php">← Back to website</a></p>
    </div>
</main>
</body>
</html>
