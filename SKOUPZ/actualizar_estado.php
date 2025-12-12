<?php
/**
 * actualizar_estado.php - Actualizar estado de pedidos
 * Solo accesible por administradores
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar que sea admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_pedido']) || !isset($data['nuevo_estado'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$idPedido = $data['id_pedido'];
$nuevoEstado = $data['nuevo_estado'];

// Validar que el estado exista
$estadosValidos = ['E01', 'E02', 'E03', 'E04', 'E05', 'E06', 'E07'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

try {
    require_once 'Config.php';
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    $sql = "UPDATE PEDIDO SET ID_ESTADO = ? WHERE ID_PEDIDO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nuevoEstado, $idPedido]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Pedido no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error actualizando estado: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
}
?>