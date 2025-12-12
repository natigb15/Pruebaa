<?php
// get_proveedores.php - Obtener lista de proveedores
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
    
    $sql = "SELECT ID_PROVEEDOR, NOMBRE_PROVEEDOR, CONTACTO_EMAIL 
            FROM PROVEEDOR 
            ORDER BY NOMBRE_PROVEEDOR";
    
    $proveedores = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'proveedores' => $proveedores
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>