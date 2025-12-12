<?php
// DESACTIVAR errores para producción
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar buffer de salida
if (ob_get_length() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si hay datos POST
if (empty($_POST['email']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos']);
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

try {
    // Incluir configuración - VERIFICA LA RUTA
    $config_path = __DIR__ . '/Config.php';
    if (!file_exists($config_path)) {
        throw new Exception('Archivo de configuración no encontrado');
    }
    
    require_once $config_path;
    
    // Crear conexión
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // PRIMERO: Buscar en CLIENTE
    $sqlCliente = "SELECT ID_CLIENTE, NOMBRE, APELLIDO, EMAIL, CONTRASENA 
                   FROM CLIENTE WHERE EMAIL = ?";
    $stmtCliente = $conn->prepare($sqlCliente);
    $stmtCliente->execute([$email]);
    $cliente = $stmtCliente->fetch();
    
    if ($cliente) {
        // COMPARACIÓN DIRECTA (texto plano)
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
                'redirect' => 'panel_cliente.php'
            ]);
            exit;
        }
    }
    
    // SEGUNDO: Buscar en EMPLEADO
    $sqlEmpleado = "SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.EMAIL, e.CONTRASENA, 
                           e.PUESTO, d.NOMBRE_DEPARTAMENTO
                    FROM EMPLEADO e
                    LEFT JOIN DEPARTAMENTO d ON e.ID_DEPARTAMENTO = d.ID_DEPARTAMENTO
                    WHERE e.EMAIL = ?";
    
    $stmtEmpleado = $conn->prepare($sqlEmpleado);
    $stmtEmpleado->execute([$email]);
    $empleado = $stmtEmpleado->fetch();
    
    if ($empleado) {
        // COMPARACIÓN DIRECTA (texto plano)
        if ($password === $empleado['CONTRASENA']) {
            // Determinar si es ADMIN
            $puesto = strtolower($empleado['PUESTO']);
            $esAdmin = false;
            
            $palabrasAdmin = ['gerente', 'admin', 'administrador', 'jefe', 'director', 'ceo', 'manager'];
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
            $_SESSION['user_departamento'] = $empleado['NOMBRE_DEPARTAMENTO'] ?? 'No asignado';
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
    
    // Si llegamos aquí, credenciales incorrectas
    echo json_encode([
        'success' => false,
        'message' => 'Email o contraseña incorrectos'
    ]);
    
} catch (PDOException $e) {
    // Log del error (pero no mostrar al usuario)
    error_log("Error PDO login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos'
    ]);
} catch (Exception $e) {
    // Log del error
    error_log("Error general login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error inesperado'
    ]);
}
?>