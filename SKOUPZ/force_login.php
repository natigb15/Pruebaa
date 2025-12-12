<?php
// Script para forzar login de un usuario específico
session_start();

// Limpiar sesión anterior
session_unset();
session_destroy();
session_start();

// Datos del cliente que quieres probar (cambia estos valores)
$test_user = [
    'id' => 101,  // Cambia al ID correcto
    'name' => 'Ana García',
    'email' => 'anag@mail.com',
    'type' => 'cliente'
];

// Establecer nueva sesión
$_SESSION['user_id'] = $test_user['id'];
$_SESSION['user_name'] = $test_user['name'];
$_SESSION['user_email'] = $test_user['email'];
$_SESSION['user_type'] = $test_user['type'];
$_SESSION['logged_in'] = true;

echo "<h2>Sesión forzada para:</h2>";
echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
echo "<p><strong>Nombre:</strong> {$_SESSION['user_name']}</p>";
echo "<p><strong>Email:</strong> {$_SESSION['user_email']}</p>";
echo "<p><strong>Tipo:</strong> {$_SESSION['user_type']}</p>";

echo '<br><a href="index.html">Ir a Index</a> | ';
echo '<a href="debug_current_user.php">Ver sesión actual</a>';
?>