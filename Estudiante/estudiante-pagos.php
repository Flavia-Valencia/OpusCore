<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/conexion.php';

$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre, u.correo
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$estudiante = $resultado->fetch_assoc();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = (int) $estudiante['id'];
$pagosRealizados = [];

$stmt = $conexion->prepare("
    SELECT 
        p.id AS pago_id,
        p.idTransaccionPasarela,
        p.estado AS estado_pago,
        p.fechaPago,
        p.monto,
        mp.nombre AS metodo_pago
    FROM pagos p
    INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
    WHERE p.idEstudiante = ? AND p.estado = 'Completado'
    ORDER BY p.fechaPago DESC
");
$stmt->bind_param('i', $idEstudiante);
$stmt->execute();
$pagosRealizados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalPagado = array_sum(array_map(fn($pago) => (float) $pago['monto'], $pagosRealizados));

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Pagos realizados</title>
    <link rel="icon" type="image/svg+xml" href="../img/logo.svg">
    <link rel="stylesheet" href="../css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="../img/logo.svg" alt="Logo" class="logo-img">
                <span class="sidebar-brand logo-text-sidebar"><span>Academia</span><strong>Futuro Digital</strong></span>
                <div class="menu-user">
                    <div class="menu-user-role">Estudiante</div>
                    <div class="menu-user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></div>
                </div>
                <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Cerrar menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="estudiante-cursos.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Todos los cursos</span>
                </a>
                <a href="vista_mis_cursos.php" class="nav-item">
                    <i class="fas fa-book-open"></i>
                    <span>Mis cursos</span>
                </a>
                <a href="estudiante-inscripciones.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Inscripción</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Calificaciones</span>
                </a>
                <div class="nav-dropdown open">
                    <button type="button" class="nav-item nav-dropdown-toggle active" onclick="togglePagosOnline()" aria-expanded="true" aria-controls="pagosOnlineMenu">
                        <i class="fas fa-credit-card"></i>
                        <span>Pagos en línea</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </button>
                    <div class="nav-submenu" id="pagosOnlineMenu">
                        <a href="estudiante-pagos.php" class="active">Pagos realizados</a>
                        <a href="estudiante-tramites-pendientes.php">Trámites pendientes</a>
                    </div>
                </div>
                <a href="#" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Mensajes</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-gear"></i>
                    <span>Configuración</span>
                </a>
            </nav>

            <a href="../includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Cerrar sesion</span>
            </a>
        </aside>

        <div class="content">
            <header class="header-panel">
                <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="../includes/logout.php" class="user-profile-panel">
                    <div class="user-info">
                        <span class="user-role">Estudiante</span>
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <div class="banner">
                <div class="banner-left">
                    <h1>Pagos realizados</h1>
                    <p>Historial de transacciones completadas y comprobantes disponibles.</p>
                </div>
                <div class="banner-fecha">
                    <strong><?= date('d/m/Y') ?></strong>
                </div>
            </div>

            <section class="pagos-resumen">
                <article class="pago-resumen-card">
                    <span>Total pagado</span>
                    <strong>$<?= number_format($totalPagado, 2) ?></strong>
                </article>
                <article class="pago-resumen-card">
                    <span>Pagos completados</span>
                    <strong><?= count($pagosRealizados) ?></strong>
                </article>
                <article class="pago-resumen-card">
                    <span>Comprobantes</span>
                    <strong><?= count($pagosRealizados) ?></strong>
                </article>
            </section>

            <section class="pagos-panel">
                <div class="pagos-panel-header">
                    <div>
                        <h2>Historial de pagos</h2>
                        <small><?= htmlspecialchars($estudiante['estudiante_nombre']) ?></small>
                    </div>
                    <a class="pago-accion pago-accion-secundaria" href="estudiante-tramites-pendientes.php">
                        Trámites pendientes
                    </a>
                </div>

                <?php if (empty($pagosRealizados)): ?>
                    <div class="inscripcion-vacia pagos-vacio">
                        <i class="fas fa-receipt"></i>
                        <p>No tienes pagos completados.</p>
                        <small>Cuando se registre un pago aprobado, aparecerá en este historial.</small>
                    </div>
                <?php else: ?>
                    <div class="pagos-tabla-wrap">
                        <table class="pagos-tabla pagos-tabla-admin">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Curso</th>
                                    <th>Periodo</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Comprobante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagosRealizados as $pago): ?>
                                    <?php
                                        $codigo = $pago['idTransaccionPasarela'] ?: 'PAY-' . str_pad((string) $pago['pago_id'], 5, '0', STR_PAD_LEFT);
                                        $fechaPago = $pago['fechaPago'] ? date('d/m/Y', strtotime($pago['fechaPago'])) : 'Por definir';
                                    ?>
                                    <tr>
                                        <td data-label="Código"><?= htmlspecialchars($codigo) ?></td>
                                        <td data-label="Curso">"Espera Factura Electronica"</td> <!-- Esto se modificará cuando se implemente la facturación electronica-->
                                        <td data-label="Periodo">Espera Factura Electronica</td> <!-- Esto se modificará cuando se implemente la facturación electronica-->
                                        <td data-label="Monto">$<?= number_format((float) $pago['monto'], 2) ?></td>
                                        <td data-label="Método"><?= htmlspecialchars($pago['metodo_pago'] ?: 'PayPal') ?></td>
                                        <td data-label="Fecha"><?= htmlspecialchars($fechaPago) ?></td>
                                        <td data-label="Estado">
                                            <span class="pago-estado-badge estado-completado">
                                                <?= htmlspecialchars($pago['estado_pago']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Comprobante">
                                            <a class="pago-accion" href="comprobantes/descargar-comprobante-pago.php?pago_id=<?= urlencode($pago['pago_id']) ?>">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/utilidades/toast.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
