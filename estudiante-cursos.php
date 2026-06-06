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
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre, u.correo
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

$columnasCursos = [];
$resColumnasCursos = $conexion->query("SHOW COLUMNS FROM cursos");
if ($resColumnasCursos) {
    while ($columna = $resColumnasCursos->fetch_assoc()) {
        $columnasCursos[] = $columna['Field'];
    }
}

$tienePeriodoEnCursos = in_array('idPeriodo', $columnasCursos, true);
$selectPeriodo = $tienePeriodoEnCursos ? "COALESCE(pi.nombre, 'Sin periodo asignado')" : "'Sin periodo asignado'";
$joinPeriodo = $tienePeriodoEnCursos ? "LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id" : "";
$groupPeriodo = $tienePeriodoEnCursos ? ", pi.nombre" : "";

// FRONTEND: modulo informativo para estudiantes. Lista todos los cursos sin filtrar por periodo activo.
$sqlCursos = "
    SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.estado,
           COALESCE(cat.nombre, 'Sin categoria') AS categoria_nombre,
           $selectPeriodo AS periodo_nombre,
           COALESCE(GROUP_CONCAT(DISTINCT cursoPrevio.nombre ORDER BY cursoPrevio.nombre SEPARATOR ', '), 'Sin prerrequisito') AS prerrequisitos
    FROM cursos c
    LEFT JOIN categorias cat ON c.idCategoria = cat.id
    LEFT JOIN prerrequisitos pr ON pr.idCursoActual = c.id
    LEFT JOIN cursos cursoPrevio ON cursoPrevio.id = pr.idCursoPrevio
    $joinPeriodo
    GROUP BY c.id, c.nombre, c.descripcion, c.costoMensual, c.estado, cat.nombre $groupPeriodo
    ORDER BY c.estado DESC, c.nombre ASC
";

$resultadoCursos = $conexion->query($sqlCursos);
$cursos = $resultadoCursos ? $resultadoCursos->fetch_all(MYSQLI_ASSOC) : [];
// Llama categorias para el filtro 
$categorias = array_unique(array_column($cursos, 'categoria_nombre'));
sort($categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Todos los cursos</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="estudiante-cursos.php" class="nav-item active">
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
                <div class="nav-dropdown">
                    <button type="button" class="nav-item nav-dropdown-toggle" onclick="togglePagosOnline()" aria-expanded="false" aria-controls="pagosOnlineMenu">
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
                    <h1>Todos los cursos</h1>
                    <p>Explora los cursos que forman parte de la oferta académica.</p>
                </div>
                <div class="banner-fecha">
                    <strong id="fecha-hoy"></strong>
                </div>
            </div>

            <div class="inscripcion-toolbar">
                <input type="text" id="buscador-curso" placeholder="Buscar curso..." class="inscripcion-buscador">
                <select id="filtro-categoria" class="inscripcion-filtro">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>

            <?php if (empty($cursos)): ?>
                <div class="inscripcion-vacia">
                    <i class="fas fa-book-open"></i>
                    <p>No hay cursos registrados.</p>
                    <small>Cuando el administrador cree cursos, aparecerán en esta sección.</small>
                </div>
            <?php else: ?>
                <section class="courses-inscripcion">
                    <?php foreach ($cursos as $curso): ?>
                        <?php
                            $activo = (int) $curso['estado'] === 1;
                            $badgeClase = $activo ? 'disponible' : 'sin-cupos';
                            $badgeTexto = $activo ? 'Disponible' : 'Inactivo';
                        ?>
                        <div class="curso-card <?= !$activo ? 'sin-cupos' : '' ?>">
                            <div class="curso-card-top">
                                <div class="curso-nombre"><?= htmlspecialchars($curso['nombre']) ?></div>
                                <span class="curso-badge <?= $badgeClase ?>"><?= htmlspecialchars($badgeTexto) ?></span>
                            </div>

                            <p class="curso-desc"><?= htmlspecialchars($curso['descripcion']) ?></p>

                            <div class="curso-divider"></div>

                            <div class="curso-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Categoría</span>
                                    <span class="meta-value"><?= htmlspecialchars($curso['categoria_nombre']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Periodo</span>
                                    <span class="meta-value"><?= htmlspecialchars($curso['periodo_nombre']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Costo mensual</span>
                                    <span class="meta-value price">$<?= number_format((float) $curso['costoMensual'], 2) ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Prerrequisito</span>
                                    <span class="meta-value"><?= htmlspecialchars($curso['prerrequisitos']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
