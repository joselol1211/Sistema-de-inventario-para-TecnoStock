<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header("Location: panel.php");
    exit();
}

$id_producto = (int)$_GET['id'];

$stmtProd = $conexion->prepare("SELECT * FROM Producto WHERE id_producto = :id");
$stmtProd->execute([':id' => $id_producto]);
$producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: panel.php?mensaje=" . urlencode("El producto solicitado no existe."));
    exit();
}

$stmtCat = $conexion->query("SELECT id_categoria, nombre FROM Categoria WHERE estado = 'Activo' ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = strtoupper(trim($_POST['codigo']));
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $id_categoria = $_POST['id_categoria'];
    $precio = $_POST['precio'];
    $stock_minimo = $_POST['stock_minimo'];

    if ($codigo === '' or $nombre === '' or $id_categoria === '') {
        $errores[] = "El código, el nombre y la categoría son obligatorios.";
    }
    if (!is_numeric($precio) or $precio < 0) {
        $errores[] = "El precio debe ser un número mayor o igual a 0.";
    }
    if (!is_numeric($stock_minimo) or $stock_minimo < 0) {
        $errores[] = "El stock mínimo no puede ser negativo.";
    }

    if (empty($errores)) {
        $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM Producto WHERE codigo = :codigo AND id_producto != :id");
        $stmtCheck->execute([':codigo' => $codigo, ':id' => $id_producto]);
        if ($stmtCheck->fetchColumn() > 0) {
            $errores[] = "El código '$codigo' ya pertenece a otro producto.";
        }
    }

    if (empty($errores)) {
        try {
            $sql = "UPDATE Producto 
                    SET codigo = :codigo, 
                        nombre = :nombre, 
                        descripcion = :descripcion, 
                        precio = :precio, 
                        stock_minimo = :stock_minimo, 
                        id_categoria = :id_categoria 
                    WHERE id_producto = :id";

            $stmtUpd = $conexion->prepare($sql);
            $stmtUpd->execute([
                ':codigo'       => $codigo,
                ':nombre'       => $nombre,
                ':descripcion'  => $descripcion,
                ':precio'       => $precio,
                ':stock_minimo' => $stock_minimo,
                ':id_categoria' => $id_categoria,
                ':id'           => $id_producto
            ]);

            header("Location: panel.php?mensaje=" . urlencode("Producto '$nombre' actualizado correctamente."));
            exit();

        } catch (PDOException $e) {
            $errores[] = "Error al actualizar en la base de datos: " . $e->getMessage();
        }
    } else {
        $producto['codigo'] = $codigo;
        $producto['nombre'] = $nombre;
        $producto['descripcion'] = $descripcion;
        $producto['id_categoria'] = $id_categoria;
        $producto['precio'] = $precio;
        $producto['stock_minimo'] = $stock_minimo;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Editar Producto</title>
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
        .section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #11076b;
            font-weight: 700;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
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
        .preview-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }
        @media (max-width: 991px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 1rem; }
            .preview-card { position: static; }
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
            <a href="panel.php" class="nav-link-custom active">
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
                    <div class="text-white-50" style="font-size: 0.75rem;">Administrador</div>
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
                        <li class="breadcrumb-item active">Editar #<?php echo htmlspecialchars($producto['codigo']); ?></li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-dark mb-0">Modificar Producto</h3>
            </div>
            <a href="panel.php" class="btn btn-white border bg-white shadow-sm rounded-3 px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger rounded-3 shadow-sm border-0">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i>Corrige los siguientes detalles:</div>
                <ul class="mb-0 small">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="editar_producto.php?id=<?php echo $id_producto; ?>">
            <div class="row g-4">
                
                <div class="col-lg-8">
                    
                    <div class="section-card shadow-sm mb-4">
                        <div class="section-title">
                            <i class="bi bi-pencil-square fs-5"></i> 1. Datos Generales
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="codigo" class="form-label small fw-bold text-secondary">CÓDIGO SKU *</label>
                                <input type="text" class="form-control text-uppercase fw-semibold" id="codigo" name="codigo" required value="<?php echo htmlspecialchars($producto['codigo']); ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="nombre" class="form-label small fw-bold text-secondary">NOMBRE DEL PRODUCTO *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_categoria" class="form-label small fw-bold text-secondary">CATEGORÍA *</label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo ($producto['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="precio" class="form-label small fw-bold text-secondary">PRECIO UNITARIO (CLP) *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">$</span>
                                    <input type="number" class="form-control" id="precio" name="precio" min="0" step="1" required value="<?php echo htmlspecialchars($producto['precio']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="descripcion" class="form-label small fw-bold text-secondary">DESCRIPCIÓN TÉCNICA</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                        </div>
                    </div>

                    <div class="section-card shadow-sm">
                        <div class="section-title">
                            <i class="bi bi-sliders fs-5"></i> 2. Control de Existencias
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">STOCK ACTUAL EN BODEGA</label>
                                <input type="text" class="form-control bg-light fw-bold" value="<?php echo $producto['stock_actual']; ?> unidades" disabled>
                                <div class="form-text">Para modificar el stock físico usa <a href="registrar_movimiento.php">Entradas y Salidas</a>.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="stock_minimo" class="form-label small fw-bold text-secondary">UMBRAL DE ALERTA (MÍNIMO) *</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" required value="<?php echo htmlspecialchars($producto['stock_minimo']); ?>">
                                <div class="form-text">Activa aviso rojo si el stock baja de este número.</div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="preview-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1">VISTA PREVIA</span>
                            <span class="<?php echo $producto['estado'] === 'Activo' ? 'text-success' : 'text-muted'; ?> small fw-bold">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?php echo $producto['estado']; ?>
                            </span>
                        </div>

                        <div class="p-3 bg-white rounded-3 border mb-3">
                            <div class="text-muted small fw-bold mb-1" id="prevCodigo">#<?php echo htmlspecialchars($producto['codigo']); ?></div>
                            <h5 class="fw-bold text-dark mb-1" id="prevNombre"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <span class="badge bg-light text-dark border mb-3" id="prevCategoria">Categoría</span>
                            
                            <div class="d-flex justify-content-between align-items-end border-top pt-2">
                                <div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Precio Unitario</div>
                                    <div class="fs-5 fw-bold text-dark" id="prevPrecio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted" style="font-size: 0.75rem;">Existencias</div>
                                    <div class="fw-bold" id="prevStock"><?php echo $producto['stock_actual']; ?> / mín. <?php echo $producto['stock_minimo']; ?></div>
                                </div>
                            </div>
                            <div id="prevAlerta" class="mt-2 d-none">
                                <span class="badge bg-danger w-100 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Stock Crítico</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-semibold" style="background-color: #11076b; border-color: #11076b;">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                            <a href="panel.php" class="btn btn-light border rounded-3 text-muted">Cancelar</a>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const stockActualFijo = <?php echo (int)$producto['stock_actual']; ?>;
        const inputCodigo = document.getElementById('codigo');
        const inputNombre = document.getElementById('nombre');
        const selectCat = document.getElementById('id_categoria');
        const inputPrecio = document.getElementById('precio');
        const inputMinimo = document.getElementById('stock_minimo');

        function actualizarVistaPrevia() {
            document.getElementById('prevCodigo').textContent = '#' + (inputCodigo.value.trim().toUpperCase() || 'SIN-CODIGO');
            document.getElementById('prevNombre').textContent = inputNombre.value.trim() || 'Nombre del Producto';
            
            const catTexto = selectCat.options[selectCat.selectedIndex]?.text || 'Sin categoría';
            document.getElementById('prevCategoria').textContent = catTexto;

            const precioVal = parseInt(inputPrecio.value) || 0;
            document.getElementById('prevPrecio').textContent = '$' + precioVal.toLocaleString('es-CL');

            const minimo = parseInt(inputMinimo.value) || 0;
            const prevStockEl = document.getElementById('prevStock');
            const prevAlertaEl = document.getElementById('prevAlerta');

            prevStockEl.textContent = `${stockActualFijo} / mín. ${minimo}`;
            
            if (stockActualFijo < minimo) {
                prevStockEl.className = 'fw-bold text-danger';
                prevAlertaEl.classList.remove('d-none');
            } else {
                prevStockEl.className = 'fw-bold text-dark';
                prevAlertaEl.classList.add('d-none');
            }
        }

        [inputCodigo, inputNombre, selectCat, inputPrecio, inputMinimo].forEach(el => {
            el.addEventListener('input', actualizarVistaPrevia);
            el.addEventListener('change', actualizarVistaPrevia);
        });

        actualizarVistaPrevia();
    </script>
</body>
</html>