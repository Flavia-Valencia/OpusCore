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
                <a href="#" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Mensajes</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-gear"></i>
                    <span>Configuración</span>
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
                    <strong><?= date('d/m/Y') ?></strong>
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
                        <table class="pagos-tabla pagos-tabla-admin">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Periodo</th>
                                    <th>Monto</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tramitesPendientes as $tramite): ?>
                                    <?php
                                        $estado = $tramite['estado'] ?: 'Pendiente';
                                        $estadoClase = strtolower($estado);
                                        $vencimiento = $tramite['fechaVencimiento'] ? date('d/m/Y', strtotime($tramite['fechaVencimiento'])) : 'Por definir';
                                    ?>
                                    <tr>
                                        <td data-label="Curso"><?= htmlspecialchars($tramite['curso']) ?></td>
                                        <td data-label="Periodo"><?= htmlspecialchars($tramite['periodo']) ?></td>
                                        <td data-label="Monto">$<?= number_format((float) $tramite['monto'], 2) ?></td>
                                        <td data-label="Vencimiento"><?= htmlspecialchars($vencimiento) ?></td>
                                        <td data-label="Estado">
                                            <span class="pago-estado-badge estado-<?= htmlspecialchars($estadoClase) ?>">
                                                <?= htmlspecialchars($estado) ?>
                                            </span>
                                        </td>
                                        <td data-label="Acción">
                                            <button
                                                type="button"
                                                class="pago-accion pago-accion-secundaria"
                                                onclick="pagarTramitePendiente(this)"
                                                data-id="<?= $tramite['id'] ?>"
                                                data-curso="<?= htmlspecialchars($tramite['curso']) ?>"
                                                data-monto="<?= number_format((float) $tramite['monto'], 2, '.', '') ?>">
                                                Pagar cuota
                                            </button>
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
    <div id="modalPago" class="modal-overlay">
    <div class="modal-pago-content">
        <button class="modal-cerrar" onclick="cerrarModalPago()">
            <i class="fas fa-times"></i>
        </button>

        <h2>Resumen de pago</h2>

        <div id="pago-lista-cursos"></div>

        <div>
            Total:
            <strong id="pago-total">$0.00</strong>
        </div>

        <div id="paypal-button-container"></div>
    </div>
</div>

    <div id="modalPagoCuota" class="modal-overlay">
        <div class="modal-contenido modal-pago">
            <button class="modal-cerrar" onclick="cerrarModalPagoCuota()">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="modal-titulo">
                <i class="fas fa-credit-card"></i> Pagar cuota
            </h2>

            <div class="pago-resumen">
                <h3>Resumen de Pago</h3>
                <div id="cuota-pago-lista" class="pago-lista-cursos"></div>
                <div class="pago-divider"></div>
                <div class="pago-total-line">
                    <strong>Total a pagar:</strong>
                    <span id="cuota-pago-total">$0.00</span>
                </div>
            </div>

            <div class="pago-metodo">
                <h3>Método de Pago</h3>
                <div class="pago-paypal-container">
                    <div id="paypal-cuota-button-container"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModalPagoCuota()">Cancelar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
