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
    SELECT e.id FROM estudiantes e
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

$idEstudiante = $estudiante['id'];

$periodoStmt = $conexion->query("SELECT * FROM PeriodoInscripcion WHERE estado = 1 LIMIT 1");
$periodo = $periodoStmt->fetch_assoc();

$cursos = [];
if ($periodo) {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.cupos, c.fechaInicio, c.fechaFin
        FROM cursos c
        WHERE c.estado = 1
        AND NOT EXISTS (
            SELECT 1 FROM prerrequisitos pr
            WHERE pr.idCursoActual = c.id
        )
        ORDER BY c.nombre ASC
    ");
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cursos = $resultado->fetch_all(MYSQLI_ASSOC);
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
    <title>ADF | Inscripciones</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">


    <div class="layout">


        <div class="content">

            <header class="header-panel">
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
                    <h1>Inscripción de Cursos 📋</h1>
                    <p>
                        <?php if ($periodo): ?>
                            Periodo activo: <strong><?= htmlspecialchars($periodo['nombre']) ?></strong>
                            &nbsp;·&nbsp; <?= $periodo['fechaInicio'] ?> → <?= $periodo['fechaFin'] ?>
                        <?php else: ?>
                            No hay un periodo de inscripción activo en este momento.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="banner-fecha">
                    <strong id="fecha-hoy"></strong>
                </div>
            </div>

            <?php if (!$periodo): ?>

                <div class="inscripcion-vacia">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>No hay un periodo de inscripción activo.</p>
                    <small>Consulta con tu administrador para más información.</small>
                </div>

            <?php elseif (empty($cursos)): ?>

                <div class="inscripcion-vacia">
                    <i class="fas fa-book-open"></i>
                    <p>No hay cursos disponibles para inscribir en este periodo.</p>
                    <small>Vuelve pronto, se habilitarán nuevos cursos.</small>
                </div>

            <?php else: ?>

                <div class="inscripcion-toolbar">
                    <input type="text" id="buscador-curso" placeholder="🔎 Buscar curso..." class="inscripcion-buscador">
                </div>

                <section class="courses-inscripcion">
                    <?php foreach ($cursos as $curso):
                        $sinCupos = $curso['cupos'] <= 0;
                        $ultimosCupos = $curso['cupos'] > 0 && $curso['cupos'] <= 5;
                    ?>
                    <div class="curso-card <?= $sinCupos ? 'sin-cupos' : '' ?>">

                        <div class="curso-card-top">
                            <div class="curso-nombre"><?= htmlspecialchars($curso['nombre']) ?></div>
                            <?php if ($sinCupos): ?>
                                <span class="curso-badge sin-cupos">Sin cupos</span>
                            <?php elseif ($ultimosCupos): ?>
                                <span class="curso-badge ultimos">Últimos cupos</span>
                            <?php else: ?>
                                <span class="curso-badge disponible">Disponible</span>
                            <?php endif; ?>
                        </div>

                        <p class="curso-desc"><?= htmlspecialchars($curso['descripcion']) ?></p>

                        <div class="curso-divider"></div>

                        <div class="curso-meta">
                            <div class="meta-item">
                                <span class="meta-label">Inicio</span>
                                <span class="meta-value"><?= $curso['fechaInicio'] ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Fin</span>
                                <span class="meta-value"><?= $curso['fechaFin'] ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Cupos</span>
                                <span class="meta-value <?= $sinCupos ? 'sin-cupos-text' : '' ?>">
                                    <?= $sinCupos ? 'Sin cupos' : $curso['cupos'] . ' disponibles' ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Costo mensual</span>
                                <span class="meta-value price">$<?= number_format($curso['costoMensual'], 2) ?></span>
                            </div>
                        </div>

                        <?php if (!$sinCupos): ?>
                            <form method="POST" action="inscribir-curso.php">
                                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                <button type="submit" class="btn-inscribir">
                                    <i class="fas fa-pen-to-square"></i> Inscribirme
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn-inscribir lleno" disabled>
                                <i class="fas fa-lock"></i> Sin cupos disponibles
                            </button>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </section>

            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
    <script>
        const fechaEl = document.getElementById('fecha-hoy');
        if (fechaEl) {
            fechaEl.textContent = new Date().toLocaleDateString('es-ES', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        }
    </script>
</body>
</html>