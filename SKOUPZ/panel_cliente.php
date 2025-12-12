<?php
session_start();

// Verificar que esté logueado y sea empleado
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'empleado') {
    header('Location: auth.html');
    exit;
}

require_once 'Config.php';

$conectar = new Conectar();
$conn = $conectar->Conexion();

$nombreEmpleado = $_SESSION['user_name'];
$puestoEmpleado = $_SESSION['user_puesto'] ?? 'Empleado';
$departamento = $_SESSION['user_departamento'] ?? 'General';

// Obtener pedidos pendientes
$sqlPedidosPendientes = "SELECT COUNT(*) as total FROM PEDIDO WHERE ID_ESTADO IN ('E01', 'E02')";
$stmtPendientes = $conn->query($sqlPedidosPendientes);
$pedidosPendientes = $stmtPendientes->fetch()['total'];

// Obtener pedidos completados hoy
$sqlPedidosHoy = "SELECT COUNT(*) as total FROM PEDIDO WHERE ID_ESTADO = 'E04' AND CONVERT(DATE, FECHA_PEDIDO) = CONVERT(DATE, GETDATE())";
$stmtHoy = $conn->query($sqlPedidosHoy);
$pedidosHoy = $stmtHoy->fetch()['total'];

// Obtener productos con stock bajo
$sqlStockBajo = "SELECT COUNT(*) as total FROM INVENTARIO WHERE CANTIDAD < 10";
$stmtStock = $conn->query($sqlStockBajo);
$stockBajo = $stmtStock->fetch()['total'];

// Obtener todos los pedidos para gestión
$sqlPedidos = "SELECT TOP 50
                    p.ID_PEDIDO,
                    c.NOMBRE as CLIENTE_NOMBRE,
                    c.APELLIDO as CLIENTE_APELLIDO,
                    p.FECHA_PEDIDO,
                    p.TOTAL,
                    e.NOMBRE_ESTADO
                FROM PEDIDO p
                JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                JOIN ESTADO e ON p.ID_ESTADO = e.ID_ESTADO
                ORDER BY p.FECHA_PEDIDO DESC";
$stmtPedidos = $conn->query($sqlPedidos);
$pedidos = $stmtPedidos->fetchAll();

// Obtener inventario
$sqlInventario = "SELECT 
                    p.NOMBRE,
                    v.SKU,
                    p.CATEGORIA,
                    p.PRECIO,
                    i.CANTIDAD,
                    t.ABREVIATURA as TALLA,
                    c.NOMBRE as COLOR
                FROM INVENTARIO i
                JOIN VARIANTE v ON i.ID_VARIANTE = v.ID_VARIANTE
                JOIN PRODUCTO p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                LEFT JOIN TALLA t ON v.ID_TALLA = t.ID_TALLA
                LEFT JOIN COLOR c ON v.ID_COLOR = c.ID_COLOR
                ORDER BY p.NOMBRE, i.CANTIDAD ASC";
$stmtInventario = $conn->query($sqlInventario);
$inventario = $stmtInventario->fetchAll();

// Obtener clientes
$sqlClientes = "SELECT 
                    ID_CLIENTE,
                    NOMBRE,
                    APELLIDO,
                    EMAIL,
                    TELEFONO_CLIENTE,
                    FECHA_REGISTRO
                FROM CLIENTE
                ORDER BY FECHA_REGISTRO DESC";
