<?php
$host = 'localhost';
$dbname = 'tecnostock';
$username = 'root'; // Usuario por defecto en XAMPP
$password = '';     // Contraseña por defecto en XAMPP (vacía)

try {
    // Se establece la conexión utilizando PDO y forzando la codificación UTF-8
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Se configura PDO para que lance excepciones detalladas si ocurre un error en las consultas
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si la conexión falla (ej. MySQL apagado), detiene el proceso y muestra el error
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>