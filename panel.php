<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$stmtCat = $conexion->query("SELECT id_categoria, nombre FROM Categoria WHERE estado = 'Activo'");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';

$sql = "SELECT p.*, c.nombre AS nombre_categoria 
        FROM Producto p 
        INNER JOIN Categoria c ON p.id_categoria = c.id_categoria 
        WHERE 1=1";

$parametros = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda)";
    $parametros[':busqueda'] = "%$busqueda%";
}

if ($categoria_filtro !== '') {
    $sql .= " AND p.id_categoria = :categoria";
    $parametros[':categoria'] = $categoria_filtro;
}

$sql .= " ORDER BY p.estado ASC, p.nombre ASC";

$stmtProd = $conexion->prepare($sql);
$stmtProd->execute($parametros);
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

$total_activos = 0;
$total_alertas = 0;
$unidades_totales = 0;
$valor_inventario = 0;

foreach ($productos as $p) {
    if ($p['estado'] === 'Activo') {
        $total_activos++;
        $unidades_totales += $p['stock_actual'];
        $valor_inventario += ($p['stock_actual'] * $p['precio']);
        if ($p['stock_actual'] < $p['stock_minimo']) {
            $total_alertas++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Workspace</title>
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
        
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Resumen de Bodega</h3>
            <p class="text-muted small mb-0">Monitoreo en tiempo real de productos y niveles críticos</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">CATÁLOGO ACTIVO</span>
                        <i class="bi bi-tag-fill text-success fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-0"><?php echo $total_activos; ?> <span class="fs-6 fw-normal text-muted">ítems</span></h3>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">STOCK FÍSICO</span>
                        <i class="bi bi-archive-fill text-primary fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-0"><?php echo number_format($unidades_totales, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">unid.</span></h3>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">VALOR INVENTARIO</span>
                        <i class="bi bi-cash-stack text-warning fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-0">$<?php echo number_format($valor_inventario, 0, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card <?php echo $total_alertas > 0 ? 'bg-danger bg-opacity-10 border-danger' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold <?php echo $total_alertas > 0 ? 'text-danger' : 'text-muted'; ?>">STOCK CRÍTICO</span>
                        <i class="bi bi-bell-fill text-danger fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-0 <?php echo $total_alertas > 0 ? 'text-danger' : ''; ?>"><?php echo $total_alertas; ?> <span class="fs-6 fw-normal">alertas</span></h3>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_GET['mensaje']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="GET" action="panel.php" class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="buscar" class="form-control border-start-0" placeholder="Filtrar por código o nombre de producto..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($categoria_filtro == $cat['id_categoria']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Buscar</button>
                <?php if ($busqueda !== '' or $categoria_filtro !== ''): ?>
                    <a href="panel.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-wrapper shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio Unit.</th>
                            <th>Existencias</th>
                            <th>Estado</th>
                            <th class="text-end">Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($productos) > 0): ?>
                            <?php foreach ($productos as $prod): ?>
                                <?php 
                                    $bajo_stock = ($prod['stock_actual'] < $prod['stock_minimo']) and ($prod['estado'] === 'Activo');
                                ?>
                                <tr class="<?php echo $bajo_stock ? 'table-danger' : ($prod['estado'] === 'Inactivo' ? 'opacity-50' : ''); ?>">
                                    <td class="fw-bold text-secondary">#<?php echo htmlspecialchars($prod['codigo']); ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 240px;"><?php echo htmlspecialchars($prod['descripcion']); ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($prod['nombre_categoria']); ?></span></td>
                                    <td>$<?php echo number_format($prod['precio'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="fw-bold <?php echo $bajo_stock ? 'text-danger' : 'text-dark'; ?>">
                                            <?php echo $prod['stock_actual']; ?>
                                        </span>
                                        <span class="text-muted small">/ mín. <?php echo $prod['stock_minimo']; ?></span>
                                        <?php if ($bajo_stock): ?>
                                            <div><span class="badge bg-danger mt-1">Reponer stock</span></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($prod['estado'] === 'Activo'): ?>
                                            <span class="text-success small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Activo</span>
                                        <?php else: ?>
                                            <span class="text-muted small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="editar_producto.php?id=<?php echo $prod['id_producto']; ?>" class="btn btn-sm btn-light border me-1" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($prod['estado'] === 'Activo'): ?>
                                            <a href="cambiar_estado.php?id=<?php echo $prod['id_producto']; ?>&estado=Inactivo" class="btn btn-sm btn-light border text-danger" title="Desactivar" onclick="return confirm('¿Desactivar producto?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="cambiar_estado.php?id=<?php echo $prod['id_producto']; ?>&estado=Activo" class="btn btn-sm btn-light border text-success" title="Reactivar">
                                                <i class="bi bi-arrow-up-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No hay productos registrados con esos filtros.</td>
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