$stmtClientes = $conn->query($sqlClientes);
$clientes = $stmtClientes->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Empleado - Skoupz</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            color: #1a1a1a;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: #2d2d2d;
            color: white;
            padding: 30px 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .logo-admin {
            padding: 0 30px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }

        .logo-admin h1 {
            font-size: 28px;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -1px;
        }

        .logo-admin p {
            font-size: 13px;
            opacity: 0.6;
            margin-top: 5px;
            font-weight: 500;
        }

        .employee-badge {
            background: rgba(91, 156, 197, 0.2);
            color: #5b9cc5;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .menu-section {
            margin-bottom: 30px;
        }

        .menu-title {
            padding: 0 30px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .menu-item {
            padding: 12px 30px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.05);
            border-left-color: #5b9cc5;
        }

        .menu-item.active {
            background: rgba(91, 156, 197, 0.15);
            border-left-color: #5b9cc5;
            color: #5b9cc5;
        }

        .menu-item .material-symbols-outlined {
            font-size: 22px;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px 40px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .header-left h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #6c757d;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5b9cc5, #2d2d2d);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #5b9cc5, #2d2d2d);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: rgba(91, 156, 197, 0.1);
            color: #5b9cc5;
        }

        .stat-card:nth-child(2) .stat-icon {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .section-header h3 {
            font-size: 20px;
            font-weight: 700;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #f8f9fa;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .data-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }

        .badge-processing {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }

        .badge-completed {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .badge-low {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }

        .badge-ok {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .info-card {
            background: linear-gradient(135deg, #5b9cc5, #2d2d2d);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .info-card h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .view-content {
            display: none;
        }

        .view-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-admin">
            <h1>Skoupz</h1>
            <p>Panel de Empleado</p>
            <span class="employee-badge">🔷 <?php echo htmlspecialchars($departamento); ?></span>
        </div>

        <div class="menu-section">
            <div class="menu-title">Principal</div>
            <div class="menu-item active" data-section="dashboard">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </div>
            <div class="menu-item" data-section="orders">
                <span class="material-symbols-outlined">shopping_bag</span>
                <span>Pedidos</span>
            </div>
            <div class="menu-item" data-section="inventory">
                <span class="material-symbols-outlined">inventory_2</span>
                <span>Inventario</span>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">Clientes</div>
            <div class="menu-item" data-section="customers">
                <span class="material-symbols-outlined">group</span>
                <span>Clientes</span>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">Cuenta</div>
            <div class="menu-item" onclick="logout()">
                <span class="material-symbols-outlined">logout</span>
                <span>Cerrar Sesión</span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <h2 id="page-title">Dashboard</h2>
                <div class="breadcrumb">Panel de Empleado / <span id="breadcrumb-current">Dashboard</span></div>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($nombreEmpleado, 0, 2)); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($nombreEmpleado); ?></div>
                        <div style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($puestoEmpleado); ?></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard View -->
        <div id="dashboard-view" class="view-content active">
            <div class="info-card">
                <h3>¡Bienvenido, <?php echo htmlspecialchars(explode(' ', $nombreEmpleado)[0]); ?>! 👋</h3>
                <p>Tienes <?php echo $pedidosPendientes; ?> pedidos pendientes de procesar.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-outlined">shopping_bag</span>
                    </div>
                    <div class="stat-value"><?php echo $pedidosPendientes; ?></div>
                    <div class="stat-label">Pedidos Pendientes</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <div class="stat-value"><?php echo $pedidosHoy; ?></div>
                    <div class="stat-label">Completados Hoy</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-outlined">inventory</span>
                    </div>
                    <div class="stat-value"><?php echo $stockBajo; ?></div>
                    <div class="stat-label">Stock Bajo</div>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3>Actividad Reciente</h3>
                </div>
                <p style="color: #6c757d; text-align: center; padding: 40px 0;">Selecciona una sección del menú para comenzar</p>
            </div>
        </div>

        <!-- Orders View -->
        <div id="orders-view" class="view-content">
            <div class="content-section">
                <div class="section-header">
                    <h3>Gestión de Pedidos</h3>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Orden #</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): 
                            $badgeClass = 'badge-pending';
                            if (strpos($pedido['NOMBRE_ESTADO'], 'PREPARACIÓN') !== false) $badgeClass = 'badge-processing';
                            if (strpos($pedido['NOMBRE_ESTADO'], 'ENTREGADO') !== false) $badgeClass = 'badge-completed';
                        ?>
                            <tr>
                                <td><strong><?php echo $pedido['ID_PEDIDO']; ?></strong></td>
                                <td><?php echo $pedido['CLIENTE_NOMBRE'] . ' ' . $pedido['CLIENTE_APELLIDO']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pedido['FECHA_PEDIDO'])); ?></td>
                                <td><strong>$<?php echo number_format($pedido['TOTAL'], 2); ?></strong></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $pedido['NOMBRE_ESTADO']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory View -->
        <div id="inventory-view" class="view-content">
            <div class="content-section">
                <div class="section-header">
                    <h3>Consulta de Inventario</h3>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventario as $item): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 3px;"><?php echo $item['NOMBRE']; ?></div>
                                    <div style="font-size: 13px; color: #6c757d;"><?php echo $item['TALLA'] . ' • ' . $item['COLOR']; ?></div>
                                </td>
                                <td><?php echo $item['SKU']; ?></td>
                                <td><?php echo $item['CATEGORIA']; ?></td>
                                <td>$<?php echo number_format($item['PRECIO'], 2); ?></td>
                                <td><strong><?php echo $item['CANTIDAD']; ?> unidades</strong></td>
                                <td>
                                    <span class="badge <?php echo $item['CANTIDAD'] < 10 ? 'badge-low' : 'badge-ok'; ?>">
                                        <?php echo $item['CANTIDAD'] < 10 ? 'Stock Bajo' : 'Disponible'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customers View -->
        <div id="customers-view" class="view-content">
            <div class="content-section">
                <div class="section-header">
                    <h3>Lista de Clientes</h3>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo $cliente['NOMBRE'] . ' ' . $cliente['APELLIDO']; ?></div>
                                    <div style="font-size: 13px; color: #6c757d;">ID: <?php echo $cliente['ID_CLIENTE']; ?></div>
                                </td>
                                <td><?php echo $cliente['EMAIL']; ?></td>
                                <td><?php echo $cliente['TELEFONO_CLIENTE']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cliente['FECHA_REGISTRO'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                if (!section) return;
                
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.view-content').forEach(view => view.classList.remove('active'));
                
                const viewId = section + '-view';
                const selectedView = document.getElementById(viewId);
                if (selectedView) {
                    selectedView.classList.add('active');
                }
                
                const titles = {
                    'dashboard': 'Dashboard',
                    'orders': 'Gestión de Pedidos',
                    'inventory': 'Consulta de Inventario',
                    'customers': 'Lista de Clientes'
                };
                
                document.getElementById('page-title').textContent = titles[section] || section;
                document.getElementById('breadcrumb-current').textContent = titles[section] || section;
            });
        });

        function logout() {
            if (confirm('¿Seguro que quieres cerrar sesión?')) {
                fetch('logout.php').then(() => window.location.href = 'index.html');
            }
        }
    </script>
</body>
</html>