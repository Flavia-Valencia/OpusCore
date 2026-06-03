<?php
// Carga la lista de cursos disponibles para el estudiante en el período activo.
// El filtro aquí excluye cursos ya inscritos y cursos con prerrequisitos no cumplidos.
require_once 'obtener-cursos-disponibles.php';
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
    <script src="https://www.paypal.com/sdk/js?client-id=Af2BotGg3h9wRXyUvU4sJPB1MDX9Mp74DMzh-v2YuU0sVHTN1POJ0LJriJ4x8J0D0kU_DATVXJMLkad2&currency=USD&locale=es_SV"></script>
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
                <a href="estudiante-cursos.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Todos los cursos</span>
                </a>
                <a href="vista_mis_cursos.php" class="nav-item ">
                    <i class="fas fa-book-open"></i>
                    <span>Mis cursos</span>
                </a>
                <a href="estudiante-inscripciones.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Inscripción</span>
                </a>
                <a href="estudiante-calificaciones.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Calificaciones</span>
                </a>
                <div class="nav-dropdown">
                    <button type="button" class="nav-item nav-dropdown-toggle" onclick="togglePagosOnline()">
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

            <!-- banner -->
            <div class="banner">
                <div class="banner-left">
                    <h1>Inscripción de Cursos 📋</h1>
                     <?php if ($periodo): ?>
                        <div class="periodo-banner">
                            <p>
                                Periodo activo:
                                <strong><?= htmlspecialchars($periodo['nombre']) ?></strong>
                            </p>

                            <p>
                                <?= $periodo['fechaInicio'] ?> → <?= $periodo['fechaFin'] ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <p>No hay un periodo de inscripción activo en este momento.</p>
                    <?php endif; ?>
                </div>
                <div class="banner-fecha">
                    <strong id="fecha-hoy"></strong>
                </div>
            </div>

            <!-- cursos disponibles para inscripción -->
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
                            <div class="curso-botones">
                                <button class="btn-inscribir"
                                    data-id="<?= $curso['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($curso['nombre']) ?>"
                                    data-descripcion="<?= htmlspecialchars($curso['descripcion']) ?>"
                                    data-horario="<?= htmlspecialchars($curso['horarios_etiqueta'] ?? 'Sin horario asignado') ?>"
                                    data-dias="<?= htmlspecialchars($curso['dias_semana'] ?? '') ?>"
                                    data-aula="<?= htmlspecialchars($curso['aulas_nombre'] ?? 'Sin aula asignada') ?>"
                                    data-docente="<?= htmlspecialchars($curso['docente_nombre'] ?? 'Sin docente asignado') ?>"
                                    data-fecha="<?= $curso['fechaInicio'] ?> → <?= $curso['fechaFin'] ?>"
                                    data-costo="$<?= number_format($curso['costoMensual'], 2) ?>"
                                    data-cupos="<?= $curso['cupos'] ?> disponibles"
                                    onclick="abrirModalInscripcion(this)">
                                    <i class="fas fa-pen-to-square"></i> Ver detalles
                                </button>
                                <button class="btn-inscribir"
                                    onclick="seleccionarCurso(this)"
                                    data-id="<?= $curso['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($curso['nombre']) ?>"
                                    data-costo="<?= number_format($curso['costoMensual'], 2, '.', '') ?>">
                                    Seleccionar
                                </button>
                            </div>
                        <?php else: ?>
                            <button class="btn-inscribir lleno" disabled>
                                <i class="fas fa-lock"></i> Sin cupos disponibles
                            </button>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </section>

            <?php endif; ?>

        </div><!-- /content -->

        <!-- BARRA DE INSCRIPCIÓN EMERGENTE -->
        <div id="barra-inscripcion" class="barra-inscripcion">
            <!-- En móvil esta pestaña queda fija a la derecha y abre el resumen sin tapar los cursos. -->
            <button
                type="button"
                id="barra-inscripcion-tab"
                class="barra-inscripcion-tab"
                onclick="toggleBarraInscripcion()"
                aria-expanded="false"
                aria-controls="barra-inscripcion-panel">
                <span id="barra-tab-count">0/5 cursos</span>
            </button>

            <!-- Este panel conserva el contenido original de la barra inferior para desktop y móvil. -->
            <div id="barra-inscripcion-panel" class="barra-card">
                <div class="barra-card-left">
                    <div class="barra-card-status">
                        <div class="barra-status-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <div class="barra-status-title">Cursos seleccionados</div>
                            <div id="barra-curso-count" class="barra-status-subtitle">0/5</div>
                        </div>
                    </div>
                    <div id="barra-cursos-nombres" class="barra-cursos-nombres"></div>
                </div>

                <div class="barra-card-center">
                    <div class="barra-total-label">Total con matrícula</div>
                    <div id="total-costo" class="barra-total-value">$0.00</div>
                    <div id="barra-progreso-dots" class="barra-progreso-dots"></div>
                    <div id="barra-porcentaje" class="barra-porcentaje">0%</div>
                </div>

                <div class="barra-card-actions">
                    <button type="button" class="btn-cancelar" onclick="cancelarInscripcion()">Cancelar selección</button>
                    <button type="button" class="btn-guardar-premium" onclick="confirmarInscripcion()">Continuar al pago</button>
                </div>
            </div>
        </div>

    </div><!-- /layout -->


