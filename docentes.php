<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fuentes e iconos de la interfaz -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>ADF | Panel Docente</title>
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
                    <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
                </div>
            </div>

            <nav>

                <ul>
                    <li class="active" onclick="window.location.href='docentes.php'">
                        <i class="fas fa-book"></i> Mis Cursos
                    </li>
                    <li onclick="window.location.href='docente-registro-notas.php'">
                        <i class="fas fa-chart-line"></i> Registro de Notas
                    </li>
                    <li onclick="window.location.href='docente-constancias.php'">
                        <i class="fas fa-file-alt"></i> Constancias
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

        <div class="content">

            <header class="header">

                <!-- Control del menu movil -->
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>

                <a href="includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role">
                            <?php echo isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : "Docente"; ?>
                        </span>
                        <span class="user-email">
                            <?php echo isset($_SESSION["usuario"]) ? htmlspecialchars($_SESSION["usuario"]) : ""; ?>
                        </span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>

            </header>

            <div class="banner">
                <div class="banner-left">
                    <h1>
                        ¡Bienvenido/a, <?php echo htmlspecialchars(isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "Docente"); ?>! 👋
                    </h1>
                    <p>Panel docente · Academia Futuro Digital</p>
                </div>

                <div class="banner-fecha">
                    <strong id="fecha-hoy"></strong>
                </div>
            </div>

            <p class="section-title">Mis Cursos</p>

            <!-- Cursos activos asignados al docente -->
            <section class="courses">
                <?php
                include("includes/conexion.php");
                include("obtener-cursos-docente.php");

                $cursos = getCursosDocente($conexion, $_SESSION["usuario"]);

                if (!empty($cursos)):
                    foreach ($cursos as $curso): ?>
                        <div
                            class="card curso-card-docente"
                            data-curso-id="<?= (int)$curso['id'] ?>"
                            data-curso-nombre="<?= htmlspecialchars($curso['nombre']) ?>"
                            tabindex="0"
                        >
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($curso['nombre']); ?></h3>

                                <div class="badges-curso">
                                    <span class="badge">Activo</span>

                                    <span class="badge badge-periodo">
                                        <?php echo !empty($curso['periodo_nombre']) ? htmlspecialchars($curso['periodo_nombre']) : 'Sin periodo'; ?>
                                    </span>
                                </div>
                            </div>
                            <p class="card-desc"><?php echo htmlspecialchars($curso['descripcion']); ?></p>
                            <div class="card-divider"></div>
                            <div class="card-meta">

                                <div class="meta-item">
                                    <span class="meta-label">Inicio</span>
                                    <span class="meta-value"><?php echo date('d/m/Y', strtotime($curso['fechaInicio'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Fin</span>
                                    <span class="meta-value"><?php echo date('d/m/Y', strtotime($curso['fechaFin'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Inscritos</span>
                                    <span class="meta-value"><?php echo $curso['alumnos_inscritos']; ?> </span>
                                </div>
                               
                            </div>
                            <!-- Accesos de gestion para el curso -->
                            <div class="curso-acciones-panel">
                                <p>¿Qué quieres gestionar?</p>
                                <div class="curso-acciones-grid">
                                    <a
                                        class="card-action"
                                        href="docente-organizacion-clases.php?curso_id=<?= urlencode($curso['id']) ?>&curso=<?= urlencode($curso['nombre']) ?>"
                                    >
                                        <i class="fas fa-folder-open"></i>
                                        Organizar clases
                                    </a>
                                    <a
                                        class="card-action card-action-secondary"
                                        href="docente-gestion-tareas.php?curso_id=<?= urlencode($curso['id']) ?>&curso=<?= urlencode($curso['nombre']) ?>"
                                    >
                                        <i class="fas fa-clipboard-list"></i>
                                        Gestionar tareas
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="no-cursos">No tienes cursos asignados por el momento.</p>
                <?php endif; ?>
            </section>

        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>
    
    <script src="./js/script.js"></script>

</body>
</html>
