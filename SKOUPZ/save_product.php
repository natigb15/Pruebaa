<?php
// save_product.php - VERSIÓN SIMPLIFICADA Y FUNCIONAL
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Obtener datos
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$sku = trim($_POST['sku'] ?? '');
$proveedor_id = intval($_POST['proveedor_id'] ?? 0);
$talla_id = intval($_POST['talla_id'] ?? 0);
$color_id = intval($_POST['color_id'] ?? 0);
$stock_inicial = intval($_POST['stock_inicial'] ?? 0);

// Validar datos básicos
if (empty($nombre) || $proveedor_id <= 0 || $talla_id <= 0 || $color_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Faltan datos requeridos: nombre, proveedor, talla o color'
    ]);
    exit;
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    $conn->beginTransaction();
    
    // 1. Insertar PRODUCTO
    $sql = "INSERT INTO PRODUCTO (NOMBRE, DESCRIPCION, CATEGORIA, PRECIO) 
            VALUES (:nombre, :descripcion, :categoria, :precio)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':categoria' => $categoria,
        ':precio' => $precio
    ]);
    
    $producto_id = $conn->lastInsertId();
    
    // 2. Insertar VARIANTE
    $sql = "INSERT INTO VARIANTE (ID_PRODUCTO, ID_TALLA, ID_COLOR, SKU, ID_PROVEEDOR) 
            VALUES (:producto_id, :talla_id, :color_id, :sku, :proveedor_id)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':producto_id' => $producto_id,
        ':talla_id' => $talla_id,
        ':color_id' => $color_id,
        ':sku' => $sku,
        ':proveedor_id' => $proveedor_id
    ]);
    
    $variante_id = $conn->lastInsertId();
    
    // 3. Insertar INVENTARIO
    // Generar ID único
    $next = $conn->query("SELECT COALESCE(MAX(CAST(SUBSTRING(ID_INVENTARIO, 5) AS UNSIGNED)), 0) + 1 as num FROM INVENTARIO");
    $num = $next->fetch(PDO::FETCH_ASSOC)['num'];
    $id_inventario = 'INV-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO INVENTARIO (ID_INVENTARIO, ID_VARIANTE, CANTIDAD, FECHA_ENTRADA) 
            VALUES (:id, :variante_id, :cantidad, CURDATE())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id' => $id_inventario,
        ':variante_id' => $variante_id,
        ':cantidad' => $stock_inicial
    ]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Producto guardado exitosamente',
        'producto_id' => $producto_id
    ]);
    
} catch (PDOException $e) {
    // Revertir en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Mensaje de error específico
    $msg = 'Error al guardar el producto';
    
    if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), 'ID_PROVEEDOR') !== false) {
            $msg = '❌ El proveedor seleccionado no existe';
        } elseif (strpos($e->getMessage(), 'ID_TALLA') !== false) {
            $msg = '❌ La talla seleccionada no existe';
        } elseif (strpos($e->getMessage(), 'ID_COLOR') !== false) {
            $msg = '❌ El color seleccionado no existe';
        } elseif (strpos($e->getMessage(), 'SKU') !== false) {
            $msg = '❌ El SKU ya existe en la base de datos';
        }
    }
    
    echo json_encode(['success' => false, 'message' => $msg]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>