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

$cursoValido = null;
$estudiantes = [];

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
    }
}

$tareasDemo = [
    [
        'titulo' => 'Guía práctica de fundamentos',
        'descripcion' => 'Resolver los ejercicios indicados para reforzar los contenidos vistos en clase.',
        'fecha' => '2026-05-29',
        'puntaje' => 20,
        'archivo' => 'Guia-practica.pdf',
        'estado' => 'Activa'
    ],
    [
        'titulo' => 'Actividad de investigación',
        'descripcion' => 'Preparar una síntesis breve sobre el tema asignado.',
        'fecha' => '2026-06-03',
        'puntaje' => 15,
        'archivo' => '',
        'estado' => 'Borrador'
    ]
];
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
    <link rel="stylesheet" href="./css/styles-docentes.css">
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
                    <li>
                        <i class="fas fa-chart-line"></i> Calificaciones
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i> Mensajes
                    </li>
                    <li>
                        <i class="fas fa-cog"></i> Configuración
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
                    <button type="button" class="contenido-btn contenido-btn-primary" id="btnNuevaTarea">
                        <i class="fas fa-plus"></i>
                        Nueva tarea
                    </button>
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
                        <p>Interfaz frontend para crear tareas, revisar entregas y asignar calificaciones del curso.</p>
                    </div>
                    <div class="organizacion-metricas">
                        <div>
                            <span>Tareas</span>
                            <strong id="tareasTotal"><?= count($tareasDemo) ?></strong>
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

                <section class="contenido-card">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Tareas registradas</h2>
                            <p>Vista local para crear, editar y visualizar tareas sin escribir en la base de datos.</p>
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
                                <?php foreach ($tareasDemo as $tarea): ?>
                                    <tr
                                        data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>"
                                        data-descripcion="<?= htmlspecialchars($tarea['descripcion']) ?>"
                                        data-fecha="<?= htmlspecialchars($tarea['fecha']) ?>"
                                        data-puntaje="<?= (int)$tarea['puntaje'] ?>"
                                        data-archivo="<?= htmlspecialchars($tarea['archivo']) ?>"
                                        data-estado="<?= htmlspecialchars($tarea['estado']) ?>"
                                    >
                                        <td data-label="Título">
                                            <strong><?= htmlspecialchars($tarea['titulo']) ?></strong>
                                            <span class="contenido-desc"><?= htmlspecialchars($tarea['descripcion']) ?></span>
                                        </td>
                                        <td data-label="Fecha límite"><?= date('d/m/Y', strtotime($tarea['fecha'])) ?></td>
                                        <td data-label="Puntaje"><?= (int)$tarea['puntaje'] ?> pts</td>
                                        <td data-label="Apoyo">
                                            <?php if (!empty($tarea['archivo'])): ?>
                                                <span class="contenido-archivo">
                                                    <i class="fas fa-paperclip"></i><?= htmlspecialchars($tarea['archivo']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="contenido-muted">Opcional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Estado">
                                            <span class="contenido-badge estado-<?= strtolower($tarea['estado']) ?>">
                                                <?= htmlspecialchars($tarea['estado']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="contenido-acciones">
                                                <button type="button" class="contenido-icon-btn editar-tarea" title="Editar tarea">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="contenido-card">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Entregas realizadas</h2>
                            <p>Listado visual con estudiantes inscritos actualmente en el curso.</p>
                        </div>
                    </div>

                    <div class="contenido-tabla-wrap">
                        <table class="contenido-tabla tareas-tabla">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Tarea</th>
                                    <th>Entrega</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($estudiantes)): ?>
                                    <tr class="contenido-empty">
                                        <td colspan="5">Este curso aún no tiene estudiantes activos inscritos.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estudiantes as $i => $estudiante): ?>
                                        <?php $estadoEntrega = $i % 3 === 0 ? 'Pendiente' : 'Entregada'; ?>
                                        <tr>
                                            <td data-label="Estudiante">
                                                <strong><?= htmlspecialchars(trim($estudiante['nombre'] . ' ' . $estudiante['apellido'])) ?></strong>
                                                <span class="contenido-desc"><?= htmlspecialchars($estudiante['correo']) ?></span>
                                            </td>
                                            <td data-label="Tarea">Guía práctica de fundamentos</td>
                                            <td data-label="Entrega">
                                                <?php if ($estadoEntrega === 'Entregada'): ?>
                                                    <span class="contenido-archivo">
                                                        <i class="fas fa-file-arrow-up"></i> entrega-estudiante.pdf
                                                    </span>
                                                <?php else: ?>
                                                    <span class="contenido-muted">Sin archivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Estado">
                                                <span class="contenido-badge estado-<?= strtolower($estadoEntrega) ?>">
                                                    <?= $estadoEntrega ?>
                                                </span>
                                            </td>
                                            <td data-label="Calificación">
                                                <div class="tarea-calificacion">
                                                    <input type="number" min="0" max="20" step="1" placeholder="0">
                                                    <button type="button" class="contenido-btn contenido-btn-light btn-calificar-tarea">
                                                        Calificar
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
                <div class="contenido-form-grid">
                    <div class="contenido-field contenido-field-wide">
                        <label for="tareaTitulo">Título</label>
                        <input type="text" id="tareaTitulo" maxlength="120" placeholder="Ej: Guía práctica de HTML" required>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label for="tareaDescripcion">Descripción</label>
                        <textarea id="tareaDescripcion" rows="4" placeholder="Indicaciones para realizar la tarea" required></textarea>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaFecha">Fecha límite</label>
                        <input type="date" id="tareaFecha" required>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaPuntaje">Puntaje</label>
                        <input type="number" id="tareaPuntaje" min="1" max="100" step="1" placeholder="Ej: 20" required>
                    </div>

                    <div class="contenido-field">
                        <label for="tareaEstado">Estado</label>
                        <select id="tareaEstado" required>
                            <option value="Activa">Activa</option>
                            <option value="Borrador">Borrador</option>
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
                                <input type="file" id="tareaArchivo"
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
                    <button type="submit" class="contenido-btn contenido-btn-primary">Guardar tarea</button>
                </div>
            </form>
        </div>
    </div>

    <script src="./js/script.js"></script>
</body>
</html>
