<?php
require_once 'Config.php';

header('Content-Type: application/json');

try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    // Buscar el nombre correcto de la columna
    $stmt = $conn->query("SHOW COLUMNS FROM COLOR LIKE 'NOMBRE%'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nombre_columna = $column ? $column['Field'] : 'NOMBRE';
    
    $query = "SELECT ID_COLOR as id, $nombre_columna as nombre FROM COLOR";
    $stmt = $conn->query($query);
    $colores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($colores);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>