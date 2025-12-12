<?php
// auth.php - CON LIMPIEZA DE SESIÓN
session_start();

// Limpiar sesión previa completamente
$_SESSION = array();

// Destruir y reiniciar sesión para evitar cache
session_destroy();
session_start();

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar campos
if (empty($_POST['email']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos']);
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

try {
    require_once 'Config.php';
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // 1. Buscar en CLIENTES
    $sqlCliente = "SELECT ID_CLIENTE, NOMBRE, APELLIDO, EMAIL, CONTRASENA 
                   FROM CLIENTE WHERE EMAIL = ?";
    $stmtCliente = $conn->prepare($sqlCliente);
    $stmtCliente->execute([$email]);
    $cliente = $stmtCliente->fetch();
    
    if ($cliente) {
        // Comparación directa (texto plano)
        if ($password === $cliente['CONTRASENA']) {
            // Login exitoso como cliente
            $_SESSION['user_id'] = $cliente['ID_CLIENTE'];
            $_SESSION['user_name'] = $cliente['NOMBRE'] . ' ' . $cliente['APELLIDO'];
            $_SESSION['user_email'] = $cliente['EMAIL'];
            $_SESSION['user_type'] = 'cliente';
            $_SESSION['logged_in'] = true;
            
            echo json_encode([
                'success' => true,
                'message' => '¡Bienvenido, ' . $cliente['NOMBRE'] . '!',
                'user_type' => 'cliente',
                'redirect' => 'index.html'
            ]);
            exit;
        }
    }
    
    // 2. Buscar en EMPLEADOS
    $sqlEmpleado = "SELECT ID_EMPLEADO, NOMBRE, APELLIDO, EMAIL, CONTRASENA, PUESTO 
                    FROM EMPLEADO WHERE EMAIL = ?";
    $stmtEmpleado = $conn->prepare($sqlEmpleado);
    $stmtEmpleado->execute([$email]);
    $empleado = $stmtEmpleado->fetch();
    
    if ($empleado) {
        // Comparación directa (texto plano)
        if ($password === $empleado['CONTRASENA']) {
            // Determinar si es ADMIN
            $puesto = strtolower($empleado['PUESTO']);
            $esAdmin = false;
            
            $palabrasAdmin = ['gerente', 'admin', 'administrador', 'jefe', 'director'];
            foreach ($palabrasAdmin as $palabra) {
                if (strpos($puesto, $palabra) !== false) {
                    $esAdmin = true;
                    break;
                }
            }
            
            $userType = $esAdmin ? 'admin' : 'empleado';
            $redirect = $esAdmin ? 'Admin.html' : 'Empleado.html';
            
            // Configurar sesión
            $_SESSION['user_id'] = $empleado['ID_EMPLEADO'];
            $_SESSION['user_name'] = $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'];
            $_SESSION['user_email'] = $empleado['EMAIL'];
            $_SESSION['user_type'] = $userType;
            $_SESSION['user_puesto'] = $empleado['PUESTO'];
            $_SESSION['logged_in'] = true;
            
            echo json_encode([
                'success' => true,
                'message' => '¡Bienvenido, ' . $empleado['NOMBRE'] . '!',
                'user_type' => $userType,
                'redirect' => $redirect
            ]);
            exit;
        }
    }
    
    // Si no encontró usuario
    echo json_encode([
        'success' => false,
        'message' => 'Email o contraseña incorrectos'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor'
    ]);
}
?>