<?php
/**
 * checkout_process.php - Procesar Pedidos Completos
 * Versión corregida para SQL Server (sin LIMIT)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Solo una sesión por script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$cartItems = $data['cart'] ?? [];
$shippingInfo = $data['shipping'] ?? [];
$paymentMethod = $data['payment'] ?? 'card';

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

try {
    require_once 'Config.php';
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    $conn->beginTransaction();
    
    $idCliente = $_SESSION['user_id'];
    
    // 1. CREAR/ACTUALIZAR DIRECCIÓN DE ENVÍO
    // SQL Server usa TOP en lugar de LIMIT
    $sqlCheckDir = "SELECT TOP 1 ID_DIRECCION FROM DIRECCION WHERE ID_CLIENTE = ?";
    $stmtCheckDir = $conn->prepare($sqlCheckDir);
    $stmtCheckDir->execute([$idCliente]);
    $direccionExistente = $stmtCheckDir->fetch();
    
    if ($direccionExistente) {
        $idDireccion = $direccionExistente['ID_DIRECCION'];
        
        // Actualizar dirección existente
        $sqlUpdateDir = "UPDATE DIRECCION SET 
                         DIRECCION = ?, 
                         APARTAMENTO_CASA = ?, 
                         CIUDAD = ?, 
                         PROVINCIA = ?, 
                         CODIGO_POSTAL = ?, 
                         PAIS_ENVIO = ?, 
                         TELEFONO_ENVIO = ? 
                         WHERE ID_DIRECCION = ?";
        $stmtUpdateDir = $conn->prepare($sqlUpdateDir);
        $stmtUpdateDir->execute([
            $shippingInfo['address'] ?? 'N/A',
            $shippingInfo['apartment'] ?? '',
            $shippingInfo['city'] ?? 'N/A',
            $shippingInfo['city'] ?? 'N/A', // Provincia = Ciudad
            $shippingInfo['zip'] ?? '0000',
            $shippingInfo['country'] ?? 'PA',
            $shippingInfo['phone'] ?? '',
            $idDireccion
        ]);
    } else {
        // Crear nueva dirección
        // SQL Server - usar ISNULL en lugar de COALESCE si es necesario
        $sqlMaxDir = "SELECT ISNULL(MAX(CAST(SUBSTRING(ID_DIRECCION, 3) AS INT)), 0) as max_num FROM DIRECCION";
        $stmtMaxDir = $conn->query($sqlMaxDir);
        $maxDirNum = $stmtMaxDir->fetch()['max_num'];
        $nuevoDirNum = $maxDirNum + 1;
        $idDireccion = 'DR' . str_pad($nuevoDirNum, 2, '0', STR_PAD_LEFT);
        
        $sqlInsertDir = "INSERT INTO DIRECCION (ID_DIRECCION, ID_CLIENTE, DIRECCION, APARTAMENTO_CASA, CIUDAD, PROVINCIA, CODIGO_POSTAL, PAIS_ENVIO, TELEFONO_ENVIO) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertDir = $conn->prepare($sqlInsertDir);
        $stmtInsertDir->execute([
            $idDireccion,
            $idCliente,
            $shippingInfo['address'] ?? 'N/A',
            $shippingInfo['apartment'] ?? '',
            $shippingInfo['city'] ?? 'N/A',
            $shippingInfo['city'] ?? 'N/A',
            $shippingInfo['zip'] ?? '0000',
            $shippingInfo['country'] ?? 'PA',
            $shippingInfo['phone'] ?? ''
        ]);
    }
    
    // 2. CALCULAR TOTALES
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $taxRate = 0.07;
    $impuesto = $subtotal * $taxRate;
    $costoEnvio = $subtotal >= 50 ? 0 : 5.00;
    $total = $subtotal + $impuesto + $costoEnvio;
    
    // 3. CREAR PEDIDO
    $sqlMaxPedido = "SELECT ISNULL(MAX(CAST(SUBSTRING(ID_PEDIDO, 2) AS INT)), 100) as max_num FROM PEDIDO";
    $stmtMaxPedido = $conn->query($sqlMaxPedido);
    $maxPedidoNum = $stmtMaxPedido->fetch()['max_num'];
    $nuevoPedidoNum = $maxPedidoNum + 1;
    $idPedido = 'P' . $nuevoPedidoNum;
    
    // Asignar empleado (el primero disponible)
    $sqlEmpleado = "SELECT TOP 1 ID_EMPLEADO FROM EMPLEADO";
    $stmtEmpleado = $conn->query($sqlEmpleado);
    $empleado = $stmtEmpleado->fetch();
    $idEmpleado = $empleado ? $empleado['ID_EMPLEADO'] : 201;
    
    // Método de envío (901 = ESTANDAR)
    $idEnvio = 901;
    
    // Estado inicial (E01 = PENDIENTE PAGO)
    $idEstado = 'E01';
    
    $fechaPedido = date('Y-m-d');
    
    $sqlPedido = "INSERT INTO PEDIDO (ID_PEDIDO, ID_CLIENTE, ID_EMPLEADO, ID_DIRECCION, ID_ENVIO, ID_ESTADO, FECHA_PEDIDO, SUBTOTAL, IMPUESTO, TOTAL) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtPedido = $conn->prepare($sqlPedido);
    $stmtPedido->execute([
        $idPedido,
        $idCliente,
        $idEmpleado,
        $idDireccion,
        $idEnvio,
        $idEstado,
        $fechaPedido,
        $subtotal,
        $impuesto,
        $total
    ]);
    
    // 4. AGREGAR DETALLES DEL PEDIDO
    foreach ($cartItems as $item) {
        // Buscar producto por nombre aproximado
        $sqlProducto = "SELECT TOP 1 ID_PRODUCTO FROM PRODUCTO WHERE NOMBRE LIKE ?";
        $stmtProducto = $conn->prepare($sqlProducto);
        $stmtProducto->execute(['%' . $item['name'] . '%']);
        $producto = $stmtProducto->fetch();
        
        if ($producto) {
            $idProducto = $producto['ID_PRODUCTO'];
            
            // Buscar la primera variante del producto
            $sqlVariante = "SELECT TOP 1 ID_VARIANTE FROM VARIANTE WHERE ID_PRODUCTO = ?";
            $stmtVariante = $conn->prepare($sqlVariante);
            $stmtVariante->execute([$idProducto]);
            $variante = $stmtVariante->fetch();
            
            if ($variante) {
                $idVariante = $variante['ID_VARIANTE'];
                
                // Insertar detalle del pedido
                $sqlDetalle = "INSERT INTO PEDIDO_DETALLE (ID_PEDIDO, ID_VARIANTE, CANTIDAD, PRECIO_UNITARIO) 
                               VALUES (?, ?, ?, ?)";
                $stmtDetalle = $conn->prepare($sqlDetalle);
                $stmtDetalle->execute([
                    $idPedido,
                    $idVariante,
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Actualizar inventario
                $sqlUpdateInv = "UPDATE INVENTARIO 
                                 SET CANTIDAD = CANTIDAD - ? 
                                 WHERE ID_VARIANTE = ?";
                $stmtUpdateInv = $conn->prepare($sqlUpdateInv);
                $stmtUpdateInv->execute([
                    $item['quantity'],
                    $idVariante
                ]);
            }
        }
    }
    
    // 5. REGISTRAR PAGO
    $sqlMaxPago = "SELECT ISNULL(MAX(CAST(SUBSTRING(ID_PAGO, 3) AS INT)), 0) as max_num FROM PAGO";
    $stmtMaxPago = $conn->query($sqlMaxPago);
    $maxPagoNum = $stmtMaxPago->fetch()['max_num'];
    $nuevoPagoNum = $maxPagoNum + 1;
    $idPago = 'PG' . str_pad($nuevoPagoNum, 2, '0', STR_PAD_LEFT);
    
    // Método de pago (401 = VISA, 402 = PayPal, etc.)
    $metodoPagoMap = [
        'card' => 401,
        'paypal' => 402,
        'crypto' => 403
    ];
    $idMetodoPago = $metodoPagoMap[$paymentMethod] ?? 401;
    
    $referenciaTransaccion = 'TRN' . time() . rand(1000, 9999);
    
    $sqlPago = "INSERT INTO PAGO (ID_PAGO, ID_PEDIDO, ID_METODO_PAGO, FECHA_PAGO, MONTO, REFERENCIA_TRANSACCION, ACTIVO) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtPago = $conn->prepare($sqlPago);
    $stmtPago->execute([
        $idPago,
        $idPedido,
        $idMetodoPago,
        $fechaPedido,
        $total,
        $referenciaTransaccion,
        1
    ]);
    
    // Actualizar estado del pedido a "EN PREPARACIÓN"
    $sqlUpdateEstado = "UPDATE PEDIDO SET ID_ESTADO = 'E02' WHERE ID_PEDIDO = ?";
    $stmtUpdateEstado = $conn->prepare($sqlUpdateEstado);
    $stmtUpdateEstado->execute([$idPedido]);
    
    $conn->commit();
    
    // Limpiar carrito después de completar la compra
    unset($_SESSION['cart']);
    
    echo json_encode([
        'success' => true,
        'message' => '¡Pedido realizado exitosamente!',
        'order_number' => $idPedido,
        'total' => number_format($total, 2)
    ]);
    
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Error checkout: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar el pedido: ' . $e->getMessage(),
        'error_details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado']);
}
?>