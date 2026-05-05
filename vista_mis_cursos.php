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
    <style>
        #sidebar-toggle {
            display: none;
        }

        .raleway-all {
            font-family: "Raleway", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }

        .logo-text-sidebar {
            font-family: "Raleway", sans-serif;
            line-height: 1.2;
        }

        .logo-text-sidebar span {
            display: block;
            font-size: 9px;
            font-weight: 500;
            opacity: 0.65;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .logo-text-sidebar strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .sidebar {
            left: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-close,
        .sidebar-logout {
            display: none;
        }

        .menu-user {
            display: none;
        }

        .header-panel {
            background: linear-gradient(135deg, #053170, #1D4B73, #069DBF);
        }

        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
                padding: 20px 14px;
            }

            .sidebar.open,
            #sidebar-toggle:checked ~ .layout .sidebar {
                left: 0;
            }

            .sidebar-logo {
                margin-bottom: 16px;
            }

            .sidebar-close {
                display: block;
                margin-left: auto;
                position: absolute;
                background: none;
                border: 0;
                color: inherit;
                font-size: 20px;
                cursor: pointer;
                top: 10px;
                right: 14px;
            }

            .sidebar nav ul {
                margin-top: 10px;
            }

            .sidebar-nav {
                margin-top: 10px;
            }

            .sidebar li {
                justify-content: flex-start;
                padding: 12px 14px;
            }

            .nav-item {
                justify-content: flex-start;
                padding: 12px 14px;
            }

            .menu-user {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                padding: 5px 6px;
                color: white;
                margin-top: 6px;
            }

            .logo-text-sidebar {
                display: none;
            }

            .sidebar-logout {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: auto;
                padding-top: 14px;
                border-top: 1px solid rgba(255,255,255,0.15);
                text-decoration: none;
                color: inherit;
            }

            .user-profile,
            .user-profile-panel {
                display: none !important;
            }
        }

    </style>
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
                <a href="vista_mis_cursos.php" class="nav-item ">
                    <i class="fas fa-pen-to-square"></i>
                    <span>Mis cursos</span>
                </a>
                <a href="estudiante-inscripciones.php" class="nav-item active">
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
                    <p>No tienes cursos inscritos actualmente.</p>

                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Costo mensual</th>
                                <th>Fecha inicio</th>
                                <th>Fecha fin</th>
                                <th>Estado</th>
                                <th>Fecha inscripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?= htmlspecialchars($curso['nombre']) ?></td>
                                <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                                <td>$<?= number_format($curso['costoMensual'], 2) ?></td>
                                <td><?= $curso['fechaInicio'] ?></td>
                                <td><?= $curso['fechaFin'] ?></td>
                                <td><?= $curso['estado_academico'] ?></td>
                                <td><?= $curso['fecha_registro'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

        </div><!-- /content -->
    </div><!-- /layout -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
