<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $serverName = "tcp:skoupzdatabase.database.windows.net,1433";
    $database = "skoupz";
    $username = "sqladmin";
    $password = "Skoupz1234";
    
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
        )
    );
    
    echo "✅ Conexión exitosa a Azure SQL Server<br>";
    
    // Verifica si las tablas existen
    $tablas = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas en la base de datos:<br>";
    echo "<pre>";
    print_r($tablas);
    echo "</pre>";
    
    // Verifica estructura de CLIENTE
    echo "Estructura de CLIENTE:<br>";
    $clientes = $conn->query("SELECT TOP 5 ID_CLIENTE, NOMBRE, APELLIDO, EMAIL, CONTRASENA FROM CLIENTE")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($clientes);
    echo "</pre>";
    
    // Verifica estructura de EMPLEADO
    echo "Estructura de EMPLEADO:<br>";
    $empleados = $conn->query("SELECT TOP 5 ID_EMPLEADO, NOMBRE, APELLIDO, EMAIL, CONTRASENA, PUESTO FROM EMPLEADO")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($empleados);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}


?>





