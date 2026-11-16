<?php

session_start();

require_once 'conexion.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    $correo = trim($_POST['correo']);
    $clave = trim($_POST['clave']);

    try {
        
        $sql = "SELECT id_usuario, nombre, clave, estado FROM Usuario WHERE correo = :correo";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    
        if ($usuario && $usuario['clave'] === $clave) {
            
            if ($usuario['estado'] === 'Activo') {
                
               
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre'];
                
    
                header("Location: panel.php");
                exit();
                
            } else {
                header("Location: index.php?error=inactivo");
                exit();
            }
        } else {
           
            header("Location: index.php?error=credenciales");
            exit();
        }

    } catch (PDOException $e) {
        
        die("Error en la consulta: " . $e->getMessage());
    }
} else {
   
    header("Location: index.php");
    exit();
}
?>