<?php
// get_orders.php - Obtener pedidos
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    $sql = "SELECT TOP 20 
                p.ID_PEDIDO,
                c.NOMBRE + ' ' + c.APELLIDO as cliente,
                p.FECHA_PEDIDO,
                p.TOTAL,
                e.NOMBRE_ESTADO as estado,
                mp.NOMBRE as metodo_pago
            FROM PEDIDO p
            INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
            INNER JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
            LEFT JOIN PAGO pg ON p.ID_PEDIDO = pg.ID_PEDIDO
            LEFT JOIN METODO_PAGO mp ON pg.ID_METODO_PAGO = mp.ID_METODO_PAGO
            ORDER BY p.FECHA_PEDIDO DESC";
    
    $pedidos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>