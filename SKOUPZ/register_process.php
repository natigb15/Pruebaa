<?php
// Desactivar TODOS los errores visuales
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier output previo
if (ob_get_length() > 0) {
    ob_clean();
}

// Configurar headers ANTES de cualquier output
header('Content-Type: application/json; charset=utf-8');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Verificar que se recibieron los datos
if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['fullName']) || empty($_POST['phone'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor completa todos los campos'
    ]);
    exit;
}

$fullName = trim($_POST['fullName']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = trim($_POST['password']);

// Separar nombre y apellido
$nameParts = explode(' ', $fullName, 2);
$nombre = $nameParts[0];
$apellido = isset($nameParts[1]) && !empty($nameParts[1]) ? $nameParts[1] : $nombre;

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email inválido'
    ]);
    exit;
}

// Validar longitud de contraseña
if (strlen($password) < 6) { // Reducido a 6 para más flexibilidad
    echo json_encode([
        'success' => false,
        'message' => 'La contraseña debe tener al menos 6 caracteres'
    ]);
    exit;
}

try {
    // Incluir la conexión
    require_once 'Config.php';
    
    // Crear instancia de conexión
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Verificar si el EMAIL ya existe en CLIENTE
    $sqlCheck = "SELECT COUNT(*) as count FROM CLIENTE WHERE EMAIL = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$email]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este email ya está registrado'
        ]);
        exit;
    }
    
    // Obtener el siguiente ID_CLIENTE
    $sqlMaxId = "SELECT ISNULL(MAX(ID_CLIENTE), 100) + 1 as next_id FROM CLIENTE";
    $stmtMaxId = $conn->query($sqlMaxId);
    $nextId = $stmtMaxId->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    // **** CAMBIO IMPORTANTE: GUARDAR EN TEXTO PLANO ****
    // NO usar password_hash, guardar directamente la contraseña
    $passwordPlain = $password; // Guardamos tal cual
    
    // Insertar nuevo CLIENTE usando los nombres exactos de tu BD
    $sql = "INSERT INTO CLIENTE (ID_CLIENTE, NOMBRE, APELLIDO, EMAIL, CONTRASENA, FECHA_REGISTRO, TELEFONO_CLIENTE) 
            VALUES (?, ?, ?, ?, ?, GETDATE(), ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$nextId, $nombre, $apellido, $email, $passwordPlain, $phone])) {
        // Crear sesión automáticamente
        $_SESSION['user_id'] = $nextId;
        $_SESSION['user_name'] = $nombre . ' ' . $apellido;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = 'cliente';
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => '¡Cuenta creada exitosamente!',
            'user_type' => 'cliente',
            'redirect' => 'panel_cliente.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear la cuenta. Intenta nuevamente.'
        ]);
    }
    
} catch (PDOException $e) {
    // Log del error
    error_log("Error registro PDO: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos. Intenta más tarde.'
    ]);
} catch (Exception $e) {
    error_log("Error general registro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado. Intenta nuevamente.'
    ]);
}
?>