<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$solicitudes = [
    [
        'id' => 'SOL-028-001',
        'solicitante' => 'Yahir Romero',
        'correo' => 'yahir.romero@academia.test',
        'rol' => 'Estudiante',
        'tipo' => 'Constancia de aprobación de curso',
        'curso' => 'Diseño Web',
        'codigoCurso' => 'DIS-101',
        'periodo' => '2026-I',
        'notaFinal' => '8.8',
        'resultado' => 'Aprobado',
        'fechaActividad' => '2026-06-03',
        'motivo' => 'Trámite laboral',
        'fecha' => '2026-06-03',
        'estado' => 'Pendiente'
    ],
    [
        'id' => 'SOL-028-002',
        'solicitante' => 'Karla Méndez',
        'correo' => 'karla.mendez@academia.test',
        'rol' => 'Docente',
        'tipo' => 'Constancia de docencia impartida',
        'curso' => 'Base de Datos',
        'codigoCurso' => 'BD-102',
        'periodo' => '2026-I',
        'notaFinal' => 'No aplica',
        'resultado' => 'Curso impartido',
        'fechaActividad' => '2026-06-02',
        'motivo' => 'Trámite bancario',
        'fecha' => '2026-06-02',
        'estado' => 'Pendiente'
    ],
    [
        'id' => 'SOL-028-003',
        'solicitante' => 'Emily Muñoz',
        'correo' => 'emily.munoz@academia.test',
        'rol' => 'Estudiante',
        'tipo' => 'Constancia de participación académica',
        'curso' => 'Programación',
        'codigoCurso' => 'PROG-103',
        'periodo' => '2026-II',
        'notaFinal' => 'No aplica',
        'resultado' => 'Participación registrada',
        'fechaActividad' => '2026-06-01',
        'motivo' => 'Justificación laboral',
        'fecha' => '2026-06-01',
        'estado' => 'Pendiente'
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>ADF | Constancias Administrativas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">
    <header class="header">
        <div class="logo">
            <img src="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>

        <input type="checkbox" id="menu-toggle" class="menu-checkbox">

        <label for="menu-toggle" class="menu-btn">
            <i class="fas fa-bars hamburguesa"></i>
            <i class="fas fa-times cerrar"></i>
        </label>

        <label for="menu-toggle" class="menu-overlay"></label>

        <nav class="nav">
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></div>
            </div>

            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-periodos.php" class="btn-nav">Periodos</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>
            <a href="./admin-pagos.php" class="btn-nav">Pagos</a>
            <a href="./admin-facturacion.php" class="btn-nav">Facturación</a>
            <a href="./admin-plazo.php" class="btn-nav">Plazo Notas</a>
            <a href="./admin-constancias.php" class="btn-nav active">Constancias</a>

            <a href="includes/logout.php" class="btn-salir">Cerrar sesión</a>

            <a href="includes/logout.php" style="text-decoration:none;">
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-role">Admin</span>
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </div>
            </a>
        </nav>
    </header>

    <main class="main constancias-page">
        <div class="page-header">
            <h1 class="titulo">CONSTANCIAS ADMINISTRATIVAS</h1>
        </div>

        <section class="constancias-banner">
            <div class="constancias-banner-texto">
                <h2>Gestión de solicitudes</h2>
                <p>Revisa solicitudes administrativas y genera constancias para enviarlas al historial.</p>
            </div>

            <div class="constancias-metricas">
                <article>
                    <span>Pendientes</span>
                    <strong id="constanciasPendientes">3</strong>
                </article>
                <article>
                    <span>Generadas hoy</span>
                    <strong id="constanciasGeneradas">0</strong>
                </article>
                <article>
                    <span>Historial</span>
                    <strong id="constanciasHistorialTotal">0</strong>
                </article>
            </div>
        </section>

        <div class="constancias-alerta" id="constanciaAlerta" aria-live="polite">
            <i class="fas fa-circle-check"></i>
            <div>
                <strong>Constancia generada</strong>
                <span id="constanciaAlertaTexto">La solicitud fue enviada al historial.</span>
            </div>
        </div>

        <section class="card constancias-card">
            <div class="constancias-section-header">
                <div>
                    <h2>Solicitudes pendientes</h2>
                    <p>Genera la constancia solicitada y el registro pasará automáticamente al historial.</p>
                </div>
            </div>

            <div class="constancias-solicitudes" id="constanciasSolicitudes">
                <?php foreach ($solicitudes as $solicitud): ?>
                    <article
                        class="constancia-solicitud"
                        data-id="<?php echo htmlspecialchars($solicitud['id']); ?>"
                        data-solicitante="<?php echo htmlspecialchars($solicitud['solicitante']); ?>"
                        data-correo="<?php echo htmlspecialchars($solicitud['correo']); ?>"
                        data-rol="<?php echo htmlspecialchars($solicitud['rol']); ?>"
                        data-tipo="<?php echo htmlspecialchars($solicitud['tipo']); ?>"
                        data-curso="<?php echo htmlspecialchars($solicitud['curso']); ?>"
                        data-codigo-curso="<?php echo htmlspecialchars($solicitud['codigoCurso']); ?>"
                        data-periodo="<?php echo htmlspecialchars($solicitud['periodo']); ?>"
                        data-nota-final="<?php echo htmlspecialchars($solicitud['notaFinal']); ?>"
                        data-resultado="<?php echo htmlspecialchars($solicitud['resultado']); ?>"
                        data-fecha-actividad="<?php echo htmlspecialchars($solicitud['fechaActividad']); ?>"
                        data-motivo="<?php echo htmlspecialchars($solicitud['motivo']); ?>"
                        data-fecha="<?php echo htmlspecialchars($solicitud['fecha']); ?>"
                    >
                        <div class="constancia-solicitud-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="constancia-solicitud-body">
                            <div class="constancia-solicitud-top">
                                <strong><?php echo htmlspecialchars($solicitud['tipo']); ?></strong>
                            </div>
                            <p><?php echo htmlspecialchars($solicitud['solicitante']); ?> · <?php echo htmlspecialchars($solicitud['curso']); ?></p>
                            <div class="constancia-solicitud-meta">
                                <span><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($solicitud['fecha']); ?></span>
                                <span><i class="fas fa-book-open"></i> <?php echo htmlspecialchars($solicitud['codigoCurso']); ?> · <?php echo htmlspecialchars($solicitud['periodo']); ?></span>
                                <span><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($solicitud['resultado']); ?></span>
                                <span><i class="fas fa-clipboard"></i> <?php echo htmlspecialchars($solicitud['motivo']); ?></span>
                            </div>
                        </div>
                        <span class="constancia-badge pendiente"><?php echo htmlspecialchars($solicitud['estado']); ?></span>
                        <button type="button" class="btn-guardar constancia-generar-btn">
                            <i class="fas fa-file-circle-plus"></i> Generar constancia
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="constancias-empty" id="constanciasSolicitudesEmpty" hidden>
                <i class="fas fa-inbox"></i>
                <p>No hay solicitudes pendientes.</p>
            </div>
        </section>

        <section class="card constancias-card">
            <div class="constancias-section-header">
                <div>
                    <h2>Historial de constancias</h2>
                    <p>Constancias generadas desde las solicitudes administrativas.</p>
                </div>
            </div>

            <div class="toolbar constancias-filtros">
                <input type="text" id="constanciaBuscador" placeholder="Buscar por solicitante, curso o código" class="input-buscar">
                <select id="constanciaTipoFiltro" class="constancia-filtro-control">
                    <option value="">Todos los tipos</option>
                    <option value="Constancia de aprobación de curso">Constancia de aprobación de curso</option>
                    <option value="Constancia de participación académica">Constancia de participación académica</option>
                    <option value="Constancia de inscripción activa">Constancia de inscripción activa</option>
                    <option value="Constancia de docencia impartida">Constancia de docencia impartida</option>
                </select>
                <input type="date" id="constanciaFechaFiltro" class="constancia-filtro-control">
            </div>

            <div class="tabla-placeholder">
                <table class="data-table mobile-cards constancias-tabla">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Solicitante</th>
                            <th>Curso</th>
                            <th>Fecha solicitud</th>
                            <th>Fecha generación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="constanciasHistorialBody">
                        <tr class="constancias-sin-historial" id="constanciasSinHistorial">
                            <td colspan="8">Todavía no hay constancias generadas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="./js/script.js"></script>
</body>
</html>
