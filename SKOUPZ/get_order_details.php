<?php
// get_order_details.php - Obtener detalles de un pedido específico
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = $_GET['id'];

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Obtener información del pedido
    $sql = "SELECT 
                p.ID_PEDIDO,
                p.FECHA_PEDIDO,
                p.SUBTOTAL,
                p.IMPUESTO,
                p.TOTAL,
                e.NOMBRE_ESTADO as estado,
                c.NOMBRE + ' ' + c.APELLIDO as cliente,
                c.EMAIL as email_cliente,
                emp.NOMBRE + ' ' + emp.APELLIDO as empleado,
                d.DIRECCION,
                d.CIUDAD,
                d.PROVINCIA,
                d.CODIGO_POSTAL,
                env.NOMBRE_METODO as metodo_envio,
                env.COSTO_BASE as costo_envio,
                mp.NOMBRE as metodo_pago,
                pg.FECHA_PAGO,
                pg.REFERENCIA_TRANSACCION
            FROM PEDIDO p
            INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
            INNER JOIN EMPLEADO emp ON p.ID_EMPLEADO = emp.ID_EMPLEADO
            INNER JOIN DIRECCION d ON p.ID_DIRECCION = d.ID_DIRECCION
            INNER JOIN ENVIO env ON p.ID_ENVIO = env.ID_ENVIO
            INNER JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
            LEFT JOIN PAGO pg ON p.ID_PEDIDO = pg.ID_PEDIDO
            LEFT JOIN METODO_PAGO mp ON pg.ID_METODO_PAGO = mp.ID_METODO_PAGO
            WHERE p.ID_PEDIDO = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Obtener detalles del pedido (productos)
    $sql_detalles = "SELECT 
                        pd.ID_VARIANTE,
                        pd.CANTIDAD,
                        pd.PRECIO_UNITARIO,
                        (pd.CANTIDAD * pd.PRECIO_UNITARIO) as total_linea,
                        pr.NOMBRE as producto,
                        t.NOMBRE as talla,
                        c.NOMBRE as color,
                        v.SKU
                    FROM PEDIDO_DETALLE pd
                    INNER JOIN VARIANTE v ON pd.ID_VARIANTE = v.ID_VARIANTE
                    INNER JOIN PRODUCTO pr ON v.ID_PRODUCTO = pr.ID_PRODUCTO
                    INNER JOIN TALLA t ON v.ID_TALLA = t.ID_TALLA
                    INNER JOIN COLOR c ON v.ID_COLOR = c.ID_COLOR
                    WHERE pd.ID_PEDIDO = ?";
    
    $stmt_detalles = $conn->prepare($sql_detalles);
    $stmt_detalles->execute([$id]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido,
        'detalles' => $detalles,
        'total_items' => count($detalles)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>