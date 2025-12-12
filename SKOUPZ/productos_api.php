<?php
/**
 * productos_api.php - API para obtener productos desde la BD
 * Devuelve productos con sus variantes, stock e imágenes
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'Config.php';
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Obtener acción
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Listar todos los productos con sus variantes
            $sql = "SELECT 
                        p.ID_PRODUCTO,
                        p.NOMBRE,
                        p.DESCRIPCION,
                        p.CATEGORIA,
                        p.PRECIO,
                        v.ID_VARIANTE,
                        v.SKU,
                        t.NOMBRE as TALLA,
                        t.ABREVIATURA as TALLA_ABREV,
                        c.NOMBRE as COLOR,
                        c.CODIGO_HEX,
                        i.CANTIDAD as STOCK,
                        prov.NOMBRE_PROVEEDOR
                    FROM PRODUCTO p
                    LEFT JOIN VARIANTE v ON p.ID_PRODUCTO = v.ID_PRODUCTO
                    LEFT JOIN TALLA t ON v.ID_TALLA = t.ID_TALLA
                    LEFT JOIN COLOR c ON v.ID_COLOR = c.ID_COLOR
                    LEFT JOIN INVENTARIO i ON v.ID_VARIANTE = i.ID_VARIANTE
                    LEFT JOIN PROVEEDOR prov ON v.ID_PROVEEDOR = prov.ID_PROVEEDOR
                    WHERE i.CANTIDAD > 0
                    ORDER BY p.ID_PRODUCTO, v.ID_VARIANTE";
            
            $stmt = $conn->query($sql);
            $rows = $stmt->fetchAll();
            
            // Agrupar por producto
            $productos = [];
            foreach ($rows as $row) {
                $idProducto = $row['ID_PRODUCTO'];
                
                if (!isset($productos[$idProducto])) {
                    $productos[$idProducto] = [
                        'id' => $row['ID_PRODUCTO'],
                        'nombre' => $row['NOMBRE'],
                        'descripcion' => $row['DESCRIPCION'],
                        'categoria' => $row['CATEGORIA'],
                        'precio' => floatval($row['PRECIO']),
                        'imagen' => 'image/' . strtoupper(str_replace(' ', '', $row['NOMBRE'])) . '_S.png',
                        'variantes' => []
                    ];
                }
                
                if ($row['ID_VARIANTE']) {
                    $productos[$idProducto]['variantes'][] = [
                        'id_variante' => $row['ID_VARIANTE'],
                        'sku' => $row['SKU'],
                        'talla' => $row['TALLA_ABREV'],
                        'color' => $row['COLOR'],
                        'color_hex' => $row['CODIGO_HEX'],
                        'stock' => intval($row['STOCK']),
                        'proveedor' => $row['NOMBRE_PROVEEDOR']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'productos' => array_values($productos)
            ]);
            break;
            
        case 'detail':
            // Detalle de un producto específico
            $idProducto = $_GET['id'] ?? 0;
            
            $sql = "SELECT 
                        p.ID_PRODUCTO,
                        p.NOMBRE,
                        p.DESCRIPCION,
                        p.CATEGORIA,
                        p.PRECIO,
                        v.ID_VARIANTE,
                        v.SKU,
                        t.NOMBRE as TALLA,
                        t.ABREVIATURA as TALLA_ABREV,
                        c.NOMBRE as COLOR,
                        c.CODIGO_HEX,
                        i.CANTIDAD as STOCK
                    FROM PRODUCTO p
                    LEFT JOIN VARIANTE v ON p.ID_PRODUCTO = v.ID_PRODUCTO
                    LEFT JOIN TALLA t ON v.ID_TALLA = t.ID_TALLA
                    LEFT JOIN COLOR c ON v.ID_COLOR = c.ID_COLOR
                    LEFT JOIN INVENTARIO i ON v.ID_VARIANTE = i.ID_VARIANTE
                    WHERE p.ID_PRODUCTO = ?
                    ORDER BY v.ID_VARIANTE";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idProducto]);
            $rows = $stmt->fetchAll();
            
            if (empty($rows)) {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                exit;
            }
            
            $producto = [
                'id' => $rows[0]['ID_PRODUCTO'],
                'nombre' => $rows[0]['NOMBRE'],
                'descripcion' => $rows[0]['DESCRIPCION'],
                'categoria' => $rows[0]['CATEGORIA'],
                'precio' => floatval($rows[0]['PRECIO']),
                'imagen' => 'image/' . strtoupper(str_replace(' ', '', $rows[0]['NOMBRE'])) . '_S.png',
                'variantes' => []
            ];
            
            foreach ($rows as $row) {
                if ($row['ID_VARIANTE']) {
                    $producto['variantes'][] = [
                        'id_variante' => $row['ID_VARIANTE'],
                        'sku' => $row['SKU'],
                        'talla' => $row['TALLA_ABREV'],
                        'color' => $row['COLOR'],
                        'color_hex' => $row['CODIGO_HEX'],
                        'stock' => intval($row['STOCK'])
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'producto' => $producto
            ]);
            break;
            
        case 'stock':
            // Verificar stock de una variante
            $idVariante = $_GET['id_variante'] ?? 0;
            
            $sql = "SELECT i.CANTIDAD, p.NOMBRE, v.SKU
                    FROM INVENTARIO i
                    JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE
                    JOIN PRODUCTO p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                    WHERE i.ID_VARIANTE = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idVariante]);
            $row = $stmt->fetch();
            
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'stock' => intval($row['CANTIDAD']),
                    'producto' => $row['NOMBRE'],
                    'sku' => $row['SKU'],
                    'disponible' => $row['CANTIDAD'] > 0
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Variante no encontrada']);
            }
            break;
            
        case 'categorias':
            // Listar todas las categorías
            $sql = "SELECT DISTINCT CATEGORIA FROM PRODUCTO ORDER BY CATEGORIA";
            $stmt = $conn->query($sql);
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (PDOException $e) {
    error_log("Error productos API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar productos']);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado']);
}
?>