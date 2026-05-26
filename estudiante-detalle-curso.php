<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function fechaCorta($fecha) {
    if (empty($fecha)) return 'Por definir';
    return date('d/m/Y', strtotime($fecha));
}

function tablaExiste($conexion, $tabla) {
    $stmt = $conexion->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $tabla);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function columnaExiste($conexion, $tabla, $columna) {
    $stmt = $conexion->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $tabla, $columna);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
if ($cursoId <= 0) {
    header("Location: vista_mis_cursos.php");
    exit();
}

$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = (int) $estudiante['id'];
$tieneCategorias = tablaExiste($conexion, 'categorias');
$tienePeriodo = tablaExiste($conexion, 'PeriodoInscripcion') && columnaExiste($conexion, 'cursos', 'idPeriodo');
$tieneHorarios = tablaExiste($conexion, 'CursoHorario') && tablaExiste($conexion, 'horarios');
$tieneAulas = $tieneHorarios && tablaExiste($conexion, 'aulas');

$selectCategoria = $tieneCategorias ? "COALESCE(cat.nombre, 'Sin categoria')" : "'Sin categoria'";
$joinCategoria = $tieneCategorias ? "LEFT JOIN categorias cat ON c.idCategoria = cat.id" : "";

$selectPeriodo = $tienePeriodo ? "COALESCE(pi.nombre, 'Sin periodo')" : "'Sin periodo'";
$joinPeriodo = $tienePeriodo ? "LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id" : "";

$sqlCurso = "
    SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.fechaInicio, c.fechaFin,
           i.estado_academico, i.fecha_registro,
           COALESCE(CONCAT(ud.nombre, ' ', ud.apellido), 'Docente por asignar') AS docente_nombre,
           $selectCategoria AS categoria_nombre,
           $selectPeriodo AS periodo_nombre,
           GREATEST(1, TIMESTAMPDIFF(WEEK, c.fechaInicio, c.fechaFin) + 1) AS duracion_semanas
    FROM inscripciones i
    INNER JOIN cursos c ON i.idCurso = c.id
    LEFT JOIN docentes d ON c.idDocente = d.id
    LEFT JOIN usuarios ud ON d.usuario_id = ud.id
    $joinCategoria
    $joinPeriodo
    WHERE i.idEstudiante = ?
      AND i.idCurso = ?
      AND i.estado_academico = 'Activo'
    LIMIT 1
";

$stmt = $conexion->prepare($sqlCurso);
$stmt->bind_param("ii", $idEstudiante, $cursoId);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();

if (!$curso) {
    header("Location: vista_mis_cursos.php");
    exit();
}

