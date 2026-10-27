<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Learning Resources';

$type = trim($_GET['type'] ?? '');
$careerId = filter_input(INPUT_GET, 'career', FILTER_VALIDATE_INT) ?: 0;
$freeOnly = isset($_GET['free']);

$where = [];
$params = [];
if ($type !== '') {
    $where[] = 'r.resource_type = :type';
    $params['type'] = $type;
}
if ($careerId > 0) {
    $where[] = '(r.career_id = :cid OR r.career_id IS NULL)';
    $params['cid'] = $careerId;
}
if ($freeOnly) {
    $where[] = 'r.is_free = 1';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT r.title, r.provider, r.resource_url, r.resource_type, r.is_free, c.title AS career_title
     FROM learning_resources r LEFT JOIN careers c ON c.id = r.career_id
     $whereSql ORDER BY r.title"
);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$types = $pdo->query('SELECT DISTINCT resource_type FROM learning_resources WHERE resource_type IS NOT NULL AND resource_type <> "" ORDER BY resource_type')->fetchAll(PDO::FETCH_COLUMN);
$careers = $pdo->query('SELECT id, title FROM careers ORDER BY title')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <h1>Learning Resources</h1>
    <p>Explore learning materials to develop your skills.</p>

    <form method="get" class="filters">
        <select name="career" aria-label="Career" data-autosubmit>
            <option value="">All careers</option>
            <?php foreach ($careers as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $careerId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" aria-label="Resource type" data-autosubmit>
            <option value="">Any type</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="inline-check"><input type="checkbox" name="free" value="1" <?= $freeOnly ? 'checked' : '' ?> data-autosubmit> Free only</label>
        <button class="button" type="submit">Apply</button>
    </form>

    <div class="career-grid">
        <?php foreach ($resources as $r): ?>
            <?php if (!safe_url($r['resource_url'])) continue; ?>
            <article class="career-card">
                <p class="category"><?= e($r['career_title'] ?? 'All careers') ?></p>
                <h2><?= e($r['title']) ?></h2>
                <p><?= e($r['provider'] ?? '') ?></p>
                <p class="badges">
                    <span class="badge badge-soft"><?= e($r['resource_type'] ?: 'Resource') ?></span>
                    <span class="badge"><?= $r['is_free'] ? 'Free' : 'May require payment' ?></span>
                </p>
                <a href="<?= e($r['resource_url']) ?>" target="_blank" rel="noopener noreferrer">Open resource →</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$resources): ?><p>No resources match these filters.</p><?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
