<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) > 100) {
    $q = mb_substr($q, 0, 100);
}

$stmt = $pdo->prepare(
    'SELECT id, title, category FROM careers
     WHERE title LIKE :q1 OR category LIKE :q2 OR skills LIKE :q3
     ORDER BY title LIMIT 8'
);
$like = '%' . like_escape($q) . '%';
$stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
