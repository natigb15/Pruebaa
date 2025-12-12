<?php
// api/get_products_catalog.php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../Config.php';
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Consulta principal de productos con inventario disponible
    $sql = "SELECT DISTINCT 
                p.ID_PRODUCTO,
                p.NOMBRE,
                p.DESCRIPCION,
                p.CATEGORIA,
                p.PRECIO,
                v.ID_VARIANTE,
                t.NOMBRE as TALLA,
                c.NOMBRE as COLOR,
                c.CODIGO_HEX,
                i.CANTIDAD as STOCK,
                v.SKU,
                CONCAT(p.NOMBRE, ' - ', c.NOMBRE, ' (', t.ABREVIATURA, ')') as DISPLAY_NAME
            FROM PRODUCTO p
            JOIN VARIANTE v ON p.ID_PRODUCTO = v.ID_PRODUCTO
            JOIN INVENTARIO i ON v.ID_VARIANTE = i.ID_VARIANTE
            JOIN TALLAS t ON v.ID_TALLA = t.ID_TALLA
            JOIN COLORES c ON v.ID_COLOR = c.ID_COLOR
            WHERE i.CANTIDAD > 0
            ORDER BY p.ID_PRODUCTO";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar productos por ID_PRODUCTO con sus variantes
    $resultado = [];
    foreach ($productos as $producto) {
        $id = $producto['ID_PRODUCTO'];
        
        if (!isset($resultado[$id])) {
            $resultado[$id] = [
                'id' => $producto['ID_PRODUCTO'],
                'nombre' => $producto['NOMBRE'],
                'descripcion' => $producto['DESCRIPCION'],
                'categoria' => $producto['CATEGORIA'],
                'precio' => (float)$producto['PRECIO'],
                'variantes' => [],
                'stock_total' => 0
            ];
        }
        
        $variante = [
            'id_variante' => $producto['ID_VARIANTE'],
            'sku' => $producto['SKU'],
            'talla' => $producto['TALLA'],
            'color' => $producto['COLOR'],
            'color_hex' => $producto['CODIGO_HEX'],
            'stock' => (int)$producto['STOCK']
        ];
        
        $resultado[$id]['variantes'][] = $variante;
        $resultado[$id]['stock_total'] += (int)$producto['STOCK'];
    }
    
    echo json_encode([
        'success' => true,
        'products' => array_values($resultado),
        'count' => count($resultado)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar productos: ' . $e->getMessage()
    ]);
}
?>