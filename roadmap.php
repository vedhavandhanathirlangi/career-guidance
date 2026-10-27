<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Learning Roadmaps';

$all = $pdo->query('SELECT id, title FROM careers ORDER BY title')->fetchAll();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$career = null;
$steps = [];

if ($id && $id > 0) {
    $stmt = $pdo->prepare('SELECT id, title FROM careers WHERE id = ?');
    $stmt->execute([$id]);
    $career = $stmt->fetch();
    if ($career) {
        $stmt = $pdo->prepare('SELECT * FROM roadmap_steps WHERE career_id = ? ORDER BY step_number, id');
        $stmt->execute([$id]);
        $steps = $stmt->fetchAll();
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="section detail">
    <h1>Learning Roadmaps</h1>
    <p>Pick a career to see what to learn first, second, and next.</p>

    <form method="get" class="filters">
        <select id="id" name="id" aria-label="Career" required>
            <option value="">Choose a career</option>
            <?php foreach ($all as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($career && (int)$career['id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">Show roadmap</button>
    </form>

    <?php if ($id && !$career): ?>
        <p>That career was not found.</p>
    <?php elseif ($career): ?>
        <h2><?= e($career['title']) ?></h2>
        <?php if ($steps): ?>
            <ol class="steps">
                <?php foreach ($steps as $s): ?>
                    <li>
                        <h3><?= e($s['title']) ?></h3>
                        <p><?= e($s['description']) ?></p>
                        <small><?= e($s['duration'] ?? '') ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p>A roadmap for this career has not been added yet.</p>
        <?php endif; ?>
        <p><a href="career-details.php?id=<?= (int)$career['id'] ?>">Open full details to track your progress →</a></p>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
