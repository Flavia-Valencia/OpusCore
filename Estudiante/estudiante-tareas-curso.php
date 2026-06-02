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

include("../includes/conexion.php");

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

$tareas = [];
if (tablaExiste($conexion, 'tareas')) {
    $tieneArchivos = tablaExiste($conexion, 'tareasArchivos');
    
    $selectArchivo = $tieneArchivos
        ? ", (
              SELECT ta.nombreArchivo
              FROM tareasArchivos ta
              WHERE ta.idTarea = t.id
              ORDER BY ta.fechaSubida DESC, ta.id DESC
              LIMIT 1
            ) AS nombre_archivo,
            (
              SELECT ta.rutaArchivo
              FROM tareasArchivos ta
              WHERE ta.idTarea = t.id
              ORDER BY ta.fechaSubida DESC, ta.id DESC
              LIMIT 1
            ) AS ruta_archivo,
            (
              SELECT ta.tipo
              FROM tareasArchivos ta
              WHERE ta.idTarea = t.id
              ORDER BY ta.fechaSubida DESC, ta.id DESC
              LIMIT 1
            ) AS tipo_archivo"
        : ", NULL AS nombre_archivo, NULL AS ruta_archivo, NULL AS tipo_archivo";

    $stmt = $conexion->prepare("
        SELECT t.id, t.titulo, t.descripcion, t.puntajeMaximo, t.fechaLimite,
            t.intentos,
            COALESCE(et.estado, 'Pendiente') AS estadoEntrega,
            et.fechaEntrega,
            et.nota,
            COALESCE(et.conteoIntentos, 0) AS conteoIntentos,
            (SELECT sc.titulo FROM sesionContenido sc WHERE sc.id = t.idSesion LIMIT 1) AS sesion_titulo
            $selectArchivo
        FROM tareas t
        LEFT JOIN entregablesTarea et 
            ON et.idTarea = t.id AND et.idEstudiante = ?
        WHERE t.idCurso = ?
        ORDER BY t.fechaLimite ASC, t.id ASC
    ");
    $stmt->bind_param("ii", $idEstudiante, $cursoId);
    $stmt->execute();
    $tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

   $pendientes = 0;
$vencidas = 0;
$hoy = new DateTime('today');

if (!empty($tareas)) {
    foreach ($tareas as $tarea) {

        $estadoEntrega = strtolower($tarea['estadoEntrega'] ?? 'pendiente');

        if ($estadoEntrega === 'entregado' || $estadoEntrega === 'revisado') {
            continue;
        }

        $fechaLimite = new DateTime($tarea['fechaLimite']);

        if ($fechaLimite < $hoy) {
            $vencidas++;
        } else {
            $pendientes++;
        }
    }
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
    <title>ADF | Tareas asignadas</title>
    <link rel="icon" type="image/svg+xml" href="../img/logo.svg">
    <link rel="stylesheet" href="../css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">
    <div class="sidebar-overlay js-sidebar-overlay" id="sidebarOverlay"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="../img/logo.svg" alt="Logo" class="logo-img">
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

            <a href="../includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Cerrar sesion</span>
            </a>
        </aside>

        <div class="content">
            <header class="header-panel">
                <button class="hamburger js-sidebar-toggle" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="../includes/logout.php" class="user-profile-panel">
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
                        <p class="detalle-breadcrumb">Mis cursos / Detalle del curso / Tareas asignadas</p>
                        <h1>Tareas asignadas</h1>
                        <p>Consulta indicaciones, archivos de apoyo y prepara tus entregas.</p>
                    </div>
                </section>

                <section class="detalle-resumen tareas-resumen-estudiante">
                    <article class="detalle-info-item">
                        <i class="fas fa-user"></i>
                        <div>
                            <span>Docente</span>
                            <strong><?= e($curso['docente_nombre']) ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-clipboard-list"></i>
                        <div>
                            <span>Tareas</span>
                            <strong><?= count($tareas) ?> asignada<?= count($tareas) === 1 ? '' : 's' ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-hourglass-half"></i>
                        <div>
                            <span>Pendientes</span>
                            <strong><?= $pendientes ?></strong>
                        </div>
                    </article>
                    <article class="detalle-info-item">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            <span>Vencidas</span>
                            <strong><?= $vencidas ?></strong>
                        </div>
                    </article>
                </section>

                <section class="detalle-panel tareas-curso-page">
                    <div class="contenidos-toolbar">
                        <div class="contenidos-search">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="search" id="tareaBuscar" placeholder="Buscar tarea...">
                        </div>
                        <select id="tareaEstadoFiltro" class="contenidos-filter">
                            <option value="">Todas</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="vencida">Vencidas</option>
                            <option value="entregada">Entregadas</option>
                        </select>
                        <select id="tareaOrdenFiltro" class="contenidos-filter">
                            <option value="proximas">Próximas entregas</option>
                            <option value="recientes">Más recientes</option>
                        </select>
                    </div>

                    <?php if (empty($tareas)): ?>
                        <div class="detalle-empty">Aún no hay tareas asignadas para este curso.</div>
                    <?php else: ?>
                        <div class="tareas-lista-estudiante" id="tareasLista">
                            <?php foreach ($tareas as $index => $tarea): ?>
                                <?php
                                    $fechaLimite = new DateTime($tarea['fechaLimite']);
                                    $estadoEntrega = strtolower($tarea['estadoEntrega'] ?? 'pendiente');

                                    $plazoVencido = $fechaLimite < $hoy;

                                    if ($estadoEntrega === 'entregado' || $estadoEntrega === 'revisado') {
                                        $estado = 'entregada';
                                        $estadoTexto = ucfirst($estadoEntrega);
                                    } elseif ($plazoVencido) {
                                        $estado = 'vencida';
                                        $estadoTexto = 'Vencida';
                                    } else {
                                        $estado = 'pendiente';
                                        $estadoTexto = 'Pendiente';
                                    }
                                    $ruta = trim((string) ($tarea['ruta_archivo'] ?? ''));
                                    $entregaRealizada = $estadoEntrega === 'entregado' || $estadoEntrega === 'revisado';
                                    $intentosMaximos = max(1, (int) ($tarea['intentos'] ?? 1));
                                    $intentosUsados  = min($intentosMaximos, max(0, (int) ($tarea['conteoIntentos'] ?? 0)));
                                    $intentosAgotados = $intentosUsados >= $intentosMaximos;
                                    $puedeAccionar = !$plazoVencido && !$intentosAgotados;
                                    $accionEntrega = $entregaRealizada ? 'reemplazar' : 'entregar';
                                    $fechaEntregaTexto = !empty($tarea['fechaEntrega'])
                                        ? date('d/m/Y H:i', strtotime($tarea['fechaEntrega']))
                                        : '';
                                    $notaClase = '';
                                    if ($tarea['nota'] !== null) {
                                        $notaValor = (float) $tarea['nota'];
                                        if ($notaValor <= 4) {
                                            $notaClase = ' baja';
                                        } elseif ($notaValor <= 7) {
                                            $notaClase = ' media';
                                        } else {
                                            $notaClase = ' alta';
                                        }
                                    }
                                    $textoBoton = $puedeAccionar
                                        ? ($entregaRealizada ? 'Reemplazar' : 'Entregar')
                                        : ($intentosAgotados ? 'Intentos agotados' : ($entregaRealizada ? 'Entregada' : 'Vencida'));
                                ?>
                                <article
                                    class="tarea-estudiante-item"
                                    data-title="<?= e(strtolower($tarea['titulo'] . ' ' . $tarea['descripcion'])) ?>"
                                    data-status="<?= e($estado) ?>"
                                    data-date="<?= e(date('Y-m-d', strtotime($tarea['fechaLimite']))) ?>"
                                >
                                    <span class="detalle-item-icon icon-<?= ($index % 4) + 1 ?>">
                                        <i class="fas fa-clipboard-check"></i>
                                    </span>
                                    <div class="tarea-estudiante-body">
                                        <div class="tarea-estudiante-title">
                                            <strong><?= e($tarea['titulo']) ?></strong>
                                            <span class="tarea-estado <?= e($estado) ?>"><?= e($estadoTexto) ?></span>
                                        </div>
                                        <p><?= e($tarea['descripcion']) ?></p>
                                        <!-- Tarjeta de contenido en tareas --> 
                                        <div class="tarea-estudiante-meta">
                                            <?php if (!empty($tarea['sesion_titulo'])): ?>
                                                <span><i class="fas fa-chalkboard"></i> <?= e($tarea['sesion_titulo']) ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-calendar-day"></i> Entrega: <?= fechaCorta($tarea['fechaLimite']) ?></span>
                                            <?php if ($tarea['nota'] !== null): ?>
                                                <span class="tarea-nota<?= e($notaClase) ?>">
                                                    <i class="fas fa-star"></i>
                                                    Nota: <?= e(number_format((float) $tarea['nota'], 2)) ?> / <?= e(number_format((float) $tarea['puntajeMaximo'], 0)) ?>
                                                </span>
                                            <?php else: ?>
                                                <span><i class="fas fa-star"></i> Puntaje: <?= (int) $tarea['puntajeMaximo'] ?> pts</span>
                                            <?php endif; ?>
                                            <?php if (!empty($tarea['nombre_archivo'])): ?>
                                                <span><i class="fas fa-paperclip"></i> <?= e($tarea['nombre_archivo']) ?></span>
                                            <?php endif; ?>
                                            <span class="tarea-intentos <?= $intentosAgotados ? 'agotado' : '' ?>">
                                                <i class="fas fa-rotate-right"></i> Intentos <?= $intentosUsados ?>/<?= $intentosMaximos ?>
                                            </span>
                                            <span class="tarea-fecha-entrega<?= $fechaEntregaTexto === '' ? ' is-hidden' : '' ?>">
                                                <i class="fas fa-clock"></i>
                                                <?= $fechaEntregaTexto !== '' ? 'Entregada: ' . e($fechaEntregaTexto) : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="tarea-estudiante-actions">
                                        <?php if (!empty($tarea['nombre_archivo'])): ?>
                                            <?php if ($tarea['tipo_archivo'] === 'Enlace' && $ruta): ?>
                                                <a href="<?= e($ruta) ?>" target="_blank" rel="noopener" class="contenido-ver">
                                                    Apoyo <i class="fas fa-chevron-right"></i>
                                                </a>
                                            <?php elseif ($tarea['tipo_archivo'] === 'Archivo' && $ruta): ?>
                                                <a href="<?= e($ruta) ?>" download="<?= e($tarea['nombre_archivo']) ?>" class="contenido-ver">
                                                    Apoyo <i class="fas fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="btn-entregar-tarea<?= $entregaRealizada ? ' is-replace' : '' ?><?= !$puedeAccionar ? ' is-disabled' : '' ?>"
                                            <?= !$puedeAccionar ? 'disabled' : '' ?>
                                            data-tarea-id="<?= (int) $tarea['id'] ?>"
                                            data-titulo="<?= e($tarea['titulo']) ?>"
                                            data-fecha="<?= fechaCorta($tarea['fechaLimite']) ?>"
                                            data-puntaje="<?= (int) $tarea['puntajeMaximo'] ?>"
                                            data-accion="<?= e($accionEntrega) ?>"
                                            data-intentos="<?= $intentosUsados ?>"
                                            data-intentos-max="<?= $intentosMaximos ?>"
                                        >
                                            <?= e($textoBoton) ?>
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="detalle-empty contenidos-empty-filter" id="tareasEmptyFiltro">No se encontraron tareas con esos filtros.</div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-overlay tarea-entrega-modal" id="modalEntregaTarea" aria-hidden="true">
        <div class="modal-contenido tarea-entrega-box" role="dialog" aria-modal="true" aria-labelledby="entregaTareaTitulo">
            <button class="modal-cerrar js-cerrar-entrega-tarea" type="button" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-titulo" id="entregaTareaTitulo"><i class="fas fa-file-arrow-up"></i> Entregar tarea</h2>
            <div class="entrega-tarea-resumen">
                <strong id="entregaTareaNombre">Tarea seleccionada</strong>
                <span id="entregaTareaMeta">Fecha de entrega</span>
                <small id="entregaTareaModo">Agrega el archivo o enlace que enviarás para esta tarea.</small>
            </div>
            <form id="formEntregaTarea" class="entrega-tarea-form">
                <input type="hidden" id="entregaTareaId">
                <div class="entrega-adjuntos">
                    <label>Adjuntos de entrega</label>
                    <div id="listaEntregaAdjuntos" class="lista-entrega-adjuntos"></div>
                    <div class="entrega-adjunto-actions">
                        <button type="button" class="entrega-adjunto-btn" id="btnEntregaArchivo">
                            <i class="fas fa-paperclip"></i> Agregar archivo
                        </button>
                        <button type="button" class="entrega-adjunto-btn" id="btnEntregaEnlace">
                            <i class="fas fa-link"></i> Agregar enlace
                        </button>
                    </div>
                </div>
                <div class="entrega-modal-actions">
                    <button type="button" class="entrega-modal-btn entrega-modal-btn-light js-cerrar-entrega-tarea">Cancelar</button>
                    <button type="submit" class="entrega-modal-btn entrega-modal-btn-primary" id="entregaTareaSubmit">Marcar como entregada</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/utilidades/toast.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
