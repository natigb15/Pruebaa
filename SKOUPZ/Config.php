<?php
class Conectar {
    protected $dbh;
    
    public function Conexion() {
        try {
            // Conexión a Azure SQL Server
            $serverName = "tcp:skoupzdatabase.database.windows.net,1433";
            $database = "skoupz";
            $username = "sqladmin";
            $password = "Skoupz1234";
            
            $conexion = new PDO(
                "sqlsrv:Server=$serverName;Database=$database",
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );
            
            return $conexion;
        } catch (PDOException $e) {
            // Solo log, no mostrar error crudo
            error_log("Error de conexión BD: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos");
        }
    }
}
?>