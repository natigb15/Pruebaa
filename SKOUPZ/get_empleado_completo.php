<?php
// get_empleado_completo.php - Obtiene TODOS los datos del empleado
session_start();
header('Content-Type: application/json');

// Simular que el empleado está logueado (esto debería venir de tu login)
$empleado_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Cambia esto según tu login

// Tu conexión a SQL Server Azure
$serverName = "tcp:skoupzdatabase.database.windows.net,1433";
$database = "skoupz";
$username = "sqladmin";
$password = "Skoupz1234";

try {
    // Conexión a Azure SQL Server
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    
    // 1. OBTENER DATOS DEL EMPLEADO
    $sql_empleado = "SELECT TOP 1 
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
    
    // 3. OBTENER PEDIDOS RECIENTES DEL EMPLEADO
    $sql_pedidos_recientes = "SELECT TOP 5 
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
    
    $stmt_pedidos_recientes = $conn->prepare($sql_pedidos_recientes);
    $stmt_pedidos_recientes->execute([$empleado_id]);
    $pedidos_recientes = $stmt_pedidos_recientes->fetchAll();
    
    // 4. OBTENER TODOS LOS PEDIDOS DEL EMPLEADO
    $sql_pedidos = "SELECT TOP 20 
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
    $pedidos = $stmt_pedidos->fetchAll();
    
    // 5. OBTENER PRODUCTOS CON STOCK
    $sql_productos = "SELECT 
                        p.ID_PRODUCTO,
                        p.NOMBRE,
                        p.CATEGORIA,
                        p.PRECIO,
                        (SELECT TOP 1 SKU FROM VARIANTE WHERE ID_PRODUCTO = p.ID_PRODUCTO) as SKU,
                        (SELECT SUM(i.CANTIDAD) 
                         FROM INVENTARIO i 
                         INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE 
                         WHERE v.ID_PRODUCTO = p.ID_PRODUCTO) as stock_total,
                        (SELECT COUNT(*) FROM VARIANTE WHERE ID_PRODUCTO = p.ID_PRODUCTO) as variantes,
                        CASE 
                            WHEN (SELECT SUM(i.CANTIDAD) FROM INVENTARIO i INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE WHERE v.ID_PRODUCTO = p.ID_PRODUCTO) = 0 THEN 'Agotado'
                            WHEN (SELECT SUM(i.CANTIDAD) FROM INVENTARIO i INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE WHERE v.ID_PRODUCTO = p.ID_PRODUCTO) < 10 THEN 'Bajo'
                            ELSE 'Disponible'
                        END as estado_stock
                      FROM PRODUCTO p
                      ORDER BY p.NOMBRE";
    
    $productos = $conn->query($sql_productos)->fetchAll();
    
    // 6. OBTENER CLIENTES
    $sql_clientes = "SELECT TOP 10 
                        c.ID_CLIENTE,
                        c.NOMBRE,
                        c.APELLIDO,
                        c.EMAIL,
                        c.TELEFONO,
                        d.CIUDAD,
                        (SELECT COUNT(*) FROM PEDIDO WHERE ID_CLIENTE = c.ID_CLIENTE) as total_pedidos,
                        CONVERT(VARCHAR, c.FECHA_REGISTRO, 106) as FECHA_REGISTRO
                     FROM CLIENTE c
                     LEFT JOIN DIRECCION d ON c.ID_CLIENTE = d.ID_CLIENTE
                     ORDER BY c.FECHA_REGISTRO DESC";
    
    $cliente = $conn->query($sql_clientes)->fetchAll();
    
    echo json_encode([
        'success' => true,
        'empleado' => $empleado,
        'stats' => $stats,
        'pedidos_recientes' => $pedidos_recientes,
        'pedidos' => $pedidos,
        'productos' => $productos,
        'clientes' => $clientes
    ]);
    
} catch (PDOException $e) {
    // En caso de error, devolver datos de ejemplo
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage(),
        'empleado' => [
            'ID_EMPLEADO' => $empleado_id,
            'NOMBRE' => 'Empleado',
            'APELLIDO' => 'Demo',
            'EMAIL' => 'demo@skoupz.com',
            'PUESTO' => 'Empleado',
            'DEPARTAMENTO' => 'General'
        ]
    ]);
}
?>