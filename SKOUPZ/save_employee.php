<?php
// save_employee.php - Guardar nuevo empleado
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
$apellido = $_POST['apellido'] ?? '';
$email = $_POST['email'] ?? '';
$puesto = $_POST['puesto'] ?? '';
$departamento = $_POST['departamento'] ?? '';
$salario = $_POST['salario'] ?? 0;
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
$contrasena = $_POST['contrasena'] ?? 'empleado123'; // Contraseña por defecto

// Validaciones básicas
if (empty($nombre) || empty($apellido) || empty($email) || empty($puesto)) {
    echo json_encode(['success' => false, 'message' => 'Por favor complete todos los campos requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Si no se proporcionó contraseña, usar email como base
if (empty($contrasena)) {
    $contrasena = strtolower(substr($nombre, 0, 1) . $apellido . '123');
}

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Verificar si email ya existe
    $sql_check = "SELECT COUNT(*) as existe FROM EMPLEADO WHERE EMAIL = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$email]);
    $existe = $stmt_check->fetch();
    
    if ($existe['existe'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email ya está registrado']);
        exit;
    }
    
    // Generar nuevo ID de empleado
    $sql_max_id = "SELECT MAX(ID_EMPLEADO) as max_id FROM EMPLEADO";
    $max_id = $conn->query($sql_max_id)->fetchColumn();
    $nuevo_id = ($max_id ? $max_id + 1 : 201);
    
    // Obtener ID_DEPARTAMENTO basado en el nombre
    $id_departamento = 'D01'; // Default
    if (!empty($departamento)) {
        $sql_dept = "SELECT ID_DEPARTAMENTO FROM DEPARTAMENTO WHERE NOMBRE_DEPARTAMENTO LIKE ?";
        $stmt_dept = $conn->prepare($sql_dept);
        $stmt_dept->execute(['%' . $departamento . '%']);
        $dept = $stmt_dept->fetch();
        if ($dept) {
            $id_departamento = $dept['ID_DEPARTAMENTO'];
        }
    }
    
    // Insertar nuevo empleado
    $sql = "INSERT INTO EMPLEADO (ID_EMPLEADO, NOMBRE, APELLIDO, EMAIL, CONTRASENA, PUESTO, FECHA_CONTRATACION, ID_DEPARTAMENTO) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $nuevo_id,
        $nombre,
        $apellido,
        $email,
        $contrasena,  // ¡AGREGADO!
        $puesto,
        $fecha_inicio,
        $id_departamento
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Registrar en log (si tienes tabla de logs)
        $sql_log = "INSERT INTO LOG_EMPLEADOS (ID_EMPLEADO, ACCION, FECHA, USUARIO) 
                   VALUES (?, 'CREACIÓN', GETDATE(), ?)";
        try {
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->execute([$nuevo_id, $_SESSION['user_name'] ?? 'Admin']);
        } catch (Exception $e) {
            // Si no existe la tabla de logs, ignorar
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Empleado creado exitosamente. Contraseña: ' . $contrasena,
            'id' => $nuevo_id,
            'credenciales' => [
                'email' => $email,
                'password' => $contrasena
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear empleado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>