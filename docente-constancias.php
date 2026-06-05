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

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/* ── Datos del usuario autenticado ── */
$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT u.id        AS usuario_id,
           d.id        AS docente_id,
           CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo
    FROM usuarios u
    INNER JOIN docentes d ON d.usuario_id = u.id 
    WHERE u.correo = ?
    LIMIT 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int) $usuario['usuario_id'];
$idDocente = (int) $usuario['docente_id'];  

/* ── Cursos finalizados del docente con estado de su solicitud de constancia ── */
$stmt = $conexion->prepare("
    SELECT
        c.id                                                AS curso_id,
        c.nombre                                            AS curso_nombre,
        c.descripcion                                       AS curso_descripcion,
        COALESCE(cat.nombre, 'Sin categoría')               AS categoria,
        COALESCE(pi.nombre, 'Sin periodo')                  AS periodo_nombre,
        c.fechaFin,
        sol.estado                                          AS estado_solicitud,
        sol.id                                              AS solicitud_id,
        sol.fechaSolicitud
    FROM cursos c
    LEFT JOIN categorias cat      ON c.idCategoria = cat.id
    LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo  = pi.id
    /* Usamos usuarios.id como FK */
    LEFT JOIN solicitudConstanciaDocente sol
           ON sol.idDocente = ? AND sol.idCurso = c.id
    INNER JOIN docentes d         ON c.idDocente = d.id
    WHERE d.usuario_id = ?
      AND c.fechaFin < CURDATE()
      AND c.estado = 1
    ORDER BY c.fechaFin DESC, c.nombre ASC
");
$stmt->bind_param("ii", $idDocente, $idUsuario);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ── Métricas ── */
$pendientes = 0;
$aprobadas  = 0;
foreach ($cursos as $c) {
    if ($c['estado_solicitud'] === 'Pendiente') $pendientes++;
    if ($c['estado_solicitud'] === 'Aprobada')  $aprobadas++;
}

/* ── Periodos únicos para el filtro ── */
$periodos = array_values(array_unique(array_column($cursos, 'periodo_nombre')));

/* ── Manejar envío de solicitud (POST) ── */
$alerta     = '';
$alertaTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idCurso'])) {
    $idCurso = (int) $_POST['idCurso'];

    /* Verificar que el curso pertenece al docente y ya finalizó */
    $chk = $conexion->prepare("
    SELECT c.id
    FROM cursos c
    INNER JOIN docentes d ON c.idDocente = d.id
    WHERE c.id = ?
      AND d.usuario_id = ?
      AND c.fechaFin < CURDATE()
      AND c.estado = 1
    LIMIT 1
    ");
    $chk->bind_param("ii", $idCurso, $idUsuario);
    $chk->execute();
    $valido = $chk->get_result()->fetch_assoc();
    $chk->close();

    /* Verificar si ya existe una solicitud PENDIENTE */
    $dup = $conexion->prepare("
        SELECT id, estado FROM solicitudConstanciaDocente
        WHERE idDocente = ? AND idCurso = ? AND estado = 'Pendiente'
        LIMIT 1
    ");
    $dup->bind_param("ii", $idDocente, $idCurso);
    $dup->execute();
    $yaPendiente = $dup->get_result()->fetch_assoc();
    $dup->close();

    if ($valido && !$yaPendiente) {
        $ins = $conexion->prepare("
            INSERT INTO solicitudConstanciaDocente (idDocente, idCurso, motivo)
            VALUES (?, ?, 'Trámite personal')
        ");
        $ins->bind_param("ii", $idDocente, $idCurso);
        if ($ins->execute()) {
            $alerta     = 'Tu solicitud fue enviada. El equipo administrativo la procesará pronto.';
            $alertaTipo = 'exito';
        } else {
            $alerta     = 'Ocurrió un error al enviar la solicitud. Intenta de nuevo.';
            $alertaTipo = 'error';
        }
        $ins->close();
    } elseif ($yaPendiente) {
        $alerta     = 'Ya tienes una solicitud pendiente para este curso. Espera a que sea procesada.';
        $alertaTipo = 'info';
    } else {
        $alerta     = 'No se pudo verificar el curso. Recarga la página.';
        $alertaTipo = 'error';
    }

    /* Recargar datos actualizados tras el POST */
    $stmt2 = $conexion->prepare("
        SELECT
            c.id                                                AS curso_id,
            c.nombre                                            AS curso_nombre,
            c.descripcion                                       AS curso_descripcion,
            COALESCE(cat.nombre, 'Sin categoría')               AS categoria,
            COALESCE(pi.nombre, 'Sin periodo')                  AS periodo_nombre,
            c.fechaFin,
            sol.estado                                          AS estado_solicitud,
            sol.id                                              AS solicitud_id,
            sol.fechaSolicitud
        FROM cursos c
        LEFT JOIN categorias cat ON c.idCategoria = cat.id
        LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
        LEFT JOIN solicitudConstanciaDocente sol
               ON sol.idDocente = ? AND sol.idCurso = c.id
        INNER JOIN docentes d ON c.idDocente = d.id
        WHERE d.usuario_id = ?
          AND c.fechaFin < CURDATE()
          AND c.estado = 1
        ORDER BY c.fechaFin DESC, c.nombre ASC
    ");
    $stmt2->bind_param("ii", $idDocente, $idUsuario);
    $stmt2->execute();
    $cursos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    $pendientes = 0;
    $aprobadas  = 0;
    foreach ($cursos as $c) {
        if ($c['estado_solicitud'] === 'Pendiente') $pendientes++;
        if ($c['estado_solicitud'] === 'Aprobada')  $aprobadas++;
    }
    $periodos = array_values(array_unique(array_column($cursos, 'periodo_nombre')));
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
    <title>ADF | Mis Constancias</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-docentes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="raleway-all">
    <input type="checkbox" id="sidebar-toggle">

    <div class="layout">
        <!-- ── SIDEBAR ── -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="./img/logo.svg" alt="Logo Academia" class="logo-img-sidebar">
                <div class="logo-text-sidebar">
                    <span>Academia</span>
                    <strong>Futuro Digital</strong>
                </div>
                <div class="menu-user">
                    <div class="menu-user-role">Docente</div>
                    <div class="menu-user-email"><?= e($_SESSION["usuario"]) ?></div>
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

        <!-- ── CONTENIDO ── -->
        <div class="content organizacion-page">
            <header class="header">
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>
                <a href="includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role"><?= e($_SESSION["rol"] ?? "Docente") ?></span>
                        <span class="user-email"><?= e($_SESSION["usuario"]) ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <!-- ── BANNER CON MÉTRICAS ── -->
            <section class="banner organizacion-banner tareas-banner">
                <div class="banner-left">
                    <h2>Mis Constancias</h2>
                    <p>Aquí aparecen los cursos que has impartido y ya finalizaron. Envía una solicitud y el equipo administrativo generará tu constancia.</p>
                </div>
                <div class="organizacion-metricas" style="grid-template-columns: repeat(2, minmax(110px, 1fr)); min-width: 0;">
                    <div>
                        <span>Pendientes</span>
                        <strong><?= $pendientes ?></strong>
                    </div>
                    <div>
                        <span>Aprobadas</span>
                        <strong><?= $aprobadas ?></strong>
                    </div>
                </div>
            </section>

            <!-- ── TABLA DE CURSOS FINALIZADOS ── -->
            <section
                class="contenido-card entregas-docente-card"
                id="constanciasModulo"
                data-toast-message="<?= e($alerta) ?>"
                data-toast-type="<?= e($alertaTipo === 'exito' ? 'success' : ($alertaTipo === 'info' ? 'info' : ($alertaTipo ? 'error' : ''))) ?>"
            >
                <div class="contenido-card-header">
                    <div>
                        <h2>Cursos finalizados</h2>
                        <p>Solo se muestran cursos cuya fecha de fin ya pasó. Puedes solicitar una constancia por curso.</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="constancias-filtros">
                    <div class="constancias-field">
                        <label for="constanciaBuscador">Buscar curso</label>
                        <div class="constancias-search-wrap">
                            <i class="fas fa-search"></i>
                            <input
                                type="text"
                                id="constanciaBuscador"
                                placeholder="Buscar por nombre del curso..."
                                class="constancias-search-input"
                            >
                        </div>
                    </div>
                    <div class="constancias-field">
                        <label for="constanciaPeriodoFiltro">Periodo académico</label>
                        <select id="constanciaPeriodoFiltro" class="constancias-select">
                            <option value="">Todos los periodos</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= e(strtolower($p)) ?>"><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="constancias-field">
                        <label for="constanciaEstadoFiltro">Estado</label>
                        <select id="constanciaEstadoFiltro" class="constancias-select">
                            <option value="">Todos los estados</option>
                            <option value="sin solicitar">Sin solicitar</option>
                            <option value="solicitado">Solicitado</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>
                </div>

                <div class="contenido-tabla-wrap">
                    <table class="constancias-tabla" id="constanciasTabla">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Fecha fin</th>
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="constanciasBody">
                            <?php if (empty($cursos)): ?>
                                <tr id="constanciasSinCursos">
                                    <td colspan="5" class="constancias-empty-td">
                                        <i class="fas fa-file-alt constancias-empty-icon"></i>
                                        <p class="constancias-empty-title">Aún no tienes cursos finalizados.</p>
                                        <small class="constancias-empty-sub">Cuando un curso que impartes llegue a su fecha de fin, aparecerá aquí y podrás solicitar tu constancia.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cursos as $c):
                                    $estado = $c['estado_solicitud'] ?? null;

                                    [$badgeClase, $badgeTexto] = match($estado) {
                                        'Pendiente' => ['pendiente', 'Solicitado'],
                                        'Aprobada'  => ['generada',  'Aprobada'],
                                        'Rechazada' => ['rechazada', 'Rechazada'],
                                        default     => ['sin-sol',   'Sin solicitar'],
                                    };

                                    $dataEstado = match($estado) {
                                        'Pendiente' => 'solicitado',
                                        'Aprobada'  => 'aprobada',
                                        'Rechazada' => 'rechazada',
                                        default     => 'sin solicitar',
                                    };

                                    $btnDesactivado = ($estado === 'Pendiente');
                                    $btnTexto       = $btnDesactivado ? 'Solicitado' : ($estado === 'Aprobada' ? 'Re-solicitar' : 'Solicitar');
                                    $btnIcono       = $btnDesactivado ? 'fa-clock' : ($estado === 'Aprobada' ? 'fa-rotate-right' : 'fa-paper-plane');
                                ?>
                                <tr
                                    class="constancia-fila"
                                    data-search="<?= e(strtolower($c['curso_nombre'])) ?>"
                                    data-periodo="<?= e(strtolower($c['periodo_nombre'])) ?>"
                                    data-estado="<?= e($dataEstado) ?>"
                                >
                                    <td data-label="Curso">
                                        <strong class="constancia-curso-nombre">
                                            <?= e($c['curso_nombre']) ?>
                                        </strong>
                                        <small class="constancia-curso-categoria">
                                            <?= e($c['categoria']) ?>
                                        </small>
                                    </td>
                                    <td data-label="Fecha fin">
                                        <?= date('d/m/Y', strtotime($c['fechaFin'])) ?>
                                    </td>
                                    <td data-label="Periodo">
                                        <?= e($c['periodo_nombre']) ?>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="constancia-badge <?= $badgeClase ?>">
                                            <?= $badgeTexto ?>
                                        </span>
                                    </td>
                                    <td data-label="Acción">
                                        <?php if (!$btnDesactivado): ?>
                                            <form method="POST" class="constancia-form">
                                                <input type="hidden" name="idCurso" value="<?= (int)$c['curso_id'] ?>">
                                                <button type="submit" class="constancia-generar-btn">
                                                    <i class="fas <?= $btnIcono ?>"></i> <?= $btnTexto ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="constancia-generar-btn constancia-generar-btn--disabled" disabled>
                                                <i class="fas <?= $btnIcono ?>"></i> <?= $btnTexto ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <tr id="constanciasSinResultados" style="display:none">
                                    <td colspan="5" class="constancias-no-resultados">
                                        No se encontraron cursos con esos filtros.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <label for="sidebar-toggle" class="overlay"></label>

    <script src="./js/script.js"></script>
</body>
</html>