$curso['horarios'] = 'Horario por definir';
$curso['aulas'] = 'Aula por definir';
if ($tieneHorarios) {
    $selectAulaHorario = $tieneAulas ? "a.aula" : "NULL AS aula";
    $joinAulaHorario = $tieneAulas ? "LEFT JOIN aulas a ON ch.idAula = a.id" : "";
    $stmt = $conexion->prepare("
        SELECT ch.dia, h.etiqueta, $selectAulaHorario
        FROM CursoHorario ch
        INNER JOIN horarios h ON ch.idHorario = h.id
        $joinAulaHorario
        WHERE ch.idCurso = ?
        ORDER BY FIELD(ch.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'), h.horaInicio
    ");
    $stmt->bind_param("i", $cursoId);
    $stmt->execute();
    $horariosRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($horariosRows)) {
        $horarios = [];
        $aulas = [];
        foreach ($horariosRows as $row) {
            $horarios[] = trim($row['dia'] . ' ' . $row['etiqueta']);
            if (!empty($row['aula'])) {
                $aulas[] = $row['aula'];
            }
        }
        $curso['horarios'] = implode(' / ', array_unique($horarios));
        if (!empty($aulas)) {
            $curso['aulas'] = implode(', ', array_unique($aulas));
        }
    }
}

$contenidos = [];
if (tablaExiste($conexion, 'sesionContenido')) {
    $stmt = $conexion->prepare("
        SELECT id, titulo, descripcion, fecha
        FROM sesionContenido
        WHERE idCurso = ? AND estado = 1
        ORDER BY fecha DESC, id DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $cursoId);
    $stmt->execute();
    $contenidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$tareas = [];
if (tablaExiste($conexion, 'tareas')) {
    $stmt = $conexion->prepare("
        SELECT t.id, t.titulo, t.descripcion, t.puntajeMaximo, t.fechaLimite,
            COALESCE(et.estado, 'Pendiente') AS estadoEntrega
        FROM tareas t
        LEFT JOIN entregablesTarea et 
            ON et.idTarea = t.id AND et.idEstudiante = ?
        WHERE t.idCurso = ? AND t.estado = 1
        ORDER BY t.fechaLimite ASC, t.id ASC
        LIMIT 5
    ");
    $stmt->bind_param("ii", $idEstudiante, $cursoId);
    $stmt->execute();
    $tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Detalle del curso</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">
    <div class="sidebar-overlay js-sidebar-overlay" id="sidebarOverlay"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.svg" alt="Logo" class="logo-img">
                <span class="sidebar-brand logo-text-sidebar"><span>Academia</span><strong>Futuro Digital</strong></span>
                <div class="menu-user">
                    <div class="menu-user-role">Estudiante</div>
                    <div class="menu-user-email"><?php echo e($_SESSION["usuario"]); ?></div>
                </div>
                <button type="button" class="sidebar-close js-sidebar-close" aria-label="Cerrar menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="estudiante-cursos.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Todos los cursos</span>
                </a>
                <a href="vista_mis_cursos.php" class="nav-item active">
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
                <div class="nav-dropdown">
                    <button type="button" class="nav-item nav-dropdown-toggle js-pagos-toggle">
                        <i class="fas fa-credit-card"></i>
                        <span>Pagos en línea</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </button>
                    <div class="nav-submenu" id="pagosOnlineMenu">
                        <a href="estudiante-pagos.php">Pagos realizados</a>
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

            <a href="includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Cerrar sesion</span>
            </a>
        </aside>

        <div class="content">
            <header class="header-panel">
                <button class="hamburger js-sidebar-toggle" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="includes/logout.php" class="user-profile-panel">
                    <div class="user-info">
                        <span class="user-role">Estudiante</span>
                        <span class="user-email"><?php echo e($_SESSION["usuario"]); ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <main class="detalle-curso-page">
                <section class="detalle-hero">
                    <div>
                        <a href="vista_mis_cursos.php" class="detalle-back">
                            <i class="fas fa-arrow-left"></i>
                            Mis cursos
                        </a>
                        <p class="detalle-breadcrumb">Mis cursos / Detalle del curso</p>
                        <h1><?= e($curso['nombre']) ?></h1>
                        <p>Accede al contenido, tareas y detalles del curso.</p>
                    </div>
                    <div class="detalle-hero-side">
                        <strong><?= date('d/m/Y') ?></strong>
                        <span><?= e($curso['estado_academico']) ?></span>
                    </div>
                </section>

                <section class="detalle-resumen">
                    <article class="detalle-info-item">
                        <i class="fas fa-user"></i>
                        <div>
                            <span>Docente</span>
                            <strong><?= e($curso['docente_nombre']) ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <span>Horario</span>
                            <strong><?= e($curso['horarios']) ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <span>Aula</span>
                            <strong><?= e($curso['aulas']) ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-calendar-days"></i>
                        <div>
                            <span>Duración</span>
                            <strong><?= (int) $curso['duracion_semanas'] ?> semanas</strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <span>Inicio</span>
                            <strong><?= fechaCorta($curso['fechaInicio']) ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-calendar-xmark"></i>
                        <div>
                            <span>Fin</span>
                            <strong><?= fechaCorta($curso['fechaFin']) ?></strong>
                        </div>
                    </article>
                </section>

                <section class="detalle-grid">
                    <div class="detalle-main-col">
                        <article class="detalle-panel detalle-general">
                            <div class="detalle-panel-title">
                                <i class="fas fa-circle-info"></i>
                                <h2>Información general</h2>
                            </div>
                            <p><?= e($curso['descripcion']) ?></p>
                            <div class="detalle-tags">
                                <span><i class="fas fa-code-branch"></i> Categoría: <?= e($curso['categoria_nombre']) ?></span>
                                <span><i class="fas fa-calendar"></i> Periodo: <?= e($curso['periodo_nombre']) ?></span>
                            </div>
                        </article>

                        <article class="detalle-panel" id="contenidos">
                            <div class="detalle-panel-header">
                                <div class="detalle-panel-title">
                                    <i class="fas fa-folder-open"></i>
                                    <h2>Contenidos publicados</h2>
                                </div>
                                <a href="estudiante-contenidos-curso.php?curso_id=<?= urlencode($curso['id']) ?>">Ver todas</a>
                            </div>

                            <?php if (empty($contenidos)): ?>
                                <div class="detalle-empty">Aún no hay contenidos publicados para este curso.</div>
                            <?php else: ?>
                                <div class="detalle-lista">
                                    <?php foreach ($contenidos as $index => $contenido): ?>
                                        <div class="detalle-lista-item">
                                            <span class="detalle-item-icon icon-<?= ($index % 4) + 1 ?>"><i class="fas fa-book-open"></i></span>
                                            <div>
                                                <strong><?= e($contenido['titulo']) ?></strong>
                                                <p><?= e($contenido['descripcion']) ?></p>
                                            </div>
                                            <time>Publicado<br><?= fechaCorta($contenido['fecha']) ?></time>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>

                        <article class="detalle-panel" id="tareas">
                            <div class="detalle-panel-header">
                                <div class="detalle-panel-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    <h2>Tareas asignadas</h2>
                                </div>
                                <a href="estudiante-tareas-curso.php?curso_id=<?= urlencode($curso['id']) ?>">Ver todas</a>
                            </div>

                            <?php if (empty($tareas)): ?>
                                <div class="detalle-empty">Aún no hay tareas asignadas para este curso.</div>
                            <?php else: ?>
                                <div class="detalle-lista">
                                    <?php foreach ($tareas as $index => $tarea): ?>
                                        <div class="detalle-lista-item tarea">
                                            <span class="detalle-item-icon icon-<?= ($index % 4) + 1 ?>"><i class="fas fa-pen"></i></span>
                                            <div>
                                                <strong><?= e($tarea['titulo']) ?></strong>
                                                <p><?= e($tarea['descripcion']) ?></p>
                                            </div>
                                            <time>Entrega<br><?= fechaCorta($tarea['fechaLimite']) ?></time>
                                            <span class="detalle-status"><?= (int) $tarea['puntajeMaximo'] ?> pts</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>

                </section>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
