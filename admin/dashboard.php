<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
send_security_headers();

// Session check on every admin page
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$difficulties = ['Beginner', 'Intermediate', 'Advanced'];
$text = fn(string $k): string => trim((string)($_POST[$k] ?? ''));
$int  = fn(string $k): ?int => filter_input(INPUT_POST, $k, FILTER_VALIDATE_INT) ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'save_career':
                $id = $int('id');
                $difficulty = in_array($text('difficulty'), $difficulties, true) ? $text('difficulty') : 'Beginner';
                if ($text('title') === '' || $text('category') === '' || $text('description') === '' || $text('skills') === '') {
                    throw new RuntimeException('Title, category, description and skills are required.');
                }
                $vals = [$text('title'), $text('category'), $text('description'), $text('education'), $text('skills'),
                         strtolower(str_replace(' ', '', $text('interest_tags'))), strtolower(str_replace(' ', '', $text('work_style'))), $difficulty];
                if ($id) {
                    $pdo->prepare('UPDATE careers SET title=?, category=?, description=?, education=?, skills=?, interest_tags=?, work_style=?, difficulty=? WHERE id=?')
                        ->execute([...$vals, $id]);
                    flash('Career updated.');
                } else {
                    $pdo->prepare('INSERT INTO careers (title, category, description, education, skills, interest_tags, work_style, difficulty) VALUES (?,?,?,?,?,?,?,?)')
                        ->execute($vals);
                    flash('Career added.');
                }
                break;

            case 'delete_career':
                if ($id = $int('id')) $pdo->prepare('DELETE FROM careers WHERE id = ?')->execute([$id]);
                flash('Career deleted.');
                break;

            case 'save_step':
                $id = $int('id'); $cid = $int('career_id'); $num = $int('step_number');
                if (!$cid || !$num || $text('title') === '' || $text('description') === '') {
                    throw new RuntimeException('Career, step number, title and description are required.');
                }
                if ($id) {
                    $pdo->prepare('UPDATE roadmap_steps SET career_id=?, step_number=?, title=?, description=?, duration=? WHERE id=?')
                        ->execute([$cid, $num, $text('title'), $text('description'), $text('duration'), $id]);
                    flash('Roadmap step updated.');
                } else {
                    $pdo->prepare('INSERT INTO roadmap_steps (career_id, step_number, title, description, duration) VALUES (?,?,?,?,?)')
                        ->execute([$cid, $num, $text('title'), $text('description'), $text('duration')]);
                    flash('Roadmap step added.');
                }
                break;

            case 'delete_step':
                if ($id = $int('id')) $pdo->prepare('DELETE FROM roadmap_steps WHERE id = ?')->execute([$id]);
                flash('Roadmap step deleted.');
                break;

            case 'save_resource':
                $id = $int('id'); $cid = $int('career_id');
                if ($text('title') === '' || !safe_url($text('resource_url'))) {
                    throw new RuntimeException('A title and a valid http(s) URL are required.');
                }
                $vals = [$cid, $text('title'), $text('provider'), $text('resource_url'), $text('resource_type'), isset($_POST['is_free']) ? 1 : 0];
                if ($id) {
                    $pdo->prepare('UPDATE learning_resources SET career_id=?, title=?, provider=?, resource_url=?, resource_type=?, is_free=? WHERE id=?')
                        ->execute([...$vals, $id]);
                    flash('Resource updated.');
                } else {
                    $pdo->prepare('INSERT INTO learning_resources (career_id, title, provider, resource_url, resource_type, is_free) VALUES (?,?,?,?,?,?)')
                        ->execute($vals);
                    flash('Resource added.');
                }
                break;

            case 'delete_resource':
                if ($id = $int('id')) $pdo->prepare('DELETE FROM learning_resources WHERE id = ?')->execute([$id]);
                flash('Resource deleted.');
                break;

            default:
                throw new RuntimeException('Unknown action.');
        }
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    } catch (PDOException $e) {
        error_log($e->getMessage());
        flash('Database error. The change was not saved.', 'error');
    }
    header('Location: dashboard.php');
    exit;
}

$stats = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM users) AS users, (SELECT COUNT(*) FROM careers) AS careers,
            (SELECT COUNT(*) FROM roadmap_steps) AS steps, (SELECT COUNT(*) FROM learning_resources) AS resources,
            (SELECT COUNT(*) FROM saved_careers) AS saves'
)->fetch();

