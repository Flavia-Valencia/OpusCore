<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

$correo = $_SESSION["usuario"];

$stmt = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre
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

$idEstudiante = (int)$estudiante['id'];

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$estudiante = $resultado->fetch_assoc();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = (int) $estudiante['id'];
$tramitesPendientes = [];

// Para backend: aquí deben salir las mensualidades/cuotas pendientes del estudiante.
// El botón de pago no debe reinscribir cursos; debe pagar la cuota específica de esta fila.
$tablaMensualidadesExiste = $conexion->query("SHOW TABLES LIKE 'mensualidades'");
$existeMensualidades = $tablaMensualidadesExiste && $tablaMensualidadesExiste->num_rows > 0;

if ($existeMensualidades) {
    $stmt = $conexion->prepare("
        SELECT 
            m.id,
            c.nombre AS curso,
            pi.nombre AS periodo,
            m.monto,
            m.fechaVencimiento,
            m.estado
        FROM mensualidades m
        INNER JOIN cursos c ON m.idCurso = c.id
        INNER JOIN PeriodoInscripcion pi ON m.idPeriodo = pi.id
        WHERE m.idEstudiante = ? AND m.estado <> 'Pagado'
        ORDER BY m.fechaVencimiento ASC
    ");
} else {
    $stmt = $conexion->prepare("
        SELECT c.nombre AS curso, pi.nombre AS periodo, c.costoMensual AS monto,
               NULL AS fechaVencimiento, 'Pendiente' AS estado
        FROM inscripciones i
        INNER JOIN cursos c ON i.idCurso = c.id
        INNER JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
        WHERE i.idEstudiante = ?
        ORDER BY i.fecha_registro DESC
    ");
}

if ($stmt) {
    $stmt->bind_param("i", $idEstudiante);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $tramitesPendientes = $resultado->fetch_all(MYSQLI_ASSOC);
}

$totalPendiente = array_sum(array_map(fn($pago) => (float) $pago['monto'], $tramitesPendientes));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Trámites pendientes</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.paypal.com/sdk/js?client-id=Af2BotGg3h9wRXyUvU4sJPB1MDX9Mp74DMzh-v2YuU0sVHTN1POJ0LJriJ4x8J0D0kU_DATVXJMLkad2&currency=USD&locale=es_SV"></script>
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.svg" alt="Logo" class="logo-img">
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
                <a href="estudiante-calificaciones.php" class="nav-item">
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
                        <a href="estudiante-pagos.php">Pagos realizados</a>
                        <a href="estudiante-tramites-pendientes.php" class="active">Trámites pendientes</a>
                    </div>
                </div>
                <a href="estudiante-constancias.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Constancias</span>
                </a>
            </nav>

            <a href="includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Cerrar sesion</span>
            </a>
        </aside>

        <div class="content">
            <header class="header-panel">
                <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="includes/logout.php" class="user-profile-panel">
                    <div class="user-info">
                        <span class="user-role">Estudiante</span>
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <div class="banner">
                <div class="banner-left">
                    <h1>Trámites pendientes</h1>
                    <p>Consulta los pagos que siguen pendientes para completar tus cursos.</p>
                </div>
                <div class="banner-fecha">
                    <strong id="fecha-hoy"></strong>
                </div>
            </div>

            <section class="pagos-resumen">
                <article class="pago-resumen-card">
                    <span>Total pendiente</span>
                    <strong>$<?= number_format($totalPendiente, 2) ?></strong>
                </article>
                <article class="pago-resumen-card">
                    <span>Trámites</span>
                    <strong><?= count($tramitesPendientes) ?></strong>
                </article>
            </section>

            <section class="pagos-panel">
                <div class="pagos-panel-header">
                    <div>
                        <h2>Trámites pendientes</h2>
                        <small><?= htmlspecialchars($estudiante['estudiante_nombre']) ?></small>
                    </div>
                    <a class="pago-accion" href="estudiante-pagos.php">Pagos realizados</a>
                </div>

                <?php if (empty($tramitesPendientes)): ?>
                    <div class="inscripcion-vacia pagos-vacio">
                        <i class="fas fa-check-circle"></i>
                        <p>No tienes trámites pendientes.</p>
                        <small>Cuando exista una cuota por pagar, aparecerá en esta sección.</small>
                    </div>
                <?php else: ?>
                    <div class="pagos-tabla-wrap">
                        <!-- Barra "Seleccionar todos" solo visible en móvil -->
                        <div class="tramites-mobile-header">
                            <span class="tramites-mobile-header-label">Cuotas pendientes</span>
                            <button type="button" class="tramites-mobile-seleccion-btn" id="tramite-mobile-select-all">
                                <i class="fas fa-check-double"></i>
                                <span id="tramite-mobile-select-all-label">Seleccionar todas</span>
                            </button>
                        </div>
                        <table class="pagos-tabla pagos-tabla-admin">
                            <thead>
                                <tr>
                                    <th class="col-check">
                                        <input type="checkbox" id="tramite-check-all" class="tramite-checkbox-all" title="Seleccionar todos">
                                    </th>
                                    <th>Curso</th>
                                    <th>Periodo</th>
                                    <th>Monto</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tramitesPendientes as $tramite): ?>
                                    <?php
                                        $estado = $tramite['estado'] ?: 'Pendiente';
                                        $estadoClase = strtolower($estado);
                                        $vencimiento = $tramite['fechaVencimiento'] ? date('d/m/Y', strtotime($tramite['fechaVencimiento'])) : 'Por definir';
                                    ?>
                                    <tr class="tramite-fila"
                                        data-id="<?= $tramite['id'] ?>"
                                        data-curso="<?= htmlspecialchars($tramite['curso']) ?>"
                                        data-monto="<?= number_format((float) $tramite['monto'], 2, '.', '') ?>">
                                        <!-- Pill de selección solo visible en móvil, oculta el checkbox nativo en móvil -->
                                        <td class="col-check">
                                            <input type="checkbox" class="tramite-checkbox"
                                                id="cb-tramite-<?= $tramite['id'] ?>"
                                                data-id="<?= $tramite['id'] ?>"
                                                data-curso="<?= htmlspecialchars($tramite['curso']) ?>"
                                                data-monto="<?= number_format((float) $tramite['monto'], 2, '.', '') ?>">
                                        </td>
                                        <!-- Pill solo visible en móvil -->
                                        <td class="tramite-mobile-pill-cell" colspan="5" style="display:none">
                                            <label class="tramite-mobile-pill" for="cb-tramite-<?= $tramite['id'] ?>">
                                                <span class="tramite-pill-icon"><i class="fas fa-check"></i></span>
                                                <div class="tramite-pill-info">
                                                    <strong><?= htmlspecialchars($tramite['curso']) ?></strong>
                                                    <span><?= htmlspecialchars($tramite['periodo']) ?> &bull; Vence <?= htmlspecialchars($vencimiento) ?></span>
                                                </div>
                                                <div class="tramite-pill-right">
                                                    <span class="tramite-pill-monto">$<?= number_format((float) $tramite['monto'], 2) ?></span>
                                                    <span class="pago-estado-badge estado-<?= htmlspecialchars($estadoClase) ?> tramite-pill-estado"><?= htmlspecialchars($estado) ?></span>
                                                </div>
                                            </label>
                                        </td>
                                        <!-- Celdas normales, visibles en desktop -->
                                        <td class="tramite-desktop-cell" data-label="Curso"><?= htmlspecialchars($tramite['curso']) ?></td>
                                        <td class="tramite-desktop-cell" data-label="Periodo"><?= htmlspecialchars($tramite['periodo']) ?></td>
                                        <td class="tramite-desktop-cell" data-label="Monto">$<?= number_format((float) $tramite['monto'], 2) ?></td>
                                        <td class="tramite-desktop-cell" data-label="Vencimiento"><?= htmlspecialchars($vencimiento) ?></td>
                                        <td class="tramite-desktop-cell" data-label="Estado">
                                            <span class="pago-estado-badge estado-<?= htmlspecialchars($estadoClase) ?>">
                                                <?= htmlspecialchars($estado) ?>
                                            </span>
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
    <!-- Panel flotante de selección múltiple (aparece al marcar checkboxes) -->
    <div id="tramites-panel" class="barra-inscripcion">
        <button
            type="button"
            id="tramites-panel-tab"
            class="barra-inscripcion-tab"
            aria-label="Ver cuotas seleccionadas"
            aria-controls="tramites-panel-card">
            <span id="tramites-tab-count">0 cuotas</span>
        </button>

        <div id="tramites-panel-card" class="barra-card">
            <div class="barra-card-left">
                <div class="barra-card-status">
                    <div class="barra-status-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <div class="barra-status-title">Trámites Seleccionados</div>
                        <div id="tramites-panel-num-text" class="barra-status-subtitle">0 cuotas marcadas</div>
                    </div>
                </div>
                <div id="tramites-panel-lista" class="barra-cursos-nombres"></div>
            </div>

            <div class="barra-card-center">
                <div class="barra-total-label">Total a pagar</div>
                <div class="barra-total-value" id="tramites-panel-total">$0.00</div>
            </div>

            <div class="barra-card-actions">
                <button type="button" class="btn-guardar-premium" id="tramites-panel-pagar-btn">
                    <i class="fas fa-credit-card"></i> Pagar ahora
                </button>
                <button type="button" class="btn-cancelar" id="tramites-panel-cancelar">
                    Cancelar selección
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de pago PayPal (se abre desde el panel) -->
    <div id="modalPagoTramites" class="modal-overlay">
        <div class="modal-contenido modal-pago">
            <button class="modal-cerrar" id="tramites-modal-cerrar">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-titulo">
                <i class="fas fa-credit-card"></i> Confirmar pago
            </h2>
            <div class="pago-resumen">
                <h3>Resumen de Pago</h3>
                <div id="tramites-modal-lista" class="pago-lista-cursos"></div>
                <div class="pago-divider"></div>
                <div class="pago-total-line">
                    <strong>Total a pagar:</strong>
                    <span id="tramites-modal-total">$0.00</span>
                </div>
            </div>
            <div class="pago-metodo">
                <h3>Método de Pago</h3>
                <div class="pago-paypal-container">
                    <div id="tramites-paypal-container"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="tramites-modal-cancelar">Cancelar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
