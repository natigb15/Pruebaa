<?php
// protect_admin.php - Proteger el panel de admin
session_start();

// Verificar que el usuario esté logueado y sea admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    echo "Acceso denegado. Solo administradores pueden acceder a esta página.";
    exit;
}

// Obtener datos del admin desde sesión
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$admin_email = $_SESSION['user_email'];
?>