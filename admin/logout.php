<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
header('Location: login.php');
exit;
