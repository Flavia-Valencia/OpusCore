<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$cursoId = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) ?: 0;
$cursoSeleccionado = trim($_GET['curso'] ?? '');
$cursoSeleccionado = $cursoSeleccionado !== '' ? $cursoSeleccionado : 'Curso seleccionado';

require_once 'includes/conexion.php';

$cursoValido   = null;
$estudiantes   = [];
$tareas        = [];
$totalEntregas = 0;

if ($cursoId > 0) {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.descripcion, c.fechaInicio, c.fechaFin, p.nombre AS periodo_nombre
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

        // Estudiantes activos del curso
        $stmt = $conexion->prepare("
            SELECT i.id, u.nombre, u.apellido, u.correo, i.estado_academico
            FROM inscripciones i
            INNER JOIN estudiantes e ON i.idEstudiante = e.id
            INNER JOIN usuarios u ON e.usuario_id = u.id
            WHERE i.idCurso = ? AND i.estado_academico = 'Activo'
            ORDER BY u.apellido, u.nombre
        ");
        $stmt->bind_param('i', $cursoId);
        $stmt->execute();
        $estudiantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Tareas reales del curso con archivos adjuntos
        $stmtTareas = $conexion->prepare("
            SELECT
                t.id,
                t.titulo,
                t.descripcion,
                t.puntajeMaximo,
                t.intentos,
                t.fechaLimite,
                t.estado,
                t.idSesion,
                sc.titulo AS sesion_titulo,
                GROUP_CONCAT(
                    CASE WHEN ta.tipo = 'Archivo' THEN ta.nombreArchivo END
                    ORDER BY ta.id SEPARATOR ', '
                ) AS archivos,
                GROUP_CONCAT(
                    CASE WHEN ta.tipo = 'Archivo' THEN ta.id END
                    ORDER BY ta.id SEPARATOR ','
                ) AS idsArchivos
            FROM tareas t
            LEFT JOIN sesionContenido sc ON sc.id = t.idSesion
            LEFT JOIN tareasArchivos ta ON ta.idTarea = t.id
            WHERE t.idCurso = ?
            GROUP BY t.id
            ORDER BY t.fechaLimite ASC
        ");
        $stmtTareas->bind_param('i', $cursoId);
        $stmtTareas->execute();
        $tareas = $stmtTareas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtTareas->close();

        // Total de entregas realizadas para las métricas
        $stmtEnt = $conexion->prepare("
            SELECT COUNT(*) AS total
            FROM entregablesTarea et
            INNER JOIN tareas t ON et.idTarea = t.id
            WHERE t.idCurso = ? AND et.estado = 'Entregado'
        ");
        $stmtEnt->bind_param('i', $cursoId);
        $stmtEnt->execute();
        $totalEntregas = $stmtEnt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmtEnt->close();
    }
    // Sesiones del curso para el select del modal
$sesiones = [];
$stmtSes = $conexion->prepare("
    SELECT id, titulo
    FROM sesionContenido
    WHERE idCurso = ? AND estado = 1
    ORDER BY id ASC
");
$stmtSes->bind_param('i', $cursoId);
$stmtSes->execute();
$sesiones = $stmtSes->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtSes->close();

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
    <title>ADF | Gestión de tareas</title>
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
                    <div class="menu-user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></div>
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
                        <span class="user-role">
                            <?php echo isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : "Docente"; ?>
                        </span>
                        <span class="user-email">
                            <?php echo htmlspecialchars($_SESSION["usuario"]); ?>
                        </span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <div class="organizacion-topbar">
                <div>
                    <a href="docentes.php" class="organizacion-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver a mis cursos
                    </a>
                    <p class="section-title organizacion-title">Gestión de tareas</p>
                    <h1><?= htmlspecialchars($cursoSeleccionado) ?></h1>
                </div>
                <?php if ($cursoValido): ?>
                    <div class="tareas-topbar-actions">
                        <a class="contenido-btn contenido-btn-primary"
                           href="docente-entregas-tareas.php?curso_id=<?= urlencode($cursoId) ?>&curso=<?= urlencode($cursoSeleccionado) ?>">
                            <i class="fas fa-file-arrow-up"></i>
                            Tareas entregadas
                        </a>
                        <button type="button" class="contenido-btn contenido-btn-primary" id="btnNuevaTarea">
                            <i class="fas fa-plus"></i>
                            Nueva tarea
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$cursoValido): ?>
                <section class="contenido-card">
                    <div class="contenido-empty">
                        Este curso no está disponible para el docente actual.
                    </div>
                </section>
            <?php else: ?>

                <section class="banner organizacion-banner tareas-banner">
                    <div class="banner-left">
                        <h2>Tareas y entregas</h2>
                        <p>Crea tareas, revisa entregas y asigna calificaciones para este curso.</p>
                    </div>
                    <div class="organizacion-metricas">
                        <div>
                            <span>Tareas</span>
                            <strong id="tareasTotal"><?= count($tareas) ?></strong>
                        </div>
                        <div>
                            <span>Entregas</span>
                            <strong><?= $totalEntregas ?></strong>
                        </div>
                        <div>
                            <span>Estudiantes</span>
                            <strong><?= count($estudiantes) ?></strong>
                        </div>
                        <div>
                            <span>Periodo</span>
                            <strong><?= htmlspecialchars($cursoValido['periodo_nombre'] ?? 'N/A') ?></strong>
                        </div>
                    </div>
                </section>

                <!-- TABLA DE TAREAS -->
                <section class="contenido-card">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Tareas registradas</h2>
                            <p>Tareas registradas para este curso. Puedes crear, editar y gestionar entregas.</p>
                        </div>
                    </div>

                    <div class="contenido-tabla-wrap">
                        <table class="contenido-tabla tareas-tabla">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Fecha límite</th>
                                    <th>Puntaje</th>
                                    <th>Apoyo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaTareasBody">
                                <?php if (empty($tareas)): ?>
                                    <tr class="contenido-empty">
                                        <td colspan="6">Este curso aún no tiene tareas registradas.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tareas as $tarea):
                                        $estadoTexto = $tarea['estado'] == 1 ? 'Activa' : 'Vencida';
                                        $claseBadge  = $tarea['estado'] == 1 ? 'activa' : 'vencida';
                                        $vencida     = $tarea['estado'] == 0;
                                    ?>
                                        <tr
                                            data-id="<?= (int)$tarea['id'] ?>"
                                            data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>"
                                            data-descripcion="<?= htmlspecialchars($tarea['descripcion']) ?>"
                                            data-fecha="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($tarea['fechaLimite']))) ?>"
                                            data-puntaje="<?= (int)$tarea['puntajeMaximo'] ?>"
                                            data-intentos="<?= (int)($tarea['intentos'] ?? 1) ?>"
                                            data-archivo="<?= htmlspecialchars($tarea['archivos'] ?? '') ?>"
                                            data-ids-archivos="<?= htmlspecialchars($tarea['idsArchivos'] ?? '') ?>"
                                            data-estado="<?= $estadoTexto ?>"
                                            data-sesion-id="<?=(int)($tarea['idSesion'] ?? 0) ?>"
                                        >
                                            <td data-label="Título">
                                                <strong><?= htmlspecialchars($tarea['titulo']) ?></strong>
                                                <span class="contenido-desc"><?= htmlspecialchars($tarea['descripcion']) ?></span>
                                            </td>
                                            <td data-label="Fecha límite">
                                                <?= date('d/m/Y H:i', strtotime($tarea['fechaLimite'])) ?>
                                            </td>
                                            <td data-label="Puntaje"><?= (int)$tarea['puntajeMaximo'] ?> pts</td>
                                            <td data-label="Apoyo">
                                                <?php if (!empty($tarea['archivos'])): ?>
                                                    <span class="contenido-archivo">
                                                        <i class="fas fa-paperclip"></i><?= htmlspecialchars($tarea['archivos']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="contenido-muted">Sin archivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Estado">
                                                <span class="contenido-badge estado-<?= $claseBadge ?>">
                                                    <?= $estadoTexto ?>
                                                </span>
                                            </td>
                                            <td data-label="Acciones">
                                                <div class="contenido-acciones">
                                                    <button type="button"
                                                        class="contenido-icon-btn editar-tarea <?= $vencida ? 'is-disabled' : '' ?>"
                                                        title="<?= $vencida ? 'No se puede editar, fecha vencida' : 'Editar tarea' ?>"
                                                        <?= $vencida ? 'disabled' : '' ?>>
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php endif; ?>

        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>

    <!-- MODAL TAREA -->
    <div class="contenido-modal" id="modalTarea" aria-hidden="true">
        <div class="contenido-modal-box" role="dialog" aria-modal="true" aria-labelledby="tareaModalTitulo">
            <div class="contenido-modal-header">
                <div>
                    <span>Tarea del curso</span>
                    <h2 id="tareaModalTitulo">Nueva tarea</h2>
                </div>
                <button type="button" class="contenido-modal-close" id="cerrarModalTarea" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formTareaDocente" novalidate>
                <input type="hidden" id="tareaId" name="id" value="">
                <input type="hidden" id="tareaCursoId" name="idCurso" value="<?= (int)$cursoId ?>">

                <div class="contenido-form-grid">
                    <div class="contenido-field contenido-field-wide">
                        <label for="tareaTitulo">Título</label>
                        <input type="text" id="tareaTitulo" name="titulo" maxlength="120" placeholder="Ej: Guía práctica de HTML" required>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label for="tareaDescripcion">Descripción</label>
                        <textarea id="tareaDescripcion" rows="4" name="descripcion" placeholder="Indicaciones para realizar la tarea" required></textarea>
                    </div>
                    <div class="contenido-field contenido-field-wide">
                        <label for="tareaSesion">Clase relacionada <span class="contenido-muted">(opcional)</span></label>
                        <select id="tareaSesion" name="idSesion">
                            <option value="">Ninguna</option>
                            <?php foreach ($sesiones as $sesion): ?>
                                <option value="<?= (int)$sesion['id'] ?>">
                                    <?= htmlspecialchars($sesion['titulo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="contenido-field">
                        <label for="tareaFecha">Fecha límite</label>
                        <input type="datetime-local" id="tareaFecha" name="fechaLimite" required>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaPuntaje">Calificación máxima</label>
                        <input type="number" id="tareaPuntaje" name="puntajeMaximo"  min="1" max="100" step="1" placeholder="Ej: 20" required>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaIntentos">Intentos permitidos</label>
                        <input type="number" id="tareaIntentos" name="intentos" min="1" max="10" step="1" value="1" required>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaEstado">Estado</label>
                        <select id="tareaEstado" name="estado" required>
                            <option value="1">Activa</option>
                            <option value="0">Borrador</option>
                        </select>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label>Archivo de apoyo <span class="contenido-muted">(opcional)</span></label>
                        <div class="adjunto-item tarea-adjunto-item">
                            <span class="adjunto-tipo">
                                <i class="fas fa-paperclip"></i> Archivo
                            </span>
                            <label class="adjunto-file-label">
                                <i class="fas fa-folder-open"></i>
                                <span class="adjunto-file-texto" id="tareaArchivoTexto">Seleccionar archivo</span>
                                <input type="file" id="tareaArchivo" name="archivo"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                                    class="adjunto-file-input">
                            </label>
                            <button type="button" class="adjunto-remove" id="limpiarArchivoTarea" title="Quitar archivo">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="contenido-modal-actions">
                    <button type="button" class="contenido-btn contenido-btn-light" id="cancelarTarea">Cancelar</button>
                    <button type="submit" class="contenido-btn contenido-btn-primary">
                        <i class="fas fa-save"></i> Guardar tarea
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="./js/script.js"></script>
</body>
</html>
