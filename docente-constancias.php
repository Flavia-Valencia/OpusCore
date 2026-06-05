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
require_once 'obtener-cursos-docente.php';

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$correoDocente = $_SESSION["usuario"];
$nombreDocente = $_SESSION["nombre"] ?? 'Docente';
$cursos = getCursosDocente($conexion, $correoDocente);
$periodos = array_values(array_unique(array_filter(array_column($cursos, 'periodo_nombre'))));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>ADF | Constancias Docente</title>
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
                    <div class="menu-user-email"><?= e($correoDocente) ?></div>
                </div>
            </div>

            <nav>
                <ul>
                    <li onclick="window.location.href='docentes.php'">
                        <i class="fas fa-book"></i> Mis Cursos
                    </li>
                    <li onclick="window.location.href='docente-registro-notas.php'">
                        <i class="fas fa-chart-line"></i> Registro de Notas
                    </li>
                    <li class="active" onclick="window.location.href='docente-constancias.php'">
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

        <div class="content">
            <header class="header">
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>

                <a href="includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role">Docente</span>
                        <span class="user-email"><?= e($correoDocente) ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <main class="constancias-docente-page">
                <section class="banner constancias-docente-banner">
                    <div class="banner-left">
                        <h1>Constancias docentes</h1>
                        <p>Solicita constancias relacionadas con cursos asignados o participación académica.</p>
                    </div>
                    <div class="constancias-docente-metricas">
                        <article>
                            <span>Cursos activos</span>
                            <strong><?= count($cursos) ?></strong>
                        </article>
                        <article>
                            <span>Solicitudes</span>
                            <strong id="docenteConstanciasTotal">0</strong>
                        </article>
                    </div>
                </section>

                <section class="contenido-card docente-constancias-card" id="docenteConstanciasModulo">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Nueva solicitud</h2>
                            <p>Completa los datos para enviar la solicitud al administrador.</p>
                        </div>
                    </div>

                    <form class="docente-constancia-form" id="docenteConstanciaForm">
                        <div class="contenido-form-grid">
                            <div class="contenido-field">
                                <label for="docenteConstanciaCurso">Curso</label>
                                <select id="docenteConstanciaCurso" required>
                                    <option value="">Seleccionar curso</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option
                                            value="<?= e($curso['nombre']) ?>"
                                            data-periodo="<?= e($curso['periodo_nombre'] ?? 'Sin periodo') ?>"
                                        >
                                            <?= e($curso['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="contenido-field">
                                <label for="docenteConstanciaTipo">Tipo de constancia</label>
                                <select id="docenteConstanciaTipo" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="Constancia de docencia impartida">Docencia impartida</option>
                                    <option value="Constancia de participación académica">Participación académica</option>
                                    <option value="Constancia laboral">Constancia laboral</option>
                                </select>
                            </div>

                            <div class="contenido-field">
                                <label for="docenteConstanciaPeriodo">Periodo</label>
                                <select id="docenteConstanciaPeriodo" required>
                                    <option value="">Seleccionar periodo</option>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?= e($periodo) ?>"><?= e($periodo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="contenido-field">
                                <label for="docenteConstanciaMotivo">Motivo</label>
                                <select id="docenteConstanciaMotivo" required>
                                    <option value="">Seleccionar motivo</option>
                                    <option value="Trámite laboral">Trámite laboral</option>
                                    <option value="Trámite bancario">Trámite bancario</option>
                                    <option value="Archivo personal">Archivo personal</option>
                                </select>
                            </div>

                            <div class="contenido-field contenido-field-wide">
                                <label for="docenteConstanciaDetalle">Detalle</label>
                                <textarea id="docenteConstanciaDetalle" rows="4" placeholder="Ej: Necesito una constancia del curso impartido durante el ciclo actual."></textarea>
                            </div>
                        </div>

                        <div class="contenido-modal-actions">
                            <button type="reset" class="contenido-btn contenido-btn-light">Limpiar</button>
                            <button type="submit" class="contenido-btn contenido-btn-primary" <?= empty($cursos) ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane"></i> Enviar solicitud
                            </button>
                        </div>
                    </form>
                </section>

                <section class="contenido-card docente-constancias-card">
                    <div class="contenido-card-header">
                        <div>
                            <h2>Solicitudes realizadas</h2>
                            <p>Historial frontend de solicitudes enviadas en esta sesión.</p>
                        </div>
                    </div>

                    <div class="contenido-tabla-wrap">
                        <table class="contenido-tabla docente-constancias-tabla">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Curso</th>
                                    <th>Periodo</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="docenteConstanciasBody">
                                <tr class="contenido-empty" id="docenteConstanciasEmpty">
                                    <td colspan="5">Aún no has enviado solicitudes.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>
    <script src="./js/script.js"></script>
</body>
</html>
