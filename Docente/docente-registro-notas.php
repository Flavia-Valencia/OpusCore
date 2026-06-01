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

require_once '../includes/conexion.php';
include_once '../api/obtener/obtener-cursos-docente.php';

$cursoValido = null;
$estudiantes = [];

if ($cursoId > 0) {
    // Validar que el curso pertenezca al docente actual
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

        // Estudiantes inscritos activos + notas del plazo activo si existen
        $stmt = $conexion->prepare("
            SELECT i.id AS inscripcion_id,
                   e.id AS estudiante_id,
                   u.nombre,
                   u.apellido,
                   u.correo,
                   rn.actividades,
                   rn.examenFinal,
                   rn.notaFinal
            FROM inscripciones i
            INNER JOIN estudiantes e ON i.idEstudiante = e.id
            INNER JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN RegistroNotas rn
                ON rn.idEstudiante = e.id
                AND rn.idCurso = i.idCurso
                AND rn.idPlazo = (
                    SELECT pn2.id
                    FROM PlazoNotas pn2
                    INNER JOIN cursos c2 ON pn2.idPeriodo = c2.idPeriodo
                    WHERE c2.id = i.idCurso
                      AND CURDATE() BETWEEN pn2.plazoInicio AND pn2.plazoFin
                    LIMIT 1
                )
            WHERE i.idCurso = ?
              AND i.estado_academico = 'Activo'
            ORDER BY u.apellido, u.nombre
        ");
        $stmt->bind_param('i', $cursoId);
        $stmt->execute();
        $estudiantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    // Si no hay curso_id, obtener todos los cursos del docente
    $cursos = getCursosDocente($conexion, $_SESSION["usuario"]);
}

// Obtener plazo activo para este curso
$plazoActivo = null;
$stmt = $conexion->prepare("
    SELECT pn.id, pn.nombre, pn.plazoFin
    FROM PlazoNotas pn
    INNER JOIN cursos c ON pn.idPeriodo = c.idPeriodo
    WHERE c.id = ?
      AND pn.estado = 1
      AND CURDATE() BETWEEN pn.plazoInicio AND pn.plazoFin
    LIMIT 1
");
$stmt->bind_param('i', $cursoId);
$stmt->execute();
$plazoActivo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Promedio grupal real desde BD
$promedioGrupal = null;
if ($plazoActivo && $cursoValido) {
    $stmt = $conexion->prepare("
        SELECT ROUND(AVG(rn.notaFinal), 2) AS promedio
        FROM RegistroNotas rn
        WHERE rn.idCurso = ?
          AND rn.idPlazo = ?
    ");
    $stmt->bind_param('ii', $cursoId, $plazoActivo['id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $promedioGrupal = $res['promedio'];
    $stmt->close();
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
    <title>ADF | Registro de Notas</title>
    <link rel="icon" type="image/svg+xml" href="../img/logo.svg">
    <link rel="stylesheet" href="../css/styles-docentes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="../img/logo.svg" alt="Logo Academia" class="logo-img-sidebar">
                <div class="logo-text-sidebar">
                    <span>Academia</span>
                    <strong>Futuro Digital</strong>
                </div>
                <div class="menu-user">
                    <div class="menu-user-role">Docente</div>
                    <div class="menu-user-email">
                        <?php echo htmlspecialchars($_SESSION["usuario"]); ?>
                    </div>
                </div>
            </div>

            <nav>
                <ul>
                    <li onclick="window.location.href='docentes.php'">
                        <i class="fas fa-book"></i> Mis Cursos
                    </li>
                    <li class="active" onclick="window.location.href='docente-registro-notas.php'">
                        <i class="fas fa-chart-line"></i> Registro de Notas
                    </li>
                </ul>
            </nav>

            <label for="sidebar-toggle" class="sidebar-close">
                <i class="fas fa-times"></i>
            </label>

            <a href="../includes/logout.php" class="sidebar-logout">
                <i class="fas fa-arrow-right-from-bracket"></i> Cerrar sesión
            </a>
        </aside>

        <div class="content organizacion-page">
            <header class="header">
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>

                <a href="../includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role">
                            <?php echo htmlspecialchars($_SESSION["rol"] ?? "Docente"); ?>
                        </span>
                        <span class="user-email">
                            <?php echo htmlspecialchars($_SESSION["usuario"]); ?>
                        </span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <?php if ($cursoId === 0): ?>
                <!-- Vista de cursos en tarjeta para asignar notas -->
                <section class="banner organizacion-banner">
                    <div class="banner-left">
                        <h2>Registro de Notas de Estudiantes</h2>
                        <p>Selecciona un curso para ingresar las calificaciones de los estudiantes.</p>
                    </div>
                </section>

                <?php if (empty($cursos)): ?>
                    <div class="no-calificaciones">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>No tienes cursos asignados activos en el periodo actual.</p>
                    </div>
                <?php else: ?>
                    <section class="courses" style="margin-top: 24px;">
                        <?php foreach ($cursos as $curso): ?>
                            <div class="card curso-card-docente" style="border-left: 3px solid #5946a8;">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <?php echo htmlspecialchars($curso['nombre']); ?>
                                    </h3>
                                    <div class="badges-curso">
                                        <span class="badge">Activo</span>
                                        <span class="badge badge-periodo">
                                            <?php echo !empty($curso['periodo_nombre'])
                                                ? htmlspecialchars($curso['periodo_nombre'])
                                                : 'Sin periodo'; ?>
                                        </span>
                                    </div>
                                </div>

                                <p class="card-desc">
                                    <?php echo htmlspecialchars($curso['descripcion']); ?>
                                </p>

                                <div class="card-divider"></div>

                                <div class="card-meta">
                                    <div class="meta-item">
                                        <span class="meta-label">Inscritos</span>
                                        <span class="meta-value">
                                            <?php echo $curso['alumnos_inscritos']; ?> activos
                                        </span>
                                    </div>
                                </div>

                                <div class="curso-acciones-panel">
                                    <a class="card-action card-action-secondary"
                                       href="docente-registro-notas.php?curso_id=<?php echo $curso['id']; ?>&curso=<?php echo urlencode($curso['nombre']); ?>"
                                       style="width: 100%; margin-top: 0;">
                                        <i class="fas fa-edit"></i> Registrar Notas
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            <?php else: ?>
                <!-- Vista de registro de notas -->
                <div class="organizacion-topbar">
                    <div>
                        <a href="Docente/docente-registro-notas.php" class="organizacion-back">
                            <i class="fas fa-arrow-left"></i>
                            Volver a selección de cursos
                        </a>
                        <p class="section-title organizacion-title">
                            Registra las notas de este curso
                        </p>
                        <h1><?php echo htmlspecialchars($cursoSeleccionado); ?></h1>
                    </div>
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
                            <h2>Información de calificaciones del curso</h2>
                            <p>Ingrese las notas de los estudiantes en la sección correspondiente.</p>
                        </div>
                        <div class="organizacion-metricas">
                            <div>
                                <span>Estudiantes</span>
                                <strong><?php echo count($estudiantes); ?></strong>
                            </div>
                            <div>
                                <span>Evaluaciones</span>
                                <strong>2</strong>
                            </div>
                        </div>
                    </section>

                    <section class="contenido-card entregas-docente-card">
                        <div class="contenido-card-header">
                            <div>
                                <h2>Estudiantes Inscritos</h2>
                                <p>Ingresa notas en la escala de 0.00 a 10.00.</p>
                            </div>
                        </div>

                        <div class="entregas-toolbar">
                            <input type="search"
                                   id="buscarEstudiantes"
                                   class="entrega-buscador"
                                   placeholder="Buscar estudiante...">
                        </div>

                        <div class="contenido-tabla-wrap">
                            <table class="contenido-tabla tareas-tabla">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th style="text-align: center;">Nota 1</th>
                                        <th style="text-align: center;">Nota 2</th>
                                        <th style="text-align: center;">Promedio Final</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaEstudiantesBody">
                                    <?php if (empty($estudiantes)): ?>
                                        <tr class="contenido-empty">
                                            <td colspan="5">
                                                No hay estudiantes activos inscritos en este curso.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($estudiantes as $estudiante): ?>
                                            
                                            <?php
                                                $tieneNota     = $estudiante['actividades'] !== null;
                                                $clasePromedio = 'promedio-vacio';
                                                $textoPromedio = '—';
                                                if ($estudiante['notaFinal'] !== null) {
                                                    $textoPromedio = number_format($estudiante['notaFinal'], 2);
                                                    $clasePromedio = $estudiante['notaFinal'] >= 6
                                                        ? 'promedio-aprobado'
                                                        : 'promedio-reprobado';
                                                }
                                            ?>
                                            <tr class="estudiante-row"
                                                data-search="<?php echo htmlspecialchars(strtolower(
                                                    $estudiante['nombre'] . ' ' .
                                                    $estudiante['apellido'] . ' ' .
                                                    $estudiante['correo']
                                                )); ?>">

                                                <!-- Estudiante -->
                                                <td data-label="Estudiante" style="text-align: left;">
                                                    <strong>
                                                        <?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?>
                                                    </strong>
                                                    <span class="contenido-desc">
                                                        <?php echo htmlspecialchars($estudiante['correo']); ?>
                                                    </span>
                                                </td>

                                                <!-- Nota 1 (actividades) -->
                                                <td data-label="Nota 1">
                                                    <div class="grade-input-container">
                                                        <input type="number"
                                                               class="nota-input"
                                                               data-insc-id="<?php echo $estudiante['inscripcion_id']; ?>"
                                                               data-estudiante-id="<?php echo $estudiante['estudiante_id']; ?>"
                                                               data-nota-num="1"
                                                               min="0" max="10" step="0.01"
                                                               placeholder="—"
                                                               value="<?php echo $tieneNota ? htmlspecialchars($estudiante['actividades']) : ''; ?>"
                                                               <?php if ($tieneNota || !$plazoActivo): ?>readonly<?php endif; ?>>
                                                        <span class="save-indicator"></span>
                                                    </div>
                                                </td>

                                                <!-- Nota 2 (examen final) -->
                                                <td data-label="Nota 2">
                                                    <div class="grade-input-container">
                                                        <input type="number"
                                                               class="nota-input"
                                                               data-insc-id="<?php echo $estudiante['inscripcion_id']; ?>"
                                                               data-estudiante-id="<?php echo $estudiante['estudiante_id']; ?>"
                                                               data-nota-num="2"
                                                               min="0" max="10" step="0.01"
                                                               placeholder="—"
                                                               value="<?php echo $tieneNota ? htmlspecialchars($estudiante['examenFinal']) : ''; ?>"
                                                               <?php if ($tieneNota || !$plazoActivo): ?>readonly<?php endif; ?>>
                                                        <span class="save-indicator"></span>
                                                    </div>
                                                </td>

                                                <!-- Promedio Final -->
                                                <td data-label="Promedio Final">
                                                    <span class="promedio-badge <?php echo $clasePromedio; ?>"
                                                          id="promedio-<?php echo $estudiante['inscripcion_id']; ?>">
                                                        <?php echo $textoPromedio; ?>
                                                    </span>
                                                </td>

                                                <!-- Acciones -->
                                                <td data-label="Acciones">
                                                    <div class="acciones-nota-group">
                                                        <?php if (!$plazoActivo): ?>
                                                            <span class="nota-bloqueada-label">
                                                                <i class="fas fa-lock"></i> Plazo cerrado
                                                            </span>
                                                        <?php else: ?>
                                                            <?php if ($tieneNota): ?>
                                                                <button class="btn-nota-editar">
                                                                    <i class="fas fa-pen"></i> Editar
                                                                </button>
                                                            <?php endif; ?>
                                                            <button class="btn-guardar-nota"
                                                                    <?php if ($tieneNota): ?>disabled style="display:none;"<?php endif; ?>>
                                                                <i class="fas fa-save"></i> Guardar
                                                            </button>
                                                        <?php endif; ?>
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
            <?php endif; ?>
        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>

    <?php if ($cursoId > 0 && $cursoValido): ?>
        <script>
            const cursoId = <?php echo $cursoId; ?>;
            const totalEstudiantes = <?php echo count($estudiantes); ?>;
            const plazoActivo = <?php echo $plazoActivo ? json_encode($plazoActivo) : 'null'; ?>;
            const promedioGrupalInicial = <?php echo $promedioGrupal !== null ? $promedioGrupal : 'null'; ?>;
        </script>

        <script src="../js/script.js"></script>

    <?php endif; ?>

</body>
</html>