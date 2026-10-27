<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    error_page(400, 'That career link is not valid.');
}

$stmt = $pdo->prepare('SELECT * FROM careers WHERE id = ?');
$stmt->execute([$id]);
$career = $stmt->fetch();
if (!$career) {
    error_page(404, 'That career was not found. It may have been removed.');
}

$stmt = $pdo->prepare('SELECT * FROM roadmap_steps WHERE career_id = ? ORDER BY step_number, id');
$stmt->execute([$id]);
$steps = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM learning_resources WHERE career_id = ? OR career_id IS NULL ORDER BY career_id IS NULL, title');
$stmt->execute([$id]);
$resources = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, title FROM careers WHERE category = ? AND id <> ? ORDER BY title LIMIT 3');
$stmt->execute([$career['category'], $id]);
$related = $stmt->fetchAll();

$skills = split_skills($career['skills']);
$user = current_user();
$saved = false;
$progress = null;

if ($user) {
    $stmt = $pdo->prepare('SELECT 1 FROM saved_careers WHERE user_id = ? AND career_id = ?');
    $stmt->execute([$user['id'], $id]);
    $saved = (bool)$stmt->fetchColumn();
    $progress = progress_summary($pdo, $user['id'], $id);
}

$pageTitle = $career['title'];
$metaDescription = $career['description'];
require __DIR__ . '/includes/header.php';
?>
<main class="section detail" data-career-id="<?= (int)$id ?>">
    <?= flash_render() ?>
    <p class="category"><?= e($career['category']) ?></p>
    <h1><?= e($career['title']) ?></h1>
    <p><?= e($career['description']) ?></p>
    <p class="badges">
        <span class="badge"><?= e($career['difficulty']) ?></span>
        <?php foreach (tag_list($career['work_style']) as $w): ?><span class="badge badge-soft"><?= e($w) ?></span><?php endforeach; ?>
    </p>

    <div class="actions">
        <?php if ($user): ?>
            <form method="post" action="save-career.php" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="career_id" value="<?= (int)$id ?>">
                <button class="button <?= $saved ? 'button-outline' : '' ?>" type="submit">
                    <?= $saved ? 'Remove from my list' : 'Save this career' ?>
                </button>
            </form>
        <?php else: ?>
            <a class="button" href="login.php?next=<?= urlencode('career-details.php?id=' . $id) ?>">Log in to save and track progress</a>
        <?php endif; ?>
        <button type="button" class="button button-outline" data-print>Print roadmap</button>
    </div>

    <h2>Education</h2>
    <p><?= e($career['education'] ?? 'Not specified') ?></p>

    <?php if ($progress): ?>
        <div class="progress-block" aria-live="polite">
            <label for="progress-bar">Your progress: <strong id="progress-text"><?= (int)$progress['percent'] ?>%</strong></label>
            <progress id="progress-bar" max="100" value="<?= (int)$progress['percent'] ?>"></progress>
        </div>
    <?php endif; ?>

    <h2>Skills to develop</h2>
    <?php if ($user): ?><p class="note">Tick the skills you already have.</p><?php endif; ?>
    <div class="chips">
        <?php foreach ($skills as $key => $label): ?>
            <?php if ($user): ?>
                <label class="chip chip-check">
                    <input type="checkbox" data-progress="skill" data-key="<?= e($key) ?>"
                        <?= in_array($key, $progress['done_skills'], true) ? 'checked' : '' ?>>
                    <?= e($label) ?>
                </label>
            <?php else: ?>
                <span class="chip"><?= e($label) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h2>Your learning roadmap</h2>
    <?php if ($steps): ?>
        <ol class="steps">
            <?php foreach ($steps as $s): ?>
                <li>
                    <h3>
                        <?php if ($user): ?>
                            <label class="step-check">
                                <input type="checkbox" data-progress="step" data-key="<?= (int)$s['id'] ?>"
                                    <?= in_array((int)$s['id'], $progress['done_steps'], true) ? 'checked' : '' ?>>
                                <?= e($s['title']) ?>
                            </label>
                        <?php else: ?>
                            <?= e($s['title']) ?>
                        <?php endif; ?>
                    </h3>
                    <p><?= e($s['description']) ?></p>
                    <small><?= e($s['duration'] ?? '') ?></small>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="note">Durations are illustrative estimates, not guaranteed completion times.</p>
    <?php else: ?>
        <p>A detailed roadmap has not been added yet.</p>
    <?php endif; ?>

    <h2>Learning resources</h2>
    <?php foreach ($resources as $r): ?>
        <?php if (!safe_url($r['resource_url'])) continue; ?>
        <p>
            <a href="<?= e($r['resource_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($r['title']) ?></a>
            — <?= e($r['provider'] ?? '') ?> (<?= $r['is_free'] ? 'free' : 'may require payment' ?>)
        </p>
    <?php endforeach; ?>
    <?php if (!$resources): ?><p>No resources added yet.</p><?php endif; ?>

    <?php if ($related): ?>
        <h2>Related careers</h2>
        <div class="chips">
            <?php foreach ($related as $r): ?>
                <a class="chip" href="career-details.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p><a href="careers.php">← Back to all careers</a></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
