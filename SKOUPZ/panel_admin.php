<?php
// logout.php
session_start();
session_destroy();

// Limpiar cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
?>