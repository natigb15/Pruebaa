<?php
// get_employees.php - Obtener lista de empleados
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
    
    $sql = "SELECT 
                e.ID_EMPLEADO,
                e.NOMBRE,
                e.APELLIDO,
                e.EMAIL,
                e.PUESTO,
                d.NOMBRE_DEPARTAMENTO as departamento,
                e.FECHA_CONTRATACION
            FROM EMPLEADO e
            LEFT JOIN DEPARTAMENTO d ON e.ID_DEPARTAMENTO = d.ID_DEPARTAMENTO
            ORDER BY e.APELLIDO, e.NOMBRE";
    
    $empleados = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>