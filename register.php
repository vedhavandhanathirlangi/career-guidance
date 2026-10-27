<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name (up to 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Use a password with at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'The two passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'An account with this email already exists. Try logging in.';
        } else {
            $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            start_user_session(['id' => (int)$pdo->lastInsertId(), 'name' => $name]);
            flash('Welcome! Your account is ready. Save a career to get started.');
            header('Location: dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <div class="auth-box">
        <h1>Create your account</h1>
        <p>Save careers and track your progress through each roadmap.</p>
        <?php foreach ($errors as $err): ?><p class="flash error"><?= e($err) ?></p><?php endforeach; ?>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <label for="name">Name</label>
            <input id="name" name="name" required maxlength="100" autocomplete="name" value="<?= e($name) ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e($email) ?>">
            <label for="password">Password (8+ characters)</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            <label for="confirm">Confirm password</label>
            <input id="confirm" name="confirm" type="password" required minlength="8" autocomplete="new-password">
            <button class="button" type="submit">Create account</button>
        </form>
        <p>Already registered? <a href="login.php">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
