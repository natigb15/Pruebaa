<?php
// cart_process.php - Manejar carrito en sesión PHP
session_start();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // ... (tu código existente)
        break;
        
    case 'save':
        $cartData = $_POST['cart_data'] ?? '[]';
        $_SESSION['cart'] = json_decode($cartData, true);
        echo json_encode(['success' => true, 'message' => 'Carrito guardado']);
        break;
        
    case 'get':
        $cart = $_SESSION['cart'] ?? [];
        echo json_encode(['success' => true, 'cart' => $cart]);
        break;
        
    case 'clear':
        unset($_SESSION['cart']);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>