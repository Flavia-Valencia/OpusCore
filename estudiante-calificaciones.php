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

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function estadoNotaClase($estado, $notaFinal) {
    if ($notaFinal === null) return 'pendiente';
    $nota = (float) $notaFinal;
    if ($nota < 6) return 'reprobado';
    if ($nota < 7.5) return 'intermedio';
    return 'aprobado';
}

function estadoNotaTexto($estadoClase) {
    if ($estadoClase === 'aprobado') return 'Aprobado';
    if ($estadoClase === 'intermedio') return 'Intermedio';
    if ($estadoClase === 'reprobado') return 'Reprobado';
    return 'Pendiente';
}

function fechaTexto($fecha, $conHora = false) {
    if (empty($fecha)) return 'Pendiente';
    $formato = $conHora ? 'd/m/Y H:i' : 'd/m/Y';
    return date($formato, strtotime($fecha));
}

$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre, u.correo
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
    LIMIT 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = (int) $estudiante['id'];

$stmt = $conexion->prepare("
    SELECT
        c.id AS curso_id,
        c.nombre AS curso_nombre,
        COALESCE(c.descripcion, '') AS descripcion,
        COALESCE(pi.nombre, 'Sin periodo') AS periodo_nombre,
        pi.fechaInicioCiclo,
        pi.fechaFinCiclo,
        COALESCE(CONCAT(ud.nombre, ' ', ud.apellido), 'Docente por asignar') AS docente_nombre,
        i.estado_academico,
        rn.actividades,
        rn.examenFinal,
        rn.notaFinal,
        rn.estadoEstudiante,
        rn.fechaRegistro,
        pn.nombre AS plazo_nombre,
        pn.plazoInicio,
        pn.plazoFin
    FROM inscripciones i
    INNER JOIN cursos c ON i.idCurso = c.id
    LEFT JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
    LEFT JOIN docentes d ON c.idDocente = d.id
    LEFT JOIN usuarios ud ON d.usuario_id = ud.id
    LEFT JOIN RegistroNotas rn
        ON rn.idCurso = c.id
        AND rn.idEstudiante = i.idEstudiante
        AND rn.id = (
            SELECT rn2.id
            FROM RegistroNotas rn2
            INNER JOIN PlazoNotas pn2 ON rn2.idPlazo = pn2.id
            WHERE rn2.idCurso = c.id
              AND rn2.idEstudiante = i.idEstudiante
            ORDER BY pn2.plazoFin DESC, rn2.id DESC
            LIMIT 1
        )
    LEFT JOIN PlazoNotas pn ON rn.idPlazo = pn.id
    WHERE i.idEstudiante = ?
      AND i.estado_academico <> 'Retirado'
    ORDER BY pi.fechaInicio DESC, c.nombre ASC
");
$stmt->bind_param("i", $idEstudiante);
$stmt->execute();
$calificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalCursos = count($calificaciones);
$notasRegistradas = array_values(array_filter($calificaciones, fn($fila) => $fila['notaFinal'] !== null));
$totalEvaluadas = count($notasRegistradas);
$aprobadas = 0;
$reprobadas = 0;
$sumaNotas = 0;

foreach ($notasRegistradas as $fila) {
    $sumaNotas += (float) $fila['notaFinal'];
    $claseEstado = estadoNotaClase($fila['estadoEstudiante'], $fila['notaFinal']);
    if ($claseEstado === 'aprobado' || $claseEstado === 'intermedio') {
        $aprobadas++;
    } elseif ($claseEstado === 'reprobado') {
        $reprobadas++;
    }
}

$promedioGeneral = $totalEvaluadas > 0 ? $sumaNotas / $totalEvaluadas : null;
$periodos = array_values(array_unique(array_map(fn($fila) => $fila['periodo_nombre'], $calificaciones)));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>ADF | Mis calificaciones</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-estudiantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">
    <div class="sidebar-overlay js-sidebar-overlay" id="sidebarOverlay"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="img/logo.svg" alt="Logo" class="logo-img">
                <span class="sidebar-brand logo-text-sidebar"><span>Academia</span><strong>Futuro Digital</strong></span>
                <div class="menu-user">
                    <div class="menu-user-role">Estudiante</div>
                    <div class="menu-user-email"><?= e($_SESSION["usuario"]) ?></div>
                </div>
                <button type="button" class="sidebar-close js-sidebar-close" aria-label="Cerrar menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="estudiante-cursos.php" class="nav-item">
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
                <a href="estudiante-calificaciones.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Calificaciones</span>
                </a>
                <div class="nav-dropdown">
                    <button type="button" class="nav-item nav-dropdown-toggle js-pagos-toggle">
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

        <div class="content">
            <header class="header-panel">
                <button class="hamburger js-sidebar-toggle" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="includes/logout.php" class="user-profile-panel">
                    <div class="user-info">
                        <span class="user-role">Estudiante</span>
                        <span class="user-email"><?= e($_SESSION["usuario"]) ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <main class="calificaciones-page">
                <section class="banner calificaciones-banner">
                    <div class="banner-left">
                        <h1>Mis calificaciones</h1>
                        <p>Consulta tus notas por curso, actividades, examen final y estado académico.</p>
                    </div>
                    <div class="banner-fecha">
                        <span>Actualizado</span>
                        <strong><?= date('d/m/Y') ?></strong>
                    </div>
                </section>

                <section class="calificaciones-resumen">
                    <article class="calificacion-stat">
                        <span class="stat-icon stat-cursos"><i class="fas fa-book-open"></i></span>
                        <div>
                            <span>Cursos evaluados</span>
                            <strong><?= $totalEvaluadas ?></strong>
                            <small><?= $totalCursos ?> inscrito<?= $totalCursos === 1 ? '' : 's' ?></small>
                        </div>
                    </article>
                    <article class="calificacion-stat">
                        <span class="stat-icon stat-promedio"><i class="fas fa-arrow-trend-up"></i></span>
                        <div>
                            <span>CUMSITO</span>
                            <strong><?= $promedioGeneral !== null ? e(number_format($promedioGeneral, 2)) : '—' ?></strong>
                            <small>Sobre 10</small>
                        </div>
                    </article>
                    <article class="calificacion-stat">
                        <span class="stat-icon stat-aprobadas"><i class="fas fa-check"></i></span>
                        <div>
                            <span>Aprobadas</span>
                            <strong><?= $aprobadas ?></strong>
                            <small><?= $totalEvaluadas > 0 ? round(($aprobadas / $totalEvaluadas) * 100) : 0 ?>% del total</small>
                        </div>
                    </article>
                    <article class="calificacion-stat">
                        <span class="stat-icon stat-reprobadas"><i class="fas fa-xmark"></i></span>
                        <div>
                            <span>Reprobadas</span>
                            <strong><?= $reprobadas ?></strong>
                            <small><?= $totalEvaluadas > 0 ? round(($reprobadas / $totalEvaluadas) * 100) : 0 ?>% del total</small>
                        </div>
                    </article>
                </section>

                <section class="calificaciones-toolbar">
                    <label class="calificaciones-field">
                        <span>Buscar curso</span>
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="search" id="calificacionBuscar" placeholder="Buscar por nombre de curso...">
                    </label>
                    <label class="calificaciones-field">
                        <span>Periodo académico</span>
                        <select id="calificacionPeriodo">
                            <option value="">Todos los periodos</option>
                            <?php foreach ($periodos as $periodo): ?>
                                <option value="<?= e(strtolower($periodo)) ?>"><?= e($periodo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </section>

                <section class="calificaciones-grid">
                    <div class="calificaciones-panel">
                        <?php if (empty($calificaciones)): ?>
                            <div class="inscripcion-vacia pagos-vacio">
                                <i class="fas fa-chart-line"></i>
                                <p>Aún no tienes calificaciones registradas.</p>
                                <small>Cuando tus cursos tengan notas publicadas, aparecerán en esta sección.</small>
                            </div>
                        <?php else: ?>
                            <div class="calificaciones-tabla-wrap">
                                <table class="calificaciones-tabla">
                                    <thead>
                                        <tr>
                                            <th>Curso</th>
                                            <th>Actividades<br><small>(30%)</small></th>
                                            <th>Examen final<br><small>(70%)</small></th>
                                            <th>Nota final<br></th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="calificacionesBody">
                                        <?php foreach ($calificaciones as $index => $fila): ?>
                                            <?php
                                                $tieneNota = $fila['notaFinal'] !== null;
                                                $estadoClase = $tieneNota ? estadoNotaClase($fila['estadoEstudiante'], $fila['notaFinal']) : 'pendiente';
                                                $estadoTexto = estadoNotaTexto($estadoClase);
                                            ?>
                                            <tr class="calificacion-row"
                                                data-search="<?= e(strtolower($fila['curso_nombre'] . ' ' . $fila['docente_nombre'])) ?>"
                                                data-periodo="<?= e(strtolower($fila['periodo_nombre'])) ?>"
                                                data-curso="<?= e($fila['curso_nombre']) ?>"
                                                data-periodo-text="<?= e($fila['periodo_nombre']) ?>"
                                                data-ciclo="<?= e(fechaTexto($fila['fechaInicioCiclo']) . ' - ' . fechaTexto($fila['fechaFinCiclo'])) ?>"
                                                data-fecha-calificacion="<?= e($tieneNota ? fechaTexto($fila['fechaRegistro'], true) : 'Pendiente de calificación') ?>"
                                                data-actividades="<?= $tieneNota ? e(number_format((float) $fila['actividades'], 2)) : '—' ?>"
                                                data-examen="<?= $tieneNota ? e(number_format((float) $fila['examenFinal'], 2)) : '—' ?>"
                                                data-final="<?= $tieneNota ? e(number_format((float) $fila['notaFinal'], 2)) : '—' ?>"
                                                data-estado="<?= e($estadoTexto) ?>"
                                                data-estado-clase="<?= e($estadoClase) ?>"
                                                data-descripcion="<?= e($fila['descripcion']) ?>"
                                                data-icon="<?= ($index % 4) + 1 ?>">
                                                <td data-label="Curso">
                                                    <div class="calificacion-curso-cell">
                                                        <span class="calificacion-course-icon icon-<?= ($index % 4) + 1 ?>">
                                                            <i class="fas fa-code"></i>
                                                        </span>
                                                        <div>
                                                            <strong><?= e($fila['curso_nombre']) ?></strong>
                                                            <small><?= e($fila['periodo_nombre']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label="Actividades"><?= $tieneNota ? e(number_format((float) $fila['actividades'], 2)) : '—' ?></td>
                                                <td data-label="Examen final"><?= $tieneNota ? e(number_format((float) $fila['examenFinal'], 2)) : '—' ?></td>
                                                <td data-label="Nota final" class="calificacion-final"><?= $tieneNota ? e(number_format((float) $fila['notaFinal'], 2)) : '—' ?></td>
                                                <td data-label="Estado">
                                                    <span class="calificacion-badge <?= e($estadoClase) ?>"><?= e($estadoTexto) ?></span>
                                                </td>
                                                <td data-label="Acción">
                                                    <button type="button" class="calificacion-detalle-btn">
                                                        Ver detalle
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="detalle-empty calificaciones-empty-filter" id="calificacionesEmpty">No se encontraron calificaciones con esos filtros.</div>
                        <?php endif; ?>
                    </div>

                </section>
            </main>
        </div>
    </div>

    <div class="modal-overlay calificacion-modal" id="modalCalificacionDetalle" aria-hidden="true">
        <div class="modal-contenido calificacion-modal-box" role="dialog" aria-modal="true" aria-labelledby="modalDetalleCurso">
            <button class="modal-cerrar" type="button" id="cerrarModalCalificacion" aria-label="Cerrar detalle">
                <i class="fas fa-times"></i>
            </button>
            <div class="calificacion-detalle-head">
                <span class="calificacion-course-icon icon-1" id="modalDetalleIcon"><i class="fas fa-code"></i></span>
                <div>
                    <strong id="modalDetalleCurso">Curso seleccionado</strong>
                    <small id="modalDetallePeriodo">Periodo académico</small>
                </div>
            </div>
            <div class="calificacion-detalle-list calificacion-context-list">
                <div><span>Fecha de calificación</span><strong id="modalDetalleFecha">—</strong></div>
                <div><span>Ciclo académico</span><strong id="modalDetalleCiclo">—</strong></div>
                <div><span>Nota final</span><strong id="modalDetalleNotaFinal">—</strong></div>
            </div>
            <div class="calificacion-detalle-estado">
                <span>Estado</span>
                <strong class="calificacion-badge pendiente" id="modalDetalleEstado">Pendiente</strong>
            </div>
        </div>
    </div>

    <script src="./js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = Array.from(document.querySelectorAll('.calificacion-row'));
            const buscar = document.getElementById('calificacionBuscar');
            const periodo = document.getElementById('calificacionPeriodo');
            const empty = document.getElementById('calificacionesEmpty');
            const modal = document.getElementById('modalCalificacionDetalle');
            const modalCerrar = document.getElementById('cerrarModalCalificacion');
            const detalleModal = {
                icon: document.getElementById('modalDetalleIcon'),
                curso: document.getElementById('modalDetalleCurso'),
                periodo: document.getElementById('modalDetallePeriodo'),
                fecha: document.getElementById('modalDetalleFecha'),
                ciclo: document.getElementById('modalDetalleCiclo'),
                notaFinal: document.getElementById('modalDetalleNotaFinal'),
                estado: document.getElementById('modalDetalleEstado')
            };

            const llenarDetalle = (destino, row) => {
                destino.icon.className = `calificacion-course-icon icon-${row.dataset.icon || '1'}`;
                destino.icon.innerHTML = '<i class="fas fa-code"></i>';
                destino.curso.textContent = row.dataset.curso;
                destino.periodo.textContent = row.dataset.periodoText;
                destino.fecha.textContent = row.dataset.fechaCalificacion;
                destino.ciclo.textContent = row.dataset.ciclo;
                destino.notaFinal.textContent = `${row.dataset.final} / 10`;
                destino.estado.className = `calificacion-badge ${row.dataset.estadoClase}`;
                destino.estado.textContent = row.dataset.estado;
            };

            const abrirDetalle = (row) => {
                if (!row) return;
                rows.forEach(item => item.classList.remove('is-selected'));
                row.classList.add('is-selected');
                llenarDetalle(detalleModal, row);

                if (modal) {
                    modal.classList.add('activo');
                    modal.setAttribute('aria-hidden', 'false');
                }
            };

            const cerrarModal = () => {
                if (!modal) return;
                modal.classList.remove('activo');
                modal.setAttribute('aria-hidden', 'true');
            };

            const filtrar = () => {
                const texto = (buscar?.value || '').trim().toLowerCase();
                const periodoValor = periodo?.value || '';
                let visibles = 0;

                rows.forEach(row => {
                    const coincideTexto = row.dataset.search.includes(texto);
                    const coincidePeriodo = !periodoValor || row.dataset.periodo === periodoValor;
                    const visible = coincideTexto && coincidePeriodo;
                    row.classList.toggle('is-hidden', !visible);
                    if (visible) visibles++;
                });

                if (empty) empty.style.display = visibles === 0 ? 'block' : 'none';
            };

            rows.forEach(row => {
                row.querySelector('.calificacion-detalle-btn')?.addEventListener('click', () => abrirDetalle(row));
                row.addEventListener('dblclick', () => abrirDetalle(row));
            });
            modalCerrar?.addEventListener('click', cerrarModal);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) cerrarModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') cerrarModal();
            });
            buscar?.addEventListener('input', filtrar);
            periodo?.addEventListener('change', filtrar);
        });
    </script>
</body>
</html>
