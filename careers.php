<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Explore Careers';

$perPage = 9;
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$difficulty = $_GET['difficulty'] ?? '';
$sort = $_GET['sort'] ?? 'title';
$page = max(1, (int)($_GET['page'] ?? 1));

$difficulties = ['Beginner', 'Intermediate', 'Advanced'];
if (!in_array($difficulty, $difficulties, true)) {
    $difficulty = '';
}
$sorts = ['title' => 'title ASC', 'newest' => 'created_at DESC, id DESC'];
if (!isset($sorts[$sort])) {
    $sort = 'title';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE :q1 OR category LIKE :q2 OR skills LIKE :q3)';
    $like = '%' . like_escape($q) . '%';
    $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
}
if ($category !== '') {
    $where[] = 'category = :category';
    $params['category'] = $category;
}
if ($difficulty !== '') {
    $where[] = 'difficulty = :difficulty';
    $params['difficulty'] = $difficulty;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM careers $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    "SELECT id, title, category, description, difficulty FROM careers $whereSql
     ORDER BY {$sorts[$sort]} LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$careers = $stmt->fetchAll();

$allCategories = $pdo->query('SELECT DISTINCT category FROM careers ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <h1>Explore Careers</h1>

    <form method="get" class="filters" id="filters">
        <input type="search" name="q" list="career-suggestions" autocomplete="off" value="<?= e($q) ?>"
               placeholder="Search by career, category or skill" aria-label="Search careers">
        <datalist id="career-suggestions"></datalist>

        <select name="category" aria-label="Category" data-autosubmit>
            <option value="">All categories</option>
            <?php foreach ($allCategories as $c): ?>
                <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="difficulty" aria-label="Difficulty" data-autosubmit>
            <option value="">Any difficulty</option>
            <?php foreach ($difficulties as $d): ?>
                <option value="<?= e($d) ?>" <?= $difficulty === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort" aria-label="Sort by" data-autosubmit>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>A to Z</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
        </select>

        <button class="button" type="submit">Search</button>
        <?php if ($q !== '' || $category !== '' || $difficulty !== ''): ?>
            <a href="careers.php">Clear filters</a>
        <?php endif; ?>
    </form>

    <p class="note"><?= $total ?> career<?= $total === 1 ? '' : 's' ?> found</p>

    <div class="career-grid">
        <?php foreach ($careers as $c): ?>
            <article class="career-card">
                <p class="category"><?= e($c['category']) ?></p>
                <h2><?= e($c['title']) ?></h2>
                <p><?= e($c['description']) ?></p>
                <p class="badges"><span class="badge"><?= e($c['difficulty']) ?></span></p>
                <a href="career-details.php?id=<?= (int)$c['id'] ?>">View details →</a>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$careers): ?>
        <p>No careers match these filters. Try a different search or <a href="careers.php">clear the filters</a>.</p>
    <?php endif; ?>

    <?= pagination_links($page, $pages, ['q' => $q, 'category' => $category, 'difficulty' => $difficulty, 'sort' => $sort]) ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
