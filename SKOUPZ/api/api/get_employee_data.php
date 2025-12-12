<?php
// get_employee_data.php - Datos específicos para empleado
require_once '../Config.php';
session_start();

header('Content-Type: application/json');

// Verificar sesión de empleado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'empleado') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empleado_id = $_SESSION['user_id'];

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Obtener información del empleado
    $sql_empleado = "SELECT ID_EMPLEADO, NOMBRE, APELLIDO, EMAIL, PUESTO 
                     FROM EMPLEADO 
                     WHERE ID_EMPLEADO = ?";
    $stmt_empleado = $conn->prepare($sql_empleado);
    $stmt_empleado->execute([$empleado_id]);
    $empleado = $stmt_empleado->fetch();
    
    // Obtener estadísticas
    $sql_stats = "SELECT 
                    (SELECT COUNT(*) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? AND ID_ESTADO = (SELECT ID_ESTADO FROM ESTADO WHERE NOMBRE_ESTADO = 'PENDIENTE')) as pedidos_pendientes,
                    (SELECT COUNT(*) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? AND CONVERT(DATE, FECHA_PEDIDO) = CONVERT(DATE, GETDATE())) as pedidos_hoy,
                    (SELECT COUNT(*) FROM INVENTARIO i 
                     INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE
                     WHERE i.CANTIDAD < 10) as stock_bajo";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->execute([$empleado_id, $empleado_id]);
    $stats = $stmt_stats->fetch();
    
    echo json_encode([
        'success' => true,
        'empleado_nombre' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
        'empleado_puesto' => $empleado['PUESTO'],
        'pedidos_pendientes' => $stats['pedidos_pendientes'] ?? 0,
        'pedidos_hoy' => $stats['pedidos_hoy'] ?? 0,
        'stock_bajo' => $stats['stock_bajo'] ?? 0
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>