$careers   = $pdo->query('SELECT * FROM careers ORDER BY title')->fetchAll();
$steps     = $pdo->query('SELECT s.*, c.title AS career_title FROM roadmap_steps s JOIN careers c ON c.id = s.career_id ORDER BY c.title, s.step_number')->fetchAll();
$resources = $pdo->query('SELECT r.*, c.title AS career_title FROM learning_resources r LEFT JOIN careers c ON c.id = r.career_id ORDER BY r.title')->fetchAll();

function find_by_id(array $rows, ?int $id): array
{
    foreach ($rows as $r) {
        if ((int)$r['id'] === $id) return $r;
    }
    return [];
}
$editCareer   = find_by_id($careers, filter_input(INPUT_GET, 'edit_career', FILTER_VALIDATE_INT) ?: null);
$editStep     = find_by_id($steps, filter_input(INPUT_GET, 'edit_step', FILTER_VALIDATE_INT) ?: null);
$editResource = find_by_id($resources, filter_input(INPUT_GET, 'edit_resource', FILTER_VALIDATE_INT) ?: null);

function career_options(array $careers, $selected, bool $allowNone): string
{
    $out = '<option value="">' . ($allowNone ? 'All careers (general)' : 'Choose a career') . '</option>';
    foreach ($careers as $c) {
        $sel = ((string)$selected === (string)$c['id']) ? ' selected' : '';
        $out .= '<option value="' . (int)$c['id'] . '"' . $sel . '>' . e($c['title']) . '</option>';
    }
    return $out;
}

