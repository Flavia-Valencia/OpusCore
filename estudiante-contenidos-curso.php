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

function normalizarRutaAdjunto($ruta) {
    $ruta = trim((string) $ruta);
    if ($ruta === '' || preg_match('/^https?:\/\//i', $ruta)) {
        return $ruta;
    }

    $rutaRelativa = ltrim(str_replace('\\', '/', $ruta), '/');
    $rutaProyecto = __DIR__ . '/' . $rutaRelativa;
    $rutaHtdocs = dirname(__DIR__) . '/' . $rutaRelativa;

    if (!file_exists($rutaProyecto) && file_exists($rutaHtdocs)) {
        return '../' . $rutaRelativa;
    }

    return $rutaRelativa;
}

function tipoVistaAdjunto($ruta, $tipo) {
    $extension = strtolower(pathinfo(parse_url((string) $ruta, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return 'imagen';
    if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov'])) return 'video';
    if ($extension === 'pdf') return 'pdf';
    if (strtolower((string) $tipo) === 'enlace') return 'enlace';
    return 'archivo';
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

$stmt = $conexion->prepare("
    SELECT c.id, c.nombre, c.descripcion, c.fechaInicio, c.fechaFin,
           i.estado_academico,
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
      AND c.estado = 1
    LIMIT 1
");
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
            if (!empty($row['aula'])) $aulas[] = $row['aula'];
        }
        $curso['horarios'] = implode(' / ', array_unique($horarios));
        if (!empty($aulas)) $curso['aulas'] = implode(', ', array_unique($aulas));
    }
}

$contenidos = [];
if (tablaExiste($conexion, 'sesionContenido')) {
    $tieneArchivos = tablaExiste($conexion, 'sesionArchivos');

    $stmt = $conexion->prepare("
        SELECT sc.id, sc.titulo, sc.descripcion, sc.fecha
        FROM sesionContenido sc
        WHERE sc.idCurso = ? AND sc.estado = 1
        ORDER BY sc.fecha DESC, sc.id DESC
    ");
    $stmt->bind_param("i", $cursoId);
    $stmt->execute();
    $contenidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($contenidos as &$contenido) {
        $contenido['adjuntos'] = [];
    }
    unset($contenido);

    if ($tieneArchivos && !empty($contenidos)) {
        $contenidoIds = array_map('intval', array_column($contenidos, 'id'));
        $placeholders = implode(',', array_fill(0, count($contenidoIds), '?'));
        $tiposBind = str_repeat('i', count($contenidoIds));
        $stmt = $conexion->prepare("
            SELECT idSesion, nombreArchivo, rutaArchivo, tipo
            FROM sesionArchivos
            WHERE idSesion IN ($placeholders)
            ORDER BY fechaSubida ASC, id ASC
        ");
        $stmt->bind_param($tiposBind, ...$contenidoIds);
        $stmt->execute();
        $adjuntosRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $adjuntosPorSesion = [];
        foreach ($adjuntosRows as $adjunto) {
            $rutaAdjunto = normalizarRutaAdjunto($adjunto['rutaArchivo']);
            $adjuntosPorSesion[(int) $adjunto['idSesion']][] = [
                'nombre' => $adjunto['nombreArchivo'],
                'ruta' => $rutaAdjunto,
                'tipo' => $adjunto['tipo'],
                'vista' => tipoVistaAdjunto($rutaAdjunto, $adjunto['tipo']),
            ];
        }

        foreach ($contenidos as &$contenido) {
            $contenido['adjuntos'] = $adjuntosPorSesion[(int) $contenido['id']] ?? [];
        }
        unset($contenido);
    }
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
    <title>ADF | Contenidos publicados</title>
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
                <a href="estudiante-calificaciones.php" class="nav-item">
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
                        <a href="estudiante-detalle-curso.php?curso_id=<?= urlencode($curso['id']) ?>" class="detalle-back">
                            <i class="fas fa-arrow-left"></i>
                            Detalle del curso
                        </a>
                        <p class="detalle-breadcrumb">Mis cursos / Detalle del curso / Contenidos publicados</p>
                        <h1>Contenidos publicados</h1>
                        <p>Materiales y publicaciones del curso <?= e($curso['nombre']) ?>.</p>
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

                <section class="detalle-panel contenidos-publicados-page">
                    <div class="contenidos-toolbar">
                        <div class="contenidos-search">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="search" id="contenidoBuscar" placeholder="Buscar contenido...">
                        </div>
                        <select id="contenidoTipoFiltro" class="contenidos-filter">
                            <option value="">Todos</option>
                            <option value="archivo">Archivos</option>
                            <option value="enlace">Enlaces</option>
                            <option value="sin-material">Sin material</option>
                        </select>
                        <select id="contenidoOrdenFiltro" class="contenidos-filter">
                            <option value="recientes">Más recientes</option>
                            <option value="antiguos">Más antiguos</option>
                        </select>
                    </div>

                    <?php if (empty($contenidos)): ?>
                        <div class="detalle-empty">Aún no hay contenidos publicados para este curso.</div>
                    <?php else: ?>
                        <div class="contenidos-lista" id="contenidosLista">
                            <?php foreach ($contenidos as $index => $contenido): ?>
                                <?php
                                    $adjuntos = $contenido['adjuntos'] ?? [];
                                    $tiposAdjuntos = array_values(array_unique(array_map(
                                        fn($adjunto) => strtolower((string) ($adjunto['tipo'] ?? '')),
                                        $adjuntos
                                    )));
                                    $tiposAdjuntos = array_filter($tiposAdjuntos);
                                    $tipoFiltro = empty($tiposAdjuntos) ? 'sin-material' : implode(' ', $tiposAdjuntos);
                                    $tipoTexto = empty($tiposAdjuntos)
                                        ? 'Sin material'
                                        : (count($tiposAdjuntos) > 1 ? 'Mixto' : ucfirst($tiposAdjuntos[0]));
                                    $adjuntosJson = json_encode($adjuntos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                    $primerAdjunto = $adjuntos[0]['nombre'] ?? '';
                                    $resumenAdjuntos = count($adjuntos) > 1
                                        ? count($adjuntos) . ' materiales disponibles'
                                        : $primerAdjunto;
                                ?>
                                <article
                                    class="contenido-publicado-item"
                                    data-title="<?= e(strtolower($contenido['titulo'] . ' ' . $contenido['descripcion'])) ?>"
                                    data-type="<?= e($tipoFiltro) ?>"
                                    data-date="<?= e($contenido['fecha']) ?>"
                                >
                                    <span class="detalle-item-icon icon-<?= ($index % 4) + 1 ?>">
                                        <i class="fas <?= $tipoFiltro === 'enlace' ? 'fa-link' : 'fa-file-lines' ?>"></i>
                                    </span>
                                    <div class="contenido-publicado-body">
                                        <strong><?= e($contenido['titulo']) ?></strong>
                                        <p><?= e($contenido['descripcion']) ?></p>
                                        <?php if (!empty($resumenAdjuntos)): ?>
                                            <small><?= e($resumenAdjuntos) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <time>Publicado<br><?= fechaCorta($contenido['fecha']) ?></time>
                                    <span class="contenido-tipo"><?= e($tipoTexto) ?></span>
                                    <?php if (!empty($adjuntos)): ?>
                                        <button
                                            type="button"
                                            class="contenido-ver js-ver-contenido"
                                            data-title="<?= e($contenido['titulo']) ?>"
                                            data-date="<?= e(fechaCorta($contenido['fecha'])) ?>"
                                            data-adjuntos="<?= e($adjuntosJson) ?>"
                                        >
                                            Ver
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="contenido-ver disabled">Ver</span>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="detalle-empty contenidos-empty-filter" id="contenidosEmptyFiltro">No se encontraron contenidos con esos filtros.</div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-overlay contenido-recursos-modal" id="modalContenidoRecursos" aria-hidden="true">
        <div class="modal-contenido contenido-recursos-box" role="dialog" aria-modal="true" aria-labelledby="contenidoRecursosTitulo">
            <button class="modal-cerrar js-cerrar-contenido-recursos" type="button" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-titulo" id="contenidoRecursosTitulo"><i class="fas fa-folder-open"></i> Materiales publicados</h2>
            <div class="entrega-tarea-resumen">
                <strong id="contenidoRecursosNombre">Contenido seleccionado</strong>
                <span id="contenidoRecursosMeta">Publicado</span>
                <small>Archivos y enlaces compartidos por el docente.</small>
            </div>
            <div class="contenido-recursos-lista" id="contenidoRecursosLista"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
