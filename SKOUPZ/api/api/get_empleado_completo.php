<?php
// get_empleado_completo.php - Obtiene todos los datos del empleado para el dashboard
require_once '../Config.php';
session_start();

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'empleado') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empleado_id = $_SESSION['user_id'];

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // 1. OBTENER DATOS PERSONALES DEL EMPLEADO
    $sql_empleado = "SELECT 
                        ID_EMPLEADO, 
                        NOMBRE, 
                        APELLIDO, 
                        EMAIL, 
                        PUESTO,
                        TELEFONO,
                        SALARIO,
                        DEPARTAMENTO
                     FROM EMPLEADO 
                     WHERE ID_EMPLEADO = ?";
    
    $stmt_empleado = $conn->prepare($sql_empleado);
    $stmt_empleado->execute([$empleado_id]);
    $empleado = $stmt_empleado->fetch();
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        exit;
    }
    
    // 2. OBTENER ESTADÍSTICAS
    $sql_stats = "SELECT 
                    (SELECT COUNT(*) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? 
                     AND ID_ESTADO IN (SELECT ID_ESTADO FROM ESTADO WHERE NOMBRE_ESTADO IN ('PENDIENTE', 'EN PROCESO'))) as pedidos_pendientes,
                    
                    (SELECT COUNT(*) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? 
                     AND CONVERT(DATE, FECHA_PEDIDO) = CONVERT(DATE, GETDATE())
                     AND ID_ESTADO = (SELECT ID_ESTADO FROM ESTADO WHERE NOMBRE_ESTADO = 'COMPLETADO')) as pedidos_hoy,
                    
                    (SELECT COUNT(DISTINCT v.ID_PRODUCTO) 
                     FROM INVENTARIO i 
                     INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE
                     WHERE i.CANTIDAD < 10) as stock_bajo,
                    
                    (SELECT COUNT(*) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? 
                     AND MONTH(FECHA_PEDIDO) = MONTH(GETDATE())) as pedidos_mes,
                    
                    (SELECT ISNULL(SUM(TOTAL), 0) FROM PEDIDO 
                     WHERE ID_EMPLEADO = ? 
                     AND MONTH(FECHA_PEDIDO) = MONTH(GETDATE())) as ventas_mes";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->execute([$empleado_id, $empleado_id, $empleado_id, $empleado_id]);
    $stats = $stmt_stats->fetch();
    
    // 3. OBTENER PEDIDOS RECIENTES
    $sql_pedidos = "SELECT TOP 5 
                        p.ID_PEDIDO,
                        c.NOMBRE + ' ' + c.APELLIDO as cliente,
                        p.FECHA_PEDIDO,
                        p.TOTAL,
                        e.NOMBRE_ESTADO as estado
                    FROM PEDIDO p
                    INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                    INNER JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
                    WHERE p.ID_EMPLEADO = ?
                    ORDER BY p.FECHA_PEDIDO DESC";
    
    $stmt_pedidos = $conn->prepare($sql_pedidos);
    $stmt_pedidos->execute([$empleado_id]);
    $pedidos_recientes = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. OBTENER PRODUCTOS CON STOCK BAJO
    $sql_productos_bajo = "SELECT TOP 5
                            p.ID_PRODUCTO,
                            p.NOMBRE,
                            SUM(i.CANTIDAD) as stock_total,
                            MIN(v.SKU) as sku_ejemplo
                        FROM PRODUCTO p
                        INNER JOIN VARIANTE v ON p.ID_PRODUCTO = v.ID_PRODUCTO
                        INNER JOIN INVENTARIO i ON v.ID_VARIANTE = i.ID_VARIANTE
                        GROUP BY p.ID_PRODUCTO, p.NOMBRE
                        HAVING SUM(i.CANTIDAD) < 10
                        ORDER BY SUM(i.CANTIDAD) ASC";
    
    $productos_bajo = $conn->query($sql_productos_bajo)->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. FECHA ACTUAL FORMATEADA
    $fecha_actual = date('l, d F Y');
    $fecha_actual_es = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ][date('l')] . ', ' . date('d') . ' de ' . [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ][date('F')] . ' ' . date('Y');
    
    // 6. MENSAJE PERSONALIZADO
    $hora = date('H');
    if ($hora < 12) {
        $saludo = 'Buenos días';
    } elseif ($hora < 19) {
        $saludo = 'Buenas tardes';
    } else {
        $saludo = 'Buenas noches';
    }
    
    $mensaje_bienvenida = "¡{$saludo}, {$empleado['NOMBRE']}! 👋";
    
    // Contar pedidos pendientes para el mensaje
    $pedidos_pendientes_count = $stats['pedidos_pendientes'] ?? 0;
    $productos_bajo_count = $stats['stock_bajo'] ?? 0;
    
    $mensaje_detalle = "Hoy es {$fecha_actual_es}. ";
    if ($pedidos_pendientes_count > 0) {
        $mensaje_detalle .= "Tienes {$pedidos_pendientes_count} pedidos pendientes de procesar";
        if ($productos_bajo_count > 0) {
            $mensaje_detalle .= " y {$productos_bajo_count} productos con stock bajo que requieren atención.";
        } else {
            $mensaje_detalle .= ".";
        }
    } else {
        $mensaje_detalle .= "No tienes pedidos pendientes. ¡Excelente trabajo!";
    }
    
    echo json_encode([
        'success' => true,
        'empleado' => $empleado,
        'stats' => [
            'pedidos_pendientes' => $stats['pedidos_pendientes'] ?? 0,
            'pedidos_hoy' => $stats['pedidos_hoy'] ?? 0,
            'stock_bajo' => $stats['stock_bajo'] ?? 0,
            'pedidos_mes' => $stats['pedidos_mes'] ?? 0,
            'ventas_mes' => $stats['ventas_mes'] ?? 0
        ],
        'pedidos_recientes' => $pedidos_recientes,
        'productos_bajo_stock' => $productos_bajo,
        'fecha_actual' => $fecha_actual_es,
        'mensaje_bienvenida' => $mensaje_bienvenida,
        'mensaje_detalle' => $mensaje_detalle,
        'iniciales' => strtoupper(substr($empleado['NOMBRE'], 0, 1) . substr($empleado['APELLIDO'], 0, 1))
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>