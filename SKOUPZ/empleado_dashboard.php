<?php
// empleado_dashboard.php - Conecta a base de datos SQL Server
session_start();
header('Content-Type: application/json');

// Verifica que el empleado este logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'empleado') {
    echo json_encode([
        'success' => false,
        'message' => 'Acceso no autorizado. Por favor inicia sesión.',
        'redirect' => 'index.html'
    ]);
    exit;
}

$empleado_id = $_SESSION['user_id']; // Esto viene del login
$empleado_nombre = $_SESSION['user_name'] ?? 'Empleado';

//conexión a SQL Server Azure
$serverName = "tcp:skoupzdatabase.database.windows.net,1433";
$database = "skoupz";
$username = "sqladmin";
$password = "Skoupz1234";


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
    
    // ========== OBTENER DATOS DEL EMPLEADO LOGEADO ==========
    $sql_empleado = "SELECT 
                        ID_EMPLEADO, 
                        NOMBRE, 
                        APELLIDO, 
                        EMAIL, 
                        PUESTO,
                        FECHA_CONTRATACION,
                        ID_DEPARTAMENTO
                     FROM EMPLEADO 
                     WHERE ID_EMPLEADO = ?";
    
    $stmt_empleado = $conn->prepare($sql_empleado);
    $stmt_empleado->execute([$empleado_id]);
    $empleado = $stmt_empleado->fetch();
    
    if (!$empleado) {
        echo json_encode([
            'success' => false,
            'message' => 'Empleado no encontrado en la base de datos',
            'redirect' => 'index.html'
        ]);
        exit;
    }
    
    // Obtener nombre del departamento
    $sql_depto = "SELECT NOMBRE_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
    $stmt_depto = $conn->prepare($sql_depto);
    $stmt_depto->execute([$empleado['ID_DEPARTAMENTO']]);
    $depto = $stmt_depto->fetch();
    $empleado['DEPARTAMENTO'] = $depto['NOMBRE_DEPARTAMENTO'] ?? 'Sin departamento';
    
    // ========== 2. OBTENER ESTADÍSTICAS ==========
    // Pedidos pendientes del empleado (PENDIENTE PAGO, EN PREPARACIÓN, EN ESPERA)
    $sql_pendientes = "SELECT COUNT(*) as total 
                       FROM PEDIDO 
                       WHERE ID_EMPLEADO = ? 
                       AND ID_ESTADO IN ('E01', 'E02', 'E07')";
    
    $stmt_pendientes = $conn->prepare($sql_pendientes);
    $stmt_pendientes->execute([$empleado_id]);
    $pendientes = $stmt_pendientes->fetch();
    
    // Pedidos completados hoy (ENTREGADO)
    $sql_hoy = "SELECT COUNT(*) as total 
                FROM PEDIDO 
                WHERE ID_EMPLEADO = ? 
                AND ID_ESTADO = 'E04'
                AND CONVERT(DATE, FECHA_PEDIDO) = CONVERT(DATE, GETDATE())";
    
    $stmt_hoy = $conn->prepare($sql_hoy);
    $stmt_hoy->execute([$empleado_id]);
    $hoy = $stmt_hoy->fetch();
    
    // Productos con stock bajo (< 10 unidades)
    $sql_stock = "SELECT COUNT(*) as total 
                  FROM INVENTARIO 
                  WHERE CANTIDAD < 10";
    
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->execute();
    $stock = $stmt_stock->fetch();
    
    // ========== 3. OBTENER PEDIDOS RECIENTES DEL EMPLEADO ==========
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
    
    $stmt_recientes = $conn->prepare($sql_pedidos_recientes);
    $stmt_recientes->execute([$empleado_id]);
    $pedidos_recientes = $stmt_recientes->fetchAll();
    
    // ========== 4. OBTENER TODOS LOS PEDIDOS DEL EMPLEADO ==========
    $sql_pedidos = "SELECT 
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
    
    // ========== 5. OBTENER PRODUCTOS CON STOCK ==========
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
                        (SELECT COUNT(*) FROM VARIANTE WHERE ID_PRODUCTO = p.ID_PRODUCTO) as variantes
                      FROM PRODUCTO p
                      ORDER BY p.NOMBRE";
    
    $productos = $conn->query($sql_productos)->fetchAll();
    
    // Agregar estado del stock
    foreach ($productos as &$producto) {
        $stock_total = $producto['stock_total'] ?? 0;
        if ($stock_total == 0) {
            $producto['estado_stock'] = 'Agotado';
        } elseif ($stock_total < 10) {
            $producto['estado_stock'] = 'Bajo';
        } else {
            $producto['estado_stock'] = 'Disponible';
        }
    }
    
    // ========== 6. OBTENER CLIENTES ==========
    $sql_clientes = "SELECT TOP 10 
                        c.ID_CLIENTE,
                        c.NOMBRE,
                        c.APELLIDO,
                        c.EMAIL,
                        c.TELEFONO_CLIENTE as TELEFONO,
                        d.CIUDAD,
                        (SELECT COUNT(*) FROM PEDIDO WHERE ID_CLIENTE = c.ID_CLIENTE) as total_pedidos,
                        CONVERT(VARCHAR, c.FECHA_REGISTRO, 106) as FECHA_REGISTRO
                     FROM CLIENTE c
                     LEFT JOIN DIRECCION d ON c.ID_CLIENTE = d.ID_CLIENTE
                     ORDER BY c.FECHA_REGISTRO DESC";
    
    $clientes = $conn->query($sql_clientes)->fetchAll();
    
    // ========== 7. CALCULAR VENTAS DEL MES ==========
    $sql_ventas_mes = "SELECT 
                         COUNT(*) as pedidos_mes,
                         ISNULL(SUM(TOTAL), 0) as ventas_mes
                       FROM PEDIDO 
                       WHERE ID_EMPLEADO = ? 
                       AND MONTH(FECHA_PEDIDO) = MONTH(GETDATE())
                       AND YEAR(FECHA_PEDIDO) = YEAR(GETDATE())";
    
    $stmt_ventas = $conn->prepare($sql_ventas_mes);
    $stmt_ventas->execute([$empleado_id]);
    $ventas = $stmt_ventas->fetch();
    
    // ========== 8. PREPARAR RESPUESTA ==========
    $response = [
        'success' => true,
        'empleado' => [
            'ID_EMPLEADO' => $empleado['ID_EMPLEADO'],
            'NOMBRE' => $empleado['NOMBRE'],
            'APELLIDO' => $empleado['APELLIDO'],
            'EMAIL' => $empleado['EMAIL'],
            'PUESTO' => $empleado['PUESTO'],
            'DEPARTAMENTO' => $empleado['DEPARTAMENTO'],
            'FECHA_CONTRATACION' => $empleado['FECHA_CONTRATACION']
        ],
        'stats' => [
            'pedidos_pendientes' => $pendientes['total'] ?? 0,
            'pedidos_hoy' => $hoy['total'] ?? 0,
            'stock_bajo' => $stock['total'] ?? 0,
            'pedidos_mes' => $ventas['pedidos_mes'] ?? 0,
            'ventas_mes' => $ventas['ventas_mes'] ?? 0
        ],
        'pedidos_recientes' => $pedidos_recientes,
        'pedidos' => $pedidos,
        'productos' => $productos,
        'clientes' => $clientes
    ];
    
    echo json_encode($response);
    
          