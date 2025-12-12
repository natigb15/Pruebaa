<?php
// get_employee_orders.php - Pedidos asignados al empleado
require_once '../Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'empleado') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empleado_id = $_SESSION['user_id'];

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    $sql = "SELECT TOP 20 
                p.ID_PEDIDO,
                c.NOMBRE + ' ' + c.APELLIDO as cliente,
                p.FECHA_PEDIDO,
                p.TOTAL,
                e.NOMBRE_ESTADO as estado
            FROM PEDIDO p
            INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
            INNER JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
            WHERE p.ID_EMPLEADO = ?
            ORDER BY 
                CASE e.NOMBRE_ESTADO 
                    WHEN 'PENDIENTE' THEN 1
                    WHEN 'EN PROCESO' THEN 2
                    ELSE 3 
                END,
                p.FECHA_PEDIDO DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$empleado_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>