<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$stmt = $pdo->prepare(
    'SELECT c.id, c.title, c.category, c.difficulty
     FROM saved_careers s JOIN careers c ON c.id = s.career_id
     WHERE s.user_id = ? ORDER BY s.saved_at DESC'
);
$stmt->execute([$user['id']]);
$saved = $stmt->fetchAll();

foreach ($saved as &$row) {
    $row['progress'] = progress_summary($pdo, $user['id'], (int)$row['id']);
}
unset($row);

$pageTitle = 'My dashboard';
require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <?= flash_render() ?>
    <h1>Hello, <?= e($user['name']) ?></h1>
    <p>Your saved careers and how far you have come on each roadmap.</p>

    <div class="career-grid">
        <?php foreach ($saved as $c): $p = $c['progress']; ?>
            <article class="career-card">
                <p class="category"><?= e($c['category']) ?></p>
                <h2><?= e($c['title']) ?></h2>
                <progress max="100" value="<?= (int)$p['percent'] ?>" aria-label="Progress"></progress>
                <p class="note">
                    <?= (int)$p['percent'] ?>% complete ·
                    <?= (int)$p['steps_done'] ?>/<?= (int)$p['steps_total'] ?> steps ·
                    <?= (int)$p['skills_done'] ?>/<?= (int)$p['skills_total'] ?> skills
                </p>
                <a href="career-details.php?id=<?= (int)$c['id'] ?>">Continue →</a>
                <form method="post" action="save-career.php" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="career_id" value="<?= (int)$c['id'] ?>">
                    <input type="hidden" name="back" value="dashboard">
                    <button class="link-btn" type="submit">Remove</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$saved): ?>
        <p>You have not saved any careers yet. <a href="career-finder.php">Find a career</a> or <a href="careers.php">browse all careers</a>.</p>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
