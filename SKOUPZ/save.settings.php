<?php
// save_settings.php - Guardar configuración
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
$company = $_POST['company'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';

// Validaciones básicas
if (empty($company) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Nombre de empresa y email son requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// En un sistema real, guardarías estos datos en una tabla de configuración
// Por ahora, solo confirmamos que se recibieron los datos

echo json_encode([
    'success' => true,
    'message' => 'Configuración guardada exitosamente',
    'data' => [
        'company' => $company,
        'email' => $email,
        'phone' => $phone,
        'address' => $address
    ]
]);

// Para implementación real, crearías una tabla como:
/*
CREATE TABLE CONFIGURACION (
    ID_CONFIG INT PRIMARY KEY,
    NOMBRE_EMPRESA VARCHAR(100),
    EMAIL_CONTACTO VARCHAR(100),
    TELEFONO VARCHAR(20),
    DIRECCION TEXT,
    ULTIMA_ACTUALIZACION DATETIME
);
*/
?>