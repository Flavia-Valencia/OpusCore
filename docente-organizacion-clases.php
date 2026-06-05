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

$contenidos = [];
$publicados = 0;
$deshabilitados = 0;

if ($cursoId > 0) {
    $stmt = $conexion->prepare("
        SELECT 
            sc.id,
            c.nombre AS curso,
            sc.titulo,
            sc.descripcion,
            sc.fecha,
            sc.estado,
            GROUP_CONCAT(
                CASE WHEN sa.tipo = 'Archivo' THEN sa.nombreArchivo END
                ORDER BY sa.id SEPARATOR ', '
            ) AS archivos,
            GROUP_CONCAT(
                CASE WHEN sa.tipo = 'Enlace' THEN sa.nombreArchivo END
                ORDER BY sa.id SEPARATOR ', '
            ) AS enlaces,
            GROUP_CONCAT(
                CASE WHEN sa.tipo = 'Archivo' THEN sa.id END
                ORDER BY sa.id SEPARATOR ','
            ) AS archivo_ids,
            GROUP_CONCAT(
                CASE WHEN sa.tipo = 'Enlace' THEN sa.id END
                ORDER BY sa.id SEPARATOR ','
            ) AS enlace_ids
        FROM sesionContenido sc
        INNER JOIN cursos c ON c.id = sc.idCurso
        LEFT JOIN sesionArchivos sa ON sa.idSesion = sc.id
        WHERE sc.idCurso = ?
        GROUP BY sc.id
        ORDER BY sc.id ASC
    ");
    $stmt->bind_param('i', $cursoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $contenidos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $publicados     = count(array_filter($contenidos, fn($c) => $c['estado'] == 1));
    $deshabilitados = count(array_filter($contenidos, fn($c) => $c['estado'] == 0));
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
    <title>ADF | Organización de clases</title>
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
                    <p class="section-title organizacion-title">Organización de clases</p>
                    <h1><?= htmlspecialchars($cursoSeleccionado) ?></h1>
                </div>
                <button type="button" class="contenido-btn contenido-btn-primary" id="btnNuevoContenido">
                    <i class="fas fa-plus"></i>
                    Nuevo contenido
                </button>
            </div>

            <section class="banner organizacion-banner">
                <div class="banner-left">
                    <h2>Contenidos por sesión</h2>
                    <p>Programar materiales, instrucciones y archivos para las sesiones de este curso.</p>
                </div>
                <div class="organizacion-metricas">
                    <div>
                        <span>Publicados</span>
                        <strong><?= str_pad($publicados, 2, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                  
                    <div>
                        <span>Deshabilitados</span>
                        <strong><?= str_pad($deshabilitados, 2, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                </div>
            </section>

            <section class="contenido-card contenido-filtros" aria-label="Filtros de contenidos">
                <div class="contenido-field contenido-field-wide">
                    <label for="buscarContenido">Buscar contenido</label>
                    <input type="search" id="buscarContenido" placeholder="Título o sesión">
                </div>
                <div class="contenido-field">
                    <label for="filtroEstadoContenido">Estado</label>
                    <select id="filtroEstadoContenido">
                        <option value="">Todos</option>
                        <option value="Publicado">Publicado</option>
                        <option value="Deshabilitado">Deshabilitado</option>
                    </select>
                </div>
            </section>

            <section class="contenido-card">
                <div class="contenido-card-header">
                    <div>
                        <h2>Contenidos registrados</h2>
                    </div>
                </div>

                <div class="contenido-tabla-wrap">
                    <table class="contenido-tabla organizacion-contenidos-tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sesión</th>
                                <th>Título</th>
                                <th>Fecha publicación</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaContenidosBody">
                            <?php if (empty($contenidos)): ?>
                                <tr class="contenido-empty">
                                    <td colspan="7">Este curso aún no tiene contenidos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contenidos as $i => $contenido): ?>
                                    <?php
                                        $estadoTexto = $contenido['estado'] == 1 ? 'Publicado' : 'Deshabilitado';
                                        $sesionNum   = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                                    ?>
                                    <tr
                                        data-id="<?= (int)$contenido['id'] ?>"
                                        data-curso="<?= htmlspecialchars($contenido['curso']) ?>"
                                        data-sesion="Sesión <?= $sesionNum ?>"
                                        data-titulo="<?= htmlspecialchars($contenido['titulo']) ?>"
                                        data-descripcion="<?= htmlspecialchars($contenido['descripcion'] ?? '') ?>"
                                        data-fecha="<?= htmlspecialchars($contenido['fecha']) ?>"
                                        data-archivo="<?= htmlspecialchars($contenido['archivos'] ?? '') ?>"
                                        data-enlaces="<?= htmlspecialchars($contenido['enlaces'] ?? '') ?>"
                                        data-archivo-ids="<?= htmlspecialchars($contenido['archivo_ids'] ?? '') ?>"
                                        data-enlace-ids="<?= htmlspecialchars($contenido['enlace_ids'] ?? '') ?>"
                                        data-estado="<?= $estadoTexto ?>"
                                    >
                                        <td data-label="ID"><?= (int)$contenido['id'] ?></td>
                                        <td data-label="Sesión">Sesión <?= $sesionNum ?></td>
                                        <td data-label="Título">
                                            <strong><?= htmlspecialchars($contenido['titulo']) ?></strong>
                                            <span class="contenido-desc"><?= htmlspecialchars($contenido['descripcion'] ?? '') ?></span>
                                        </td>
                                        <td data-label="Fecha publicación"><?= date('d/m/Y', strtotime($contenido['fecha'])) ?></td>
                                        <td data-label="Archivo">
                                            <?php if (!empty($contenido['archivos'])): ?>
                                                <span class="contenido-archivo">
                                                    <i class="fas fa-paperclip"></i><?= htmlspecialchars($contenido['archivos']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($contenido['enlaces'])): ?>
                                                <span class="contenido-archivo">
                                                    <i class="fas fa-link"></i><?= htmlspecialchars($contenido['enlaces']) ?>
                                                </span>
                                            <?php else: ?>
                                                <?php if (empty($contenido['archivos'])): ?>
                                                    <span class="contenido-muted">Sin adjuntos</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Estado">
                                            <span class="contenido-badge estado-<?= strtolower($estadoTexto) ?>">
                                                <?= $estadoTexto ?>
                                            </span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="contenido-acciones">
                                                <button type="button" class="contenido-icon-btn editar-contenido" title="Editar contenido">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" class="contenido-toggle <?= $contenido['estado'] == 0 ? 'btn-habilitar' : '' ?>" data-action="toggle">
                                                    <?= $contenido['estado'] == 0 ? 'Habilitar' : 'Deshabilitar' ?>
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

        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>

    <div class="contenido-modal" id="modalContenido" aria-hidden="true">
        <div class="contenido-modal-box" role="dialog" aria-modal="true" aria-labelledby="contenidoModalTitulo">
            <div class="contenido-modal-header">
                <div>
                    <span>Contenido de clase</span>
                    <h2 id="contenidoModalTitulo">Nuevo contenido</h2>
                </div>
                <button type="button" class="contenido-modal-close" id="cerrarModalContenido" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formContenidoClase" novalidate>
                <input type="hidden" id="contenidoId">
                <input type="hidden" id="contenidoCursoId" value="<?= (int)$cursoId ?>">

                <div class="contenido-form-grid">
                    <div class="contenido-field">
                        <label>Curso</label>
                        <input type="text" id="contenidoCurso" value="<?= htmlspecialchars($cursoSeleccionado) ?>" readonly>
                    </div>

                    <div class="contenido-field">
                        <label for="contenidoSesion">Número de sesión</label>
                        <input type="text" id="contenidoSesion" readonly tabindex = "-1">
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label for="contenidoTitulo">Título</label>
                        <input type="text" id="contenidoTitulo" maxlength="120" placeholder="Ej: Introducción a HTML semántico" required>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label for="contenidoDescripcion">Descripción</label>
                        <textarea id="contenidoDescripcion" rows="4" placeholder="Resumen del material o indicaciones para la sesión" required></textarea>
                    </div>

                    <div class="contenido-field">
                        <label for="contenidoFecha">Fecha de publicación</label>
                        <input type="date" id="contenidoFecha" required>
                    </div>

                    <div class="contenido-field">
                        <label for="contenidoEstado">Estado</label>
                        <select id="contenidoEstado" required>
                            <option value="Publicado">Publicado</option>
                            <option value="Deshabilitado">Deshabilitado</option>
                        </select>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label>Archivos adjuntos <span class="contenido-muted">(opcional)</span></label>

                        <div id="adjuntosActuales" class="adjuntos-actuales"></div>
                        <div id="listaAdjuntos">
                            <!-- Los items se agregan dinámicamente -->
                        </div>
                        <div class="contenido-adjunto-btns">
                            <button type="button" class="contenido-btn contenido-btn-light" id="btnAgregarArchivo">
                                <i class="fas fa-paperclip"></i> Agregar archivo
                            </button>
                            <button type="button" class="contenido-btn contenido-btn-light" id="btnAgregarEnlace">
                                <i class="fas fa-link"></i> Agregar enlace
                            </button>
                        </div>
                    </div>
                </div>

                <div class="contenido-modal-actions">
                    <button type="button" class="contenido-btn contenido-btn-light" id="cancelarContenido">Cancelar</button>
                    <button type="submit" class="contenido-btn contenido-btn-primary">Guardar contenido</button>
                </div>
            </form>
        </div>
    </div>

    <script src="./js/script.js"></script>
</body>
</html>
