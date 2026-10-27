<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    unset($_SESSION['user_id'], $_SESSION['user_name']);
    session_regenerate_id(true);
    flash('You have been logged out.');
}
header('Location: login.php');
exit;
