<?php
// get_admin_data.php - Obtener datos para el dashboard
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

// Solo admin puede acceder
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // 1. Contar empleados activos
    $sql_empleados = "SELECT COUNT(*) as total FROM EMPLEADO";
    $empleados = $conn->query($sql_empleados)->fetchColumn();
    
    // 2. Contar pedidos del mes actual
    $sql_pedidos = "SELECT COUNT(*) as total FROM PEDIDO 
                    WHERE MONTH(FECHA_PEDIDO) = MONTH(GETDATE()) 
                    AND YEAR(FECHA_PEDIDO) = YEAR(GETDATE())";
    $pedidos = $conn->query($sql_pedidos)->fetchColumn();
    
    // 3. Calcular total de productos (usando VARIANTE)
    $sql_productos = "SELECT COUNT(DISTINCT ID_PRODUCTO) as total FROM VARIANTE";
    $productos = $conn->query($sql_productos)->fetchColumn();
    
    // 4. Calcular ventas del mes
    $sql_ventas = "SELECT ISNULL(SUM(TOTAL), 0) as total FROM PEDIDO 
                   WHERE MONTH(FECHA_PEDIDO) = MONTH(GETDATE()) 
                   AND YEAR(FECHA_PEDIDO) = YEAR(GETDATE())";
    $ventas = $conn->query($sql_ventas)->fetchColumn();
    
    // 5. Obtener pedidos recientes (últimos 5)
    $sql_recent_orders = "SELECT TOP 5 
                                 p.ID_PEDIDO, 
                                 c.NOMBRE + ' ' + c.APELLIDO as cliente,
                                 p.FECHA_PEDIDO, 
                                 p.TOTAL, 
                                 e.NOMBRE_ESTADO as estado
                          FROM PEDIDO p
                          INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                          INNER JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
                          ORDER BY p.FECHA_PEDIDO DESC";
    $pedidos_recientes = $conn->query($sql_recent_orders)->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Obtener productos con bajo stock (<10 unidades)
    $sql_low_stock = "SELECT COUNT(*) as bajo_stock 
                      FROM INVENTARIO 
                      WHERE CANTIDAD < 10";
    $bajo_stock = $conn->query($sql_low_stock)->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'empleados' => (int)$empleados,
            'pedidos_mes' => (int)$pedidos,
            'productos_stock' => (int)$productos,
            'ventas_mes' => (float)$ventas,
            'bajo_stock' => (int)$bajo_stock
        ],
        'pedidos_recientes' => $pedidos_recientes,
        'admin_name' => $_SESSION['user_name'] ?? 'Administrador'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>