function delete_form(string $action, int $id, string $confirm): string
{
    return '<form method="post" class="inline-form" data-confirm="' . e($confirm) . '">' . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="button button-small btn-danger" type="submit">Delete</button></form>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title>Admin dashboard | CareerPath India</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-base="../">
<header class="navbar">
    <a class="logo" href="dashboard.php">Admin dashboard</a>
    <div class="nav-actions">
        <a href="../index.php" target="_blank" rel="noopener">View website</a>
        <form method="post" action="logout.php" class="inline-form"><?= csrf_field() ?><button class="link-btn" type="submit">Log out</button></form>
    </div>
</header>

<main class="admin-wrap">
    <?= flash_render() ?>

    <ul class="stats stats-admin">
        <li><strong><?= (int)$stats['users'] ?></strong> students</li>
        <li><strong><?= (int)$stats['careers'] ?></strong> careers</li>
        <li><strong><?= (int)$stats['steps'] ?></strong> roadmap steps</li>
        <li><strong><?= (int)$stats['resources'] ?></strong> resources</li>
        <li><strong><?= (int)$stats['saves'] ?></strong> saved careers</li>
    </ul>

    <section class="admin-box">
        <h2><?= $editCareer ? 'Edit career' : 'Add a career' ?></h2>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_career">
            <?php if ($editCareer): ?><input type="hidden" name="id" value="<?= (int)$editCareer['id'] ?>"><?php endif; ?>
            <input name="title" placeholder="Title" required value="<?= e($editCareer['title'] ?? '') ?>">
            <input name="category" placeholder="Category" required value="<?= e($editCareer['category'] ?? '') ?>">
            <textarea name="description" placeholder="Description" rows="3" required><?= e($editCareer['description'] ?? '') ?></textarea>
            <input name="education" placeholder="Education" value="<?= e($editCareer['education'] ?? '') ?>">
            <input name="skills" placeholder="Skills (comma separated)" required value="<?= e($editCareer['skills'] ?? '') ?>">
            <input name="interest_tags" placeholder="Interest tags: coding, design, analysis, security, creativity, communication" value="<?= e($editCareer['interest_tags'] ?? '') ?>">
            <input name="work_style" placeholder="Work style tags: remote, team, independent, hands-on" value="<?= e($editCareer['work_style'] ?? '') ?>">
            <select name="difficulty" aria-label="Difficulty">
                <?php foreach ($difficulties as $d): ?>
                    <option value="<?= e($d) ?>" <?= ($editCareer['difficulty'] ?? 'Beginner') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
            <div><button class="button" type="submit"><?= $editCareer ? 'Save changes' : 'Add career' ?></button>
            <?php if ($editCareer): ?> <a href="dashboard.php">Cancel</a><?php endif; ?></div>
        </form>
    </section>

    <section class="admin-box">
        <h2>Careers (<?= count($careers) ?>)</h2>
        <table>
            <tr><th>Title</th><th>Category</th><th>Level</th><th>Actions</th></tr>
            <?php foreach ($careers as $c): ?>
                <tr>
                    <td><?= e($c['title']) ?></td><td><?= e($c['category']) ?></td><td><?= e($c['difficulty']) ?></td>
                    <td><a href="?edit_career=<?= (int)$c['id'] ?>">Edit</a>
                        <?= delete_form('delete_career', (int)$c['id'], 'Delete this career and its roadmap steps?') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>

    <section class="admin-box">
        <h2><?= $editStep ? 'Edit roadmap step' : 'Add a roadmap step' ?></h2>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_step">
            <?php if ($editStep): ?><input type="hidden" name="id" value="<?= (int)$editStep['id'] ?>"><?php endif; ?>
            <select name="career_id" required aria-label="Career"><?= career_options($careers, $editStep['career_id'] ?? '', false) ?></select>
            <input name="step_number" type="number" min="1" placeholder="Step number" required value="<?= e((string)($editStep['step_number'] ?? '')) ?>">
            <input name="title" placeholder="Step title" required value="<?= e($editStep['title'] ?? '') ?>">
            <textarea name="description" placeholder="Description" rows="2" required><?= e($editStep['description'] ?? '') ?></textarea>
            <input name="duration" placeholder="Duration (e.g. 2–4 weeks)" value="<?= e($editStep['duration'] ?? '') ?>">
            <div><button class="button" type="submit"><?= $editStep ? 'Save changes' : 'Add step' ?></button>
            <?php if ($editStep): ?> <a href="dashboard.php">Cancel</a><?php endif; ?></div>
        </form>
    </section>

    <section class="admin-box">
        <h2>Roadmap steps (<?= count($steps) ?>)</h2>
        <table>
            <tr><th>Career</th><th>#</th><th>Title</th><th>Actions</th></tr>
            <?php foreach ($steps as $s): ?>
                <tr>
                    <td><?= e($s['career_title']) ?></td><td><?= (int)$s['step_number'] ?></td><td><?= e($s['title']) ?></td>
                    <td><a href="?edit_step=<?= (int)$s['id'] ?>">Edit</a>
                        <?= delete_form('delete_step', (int)$s['id'], 'Delete this step?') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>

    <section class="admin-box">
        <h2><?= $editResource ? 'Edit learning resource' : 'Add a learning resource' ?></h2>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_resource">
            <?php if ($editResource): ?><input type="hidden" name="id" value="<?= (int)$editResource['id'] ?>"><?php endif; ?>
            <select name="career_id" aria-label="Career"><?= career_options($careers, $editResource['career_id'] ?? '', true) ?></select>
            <input name="title" placeholder="Title" required value="<?= e($editResource['title'] ?? '') ?>">
            <input name="provider" placeholder="Provider" value="<?= e($editResource['provider'] ?? '') ?>">
            <input name="resource_url" type="url" placeholder="https://..." required value="<?= e($editResource['resource_url'] ?? '') ?>">
            <input name="resource_type" placeholder="Type (Documentation, Courses, Tutorial...)" value="<?= e($editResource['resource_type'] ?? '') ?>">
            <label class="inline-check"><input type="checkbox" name="is_free" value="1" <?= (!$editResource || $editResource['is_free']) ? 'checked' : '' ?>> Free to use</label>
            <div><button class="button" type="submit"><?= $editResource ? 'Save changes' : 'Add resource' ?></button>
            <?php if ($editResource): ?> <a href="dashboard.php">Cancel</a><?php endif; ?></div>
        </form>
    </section>

    <section class="admin-box">
        <h2>Resources (<?= count($resources) ?>)</h2>
        <table>
            <tr><th>Title</th><th>Career</th><th>Actions</th></tr>
            <?php foreach ($resources as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td><td><?= e($r['career_title'] ?? 'General') ?></td>
                    <td><a href="?edit_resource=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete_resource', (int)$r['id'], 'Delete this resource?') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
</main>
<script src="../assets/js/app.js"></script>
</body>
</html>
