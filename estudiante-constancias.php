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

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

//Obtener datos del estudiante
$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
    LIMIT 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = (int) $estudiante['id'];

//Cursos en los que está inscrito el estudiante con estado de su solicitud de constancia y notas
$stmt = $conexion->prepare("
    SELECT
        c.id                                            AS curso_id,
        c.nombre                                        AS curso_nombre,
        COALESCE(cat.nombre, 'Sin categoría')           AS categoria,
        COALESCE(pi.nombre, 'Sin periodo')              AS periodo_nombre,
        pi.id                                           AS periodo_id,
        rn.notaFinal,
        rn.estadoEstudiante                             AS estado_academico,
        sol.estado                                      AS estado_solicitud,
        sol.id                                          AS solicitud_id,
        sol.fechaSolicitud
    FROM inscripciones i
    INNER JOIN cursos c       ON i.idCurso = c.id
    LEFT  JOIN categorias cat ON c.idCategoria = cat.id
    LEFT  JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
    LEFT  JOIN RegistroNotas rn
           ON rn.idEstudiante = i.idEstudiante AND rn.idCurso = c.id
    LEFT  JOIN solicitudConstanciaEstudiante sol
           ON sol.idEstudiante = i.idEstudiante AND sol.idCurso = c.id
    WHERE i.idEstudiante = ?
    ORDER BY pi.fechaInicioCiclo DESC, c.nombre ASC
");
$stmt->bind_param("i", $idEstudiante);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Métricas 
$pendientes = 0;
$aprobadas  = 0;
foreach ($cursos as $c) {
    if ($c['estado_solicitud'] === 'Pendiente') $pendientes++;
    if ($c['estado_solicitud'] === 'Aprobada')  $aprobadas++;
}


$periodos = array_values(array_unique(array_column($cursos, 'periodo_nombre')));

$alerta     = '';
$alertaTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idCurso'])) {
    $idCurso = (int) $_POST['idCurso'];

    //Verificar que el curso está aprobado por el estudiante con nota >= 6.00 
    $chk = $conexion->prepare("
        SELECT id, notaFinal, estadoEstudiante FROM RegistroNotas
        WHERE idEstudiante = ? AND idCurso = ?
        LIMIT 1
    ");
    $chk->bind_param("ii", $idEstudiante, $idCurso);
    $chk->execute();
    $notaInfo = $chk->get_result()->fetch_assoc();
    $chk->close();

    $aprobado = ($notaInfo && $notaInfo['estadoEstudiante'] === 'Aprobado' && (float)$notaInfo['notaFinal'] >= 6.00);

    // Verificar si ya existe una solicitud PENDIENTE
    $dup = $conexion->prepare("
        SELECT id, estado FROM solicitudConstanciaEstudiante
        WHERE idEstudiante = ? AND idCurso = ? AND estado = 'Pendiente'
        LIMIT 1
    ");
    $dup->bind_param("ii", $idEstudiante, $idCurso);
    $dup->execute();
    $yaPendiente = $dup->get_result()->fetch_assoc();
    $dup->close();

    if (!$aprobado) {
        $alerta     = 'No cumples con los requisitos para solicitar la constancia de este curso. Debes tener una nota final de 6.00 o superior y el curso aprobado.';
        $alertaTipo = 'error';
    } elseif ($yaPendiente) {
        $alerta     = 'Ya tienes una solicitud pendiente para este curso. Espera a que sea procesada.';
        $alertaTipo = 'info';
    } else {
        $ins = $conexion->prepare("
            INSERT INTO solicitudConstanciaEstudiante (idEstudiante, idCurso, motivo)
            VALUES (?, ?, 'Trámite personal')
        ");
        $ins->bind_param("ii", $idEstudiante, $idCurso);
        try {
            if ($ins->execute()) {
                $alerta     = 'Tu solicitud fue enviada. El equipo administrativo la procesará pronto.';
                $alertaTipo = 'exito';
            } else {
                $alerta     = 'Ocurrió un error al enviar la solicitud. Intenta de nuevo.';
                $alertaTipo = 'error';
            }
        } catch (mysqli_sql_exception $e) {
            $alerta     = 'Error al procesar la solicitud: ' . $e->getMessage();
            $alertaTipo = 'error';
        }
        $ins->close();
    }

    $stmt2 = $conexion->prepare("
        SELECT
            c.id                                            AS curso_id,
            c.nombre                                        AS curso_nombre,
            COALESCE(cat.nombre,'Sin categoría')            AS categoria,
            COALESCE(pi.nombre,'Sin periodo')               AS periodo_nombre,
            pi.id                                           AS periodo_id,
            rn.notaFinal,
            rn.estadoEstudiante                             AS estado_academico,
            sol.estado                                      AS estado_solicitud,
            sol.id                                          AS solicitud_id,
            sol.fechaSolicitud
        FROM inscripciones i
        INNER JOIN cursos c       ON i.idCurso = c.id
        LEFT  JOIN categorias cat ON c.idCategoria = cat.id
        LEFT  JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
        LEFT  JOIN RegistroNotas rn
               ON rn.idEstudiante = i.idEstudiante AND rn.idCurso = c.id
        LEFT  JOIN solicitudConstanciaEstudiante sol
               ON sol.idEstudiante = i.idEstudiante AND sol.idCurso = c.id
        WHERE i.idEstudiante = ?
        ORDER BY pi.fechaInicioCiclo DESC, c.nombre ASC
    ");
    $stmt2->bind_param("i", $idEstudiante);
    $stmt2->execute();
    $cursos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    $pendientes = 0;
    $aprobadas  = 0;
    foreach ($cursos as $c) {
        if ($c['estado_solicitud'] === 'Pendiente') $pendientes++;
        if ($c['estado_solicitud'] === 'Aprobada')  $aprobadas++;
    }
    $periodos = array_values(array_unique(array_column($cursos, 'periodo_nombre')));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>ADF | Mis Constancias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles-estudiantes.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">
<input type="checkbox" id="sidebar-toggle">
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="layout">
    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="img/logo.svg" alt="Logo" class="logo-img">
            <span class="sidebar-brand logo-text-sidebar">
                <span>Academia</span><strong>Futuro Digital</strong>
            </span>
            <div class="menu-user">
                <div class="menu-user-role">Estudiante</div>
                <div class="menu-user-email"><?= e($_SESSION["usuario"]) ?></div>
            </div>
            <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="estudiante-cursos.php" class="nav-item">
                <i class="fas fa-layer-group"></i><span>Todos los cursos</span>
            </a>
            <a href="vista_mis_cursos.php" class="nav-item">
                <i class="fas fa-book-open"></i><span>Mis cursos</span>
            </a>
            <a href="estudiante-inscripciones.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i><span>Inscripción</span>
            </a>
            <a href="estudiante-calificaciones.php" class="nav-item">
                <i class="fas fa-chart-line"></i><span>Calificaciones</span>
            </a>
            <div class="nav-dropdown">
                <button type="button" class="nav-item nav-dropdown-toggle" onclick="togglePagosOnline()" aria-expanded="false">
                    <i class="fas fa-credit-card"></i>
                    <span>Pagos en línea</span>
                    <i class="fas fa-chevron-down nav-arrow"></i>
                </button>
                <div class="nav-submenu" id="pagosOnlineMenu">
                    <a href="estudiante-pagos.php">Pagos realizados</a>
                    <a href="estudiante-tramites-pendientes.php">Trámites pendientes</a>
                </div>
            </div>
            <a href="estudiante-constancias.php" class="nav-item active">
                <i class="fas fa-file-alt"></i><span>Constancias</span>
            </a>
        </nav>

        <a href="includes/logout.php" class="sidebar-logout">
            <i class="fas fa-arrow-right-from-bracket"></i><span>Cerrar sesión</span>
        </a>
    </aside>

    <!-- ── CONTENIDO ── -->
    <div class="content">
        <header class="header-panel">
            <button class="hamburger" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a href="includes/logout.php" class="user-profile-panel">
                <div class="user-info">
                    <span class="user-role">Estudiante</span>
                    <span class="user-email"><?= e($_SESSION["usuario"]) ?></span>
                </div>
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
            </a>
        </header>

        <main class="main constancias-page">

            <!-- ── BANNER CON MÉTRICAS ── -->
            <section class="constancias-banner">
                <div class="constancias-banner-texto">
                    <h2>Mis Constancias</h2>
                    <p>Aquí aparecen los cursos que has aprobado. Envía una solicitud y el equipo administrativo generará tu constancia.</p>
                </div>
                <div class="constancias-metricas">
                    <article>
                        <span>Pendientes</span>
                        <strong><?= $pendientes ?></strong>
                    </article>
                    <article>
                        <span>Aprobadas</span>
                        <strong><?= $aprobadas ?></strong>
                    </article>
                </div>
            </section>

            <!-- ── TABLA DE CURSOS APROBADOS ── -->
            <section
                class="constancias-card"
                id="constanciasModulo"
                data-toast-message="<?= e($alerta) ?>"
                data-toast-type="<?= e($alertaTipo === 'exito' ? 'success' : ($alertaTipo === 'info' ? 'info' : ($alertaTipo ? 'error' : ''))) ?>"
            >
                <div class="constancias-section-header">
                    <div>
                        <h2>Cursos aprobados</h2>
                        <p>Solo se muestran cursos con calificación aprobada. Puedes solicitar una constancia por curso.</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="constancias-filtros">
                    <div class="constancias-field">
                        <label for="constanciaBuscador">Buscar curso</label>
                        <div class="constancias-search-wrap">
                            <i class="fas fa-search"></i>
                            <input
                                type="text"
                                id="constanciaBuscador"
                                placeholder="Buscar por nombre del curso..."
                                class="constancias-search-input"
                            >
                        </div>
                    </div>
                    <div class="constancias-field">
                        <label for="constanciaPeriodoFiltro">Periodo académico</label>
                        <select id="constanciaPeriodoFiltro" class="constancias-select">
                            <option value="">Todos los periodos</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= e(strtolower($p)) ?>"><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="constancias-field">
                        <label for="constanciaEstadoFiltro">Estado</label>
                        <select id="constanciaEstadoFiltro" class="constancias-select">
                            <option value="">Todos los estados</option>
                            <option value="sin solicitar">Sin solicitar</option>
                            <option value="solicitado">Solicitado</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>
                </div>

                <div class="tabla-placeholder">
                    <table class="constancias-tabla" id="constanciasTabla">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Nota final</th>
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="constanciasBody">
                            <?php if (empty($cursos)): ?>
                                <tr id="constanciasSinCursos">
                                    <td colspan="5" class="constancias-empty-td">
                                        <i class="fas fa-file-alt constancias-empty-icon"></i>
                                        <p class="constancias-empty-title">Aún no tienes cursos aprobados.</p>
                                        <small class="constancias-empty-sub">Cuando apruebes un curso aparecerá aquí y podrás solicitar tu constancia.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cursos as $c):
                                    $estado = $c['estado_solicitud'] ?? null;
                                    $aprobado = ($c['estado_academico'] === 'Aprobado' && (float)$c['notaFinal'] >= 6.00);

                                    // Badge visual y estado de elegibilidad
                                    if (!$aprobado) {
                                        $badgeClase = 'rechazada';
                                        $badgeTexto = 'No Cumple Requisitos';
                                        $dataEstado = 'sin solicitar';
                                        $btnDesactivado = true;
                                        $btnTexto = 'No elegible';
                                        $btnIcono = 'fa-ban';
                                    } else {
                                        [$badgeClase, $badgeTexto] = match($estado) {
                                            'Pendiente' => ['pendiente', 'Solicitado'],
                                            'Aprobada'  => ['generada',  'Aprobada'],
                                            'Rechazada' => ['rechazada', 'Rechazada'],
                                            default     => ['sin-sol',   'Sin solicitar'],
                                        };

                                        $dataEstado = match($estado) {
                                            'Pendiente' => 'solicitado',
                                            'Aprobada'  => 'aprobada',
                                            'Rechazada' => 'rechazada',
                                            default     => 'sin solicitar',
                                        };
                                        $btnDesactivado = ($estado === 'Pendiente');
                                        $btnTexto       = $estado === 'Pendiente' ? 'Solicitado' : ($estado === 'Aprobada' ? 'Re-solicitar' : 'Solicitar constancia');
                                        $btnIcono       = $btnDesactivado ? 'fa-clock' : ($estado === 'Aprobada' ? 'fa-redo' : 'fa-paper-plane');
                                    }
                                ?>
                                <tr
                                    class="constancia-fila"
                                    data-search="<?= e(strtolower($c['curso_nombre'])) ?>"
                                    data-periodo="<?= e(strtolower($c['periodo_nombre'])) ?>"
                                    data-estado="<?= e($dataEstado) ?>"
                                >
                                    <td data-label="Curso">
                                        <strong class="constancia-curso-nombre">
                                            <?= e($c['curso_nombre']) ?>
                                        </strong>
                                        <small class="constancia-curso-categoria">
                                            <?= e($c['categoria']) ?>
                                        </small>
                                    </td>
                                    <td data-label="Nota final" class="constancia-nota-final">
                                        <?= number_format((float)$c['notaFinal'], 2) ?>
                                    </td>
                                    <td data-label="Periodo">
                                        <?= e($c['periodo_nombre']) ?>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="constancia-badge <?= $badgeClase ?>">
                                            <?= $badgeTexto ?>
                                        </span>
                                    </td>
                                    <td data-label="Acción">
                                        <?php if (!$btnDesactivado): ?>
                                            <form method="POST" class="constancia-form">
                                                <input type="hidden" name="idCurso" value="<?= (int)$c['curso_id'] ?>">
                                                <button type="submit" class="constancia-generar-btn">
                                                    <i class="fas <?= $btnIcono ?>"></i> <?= $btnTexto ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="constancia-generar-btn constancia-generar-btn--disabled" disabled>
                                                <i class="fas <?= $btnIcono ?>"></i> <?= $btnTexto ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <tr id="constanciasSinResultados" style="display:none">
                                    <td colspan="5" class="constancias-no-resultados">
                                        No se encontraron cursos con esos filtros.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<script src="./js/script.js"></script>
</body>
</html>
