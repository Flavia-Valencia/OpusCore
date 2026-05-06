<?php
require_once 'mis_cursos.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Inscripciones</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">

    <input type="checkbox" id="sidebar-toggle">

    <!-- overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="layout">

        <!-- sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.svg" alt="Logo" class="logo-img">
                <span class="sidebar-brand logo-text-sidebar"><span>Academia</span><strong>Futuro Digital</strong></span>
                <div class="menu-user">
                    <div class="menu-user-role">Estudiante</div>
                    <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
                </div>
                <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Cerrar menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="vista_mis_cursos.php" class="nav-item active ">
                    <i class="fas fa-pen-to-square"></i>
                    <span>Mis cursos</span>
                </a>
                <a href="estudiante-inscripciones.php" class="nav-item">
                    <i class="fas fa-pen-to-square"></i>
                    <span>Inscripción</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>Calificaciones</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Pagos</span>
                </a>
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

        <!-- contenido principal -->
        <div class="content">

            <!-- header -->
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

               
                <?php if (empty($cursos)): ?>
                    <div class="inscripcion-vacia">
                        <i class="fas fa-book-open"></i>
                        <p>No tienes cursos inscritos actualmente.</p>
                    </div>
                    <?php else: ?>
                        <section class="courses-inscripcion">
                            <?php foreach ($cursos as $curso): ?>
                             <div class="curso-card">
                                    <div class="curso-card-top">
                                        <h3 class="curso-nombre">
                                            <?= htmlspecialchars($curso['nombre']) ?>
                                        </h3>

                                        <span class="curso-badge disponible">
                                            <?= htmlspecialchars($curso['estado_academico']) ?>
                                        </span>
                                    </div>

                                    <p class="curso-desc">
                                        <?= htmlspecialchars($curso['descripcion']) ?>
                                    </p>

                                    <div class="curso-divider"></div>

                                    <div class="curso-meta">
                                        <div class="meta-item">
                                            <span class="meta-label">Costo mensual</span>
                                            <span class="meta-value price">
                                                $<?= number_format($curso['costoMensual'], 2) ?>
                                            </span>
                                        </div>

                                        <div class="meta-item">
                                            <span class="meta-label">Inicio</span>
                                            <span class="meta-value">
                                                <?= htmlspecialchars($curso['fechaInicio']) ?>
                                            </span>
                                        </div>

                                        <div class="meta-item">
                                            <span class="meta-label">Fin</span>
                                            <span class="meta-value">
                                                <?= htmlspecialchars($curso['fechaFin']) ?>
                                            </span>
                                        </div>

                                        <div class="meta-item">
                                            <span class="meta-label">Inscripción</span>
                                            <span class="meta-value">
                                                <?= htmlspecialchars($curso['fecha_registro']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                <?php endif; ?>

        </div><!-- /content -->
    </div><!-- /layout -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
