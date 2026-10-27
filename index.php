<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';

$counts = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM careers) AS careers,
            (SELECT COUNT(*) FROM roadmap_steps) AS steps,
            (SELECT COUNT(*) FROM learning_resources) AS resources'
)->fetch();

$popular = $pdo->query(
    'SELECT c.id, c.title, c.category, c.description, c.difficulty, COUNT(s.career_id) AS saves
     FROM careers c
     LEFT JOIN saved_careers s ON s.career_id = c.id
     GROUP BY c.id, c.title, c.category, c.description, c.difficulty
     ORDER BY saves DESC, c.title
     LIMIT 6'
)->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM careers ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <h1>Discover your career.<br>Build your future.</h1>
        <p>Explore career options, learn essential skills, and follow a roadmap at your own pace.</p>

        <form class="hero-search" action="careers.php" method="get">
            <input type="search" name="q" list="career-suggestions" autocomplete="off"
                   placeholder="Search software, design, data..." aria-label="Search careers">
            <datalist id="career-suggestions"></datalist>
            <button class="button" type="submit">Search</button>
        </form>
        <a class="button button-outline" href="career-finder.php">Find My Career</a>

        <ul class="stats">
            <li><strong><?= (int)$counts['careers'] ?></strong> careers</li>
            <li><strong><?= (int)$counts['steps'] ?></strong> roadmap steps</li>
            <li><strong><?= (int)$counts['resources'] ?></strong> learning resources</li>
        </ul>
    </section>

    <?php if ($categories): ?>
    <section class="section">
        <h2>Browse by category</h2>
        <div class="chips">
            <?php foreach ($categories as $cat): ?>
                <a class="chip" href="careers.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="section">
        <h2>Popular career paths</h2>
        <p>Careers saved most often by students come first.</p>
        <div class="career-grid">
            <?php foreach ($popular as $c): ?>
                <article class="career-card">
                    <p class="category"><?= e($c['category']) ?></p>
                    <h3><?= e($c['title']) ?></h3>
                    <p><?= e($c['description']) ?></p>
                    <p class="badges"><span class="badge"><?= e($c['difficulty']) ?></span></p>
                    <a href="career-details.php?id=<?= (int)$c['id'] ?>">View career →</a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$popular): ?><p>No careers have been added yet.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
