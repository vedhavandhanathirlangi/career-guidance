<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Find My Career';

$interestOptions = [
    'coding' => 'Coding and building apps', 'design' => 'Design', 'analysis' => 'Data and numbers',
    'security' => 'Security', 'creativity' => 'Creative work', 'communication' => 'Communication and people',
];
$styleOptions = [
    'remote' => 'Remote-friendly work', 'team' => 'Working in a team',
    'independent' => 'Working independently', 'hands-on' => 'Hands-on technical work',
];
$levelOptions = ['beginner' => 'Beginner (starting from scratch)', 'some' => 'Some experience', 'experienced' => 'Experienced'];
$stageOptions = ['school' => 'School', 'diploma' => 'Diploma / ITI', 'undergrad' => 'Undergraduate', 'graduate' => 'Graduate or working'];
$stageTips = [
    'school'   => 'While you are in school, start with free beginner resources and small projects. You do not need to choose a career yet.',
    'diploma'  => 'A diploma gives you a practical base. Build one small project and add it to a portfolio.',
    'undergrad'=> 'Use your college years for projects and internships. Pick one roadmap and follow it step by step.',
    'graduate' => 'Focus on the skills employers ask for in job listings. Complete a roadmap and show your work in a portfolio.',
];

$submitted = isset($_GET['go']);
$selInterests = array_values(array_intersect((array)($_GET['interests'] ?? []), array_keys($interestOptions)));
$selStyles    = array_values(array_intersect((array)($_GET['styles'] ?? []), array_keys($styleOptions)));
$level = array_key_exists((string)($_GET['level'] ?? ''), $levelOptions) ? $_GET['level'] : 'beginner';
$stage = array_key_exists((string)($_GET['stage'] ?? ''), $stageOptions) ? $_GET['stage'] : '';

$results = [];
$formError = '';

if ($submitted) {
    if (!$selInterests) {
        $formError = 'Choose at least one interest.';
    } else {
        $rank = ['Beginner' => 0, 'Intermediate' => 1, 'Advanced' => 2];
        $levelRank = ['beginner' => 0, 'some' => 1, 'experienced' => 2][$level];
        $max = count($selInterests) * 10 + count($selStyles) * 5 + 3;

        $careers = $pdo->query('SELECT id, title, category, description, interest_tags, work_style, difficulty FROM careers')->fetchAll();
        foreach ($careers as $c) {
            $matchI = array_values(array_intersect($selInterests, tag_list($c['interest_tags'])));
            $matchS = array_values(array_intersect($selStyles, tag_list($c['work_style'])));
            if (!$matchI && !$matchS) {
                continue;
            }
            $score = count($matchI) * 10 + count($matchS) * 5;
            if (($rank[$c['difficulty']] ?? 0) <= $levelRank) {
                $score += 3;
            }
            $c['percent'] = min(100, (int)round($score / $max * 100));
            $c['score'] = $score;
            $c['why'] = array_merge($matchI, $matchS);
            $results[] = $c;
        }
        usort($results, fn($a, $b) => [$b['score'], $a['title']] <=> [$a['score'], $b['title']]);
        $results = array_slice($results, 0, 6);
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <h1>Find a career that matches you</h1>
    <p>Answer a few questions. Careers are ranked by how well they match your choices.</p>

    <form method="get" class="finder-form">
        <input type="hidden" name="go" value="1">

        <fieldset>
            <legend>What do you enjoy? (choose one or more)</legend>
            <?php foreach ($interestOptions as $v => $label): ?>
                <label class="inline-check"><input type="checkbox" name="interests[]" value="<?= e($v) ?>" <?= in_array($v, $selInterests, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>How do you like to work? (optional)</legend>
            <?php foreach ($styleOptions as $v => $label): ?>
                <label class="inline-check"><input type="checkbox" name="styles[]" value="<?= e($v) ?>" <?= in_array($v, $selStyles, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <div class="field-row">
            <label>Your current stage
                <select name="stage">
                    <option value="">Prefer not to say</option>
                    <?php foreach ($stageOptions as $v => $label): ?>
                        <option value="<?= e($v) ?>" <?= $stage === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Your experience in this field
                <select name="level">
                    <?php foreach ($levelOptions as $v => $label): ?>
                        <option value="<?= e($v) ?>" <?= $level === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <button class="button" type="submit">Show my matches</button>
    </form>

    <?php if ($formError): ?><p class="flash error"><?= e($formError) ?></p><?php endif; ?>

    <?php if ($submitted && !$formError): ?>
        <h2>Your best matches</h2>
        <?php if ($stage !== ''): ?><p class="note"><?= e($stageTips[$stage]) ?></p><?php endif; ?>
        <div class="career-grid">
            <?php foreach ($results as $c): ?>
                <article class="career-card">
                    <p class="category"><?= e($c['category']) ?></p>
                    <h3><?= e($c['title']) ?></h3>
                    <p class="match"><strong><?= (int)$c['percent'] ?>% match</strong></p>
                    <progress max="100" value="<?= (int)$c['percent'] ?>" aria-label="Match score"></progress>
                    <p class="note">Matched on: <?= e(implode(', ', $c['why'])) ?></p>
                    <a href="career-details.php?id=<?= (int)$c['id'] ?>">Explore this career →</a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$results): ?><p>No matching careers are available yet. Try choosing different interests.</p><?php endif; ?>
        <p class="note">The match score only compares your choices with career tags. It is a starting point, not a test of what you can do.</p>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
