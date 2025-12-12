<?php
// get_products.php - Obtener lista de productos
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Obtener productos con información básica
    $sql = "SELECT 
                p.ID_PRODUCTO,
                p.NOMBRE,
                p.DESCRIPCION,
                p.CATEGORIA,
                p.PRECIO
            FROM PRODUCTO p
            ORDER BY p.NOMBRE";
    
    $productos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada producto, obtener stock total y variantes
    foreach ($productos as &$producto) {
        $producto_id = $producto['ID_PRODUCTO'];
        
        // Obtener total stock desde INVENTARIO
        $sql_stock = "SELECT SUM(i.CANTIDAD) as total_stock
                      FROM INVENTARIO i
                      INNER JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE
                      WHERE v.ID_PRODUCTO = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->execute([$producto_id]);
        $stock = $stmt_stock->fetch();
        
        $total_stock = $stock['total_stock'] ?? 0;
        
        // Determinar estado del stock
        if ($total_stock == 0) {
            $estado_stock = 'Agotado';
        } elseif ($total_stock < 10) {
            $estado_stock = 'Bajo';
        } else {
            $estado_stock = 'Disponible';
        }
        
        // Contar variantes (colores/tallas)
        $sql_variantes = "SELECT COUNT(*) as num_variantes
                          FROM VARIANTE 
                          WHERE ID_PRODUCTO = ?";
        $stmt_variantes = $conn->prepare($sql_variantes);
        $stmt_variantes->execute([$producto_id]);
        $variantes = $stmt_variantes->fetch();
        
        $producto['stock_total'] = (int)$total_stock;
        $producto['estado_stock'] = $estado_stock;
        $producto['num_variantes'] = $variantes['num_variantes'] ?? 0;
        
        // Obtener primera variante para SKU
        $sql_sku = "SELECT TOP 1 SKU FROM VARIANTE WHERE ID_PRODUCTO = ?";
        $stmt_sku = $conn->prepare($sql_sku);
        $stmt_sku->execute([$producto_id]);
        $sku_data = $stmt_sku->fetch();
        
        $producto['SKU'] = $sku_data['SKU'] ?? 'SKZ-' . str_pad($producto_id, 3, '0', STR_PAD_LEFT);
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>