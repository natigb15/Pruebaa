<?php
// get_product_details.php - Obtener detalles de un producto específico
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
    
    // Obtener información del producto
    $sql = "SELECT 
                p.ID_PRODUCTO,
                p.NOMBRE,
                p.DESCRIPCION,
                p.CATEGORIA,
                p.PRECIO
            FROM PRODUCTO p
            WHERE p.ID_PRODUCTO = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    
    // Obtener variantes (tallas/colores)
    $sql_variantes = "SELECT 
                        v.ID_VARIANTE,
                        v.SKU,
                        t.NOMBRE as talla,
                        t.ABREVIATURA as talla_abrev,
                        c.NOMBRE as color,
                        c.CODIGO_HEX as color_hex,
                        pr.NOMBRE_PROVEEDOR as proveedor
                    FROM VARIANTE v
                    INNER JOIN TALLA t ON v.ID_TALLA = t.ID_TALLA
                    INNER JOIN COLOR c ON v.ID_COLOR = c.ID_COLOR
                    INNER JOIN PROVEEDOR pr ON v.ID_PROVEEDOR = pr.ID_PROVEEDOR
                    WHERE v.ID_PRODUCTO = ?
                    ORDER BY t.ABREVIATURA, c.NOMBRE";
    
    $stmt_variantes = $conn->prepare($sql_variantes);
    $stmt_variantes->execute([$id]);
    $variantes = $stmt_variantes->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener stock por variante
    foreach ($variantes as &$variante) {
        $sql_stock = "SELECT SUM(CANTIDAD) as stock
                      FROM INVENTARIO 
                      WHERE ID_VARIANTE = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->execute([$variante['ID_VARIANTE']]);
        $stock = $stmt_stock->fetch();
        
        $variante['stock'] = $stock['stock'] ?? 0;
    }
    
    // Obtener pedidos recientes con este producto
    $sql_pedidos = "SELECT 
                        pd.ID_PEDIDO,
                        p.FECHA_PEDIDO,
                        pd.CANTIDAD,
                        pd.PRECIO_UNITARIO,
                        (pd.CANTIDAD * pd.PRECIO_UNITARIO) as total_linea,
                        c.NOMBRE + ' ' + c.APELLIDO as cliente
                    FROM PEDIDO_DETALLE pd
                    INNER JOIN PEDIDO p ON pd.ID_PEDIDO = p.ID_PEDIDO
                    INNER JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                    INNER JOIN VARIANTE v ON pd.ID_VARIANTE = v.ID_VARIANTE
                    WHERE v.ID_PRODUCTO = ?
                    ORDER BY p.FECHA_PEDIDO DESC
                    LIMIT 10";
    
    $stmt_pedidos = $conn->prepare($sql_pedidos);
    $stmt_pedidos->execute([$id]);
    $pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'producto' => $producto,
        'variantes' => $variantes,
        'pedidos' => $pedidos,
        'total_variantes' => count($variantes),
        'total_vendido' => array_sum(array_column($pedidos, 'total_linea'))
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>