<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$stmtProd = $conexion->query("SELECT id_producto, codigo, nombre, stock_actual, stock_minimo FROM Producto WHERE estado = 'Activo' ORDER BY nombre ASC");
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = $_POST['id_producto'];
    $tipo = $_POST['tipo'];
    $cantidad = $_POST['cantidad'];
    $observacion = trim($_POST['observacion']);
    $id_usuario = $_SESSION['id_usuario'];

    if ($id_producto === '' or ($tipo !== 'Entrada' and $tipo !== 'Salida')) {
        $errores[] = "Debes seleccionar un producto y un tipo de movimiento válido.";
    }
    if (!is_numeric($cantidad) or $cantidad <= 0) {
        $errores[] = "La cantidad debe ser un número entero mayor a 0.";
    }
    if ($observacion === '') {
        $errores[] = "Debes indicar la observación o referencia del movimiento.";
    }

    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $stmtStock = $conexion->prepare("SELECT nombre, stock_actual FROM Producto WHERE id_producto = :id FOR UPDATE");
            $stmtStock->execute([':id' => $id_producto]);
            $prodActual = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$prodActual) {
                throw new Exception("El producto seleccionado no existe.");
            }

            $stock_anterior = (int)$prodActual['stock_actual'];
            $cantidad_int = (int)$cantidad;

            if ($tipo === 'Salida' and $cantidad_int > $stock_anterior) {
                throw new Exception("Stock insuficiente para '{$prodActual['nombre']}'. Disponible: $stock_anterior unidades.");
            }

            $nuevo_stock = ($tipo === 'Entrada') ? ($stock_anterior + $cantidad_int) : ($stock_anterior - $cantidad_int);

            $sqlMov = "INSERT INTO Movimiento (tipo, cantidad, observacion, id_producto, id_usuario) 
                       VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)";
            $stmtMov = $conexion->prepare($sqlMov);
            $stmtMov->execute([
                ':tipo'        => $tipo,
                ':cantidad'    => $cantidad_int,
                ':observacion' => $observacion,
                ':id_producto' => $id_producto,
                ':id_usuario'  => $id_usuario
            ]);

            $sqlUpd = "UPDATE Producto SET stock_actual = :nuevo_stock WHERE id_producto = :id_producto";
            $stmtUpd = $conexion->prepare($sqlUpd);
            $stmtUpd->execute([
                ':nuevo_stock' => $nuevo_stock,
                ':id_producto' => $id_producto
            ]);

            $conexion->commit();

            header("Location: panel.php?mensaje=" . urlencode("$tipo de $cantidad_int unid. registrada para '{$prodActual['nombre']}'. Nuevo stock: $nuevo_stock."));
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Entradas y Salidas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f1f5f9;
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
        .preview-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 1.5rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border-color: #cbd5e1;
            padding: 0.65rem 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #11076b;
            box-shadow: 0 0 0 0.2rem rgba(17, 7, 107, 0.15);
        }
        .btn-check:checked + .btn-outline-success {
            background-color: #059669;
            border-color: #059669;
            color: #fff;
        }
        .btn-check:checked + .btn-outline-danger {
            background-color: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }
        @media (max-width: 991px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

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
            <a href="registrar_movimiento.php" class="nav-link-custom active">
                <i class="bi bi-arrow-down-up"></i> Entradas y Salidas
            </a>
            <a href="movimientos.php" class="nav-link-custom">
                <i class="bi bi-clock-history"></i> Historial Movimientos
            </a>
            <a href="usuarios.php" class="nav-link-custom">
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
                </div>
            </div>
            <a href="cerrar_sesion.php" class="btn btn-danger btn-sm w-100 rounded-3 fw-semibold">
                <i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-1">
                        <li class="breadcrumb-item"><a href="panel.php" class="text-decoration-none">Inventario</a></li>
                        <li class="breadcrumb-item active">Entradas y Salidas</li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-dark mb-0">Registro de Entradas y Salidas</h3>
            </div>
            <a href="movimientos.php" class="btn btn-white border bg-white shadow-sm rounded-3 px-3">
                <i class="bi bi-clock-history me-1 text-primary"></i> Ver historial completo
            </a>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger rounded-3 shadow-sm border-0">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i>No se pudo procesar el movimiento:</div>
                <ul class="mb-0 small">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="registrar_movimiento.php">
            <div class="row g-4">
                
                <div class="col-lg-7">
                    <div class="section-card shadow-sm">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary d-block">1. TIPO DE OPERACIÓN *</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipoEntrada" value="Entrada" checked>
                                    <label class="btn btn-outline-success w-100 py-3 fw-bold rounded-3" for="tipoEntrada">
                                        <i class="bi bi-box-arrow-in-down fs-5 d-block mb-1"></i>
                                        ENTRADA (Ingreso)
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipoSalida" value="Salida" <?php echo (isset($_POST['tipo']) and $_POST['tipo'] === 'Salida') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger w-100 py-3 fw-bold rounded-3" for="tipoSalida">
                                        <i class="bi bi-box-arrow-up fs-5 d-block mb-1"></i>
                                        SALIDA (Despacho)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="id_producto" class="form-label small fw-bold text-secondary">2. SELECCIONAR PRODUCTO *</label>
                            <select class="form-select" id="id_producto" name="id_producto" required>
                                <option value="" data-stock="0" data-minimo="0">Elige un producto del catálogo...</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?php echo $prod['id_producto']; ?>" 
                                            data-stock="<?php echo $prod['stock_actual']; ?>"
                                            data-minimo="<?php echo $prod['stock_minimo']; ?>"
                                            <?php echo (isset($_POST['id_producto']) and $_POST['id_producto'] == $prod['id_producto']) ? 'selected' : ''; ?>>
                                        [<?php echo htmlspecialchars($prod['codigo']); ?>] <?php echo htmlspecialchars($prod['nombre']); ?> — (Stock: <?php echo $prod['stock_actual']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="cantidad" class="form-label small fw-bold text-secondary">3. CANTIDAD *</label>
                                <input type="number" class="form-control fw-bold" id="cantidad" name="cantidad" min="1" required value="<?php echo isset($_POST['cantidad']) ? htmlspecialchars($_POST['cantidad']) : '1'; ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="observacion" class="form-label small fw-bold text-secondary">4. OBSERVACIÓN / REFERENCIA *</label>
                                <input type="text" class="form-control" id="observacion" name="observacion" required placeholder="Ej: Reposición factura #402 / Venta mostrador" value="<?php echo isset($_POST['observacion']) ? htmlspecialchars($_POST['observacion']) : ''; ?>">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="preview-card shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1">CÁLCULO EN BODEGA</span>
                                <span class="text-muted small"><i class="bi bi-shield-check me-1"></i>Transacción Segura</span>
                            </div>

                            <div class="p-3 bg-white rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted small">Stock Actual:</span>
                                    <span class="fw-bold fs-5" id="simActual">-</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted small">Operación:</span>
                                    <span class="fw-bold fs-5 text-success" id="simOperacion">+ 1</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2">
                                    <span class="fw-bold text-dark">Stock Resultante:</span>
                                    <span class="fw-bold fs-4 text-primary" id="simFinal">-</span>
                                </div>
                            </div>

                            <div id="simAlerta" class="alert alert-danger small py-2 d-none mb-3">
                                <i class="bi bi-x-circle-fill me-1"></i> La salida supera el stock disponible.
                            </div>
                        </div>

                        <button type="submit" id="btnConfirmar" class="btn btn-primary btn-lg w-100 rounded-3 fw-semibold" style="background-color: #11076b; border-color: #11076b;">
                            <i class="bi bi-check2-circle me-1"></i> Confirmar Movimiento
                        </button>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const selectProd = document.getElementById('id_producto');
        const inputCant = document.getElementById('cantidad');
        const radioEntrada = document.getElementById('tipoEntrada');
        const radioSalida = document.getElementById('tipoSalida');
        const btnConfirmar = document.getElementById('btnConfirmar');

        function simularMovimiento() {
            const opcion = selectProd.options[selectProd.selectedIndex];
            const cant = parseInt(inputCant.value) || 0;
            const esEntrada = radioEntrada.checked;

            const simActual = document.getElementById('simActual');
            const simOperacion = document.getElementById('simOperacion');
            const simFinal = document.getElementById('simFinal');
            const simAlerta = document.getElementById('simAlerta');

            simOperacion.textContent = (esEntrada ? '+ ' : '- ') + cant;
            simOperacion.className = 'fw-bold fs-5 ' + (esEntrada ? 'text-success' : 'text-danger');

            if (!selectProd.value) {
                simActual.textContent = '-';
                simFinal.textContent = '-';
                simAlerta.classList.add('d-none');
                btnConfirmar.disabled = false;
                return;
            }

            const stockActual = parseInt(opcion.getAttribute('data-stock')) || 0;
            const resultado = esEntrada ? (stockActual + cant) : (stockActual - cant);

            simActual.textContent = stockActual + ' unid.';

            if (resultado < 0) {
                simFinal.textContent = resultado + ' unid.';
                simFinal.className = 'fw-bold fs-4 text-danger';
                simAlerta.classList.remove('d-none');
                btnConfirmar.disabled = true;
            } else {
                simFinal.textContent = resultado + ' unid.';
                simFinal.className = 'fw-bold fs-4 text-primary';
                simAlerta.classList.add('d-none');
                btnConfirmar.disabled = false;
            }
        }

        [selectProd, inputCant, radioEntrada, radioSalida].forEach(el => {
            el.addEventListener('change', simularMovimiento);
            el.addEventListener('input', simularMovimiento);
        });

        simularMovimiento();
    </script>
</body>
</html>