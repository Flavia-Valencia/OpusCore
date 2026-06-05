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

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function fechaEntregaDocente($fecha) {
    if (empty($fecha)) return 'Sin registro';
    return date('d/m/Y H:i', strtotime($fecha));
}

$cursoId = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) ?: 0;
$cursoSeleccionado = trim($_GET['curso'] ?? '');
$cursoSeleccionado = $cursoSeleccionado !== '' ? $cursoSeleccionado : 'Curso seleccionado';

$cursoValido = null;
$entregas = [];

if ($cursoId > 0) {
    // Valida que el curso pertenezca al docente actual.
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, p.nombre AS periodo_nombre
        FROM cursos c
        INNER JOIN docentes d ON c.idDocente = d.id
        INNER JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN PeriodoInscripcion p ON c.idPeriodo = p.id
        WHERE c.id = ? AND u.correo = ? AND c.estado = 1
        LIMIT 1
    ");
    $stmt->bind_param('is', $cursoId, $_SESSION["usuario"]);
    $stmt->execute();
    $cursoValido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cursoValido) {
        $cursoSeleccionado = $cursoValido['nombre'];

        // Lista tareas y entregas por estudiante.
        $stmt = $conexion->prepare("
            SELECT
                t.id AS idTarea,
                t.titulo AS tareaTitulo,
                t.fechaLimite,
                t.puntajeMaximo,
                u.nombre,
                u.apellido,
                u.correo,
                et.id AS idEntrega,
                COALESCE(et.estado, 'Pendiente') AS estadoEntrega,
                et.fechaEntrega,
                et.nota,
                GROUP_CONCAT(
                    CASE WHEN ea.tipo = 'Archivo' THEN ea.nombreArchivo END
                    ORDER BY ea.id SEPARATOR '||'
                ) AS archivosEntrega,
                GROUP_CONCAT(
                    CASE WHEN ea.tipo = 'Archivo' THEN ea.rutaArchivo END
                    ORDER BY ea.id SEPARATOR '||'
                ) AS rutasEntrega,
                GROUP_CONCAT(
                    CASE WHEN ea.tipo = 'Enlace' THEN ea.nombreArchivo END
                    ORDER BY ea.id SEPARATOR '||'
                ) AS enlacesEntrega,
                GROUP_CONCAT(
                    CASE WHEN ea.tipo = 'Enlace' THEN ea.rutaArchivo END
                    ORDER BY ea.id SEPARATOR '||'
                ) AS urlsEntrega
            FROM tareas t
            INNER JOIN inscripciones i ON i.idCurso = t.idCurso AND i.estado_academico = 'Activo'
            INNER JOIN estudiantes e ON i.idEstudiante = e.id
            INNER JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN entregablesTarea et ON et.idTarea = t.id AND et.idEstudiante = e.id
            LEFT JOIN entregaArchivos ea ON ea.idEntrega = et.id
            WHERE t.idCurso = ?
            GROUP BY
                t.id, t.titulo, t.fechaLimite, t.puntajeMaximo,
                u.nombre, u.apellido, u.correo,
                et.id, et.estado, et.fechaEntrega, et.nota
            ORDER BY t.fechaLimite ASC, u.apellido ASC, u.nombre ASC
        ");
        $stmt->bind_param('i', $cursoId);
        $stmt->execute();
        $entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$totalEntregadas = 0;
$totalPendientes = 0;
$totalRevisadas = 0;

foreach ($entregas as $entrega) {
    $estado = strtolower($entrega['estadoEntrega'] ?? 'pendiente');
    if ($estado === 'pendiente') $totalPendientes++;
    if ($estado === 'entregado') $totalEntregadas++;
    if ($estado === 'revisado') $totalRevisadas++;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>ADF | Tareas entregadas</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-docentes.css?v=entregas-ui">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="./img/logo.svg" alt="Logo Academia" class="logo-img-sidebar">
                <div class="logo-text-sidebar">
                    <span>Academia</span>
                    <strong>Futuro Digital</strong>
                </div>
                <div class="menu-user">
                    <div class="menu-user-role">Docente</div>
                    <div class="menu-user-email"><?= e($_SESSION["usuario"]) ?></div>
                </div>
            </div>

            <nav>
                <ul>
                    <li class="active" onclick="window.location.href='docentes.php'">
                        <i class="fas fa-book"></i> Mis Cursos
                    </li>
                    <li onclick="window.location.href='docente-registro-notas.php'">
                        <i class="fas fa-chart-line"></i> Calificaciones
                    </li>
                    <li onclick="window.location.href='docente-constancias.php'">
                        <i class="fas fa-file-lines"></i> Constancias
                    </li>
                </ul>
            </nav>

            <label for="sidebar-toggle" class="sidebar-close">
                <i class="fas fa-times"></i>
            </label>

            <a href="includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i> Cerrar sesión
            </a>
        </aside>

        <div class="content organizacion-page">
            <header class="header">
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>

                <a href="includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role"><?= e($_SESSION["rol"] ?? "Docente") ?></span>
                        <span class="user-email"><?= e($_SESSION["usuario"]) ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <div class="organizacion-topbar">
                <div>
                    <a href="docente-gestion-tareas.php?curso_id=<?= urlencode($cursoId) ?>&curso=<?= urlencode($cursoSeleccionado) ?>" class="organizacion-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver a gestión de tareas
                    </a>
                    <p class="section-title organizacion-title">Tareas entregadas</p>
                    <h1><?= e($cursoSeleccionado) ?></h1>
                </div>
            </div>

            <?php if (!$cursoValido): ?>
                <section class="contenido-card">
                    <div class="contenido-empty">Este curso no está disponible para el docente actual.</div>
                </section>
            <?php else: ?>
                <section class="banner organizacion-banner tareas-banner">
                    <div class="banner-left">
                        <h2>Entregas de estudiantes</h2>
                        <p>Consulta archivos, enlaces, estados y calificaciones por tarea.</p>
                    </div>
                    <div class="organizacion-metricas">
                        <div>
                            <span>Registros</span>
                            <strong id="entregasTotalVisible"><?= count($entregas) ?></strong>
                        </div>
                        <div>
                            <span>Entregadas</span>
                            <strong><?= $totalEntregadas ?></strong>
                        </div>
                        <div>
                            <span>Pendientes</span>
                            <strong><?= $totalPendientes ?></strong>
                        </div>
                        <div>
                            <span>Revisadas</span>
                            <strong><?= $totalRevisadas ?></strong>
                        </div>
                    </div>
                </section>

                <section class="contenido-card entregas-docente-card">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Listado de entregas</h2>
                            <p>Busca por tarea, estudiante, correo, estado o archivo.</p>
                        </div>
                    </div>

                    <div class="entregas-toolbar">
                        <input type="search" id="buscarEntregasDocente" class="entrega-buscador" placeholder="Buscar entrega...">
                        <select id="filtroEntregasEstado" class="entrega-filtro">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="entregado">Entregado</option>
                            <option value="revisado">Revisado</option>
                            <option value="vencido">Vencido</option>
                        </select>
                    </div>

                    <div class="contenido-tabla-wrap">
                        <table class="contenido-tabla tareas-tabla entregas-docente-tabla">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Tarea</th>
                                    <th>Estado</th>
                                    <th>Entrega</th>
                                    <th>Archivos</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEntregasDocente">
                                <?php if (empty($entregas)): ?>
                                    <tr class="contenido-empty">
                                        <td colspan="6">Todavía no hay tareas o estudiantes para listar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entregas as $entrega): ?>
                                        <?php
                                            $estadoEntrega = $entrega['estadoEntrega'] ?? 'Pendiente';
                                            $claseEstado = strtolower($estadoEntrega);
                                            $estudianteNombre = trim($entrega['nombre'] . ' ' . $entrega['apellido']);
                                            $archivos = array_filter(array_map('trim', explode('||', $entrega['archivosEntrega'] ?? '')));
                                            $rutas = array_map('trim', explode('||', $entrega['rutasEntrega'] ?? ''));
                                            $enlaces = array_filter(array_map('trim', explode('||', $entrega['enlacesEntrega'] ?? '')));
                                            $urls = array_map('trim', explode('||', $entrega['urlsEntrega'] ?? ''));
                                            $textoBusqueda = strtolower($estudianteNombre . ' ' . $entrega['correo'] . ' ' . $entrega['tareaTitulo'] . ' ' . $estadoEntrega . ' ' . implode(' ', $archivos) . ' ' . implode(' ', $enlaces));
                                        ?>
                                        <tr class="entrega-docente-row"
                                            data-search="<?= e($textoBusqueda) ?>"
                                            data-estado="<?= e($claseEstado) ?>"
                                            data-id-entrega="<?= (int) ($entrega['idEntrega'] ?? 0) ?>">

                                            <td data-label="Estudiante">
                                                <strong><?= e($estudianteNombre) ?></strong>
                                                <span class="contenido-desc"><?= e($entrega['correo']) ?></span>
                                            </td>
                                            <td data-label="Tarea">
                                                <strong><?= e($entrega['tareaTitulo']) ?></strong>
                                                <span class="contenido-desc">Límite: <?= e(fechaEntregaDocente($entrega['fechaLimite'])) ?></span>
                                            </td>
                                            <td data-label="Estado">
                                                <span class="contenido-badge estado-<?= e($claseEstado) ?>"><?= e($estadoEntrega) ?></span>
                                            </td>
                                            <td data-label="Entrega"><?= e(fechaEntregaDocente($entrega['fechaEntrega'])) ?></td>
                                            <td data-label="Archivos">
                                                <?php if (empty($archivos) && empty($enlaces)): ?>
                                                    <span class="contenido-muted">Sin adjuntos</span>
                                                <?php else: ?>
                                                    <div class="entrega-archivo-lista">
                                                        <?php foreach ($archivos as $i => $archivo): ?>
                                                            <?php $ruta = $rutas[$i] ?? ''; ?>
                                                            <a href="<?= e($ruta) ?>" class="entrega-descarga" download="<?= e($archivo) ?>">
                                                                <i class="fas fa-download"></i>
                                                                <?= e($archivo) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                        <?php foreach ($enlaces as $i => $enlace): ?>
                                                            <?php $url = $urls[$i] ?? '#'; ?>
                                                            <a href="<?= e($url) ?>" class="entrega-descarga" target="_blank" rel="noopener">
                                                                <i class="fas fa-link"></i>
                                                                <?= e($enlace) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Nota">
                                                <?php if ($entrega['nota'] !== null): ?>
                                                    <div class="tarea-calificacion">
                                                        <span class="nota-valor"><?= e(number_format((float) $entrega['nota'], 2)) ?> pts</span>
                                                        <button type="button" class="btn-editar-nota" title="Editar nota">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    </div>
                                                <?php elseif ($entrega['idEntrega'] && $entrega['estadoEntrega'] === 'Entregado'): ?>
                                                    <div class="tarea-calificacion">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            max="<?= e($entrega['puntajeMaximo']) ?>"
                                                            step="0.01"
                                                            placeholder="0 - <?= e($entrega['puntajeMaximo']) ?>"
                                                        >
                                                        <button
                                                            type="button"
                                                            class="btn-calificar-tarea"
                                                            data-id-entrega="<?= (int) $entrega['idEntrega'] ?>"
                                                        >
                                                            Calificar
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="contenido-muted">Sin nota</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="contenido-empty entregas-empty-filter" id="entregasDocenteEmpty">No se encontraron entregas con esos filtros.</div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>
    <script src="./js/script.js"></script>
</body>
</html>
