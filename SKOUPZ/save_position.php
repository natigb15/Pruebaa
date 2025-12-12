<?php
// save_position.php - Guardar nuevo cargo
require_once 'Config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$departamento = $_POST['departamento'] ?? '';
$salario = $_POST['salario'] ?? 0;
$descripcion = $_POST['descripcion'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($departamento) || $salario <= 0) {
    echo json_encode(['success' => false, 'message' => 'Por favor complete todos los campos requeridos']);
    exit;
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // En un sistema real, crearías una tabla para cargos como:
    /*
    CREATE TABLE CARGOS (
        ID_CARGO INT PRIMARY KEY,
        NOMBRE_CARGO VARCHAR(100),
        DEPARTAMENTO VARCHAR(100),
        SALARIO_BASE DECIMAL(10,2),
        DESCRIPCION TEXT,
        ESTADO VARCHAR(20) DEFAULT 'Activo'
    );
    */
    
    // Por ahora, solo simulamos el guardado
    echo json_encode([
        'success' => true,
        'message' => 'Cargo creado exitosamente',
        'data' => [
            'nombre' => $nombre,
            'departamento' => $departamento,
            'salario' => $salario,
            'descripcion' => $descripcion
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>