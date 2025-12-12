<?php
require_once 'Config.php';

$conectar = new Conectar();
$conn = $conectar->Conexion();

// Test con un cliente conocido
$email_cliente = "orl@gmail.com";
$password_test = "12345678"; // Prueba con la contraseña que usas

$sql = "SELECT CONTRASENA FROM CLIENTE WHERE EMAIL = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$email_cliente]);
$result = $stmt->fetch();

echo "<h3>Test para cliente: $email_cliente</h3>";
echo "Hash en BD: " . $result['CONTRASENA'] . "<br>";

// Probar password_verify
if (password_verify($password_test, $result['CONTRASENA'])) {
    echo "<span style='color:green;'>✅ password_verify: CORRECTO</span>";
} else {
    echo "<span style='color:red;'>❌ password_verify: FALLÓ</span>";
}

echo "<hr>";

// Test con un empleado conocido
$email_empleado = "admin@skoupz.com";
$password_test_emp = "empleado123"; // Prueba con esta contraseña común

$sql2 = "SELECT CONTRASENA FROM EMPLEADO WHERE EMAIL = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->execute([$email_empleado]);
$result2 = $stmt2->fetch();

echo "<h3>Test para empleado: $email_empleado</h3>";
echo "Hash en BD: " . $result2['CONTRASENA'] . "<br>";

// Probar password_verify
if (password_verify($password_test_emp, $result2['CONTRASENA'])) {
    echo "<span style='color:green;'>✅ password_verify: CORRECTO</span>";
} else {
    echo "<span style='color:red;'>❌ password_verify: FALLÓ</span>";
    
    // Probar con otra contraseña común
    $common_passwords = ['password', '123456', 'admin', 'empleado123', 'skoupz123'];
    foreach ($common_passwords as $pass) {
        if (password_verify($pass, $result2['CONTRASENA'])) {
            echo "<br><span style='color:orange;'>⚠️ La contraseña es: $pass</span>";
            break;
        }
    }
}
?>