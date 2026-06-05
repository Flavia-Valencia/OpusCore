<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
date_default_timezone_set('America/El_Salvador');

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

// Polling: Notificación en tiempo real al administrador
if (isset($_GET['check_new_requests'])) {
    header('Content-Type: application/json');
    $resEst = $conexion->query("SELECT COUNT(*) AS total FROM solicitudConstanciaEstudiante WHERE estado = 'Pendiente'");
    $totalEst = $resEst ? $resEst->fetch_assoc()['total'] : 0;
    
    $resDoc = $conexion->query("SELECT COUNT(*) AS total FROM solicitudConstanciaDocente WHERE estado = 'Pendiente'");
    $totalDoc = $resDoc ? $resDoc->fetch_assoc()['total'] : 0;
    
    echo json_encode(['total' => ($totalEst + $totalDoc)]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'generar') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['error' => true, 'mensaje' => 'Datos inválidos.']);
        exit();
    }
    
    $solicitudIdFull = $input['solicitud_id_full'] ?? ''; // e.g., "SOL-EST-5" o "SOL-DOC-3"
    $parts = explode('-', $solicitudIdFull);
    if (count($parts) < 3) {
        echo json_encode(['error' => true, 'mensaje' => 'ID de solicitud incorrecto.']);
        exit();
    }
    
    $tipoRol = $parts[1]; // "estudiante" o "docente"
    $solicitudId = (int)$parts[2];
    
    if ($tipoRol === 'EST') {
        $stmt = $conexion->prepare("
            SELECT sol.id AS solicitud_id, sol.idCurso, sol.fechaSolicitud AS fecha_solicitud_raw, u.id AS usuario_id, u.nombre, u.apellido, u.correo, 
                   e.telefono, e.direccion, e.id AS estudiante_id, c.nombre AS curso_nombre
            FROM solicitudConstanciaEstudiante sol
            INNER JOIN estudiantes e ON sol.idEstudiante = e.id
            INNER JOIN usuarios u ON e.usuario_id = u.id
            INNER JOIN cursos c ON sol.idCurso = c.id
            WHERE sol.id = ? AND sol.estado = 'Pendiente'
            LIMIT 1
        ");
    } else {
        $stmt = $conexion->prepare("
            SELECT sol.id AS solicitud_id, sol.idCurso, sol.fechaSolicitud AS fecha_solicitud_raw, u.id AS usuario_id, u.nombre, u.apellido, u.correo, 
                   d.telefono, d.direccion, d.id AS docente_id, c.nombre AS curso_nombre
            FROM solicitudConstanciaDocente sol
            INNER JOIN docentes d ON sol.idDocente = d.id
            INNER JOIN usuarios u ON d.usuario_id = u.id
            INNER JOIN cursos c ON sol.idCurso = c.id
            WHERE sol.id = ? AND sol.estado = 'Pendiente'
            LIMIT 1
        ");
    }
    
    $stmt->bind_param("i", $solicitudId);
    $stmt->execute();
    $solInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$solInfo) {
        echo json_encode(['error' => true, 'mensaje' => 'La solicitud no existe o ya fue procesada.']);
        exit();
    }
    

    $nombre = trim($solInfo['nombre'] ?? '');
    $apellido = trim($solInfo['apellido'] ?? '');
    $correo = trim($solInfo['correo'] ?? '');
    $telefono = trim($solInfo['telefono'] ?? '');
    $direccion = trim($solInfo['direccion'] ?? '');
    
    $camposBase = empty($nombre) || empty($apellido) || empty($correo);
    $camposEstudiante = ($tipoRol === 'EST') && (empty($telefono) || empty($direccion));

    if ($camposBase || $camposEstudiante) {
        echo json_encode(['error' => true, 'mensaje' => 'La información del usuario está incompleta. Verifique que tenga nombre, apellido y correo antes de generar la constancia.']);
        exit();
    }
    
    //Generar la constancia y el PDF
    $resNum = $conexion->query("SELECT COUNT(*) AS total FROM constancias");
    $siguiente = ($resNum->fetch_assoc()['total'] ?? 0) + 1;
    $codigoConstancia = 'CONST-' . date('Y') . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    $solicitante = $nombre . ' ' . $apellido;
    $destino = ($tipoRol === 'EST') ? 'Estudiante' : 'Docente';
    $tipoConstancia = ($tipoRol === 'EST') ? 'Constancia de aprobación de curso' : 'Constancia de docencia impartida';
    $curso = $solInfo['curso_nombre'];
    $idCurso = $solInfo['idCurso'];
    
    $stmtCurso = $conexion->prepare("
        SELECT c.nombre, c.id, pi.nombre AS periodo_nombre 
        FROM cursos c
        LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmtCurso->bind_param("i", $idCurso);
    $stmtCurso->execute();
    $cursoData = $stmtCurso->get_result()->fetch_assoc();
    $stmtCurso->close();
    
    $periodo = $cursoData['periodo_nombre'] ?? 'Sin periodo';
    $codigoCurso = strtoupper(substr($curso, 0, 4)) . '-' . $idCurso;
    
    $notaFinal = 'No aplica';
    $resultado = ($tipoRol === 'EST') ? 'Aprobado' : 'Curso impartido';
    $fechaActividad = date('d/m/Y');
    
    if ($tipoRol === 'EST') {
        $stmtNota = $conexion->prepare("
            SELECT notaFinal, fechaRegistro 
            FROM RegistroNotas 
            WHERE idEstudiante = ? AND idCurso = ? 
            LIMIT 1
        ");
        $stmtNota->bind_param("ii", $solInfo['estudiante_id'], $idCurso);
        $stmtNota->execute();
        $notaData = $stmtNota->get_result()->fetch_assoc();
        $stmtNota->close();
        
        if ($notaData) {
            $notaFinal = number_format((float)$notaData['notaFinal'], 2);
            $fechaActividad = date('d/m/Y', strtotime($notaData['fechaRegistro']));
        }
    }
    
    $motivo = $input['motivo'] ?? 'Trámite personal';
    $fechaSolicitud = date('d/m/Y');
    $horaSolicitud = date('h:i A');
    $fechaEmision = date('d/m/Y');
    $horaEmision = date('h:i A');
    $estado = 'Generada';
    
    // Cargar la plantilla HTML
    ob_start();
    include 'comprobantes/vista-constancia-administrativa.php';
    $htmlContent = ob_get_clean();
    
    // Generar PDF usando Dompdf
    require_once 'includes/dompdf/autoload.inc.php';
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();
    
    $dirPDF = 'uploads/constancias/';
    if (!is_dir($dirPDF)) {
        mkdir($dirPDF, 0777, true);
    }
    $nombreArchivo = 'constancia_' . $codigoConstancia . '.pdf';
    $rutaPDFCompleta = $dirPDF . $nombreArchivo;
    file_put_contents($rutaPDFCompleta, $pdfOutput);
    
    $idUsuarioSolicitante = $solInfo['usuario_id'];
    $adminCorreo = $_SESSION['usuario'];
    $stmtAdmin = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
    $stmtAdmin->bind_param("s", $adminCorreo);
    $stmtAdmin->execute();
    $adminInfo = $stmtAdmin->get_result()->fetch_assoc();
    $stmtAdmin->close();
    
    $idGeneradoPor = $adminInfo['id'] ?? 1;
    
    $stmtIns = $conexion->prepare("
        INSERT INTO constancias (codigoConstancia, tipo, idUsuarioSolicitante, idCurso, fechaSolicitud, idGeneradoPor, rutaPDF)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $tipoConstanciaDB = ($tipoRol === 'EST' ? 'Estudiante' : 'Docente');
    $stmtIns->bind_param("ssiisis", $codigoConstancia, $tipoConstanciaDB, $idUsuarioSolicitante, $idCurso, $solInfo['fecha_solicitud_raw'], $idGeneradoPor, $rutaPDFCompleta);
    $stmtIns->execute();
    $stmtIns->close();
    
    //Actualizar el estado de la solicitud
    if ($tipoRol === 'EST') {
        $stmtUpd = $conexion->prepare("UPDATE solicitudConstanciaEstudiante SET estado = 'Aprobada' WHERE id = ?");
    } else {
        $stmtUpd = $conexion->prepare("UPDATE solicitudConstanciaDocente SET estado = 'Aprobada' WHERE id = ?");
    }
    $stmtUpd->bind_param("i", $solicitudId);
    $stmtUpd->execute();
    $stmtUpd->close();
    
    //Enviar automáticamente al correo electrónico usando PHPMailer
    require_once 'includes/PHPMailer/PHPMailer.php';
    require_once 'includes/PHPMailer/SMTP.php';
    require_once 'includes/PHPMailer/Exception.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $correoEnviado = false;
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'academiafuturodigital6@gmail.com';
        $mail->Password = 'qrgzjvlgqccqcoab';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('academiafuturodigital6@gmail.com', 'Academia Futuro Digital');
        $mail->addAddress($correo, $solicitante);
        $mail->addStringAttachment($pdfOutput, 'constancia_' . $codigoConstancia . '.pdf', 'base64', 'application/pdf');
        
        $mail->isHTML(true);
        $mail->Subject = 'Su Constancia Academica ha sido generada - Academia Futuro Digital';
        
        $cuerpoMail = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #069dbf;'>¡Hola, {$solicitante}!</h2>
                <p>Nos complace informarle que su solicitud de constancia ha sido procesada con éxito.</p>
                <p>Adjunto a este correo encontrará el archivo PDF oficial correspondiente a su <strong>{$tipoConstancia}</strong> para el curso <strong>{$curso}</strong>.</p>
                <p>Detalles del documento:</p>
                <ul>
                    <li><strong>Código:</strong> {$codigoConstancia}</li>
                    <li><strong>Curso:</strong> {$curso} ({$codigoCurso})</li>
                    <li><strong>Periodo:</strong> {$periodo}</li>
                    " . (($tipoRol === 'EST') ? "<li><strong>Nota Final:</strong> {$notaFinal}</li>" : "") . "
                    <li><strong>Fecha de Emisión:</strong> {$fechaEmision}</li>
                </ul>
                <br>
                <p>Atentamente,</p>
                <p><strong>Administración Académica</strong><br>Academia Futuro Digital</p>
            </div>
        ";
        
        $mail->Body = $cuerpoMail;
        $mail->send();
        $correoEnviado = true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Constancia generada con éxito' . ($correoEnviado ? ' y enviada al correo del solicitante.' : ' (error al enviar correo).'),
        'codigo' => $codigoConstancia,
        'solicitante' => $solicitante,
        'correo' => $correo,
        'rol' => $destino,
        'tipo' => $tipoConstancia,
        'curso' => $curso,
        'codigoCurso' => $codigoCurso,
        'periodo' => $periodo,
        'notaFinal' => $notaFinal,
        'resultado' => $resultado,
        'fechaActividad' => $fechaActividad,
        'motivo' => $motivo,
        'fechaSolicitud' => $fechaSolicitud,
        'horaSolicitud' => $horaSolicitud,
        'fechaGeneracion' => $fechaEmision,
        'horaGeneracion' => $horaEmision,
        'estado' => $estado,
        'rutaPDF' => $rutaPDFCompleta,
        'correoEnviado' => $correoEnviado
    ]);
    exit();
}

//Cargar solicitudes pendientes de la Base de Datos
$queryEst = "
    SELECT 
        'Estudiante' AS rol,
        'Constancia de aprobación de curso' AS tipo,
        sol.id AS solicitud_id,
        CONCAT(u.nombre, ' ', u.apellido) AS solicitante,
        u.correo,
        c.nombre AS curso,
        c.id AS curso_id,
        pi.nombre AS periodo_nombre,
        rn.notaFinal,
        rn.estadoEstudiante AS resultado,
        sol.fechaSolicitud AS fecha,
        sol.motivo
    FROM solicitudConstanciaEstudiante sol
    INNER JOIN estudiantes e ON sol.idEstudiante = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN cursos c ON sol.idCurso = c.id
    LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
    LEFT JOIN RegistroNotas rn ON rn.idEstudiante = e.id AND rn.idCurso = c.id
    WHERE sol.estado = 'Pendiente'
";

$queryDoc = "
    SELECT 
        'Docente' AS rol,
        'Constancia de docencia impartida' AS tipo,
        sol.id AS solicitud_id,
        CONCAT(u.nombre, ' ', u.apellido) AS solicitante,
        u.correo,
        c.nombre AS curso,
        c.id AS curso_id,
        pi.nombre AS periodo_nombre,
        'No aplica' AS notaFinal,
        'Curso impartido' AS resultado,
        sol.fechaSolicitud AS fecha,
        sol.motivo
    FROM solicitudConstanciaDocente sol
    INNER JOIN docentes d ON sol.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    INNER JOIN cursos c ON sol.idCurso = c.id
    LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
    WHERE sol.estado = 'Pendiente'
";

$solicitudes = [];
$resEst = $conexion->query($queryEst);
if ($resEst) {
    while ($row = $resEst->fetch_assoc()) {
        $codigoCurso = strtoupper(substr($row['curso'], 0, 4)) . '-' . $row['curso_id'];
        $solicitudes[] = [
            'id' => 'SOL-EST-' . $row['solicitud_id'],
            'solicitante' => $row['solicitante'],
            'correo' => $row['correo'],
            'rol' => 'Estudiante',
            'tipo' => $row['tipo'],
            'curso' => $row['curso'],
            'codigoCurso' => $codigoCurso,
            'periodo' => $row['periodo_nombre'] ?? 'Sin periodo',
            'notaFinal' => number_format((float)$row['notaFinal'], 1),
            'resultado' => $row['resultado'] ?? 'Aprobado',
            'fechaActividad' => date('Y-m-d', strtotime($row['fecha'])),
            'motivo' => $row['motivo'] ?? 'Trámite personal',
            'fecha' => date('Y-m-d', strtotime($row['fecha'])),
            'estado' => 'Pendiente'
        ];
    }
}

$resDoc = $conexion->query($queryDoc);
if ($resDoc) {
    while ($row = $resDoc->fetch_assoc()) {
        $codigoCurso = strtoupper(substr($row['curso'], 0, 4)) . '-' . $row['curso_id'];
        $solicitudes[] = [
            'id' => 'SOL-DOC-' . $row['solicitud_id'],
            'solicitante' => $row['solicitante'],
            'correo' => $row['correo'],
            'rol' => 'Docente',
            'tipo' => $row['tipo'],
            'curso' => $row['curso'],
            'codigoCurso' => $codigoCurso,
            'periodo' => $row['periodo_nombre'] ?? 'Sin periodo',
            'notaFinal' => 'No aplica',
            'resultado' => 'Curso impartido',
            'fechaActividad' => date('Y-m-d', strtotime($row['fecha'])),
            'motivo' => $row['motivo'] ?? 'Trámite personal',
            'fecha' => date('Y-m-d', strtotime($row['fecha'])),
            'estado' => 'Pendiente'
        ];
    }
}

//Cargar KPIs actualizados de la Base de Datos
$kpiPendientes = count($solicitudes);

$resGenHoy = $conexion->query("SELECT COUNT(*) AS total FROM constancias WHERE DATE(fechaGeneracion) = CURDATE()");
$kpiGeneradasHoy = $resGenHoy ? $resGenHoy->fetch_assoc()['total'] : 0;

$resHistTotal = $conexion->query("SELECT COUNT(*) AS total FROM constancias");
$kpiHistorialTotal = $resHistTotal ? $resHistTotal->fetch_assoc()['total'] : 0;

//Cargar Historial de constancias
$queryHist = "
    SELECT 
        c.codigoConstancia,
        c.tipo,
        CONCAT(u.nombre, ' ', u.apellido) AS solicitante,
        u.correo,
        c.rutaPDF,
        c.fechaGeneracion,
        c.fechaSolicitud,
        cur.nombre AS curso_nombre,
        cur.id AS curso_id
    FROM constancias c
    INNER JOIN usuarios u ON c.idUsuarioSolicitante = u.id
    INNER JOIN cursos cur ON c.idCurso = cur.id
    ORDER BY c.fechaGeneracion DESC
";
$historialDb = [];
$resHist = $conexion->query($queryHist);
if ($resHist) {
    while ($row = $resHist->fetch_assoc()) {
        $historialDb[] = $row;
    }
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
                    <strong id="constanciasPendientes"><?= $kpiPendientes ?></strong>
                </article>
                <article>
                    <span>Generadas hoy</span>
                    <strong id="constanciasGeneradas"><?= $kpiGeneradasHoy ?></strong>
                </article>
                <article>
                    <span>Historial</span>
                    <strong id="constanciasHistorialTotal"><?= $kpiHistorialTotal ?></strong>
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
                        <?php if (empty($historialDb)): ?>
                            <tr class="constancias-sin-historial" id="constanciasSinHistorial">
                                <td colspan="8">Todavía no hay constancias generadas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historialDb as $hist): ?>
                                <tr data-historial="true" data-busqueda="<?= htmlspecialchars(strtolower($hist['codigoConstancia'] . ' ' . $hist['solicitante'] . ' ' . ($hist['tipo'] === 'Estudiante' ? 'Constancia de aprobación de curso' : 'Constancia de docencia impartida') . ' ' . $hist['curso_nombre'])) ?>" data-tipo="<?= htmlspecialchars($hist['tipo'] === 'Estudiante' ? 'Constancia de aprobación de curso' : 'Constancia de docencia impartida') ?>" data-fecha="<?= date('Y-m-d', strtotime($hist['fechaGeneracion'])) ?>">
                                    <td data-label="Código"><?= htmlspecialchars($hist['codigoConstancia']) ?></td>
                                    <td data-label="Tipo"><?= htmlspecialchars($hist['tipo'] === 'Estudiante' ? 'Constancia de aprobación de curso' : 'Constancia de docencia impartida') ?></td>
                                    <td data-label="Solicitante"><?= htmlspecialchars($hist['solicitante']) ?></td>
                                    <td data-label="Curso"><?= htmlspecialchars($hist['curso_nombre']) ?><br><small><?= htmlspecialchars(strtoupper(substr($hist['curso_nombre'], 0, 4)) . '-' . $hist['curso_id']) ?></small></td>
                                    <td data-label="Fecha solicitud"><?= $hist['fechaSolicitud'] ? date('Y-m-d', strtotime($hist['fechaSolicitud'])) : '-' ?></td>
                                    <td data-label="Fecha generación"><?= date('Y-m-d', strtotime($hist['fechaGeneracion'])) ?></td>
                                    <td data-label="Estado"><span class="constancia-badge generada">Generada</span></td>
                                    <td data-label="Acciones">
                                        <a class="link-accion constancia-pdf-btn" href="<?= htmlspecialchars($hist['rutaPDF']) ?>" target="_blank" rel="noopener">Ver constancia</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="constancias-sin-historial" id="constanciasSinHistorial" hidden>
                                <td colspan="8">Todavía no hay constancias generadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="./js/script.js"></script>
</body>
</html>
