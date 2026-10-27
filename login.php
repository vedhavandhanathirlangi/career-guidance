<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$next = safe_next((string)($_POST['next'] ?? $_GET['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (too_many_attempts($pdo, 'user')) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            clear_attempts($pdo, 'user');
            start_user_session($row);
            header('Location: ' . $next);
            exit;
        }
        record_failed_attempt($pdo, 'user');
        $error = 'Incorrect email or password.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <div class="auth-box">
        <h1>Log in</h1>
        <?= flash_render() ?>
        <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required autocomplete="email">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="button" type="submit">Log in</button>
        </form>
        <p>New here? <a href="register.php">Create an account</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
