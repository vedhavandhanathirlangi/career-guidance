<?php
// Run ONCE from the command line (not from the browser):
//   C:\xampp\php\php.exe admin\create-admin.php yourname YourStrongPassword
// Delete this file afterwards.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}
require_once __DIR__ . '/../config/database.php';

$user = $argv[1] ?? null;
$pass = $argv[2] ?? null;
if (!$user || !$pass || strlen($pass) < 10) {
    exit("Usage: php create-admin.php <username> <password (10+ characters)>\n");
}

$pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
    ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
echo "Admin '$user' created. Delete this file now.\n";
