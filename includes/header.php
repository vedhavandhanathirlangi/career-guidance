<?php
require_once __DIR__ . '/functions.php';
send_security_headers();
$pageTitle = $pageTitle ?? 'CareerPath India';
$metaDescription = $metaDescription ?? 'Explore careers, see the skills you need and follow a step-by-step learning roadmap.';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | CareerPath India</title>
    <script>
        try {
            var t = localStorage.getItem('theme');
            if (!t && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
            if (t) document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-base="">
<a class="skip-link" href="#main">Skip to content</a>
<header class="navbar">
    <a class="logo" href="index.php">CareerPath India</a>
    <nav aria-label="Main">
        <a href="index.php">Home</a>
        <a href="careers.php">Explore Careers</a>
        <a href="career-finder.php">Find My Career</a>
        <a href="roadmap.php">Roadmaps</a>
        <a href="resources.php">Resources</a>
    </nav>
    <div class="nav-actions">
        <button type="button" class="icon-btn" id="theme-toggle" aria-label="Switch between light and dark theme">Theme</button>
        <?php if ($user): ?>
            <a href="dashboard.php">My dashboard</a>
            <form method="post" action="logout.php" class="inline-form">
                <?= csrf_field() ?>
                <button class="link-btn" type="submit">Log out</button>
            </form>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a class="button button-small" href="register.php">Sign up</a>
        <?php endif; ?>
    </div>
</header>
<div id="main">
