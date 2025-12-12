<?php
require_once 'Config.php';

header('Content-Type: application/json');

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // USAR NOMBRE_PROVEEDOR (no solo NOMBRE)
    $stmt = $conn->query("SELECT ID_PROVEEDOR as id, NOMBRE_PROVEEDOR as nombre FROM PROVEEDOR");
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($proveedores);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>