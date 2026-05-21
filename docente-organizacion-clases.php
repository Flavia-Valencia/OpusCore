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

$contenidos = [
    [
        'id' => 1,
        'curso' => 'Diseño de Páginas Web',
        'sesion' => 'Sesión 01',
        'titulo' => 'Introducción a HTML semántico',
        'descripcion' => 'Material base para reconocer estructura semántica y etiquetas principales.',
        'fecha' => '2026-05-18',
        'archivo' => 'guia-html-semantico.pdf',
        'estado' => 'Publicado'
    ],
    [
        'id' => 2,
        'curso' => 'Administración de Sistemas Operativos',
        'sesion' => 'Sesión 03',
        'titulo' => 'Comandos básicos de terminal',
        'descripcion' => 'Resumen de comandos iniciales y práctica guiada para consola.',
        'fecha' => '2026-05-22',
        'archivo' => 'terminal-basica.docx',
        'estado' => 'Borrador'
    ],
    [
        'id' => 3,
        'curso' => 'Diseño de Páginas Web',
        'sesion' => 'Sesión 02',
        'titulo' => 'Selectores CSS y cascada',
        'descripcion' => 'Ejemplos de selectores, especificidad y reglas visuales.',
        'fecha' => '2026-05-25',
        'archivo' => '',
        'estado' => 'Deshabilitado'
    ],
];

if ($cursoId > 0 && $cursoSeleccionado !== 'Curso seleccionado') {
    $contenidos = array_values(array_filter($contenidos, function ($contenido) use ($cursoSeleccionado) {
        return $contenido['curso'] === $cursoSeleccionado;
    }));
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
                        <strong>08</strong>
                    </div>
                    <div>
                        <span>Borradores</span>
                        <strong>03</strong>
                    </div>
                    <div>
                        <span>Deshabilitados</span>
                        <strong>01</strong>
                    </div>
                </div>
            </section>

            <section class="contenido-card contenido-filtros" aria-label="Filtros de contenidos">
                <div class="contenido-field contenido-field-wide">
                    <label for="buscarContenido">Buscar contenido</label>
                    <input type="search" id="buscarContenido" placeholder="Título, curso o sesión">
                </div>
                <div class="contenido-field">
                    <label for="filtroEstadoContenido">Estado</label>
                    <select id="filtroEstadoContenido">
                        <option value="">Todos</option>
                        <option value="Publicado">Publicado</option>
                        <option value="Borrador">Borrador</option>
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
                    <table class="contenido-tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Curso</th>
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
                                    <td colspan="8">Este curso aún no tiene contenidos registrados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($contenidos as $contenido): ?>
                                <tr
                                    data-id="<?= (int)$contenido['id'] ?>"
                                    data-curso="<?= htmlspecialchars($contenido['curso']) ?>"
                                    data-sesion="<?= htmlspecialchars($contenido['sesion']) ?>"
                                    data-titulo="<?= htmlspecialchars($contenido['titulo']) ?>"
                                    data-descripcion="<?= htmlspecialchars($contenido['descripcion']) ?>"
                                    data-fecha="<?= htmlspecialchars($contenido['fecha']) ?>"
                                    data-archivo="<?= htmlspecialchars($contenido['archivo']) ?>"
                                    data-estado="<?= htmlspecialchars($contenido['estado']) ?>"
                                >
                                    <td data-label="ID"><?= (int)$contenido['id'] ?></td>
                                    <td data-label="Curso"><?= htmlspecialchars($contenido['curso']) ?></td>
                                    <td data-label="Sesión"><?= htmlspecialchars($contenido['sesion']) ?></td>
                                    <td data-label="Título">
                                        <strong><?= htmlspecialchars($contenido['titulo']) ?></strong>
                                        <span class="contenido-desc"><?= htmlspecialchars($contenido['descripcion']) ?></span>
                                    </td>
                                    <td data-label="Fecha publicación"><?= date('d/m/Y', strtotime($contenido['fecha'])) ?></td>
                                    <td data-label="Archivo">
                                        <?php if (!empty($contenido['archivo'])): ?>
                                            <span class="contenido-archivo"><i class="fas fa-paperclip"></i><?= htmlspecialchars($contenido['archivo']) ?></span>
                                        <?php else: ?>
                                            <span class="contenido-muted">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="contenido-badge estado-<?= strtolower($contenido['estado']) ?>">
                                            <?= htmlspecialchars($contenido['estado']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Acciones">
                                        <div class="contenido-acciones">
                                            <button type="button" class="contenido-icon-btn editar-contenido" title="Editar contenido">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="contenido-toggle" data-action="toggle">
                                                <?= $contenido['estado'] === 'Deshabilitado' ? 'Habilitar' : 'Deshabilitar' ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                <input type="hidden" id="contenidoCurso" value="<?= htmlspecialchars($cursoSeleccionado) ?>">

                <div class="contenido-form-grid">
                    <div class="contenido-field">
                        <label>Curso</label>
                        <input type="text" value="<?= htmlspecialchars($cursoSeleccionado) ?>" readonly>
                    </div>

                    <div class="contenido-field">
                        <label for="contenidoSesion">Número de sesión</label>
                        <input type="number" id="contenidoSesion" min="1" step="1" placeholder="Ej: 5" required>
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
                            <option value="Borrador">Borrador</option>
                            <option value="Deshabilitado">Deshabilitado</option>
                        </select>
                    </div>

                    <div class="contenido-field contenido-field-wide">
                        <label for="contenidoArchivo">Archivo adjunto opcional</label>
                        <input type="file" id="contenidoArchivo" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg">
                        <small id="contenidoArchivoActual">Backend integrará la carga real del archivo.</small>
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
