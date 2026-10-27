<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: careers.php');
    exit;
}
csrf_check();
$user = require_login();

$careerId = filter_input(INPUT_POST, 'career_id', FILTER_VALIDATE_INT);
$back = $_POST['back'] ?? '';
if (!$careerId) {
    error_page(400, 'That career link is not valid.');
}

$stmt = $pdo->prepare('SELECT 1 FROM careers WHERE id = ?');
$stmt->execute([$careerId]);
if (!$stmt->fetchColumn()) {
    error_page(404, 'That career was not found.');
}

$stmt = $pdo->prepare('SELECT 1 FROM saved_careers WHERE user_id = ? AND career_id = ?');
$stmt->execute([$user['id'], $careerId]);

if ($stmt->fetchColumn()) {
    $pdo->prepare('DELETE FROM saved_careers WHERE user_id = ? AND career_id = ?')->execute([$user['id'], $careerId]);
    flash('Removed from your list.');
} else {
    $pdo->prepare('INSERT INTO saved_careers (user_id, career_id) VALUES (?, ?)')->execute([$user['id'], $careerId]);
    flash('Saved to your list.');
}

header('Location: ' . ($back === 'dashboard' ? 'dashboard.php' : 'career-details.php?id=' . $careerId));
exit;
