<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ---------- Output and security helpers ---------- */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Invalid form token. Go back, refresh the page and try again.');
    }
}

function safe_url(string $url): bool
{
    return (bool) preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL);
}

function like_escape(string $s): string
{
    return addcslashes($s, '%_\\');
}

/* ---------- Flash messages and error pages ---------- */

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['m' => $message, 't' => $type];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<p class="flash ' . ($f['t'] === 'error' ? 'error' : '') . '" role="status">' . e($f['m']) . '</p>';
}

function error_page(int $code, string $message)
{
    http_response_code($code);
    $pageTitle = 'Something went wrong';
    require __DIR__ . '/header.php';
    echo '<main class="section"><h1>' . e($pageTitle) . '</h1><p>' . e($message) . '</p>'
       . '<p><a href="careers.php">Browse careers</a></p></main>';
    require __DIR__ . '/footer.php';
    exit;
}

/* ---------- Student accounts ---------- */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return ['id' => (int)$_SESSION['user_id'], 'name' => (string)($_SESSION['user_name'] ?? '')];
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $next = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        flash('Please log in to continue.', 'error');
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
    return $user;
}

function safe_next(string $next): string
{
    // Only allow simple relative page names such as career-details.php?id=3
    return preg_match('/^[a-z0-9\-]+\.php(\?[a-zA-Z0-9=&_\-]*)?$/', $next) ? $next : 'dashboard.php';
}

function start_user_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = (string)$user['name'];
}

/* ---------- Login throttling (database based) ---------- */

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function too_many_attempts(PDO $pdo, string $scope): bool
{
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)')->execute();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE scope = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$scope, client_ip()]);
    return (int)$stmt->fetchColumn() >= 5;
}

function record_failed_attempt(PDO $pdo, string $scope): void
{
    $pdo->prepare('INSERT INTO login_attempts (scope, ip) VALUES (?, ?)')->execute([$scope, client_ip()]);
}

function clear_attempts(PDO $pdo, string $scope): void
{
    $pdo->prepare('DELETE FROM login_attempts WHERE scope = ? AND ip = ?')->execute([$scope, client_ip()]);
}

/* ---------- Data helpers ---------- */

/** "A, B" -> ['a' => 'A', 'b' => 'B'] (lower-case key => display name) */
function split_skills(string $skills): array
{
    $out = [];
    foreach (explode(',', $skills) as $s) {
        $s = trim($s);
        if ($s !== '') {
            $out[mb_strtolower($s)] = $s;
        }
    }
    return $out;
}

/** "a,b ,c" -> ['a','b','c'] */
function tag_list(?string $tags): array
{
    return array_values(array_filter(array_map(
        fn($t) => mb_strtolower(trim($t)),
        explode(',', $tags ?? '')
    )));
}

function progress_summary(PDO $pdo, int $userId, int $careerId): array
{
    $stmt = $pdo->prepare('SELECT skills FROM careers WHERE id = ?');
    $stmt->execute([$careerId]);
    $skills = split_skills((string)$stmt->fetchColumn());

    $stmt = $pdo->prepare('SELECT id FROM roadmap_steps WHERE career_id = ?');
    $stmt->execute([$careerId]);
    $stepIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = $pdo->prepare('SELECT item_type, item_key FROM user_progress WHERE user_id = ? AND career_id = ?');
    $stmt->execute([$userId, $careerId]);

    $doneSteps = [];
    $doneSkills = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['item_type'] === 'step' && in_array((int)$row['item_key'], $stepIds, true)) {
            $doneSteps[] = (int)$row['item_key'];
        } elseif ($row['item_type'] === 'skill' && isset($skills[$row['item_key']])) {
            $doneSkills[] = $row['item_key'];
        }
    }

    $total = count($stepIds) + count($skills);
    $done = count($doneSteps) + count($doneSkills);

    return [
        'steps_done'   => count($doneSteps),
        'steps_total'  => count($stepIds),
        'skills_done'  => count($doneSkills),
        'skills_total' => count($skills),
        'percent'      => $total > 0 ? (int)round($done / $total * 100) : 0,
        'done_steps'   => $doneSteps,
        'done_skills'  => $doneSkills,
    ];
}

function pagination_links(int $page, int $pages, array $params): string
{
    if ($pages <= 1) {
        return '';
    }
    unset($params['page']);
    $html = '<nav class="pagination" aria-label="Pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $url = '?' . http_build_query($params + ['page' => $i]);
        $html .= $i === $page
            ? '<span class="page current" aria-current="page">' . $i . '</span>'
            : '<a class="page" href="' . e($url) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}
