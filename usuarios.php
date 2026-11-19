<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';


define('CLAVE_MAESTRA_USUARIOS', 'admin1234');

$error_acceso = '';
$errores = [];
$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';

// Verificar si el usuario está enviando la segunda contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] === 'desbloquear') {
    $clave_ingresada = $_POST['clave_maestra'] ?? '';
    if ($clave_ingresada === CLAVE_MAESTRA_USUARIOS) {
        $_SESSION['acceso_usuarios_autorizado'] = true;
    } else {
        $error_acceso = "Contraseña maestra incorrecta. Acceso denegado.";
    }
}


if (isset($_GET['bloquear'])) {
    unset($_SESSION['acceso_usuarios_autorizado']);
    header("Location: usuarios.php");
    exit();
}

$autorizado = isset($_SESSION['acceso_usuarios_autorizado']) && $_SESSION['acceso_usuarios_autorizado'] === true;


if ($autorizado) {

    // 1. AGREGAR NUEVO USUARIO
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = trim($_POST['nombre']);
        $correo = trim($_POST['correo']);
        $clave  = $_POST['clave'];

        if ($nombre === '' or $correo === '' or $clave === '') {
            $errores[] = "Todos los campos son obligatorios para crear un usuario.";
        } elseif (strlen($clave) < 4) {
            $errores[] = "La contraseña debe tener al menos 4 caracteres.";
        } else {
            // Verificar si el correo ya existe
            $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM Usuario WHERE correo = :correo");
            $stmtCheck->execute([':correo' => $correo]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errores[] = "El correo '$correo' ya está registrado.";
            } else {
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmtIns = $conexion->prepare("INSERT INTO Usuario (nombre, correo, clave, estado) VALUES (:nombre, :correo, :clave, 'Activo')");
                $stmtIns->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':clave'  => $clave_hash
                ]);
                header("Location: usuarios.php?mensaje=" . urlencode("Usuario '$nombre' creado correctamente."));
                exit();
            }
        }
    }

  
    if (isset($_GET['cambiar_estado']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_target = (int)$_GET['id'];
        $nuevo_estado = $_GET['cambiar_estado'];

        if ($id_target === (int)$_SESSION['id_usuario']) {
            $errores[] = "No puedes desactivar tu propia cuenta mientras tienes la sesión abierta.";
        } elseif ($nuevo_estado === 'Activo' or $nuevo_estado === 'Inactivo') {
            $stmtUpd = $conexion->prepare("UPDATE Usuario SET estado = :estado WHERE id_usuario = :id");
            $stmtUpd->execute([':estado' => $nuevo_estado, ':id' => $id_target]);
            $texto_est = ($nuevo_estado === 'Activo') ? 'habilitado' : 'desactivado';
            header("Location: usuarios.php?mensaje=" . urlencode("Usuario $texto_est correctamente."));
            exit();
        }
    }

    
    if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
        $id_del = (int)$_GET['eliminar'];

        if ($id_del === (int)$_SESSION['id_usuario']) {
            $errores[] = "No puedes eliminar tu propio usuario en sesión.";
        } else {
            // Comprobar si tiene movimientos asociados en la tabla Movimiento
            $stmtMov = $conexion->prepare("SELECT COUNT(*) FROM Movimiento WHERE id_usuario = :id");
            $stmtMov->execute([':id' => $id_del]);
            if ($stmtMov->fetchColumn() > 0) {
                $errores[] = "No se puede eliminar este usuario porque tiene movimientos registrados en el historial. Desactívalo en su lugar.";
            } else {
                $stmtDel = $conexion->prepare("DELETE FROM Usuario WHERE id_usuario = :id");
                $stmtDel->execute([':id' => $id_del]);
                header("Location: usuarios.php?mensaje=" . urlencode("Usuario eliminado permanentemente."));
                exit();
            }
        }
    }

    
    $usuarios = $conexion->query("SELECT id_usuario, nombre, correo, estado FROM Usuario ORDER BY estado ASC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
        }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #11076b;
            color: #ecfdf5;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 1.5rem;
            font-size: 1.4rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link-custom {
            color: #eaeeec;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .nav-link-custom:hover, .nav-link-custom.active {
            background-color: rgba(255,255,255,0.08);
            color: #ffffff;
            border-left-color: #38bdf8;
        }
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        .section-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.75rem;
        }
        .lock-card {
            max-width: 440px;
            margin: 3rem auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(17, 7, 107, 0.08);
        }
        .table-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
        }
        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .form-control {
            border-radius: 10px;
            border-color: #cbd5e1;
            padding: 0.65rem 0.9rem;
        }
        .form-control:focus {
            border-color: #11076b;
            box-shadow: 0 0 0 0.2rem rgba(17, 7, 107, 0.15);
        }
        @media (max-width: 991px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

    <!-- Menú Lateral Fijo -->
    <aside class="sidebar">
        <a href="panel.php" class="sidebar-brand">
            <i class="bi bi-boxes text-warning"></i>
            <span>TecnoStock</span>
        </a>
        
        <div class="py-3 flex-grow-1">
            <div class="px-4 pb-2 text-uppercase small opacity-50 fw-bold" style="font-size: 0.7rem;">Menú Principal</div>
            <a href="panel.php" class="nav-link-custom">
                <i class="bi bi-grid-1x2-fill"></i> Inventario General
            </a>
            <a href="crear_producto.php" class="nav-link-custom">
                <i class="bi bi-plus-square-fill"></i> Nuevo Producto
            </a>
            <a href="registrar_movimiento.php" class="nav-link-custom">
                <i class="bi bi-arrow-down-up"></i> Entradas y Salidas
            </a>
            <a href="movimientos.php" class="nav-link-custom">
                <i class="bi bi-clock-history"></i> Historial Movimientos
            </a>
            <a href="usuarios.php" class="nav-link-custom active">
                <i class="bi bi-people-fill"></i> Gestión Usuarios
            </a>
        </div>

        <div class="p-3 border-top border-white border-opacity-10">
            <div class="d-flex align-items-center gap-2 mb-3 px-2">
                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate small"><?php echo htmlspecialchars($_SESSION['nombre']); ?></div>
                    <div class="text-white-50" style="font-size: 0.75rem;">Administrador</div>
                </div>
            </div>
            <a href="cerrar_sesion.php" class="btn btn-danger btn-sm w-100 rounded-3 fw-semibold">
                <i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    
    <main class="main-content">

        <?php if (!$autorizado): ?>
            
            <div class="lock-card text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 68px; height: 68px; background-color: rgba(17, 7, 107, 0.1); color: #11076b;">
                    <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Área Restringida</h4>
                <p class="text-muted small mb-4">Ingresa la segunda contraseña de seguridad para administrar las cuentas de usuario.</p>

                <?php if ($error_acceso !== ''): ?>
                    <div class="alert alert-danger border-0 rounded-3 py-2 small mb-3">
                        <i class="bi bi-x-circle-fill me-1"></i><?php echo htmlspecialchars($error_acceso); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="accion" value="desbloquear">
                    <div class="mb-3 text-start">
                        <label for="clave_maestra" class="form-label small fw-bold text-secondary">CONTRASEÑA DE SEGURIDAD</label>
                        <input type="password" class="form-control text-center fw-bold" id="clave_maestra" name="clave_maestra" required autofocus placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold" style="background-color: #11076b; border-color: #11076b;">
                        <i class="bi bi-unlock-fill me-1"></i> Desbloquear Módulo
                    </button>
                </form>
            </div>

        <?php else: ?>
            
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Gestión de Usuarios</h3>
                    <p class="text-muted small mb-0">Administra el acceso, creación y estado de las cuentas del sistema</p>
                </div>
                <a href="usuarios.php?bloquear=1" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
                    <i class="bi bi-lock-fill me-1"></i> Bloquear Acceso
                </a>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger rounded-3 shadow-sm border-0">
                    <ul class="mb-0 small">
                        <?php foreach ($errores as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="section-card shadow-sm">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Nuevo Usuario</h5>
                        <form method="POST" action="usuarios.php">
                            <input type="hidden" name="accion" value="crear">
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label small fw-bold text-secondary">NOMBRE COMPLETO *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej: Camila Rojas">
                            </div>

                            <div class="mb-3">
                                <label for="correo" class="form-label small fw-bold text-secondary">CORREO ELECTRÓNICO *</label>
                                <input type="email" class="form-control" id="correo" name="correo" required placeholder="camila@tecnostock.cl">
                            </div>

                            <div class="mb-4">
                                <label for="clave" class="form-label small fw-bold text-secondary">CONTRASEÑA DE INGRESO *</label>
                                <input type="password" class="form-control" id="clave" name="clave" required placeholder="Mínimo 4 caracteres">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold" style="background-color: #11076b; border-color: #11076b;">
                                <i class="bi bi-check2-circle me-1"></i> Registrar Usuario
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-lg-8">
                    <div class="table-wrapper shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Correo</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                        <?php $es_actual = ((int)$u['id_usuario'] === (int)$_SESSION['id_usuario']); ?>
                                        <tr class="<?php echo $u['estado'] === 'Inactivo' ? 'opacity-50' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width: 34px; height: 34px; background-color: #11076b; font-size: 0.85rem;">
                                                        <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-dark">
                                                            <?php echo htmlspecialchars($u['nombre']); ?>
                                                            <?php if ($es_actual): ?>
                                                                <span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size: 0.7rem;">Tú</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($u['correo']); ?></td>
                                            <td>
                                                <?php if ($u['estado'] === 'Activo'): ?>
                                                    <span class="text-success small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Habilitado</span>
                                                <?php else: ?>
                                                    <span class="text-muted small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Desactivado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if (!$es_actual): ?>
                                                    <?php if ($u['estado'] === 'Activo'): ?>
                                                        <a href="usuarios.php?cambiar_estado=Inactivo&id=<?php echo $u['id_usuario']; ?>" class="btn btn-sm btn-light border text-warning me-1" title="Desactivar acceso" onclick="return confirm('¿Desactivar el acceso de este usuario?');">
                                                            <i class="bi bi-person-dash-fill"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="usuarios.php?cambiar_estado=Activo&id=<?php echo $u['id_usuario']; ?>" class="btn btn-sm btn-light border text-success me-1" title="Habilitar acceso">
                                                            <i class="bi bi-person-check-fill"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="usuarios.php?eliminar=<?php echo $u['id_usuario']; ?>" class="btn btn-sm btn-light border text-danger" title="Eliminar usuario" onclick="return confirm('¿Eliminar permanentemente este usuario?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Sesión actual</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>