<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function out(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['error' => 'Use POST.']);
}
$user = current_user();
if (!$user) {
    out(401, ['error' => 'Log in to track your progress.']);
}
$sent = $_POST['csrf'] ?? '';
if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
    out(403, ['error' => 'Your session expired. Refresh the page and try again.']);
}

$careerId = filter_input(INPUT_POST, 'career_id', FILTER_VALIDATE_INT);
$type = $_POST['type'] ?? '';
$key = trim((string)($_POST['key'] ?? ''));

if (!$careerId || !in_array($type, ['step', 'skill'], true) || $key === '') {
    out(400, ['error' => 'Invalid request.']);
}

try {
    if ($type === 'step') {
        $stmt = $pdo->prepare('SELECT 1 FROM roadmap_steps WHERE id = ? AND career_id = ?');
        $stmt->execute([(int)$key, $careerId]);
        if (!$stmt->fetchColumn()) {
            out(400, ['error' => 'That step does not belong to this career.']);
        }
        $key = (string)(int)$key;
    } else {
        $stmt = $pdo->prepare('SELECT skills FROM careers WHERE id = ?');
        $stmt->execute([$careerId]);
        $skills = split_skills((string)$stmt->fetchColumn());
        $key = mb_strtolower($key);
        if (!isset($skills[$key])) {
            out(400, ['error' => 'That skill does not belong to this career.']);
        }
    }

    $stmt = $pdo->prepare('SELECT id FROM user_progress WHERE user_id = ? AND career_id = ? AND item_type = ? AND item_key = ?');
    $stmt->execute([$user['id'], $careerId, $type, $key]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare('DELETE FROM user_progress WHERE id = ?')->execute([$existing]);
        $checked = false;
    } else {
        $pdo->prepare('INSERT INTO user_progress (user_id, career_id, item_type, item_key) VALUES (?, ?, ?, ?)')
            ->execute([$user['id'], $careerId, $type, $key]);
        $checked = true;
    }

    $s = progress_summary($pdo, $user['id'], $careerId);
    out(200, ['checked' => $checked, 'percent' => $s['percent']]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    out(500, ['error' => 'Could not save your progress. Please try again.']);
}
