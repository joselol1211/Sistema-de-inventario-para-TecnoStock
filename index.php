<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">TecnoStock</h3>
                        <p class="text-center text-muted mb-4">Ingresa tus credenciales para acceder al inventario</p>
                        
                        <form action="validar_login.php" method="POST" id="formLogin">
                            
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" required placeholder="ejemplo@tecnostock.cl">
                            </div>
                            
                            <div class="mb-4">
                                <label for="clave" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="clave" name="clave" required placeholder="********">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Iniciar Sesión</button>
                            </div>

                            
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger mt-3 text-center" role="alert">
                                    <?php 
                                        if ($_GET['error'] == 'credenciales') {
                                            echo "Correo o contraseña incorrectos.";
                                        } elseif ($_GET['error'] == 'inactivo') {
                                            echo "Tu cuenta se encuentra desactivada. Contacta al administrador.";
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <script>
        document.getElementById('formLogin').addEventListener('submit', function(e) {
            const correo = document.getElementById('correo').value.trim();
            const clave = document.getElementById('clave').value.trim();

            if (correo === '' || clave === '') {
                e.preventDefault();
                alert('Por favor, completa todos los campos sin dejar espacios en blanco.');
            }
        });
    </script>
</body>
</html>