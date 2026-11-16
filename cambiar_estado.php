<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

if (isset($_GET['id']) and is_numeric($_GET['id']) and isset($_GET['estado'])) {
    $id_producto = (int)$_GET['id'];
    $nuevo_estado = $_GET['estado'];

    if ($nuevo_estado === 'Activo' or $nuevo_estado === 'Inactivo') {
        try {
            $stmtNombre = $conexion->prepare("SELECT nombre FROM Producto WHERE id_producto = :id");
            $stmtNombre->execute([':id' => $id_producto]);
            $prod = $stmtNombre->fetch(PDO::FETCH_ASSOC);

            if ($prod) {
                $stmtUpd = $conexion->prepare("UPDATE Producto SET estado = :estado WHERE id_producto = :id");
                $stmtUpd->execute([
                    ':estado' => $nuevo_estado,
                    ':id'     => $id_producto
                ]);

                $accion = ($nuevo_estado === 'Inactivo') ? 'desactivado' : 'reactivado';
                header("Location: panel.php?mensaje=" . urlencode("El producto '{$prod['nombre']}' fue $accion correctamente."));
                exit();
            }
        } catch (PDOException $e) {
            header("Location: panel.php?mensaje=" . urlencode("Error al cambiar el estado del producto."));
            exit();
        }
    }
}

header("Location: panel.php");
exit();
?>