<?php
// check_session.php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'user_name' => $_SESSION['user_name'] ?? '',
    'user_email' => $_SESSION['user_email'] ?? '',
    'user_type' => $_SESSION['user_type'] ?? ''
]);
?>