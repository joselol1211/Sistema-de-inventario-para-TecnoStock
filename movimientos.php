<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$sql = "SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
        FROM Movimiento m
        INNER JOIN Producto p ON m.id_producto = p.id_producto
        INNER JOIN Usuario u ON m.id_usuario = u.id_usuario
        WHERE 1=1";

$parametros = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda OR m.observacion LIKE :busqueda)";
    $parametros[':busqueda'] = "%$busqueda%";
}

if ($tipo_filtro === 'Entrada' or $tipo_filtro === 'Salida') {
    $sql .= " AND m.tipo = :tipo";
    $parametros[':tipo'] = $tipo_filtro;
}

$sql .= " ORDER BY m.id_movimiento DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_entradas = 0;
$total_salidas = 0;
foreach ($movimientos as $m) {
    if ($m['tipo'] === 'Entrada') {
        $total_entradas += $m['cantidad'];
    } else {
        $total_salidas += $m['cantidad'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Historial de Movimientos</title>
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
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
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
            <a href="registrar_movimiento.php" class="nav-link-custom">
                <i class="bi bi-arrow-down-up"></i> Entradas y Salidas
            </a>
            <a href="movimientos.php" class="nav-link-custom active">
                <i class="bi bi-clock-history"></i> Historial Movimientos
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
        
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Historial de Movimientos</h3>
            <p class="text-muted small mb-0">Registro cronológico de todos los ingresos y despachos de bodega</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">TOTAL REGISTROS</span>
                        <i class="bi bi-journal-text text-primary fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-0"><?php echo count($movimientos); ?> <span class="fs-6 fw-normal text-muted">mov.</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">UNIDADES INGRESADAS</span>
                        <i class="bi bi-box-arrow-in-down text-success fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-success mb-0">+<?php echo number_format($total_entradas, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">unid.</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">UNIDADES DESPACHADAS</span>
                        <i class="bi bi-box-arrow-up text-danger fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-danger mb-0">-<?php echo number_format($total_salidas, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">unid.</span></h3>
                </div>
            </div>
        </div>

        <form method="GET" action="movimientos.php" class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="buscar" class="form-control border-start-0" placeholder="Buscar por producto, código u observación..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos (Entradas y Salidas)</option>
                    <option value="Entrada" <?php if ($tipo_filtro === 'Entrada') echo 'selected'; ?>>Solo Entradas</option>
                    <option value="Salida" <?php if ($tipo_filtro === 'Salida') echo 'selected'; ?>>Solo Salidas</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                <?php if ($busqueda !== '' or $tipo_filtro !== ''): ?>
                    <a href="movimientos.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-wrapper shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha y Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Observación</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($movimientos) > 0): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <?php $fecha_reg = $mov['fecha_movimiento'] ?? $mov['fecha'] ?? $mov['fecha_hora'] ?? 'now'; ?>
                                <tr>
                                    <td class="text-muted small">#<?php echo $mov['id_movimiento']; ?></td>
                                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($fecha_reg)); ?></td>
                                    <td>
                                        <?php if ($mov['tipo'] === 'Entrada'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="bi bi-arrow-down-left me-1"></i>Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                                <i class="bi bi-arrow-up-right me-1"></i>Salida
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-secondary">#<?php echo htmlspecialchars($mov['codigo']); ?></span>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($mov['producto_nombre']); ?></span>
                                    </td>
                                    <td class="fw-bold fs-6 <?php echo $mov['tipo'] === 'Entrada' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($mov['tipo'] === 'Entrada' ? '+' : '-') . $mov['cantidad']; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['observacion']); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($mov['usuario_nombre']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No se encontraron movimientos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>