<!-- MODAL CONFIRMAR INSCRIPCIÓN -->
<div id="modalInscripcion" class="modal-overlay">
    <div class="modal-contenido modal-horarios-premium">
        <button class="modal-cerrar" onclick="cerrarModalInscripcion()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-pen-to-square"></i> Detalles Generales
        </h2>

        <h3 class="modal-subtitulo">Detalle del curso</h3>

        <div class="horario-card-registro">
            <div class="horario-grid">
                <div class="horario-campo">
                    <label>CURSO</label>
                    <p id="modalCursoNombre"></p>
                </div>
                <div class="horario-campo full-width">
                    <label>DESCRIPCIÓN</label>
                    <p id="modalCursoDescripcion"></p>
                </div>
                <div class="horario-campo full-width">
                    <label>DOCENTE</label>
                    <p id="modalCursoDocente"></p>
                </div>
                <div class="horario-campo">
                    <label>HORARIO</label>
                    <p id="modalCursoHorario"></p>
                </div>
                <div class="horario-campo">
                    <label>DÍAS</label>
                    <p id="modalCursoDias"></p>
                </div>
                <div class="horario-campo">
                    <label>AULA</label>
                    <p id="modalCursoAula"></p>
                </div>
                <div class="horario-campo">
                    <label>FECHA</label>
                    <p id="modalCursoFecha"></p>
                </div>
                <div class="horario-campo">
                    <label>COSTO</label>
                    <p id="modalCursoCosto"></p>
                </div>
                <div class="horario-campo">
                    <label>CUPOS</label>
                    <p id="modalCursoCupos"></p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-cancelar" onclick="cerrarModalInscripcion()">Volver</button>
            
        </div>
    </div>
</div>

<!-- MODAL DE PAGO -->
<div id="modalPago" class="modal-overlay">
    <div class="modal-contenido modal-pago">
        <button class="modal-cerrar" onclick="cerrarModalPago()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-credit-card"></i> Procesar Pago
        </h2>

        <div class="pago-resumen">
            <h3>Resumen de Inscripción</h3>
            <div id="pago-lista-cursos" class="pago-lista-cursos"></div>
            <!-- ← Línea de matrícula -->
            <div class="pago-total-line" id="linea-matricula">
                <span>Matrícula</span>
                <span>$25.00</span>
            </div>
            <div class="pago-divider"></div>
            <div class="pago-total-line">
                <strong>Total a pagar:</strong>
                <span id="pago-total">$0.00</span>
            </div>
        </div>

        <div class="pago-metodo">
            <h3>Método de Pago</h3>
            <div class="pago-paypal-container">
                <!-- PayPal Buttons Placeholder -->
                <div id="paypal-button-container"></div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-cancelar" onclick="cerrarModalPago()">Cancelar</button>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>
</body>
</html>
