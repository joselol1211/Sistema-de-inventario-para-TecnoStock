<?php
session_start();

// Si ya tiene sesión activa, redirigir al panel
if (isset($_SESSION['id_usuario'])) {
    header("Location: panel.php");
    exit();
}

require_once 'conexion.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo']);
    $clave = $_POST['clave'];

    if ($correo === '' or $clave === '') {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            // Traemos también la columna 'estado' para validar si está habilitado
            $stmt = $conexion->prepare("SELECT id_usuario, nombre, correo, clave, estado FROM Usuario WHERE correo = :correo LIMIT 1");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario and (password_verify($clave, $usuario['clave']) or $clave === $usuario['clave'])) {
                
                // BLOQUEO SI EL USUARIO ESTÁ INACTIVO
                if ($usuario['estado'] !== 'Activo') {
                    $error = "Tu cuenta se encuentra desactivada. Contacta al administrador.";
                } else {
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    header("Location: panel.php");
                    exit();
                }

            } else {
                $error = "Correo electrónico o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error de conexión con la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-container {
            max-width: 920px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(17, 7, 107, 0.14);
            border: 1px solid #e2e8f0;
        }
        .brand-panel {
            background: linear-gradient(155deg, #11076b 0%, #170c85 55%, #09043a 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            min-height: 460px;
        }
        .brand-panel::before {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, rgba(250, 204, 21, 0.08) 45%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }
        .logo-box {
            width: 96px;
            height: 96px;
            background-color: #facc15;
            color: #0f172a;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.35);
            margin: 0 auto 1.5rem auto;
            position: relative;
            z-index: 2;
        }
        .brand-title {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.1;
            position: relative;
            z-index: 2;
        }
        .brand-subtitle {
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.95rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }
        .form-panel {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: #11076b;
            box-shadow: 0 0 0 0.25rem rgba(17, 7, 107, 0.12);
        }
        .input-group-text {
            border-radius: 12px;
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }
        .btn-login {
            background-color: #11076b;
            border-color: #11076b;
            color: #ffffff;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background-color: #1d0fa3;
            border-color: #1d0fa3;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(17, 7, 107, 0.25);
        }
        @media (max-width: 767px) {
            .brand-panel { min-height: 260px; padding: 2rem; }
            .logo-box { width: 72px; height: 72px; font-size: 2.3rem; margin-bottom: 1rem; }
            .brand-title { font-size: 2.1rem; }
            .form-panel { padding: 2rem; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="row g-0">
            
            <!-- Columna Izquierda: Logo en grande -->
            <div class="col-md-6 brand-panel">
                <div>
                    <div class="logo-box">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="brand-title">TecnoStock</div>
                    <div class="brand-subtitle">Gestión de Inventario</div>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Acceso -->
            <div class="col-md-6 form-panel">
                <div class="mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-pill mb-2">ACCESO AL SISTEMA</span>
                    <h3 class="fw-bold text-dark mb-1">Bienvenido de nuevo</h3>
                    <p class="text-muted small">Ingresa tus credenciales para acceder al panel de bodega</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php">
                    <div class="mb-3">
                        <label for="correo" class="form-label small fw-bold text-secondary">CORREO ELECTRÓNICO</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" id="correo" name="correo" required placeholder="admin@tecnostock.cl" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="clave" class="form-label small fw-bold text-secondary">CONTRASEÑA</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="clave" name="clave" required placeholder="••••••••">
                            <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" id="togglePass" style="border-color: #cbd5e1; border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                                <i class="bi bi-eye" id="iconoOjo"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-login btn-lg">
                            Ingresar al Panel <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePass = document.getElementById('togglePass');
        const inputPass = document.getElementById('clave');
        const iconoOjo = document.getElementById('iconoOjo');

        togglePass.addEventListener('click', function () {
            const esPassword = inputPass.getAttribute('type') === 'password';
            inputPass.setAttribute('type', esPassword ? 'text' : 'password');
            iconoOjo.className = esPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
</body>
</html>