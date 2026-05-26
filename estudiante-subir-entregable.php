<?php

session_start();
include("includes/conexion.php");

header('Content-Type: application/json');

// ── 1. Verificar sesión y que sea estudiante ──────────────────
if (!isset($_SESSION["usuario"]) || $_SESSION["rol_id"] != 2) {
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

// ── 2. Recibir y validar datos del formulario ─────────────────
$idTarea = isset($_POST["idTarea"]) ? intval($_POST["idTarea"]) : 0;

if ($idTarea <= 0) {
    echo json_encode(["success" => false, "message" => "Tarea no válida."]);
    exit();
}

if ($idTarea <= 0) {
    echo json_encode(["success" => false, "message" => "Tarea no válida."]);
    exit();
}

// Validar que se haya enviado al menos un archivo o un enlace
$tieneArchivo = isset($_FILES["archivos"]) && !empty($_FILES["archivos"]["name"][0]);
$tieneEnlace  = isset($_POST["enlace"])    && trim($_POST["enlace"]) !== "";

if (!$tieneArchivo && !$tieneEnlace) {
    echo json_encode(["success" => false, "message" => "Debes adjuntar al menos un archivo o enlace."]);
    exit();
}

// ── 3. Obtener el id del estudiante desde el correo en sesión ─
$correo = mysqli_real_escape_string($conexion, $_SESSION["usuario"]);
$sqlEstudiante = "SELECT e.id FROM estudiantes e 
                  INNER JOIN usuarios u ON u.id = e.usuario_id 
                  WHERE u.correo = '$correo'";
$resEstudiante = mysqli_query($conexion, $sqlEstudiante);

if (mysqli_num_rows($resEstudiante) == 0) {
    echo json_encode(["success" => false, "message" => "Estudiante no encontrado."]);
    exit();
}
$idEstudiante = mysqli_fetch_assoc($resEstudiante)["id"];

// ── 4. Verificar que la tarea exista y esté activa ────────────
$sqlTarea = "SELECT t.id, t.idCurso, t.fechaLimite, t.estado 
             FROM tareas t 
             WHERE t.id = $idTarea";
$resTarea = mysqli_query($conexion, $sqlTarea);

if (mysqli_num_rows($resTarea) == 0) {
    echo json_encode(["success" => false, "message" => "La tarea no existe."]);
    exit();
}
$tarea = mysqli_fetch_assoc($resTarea);

// Validación de fecha límite en PHP (el trigger de BD también lo bloquea,
// pero así devolvemos un mensaje amigable antes de llegar al INSERT)
if ($tarea["estado"] == 0 || strtotime($tarea["fechaLimite"]) < time()) {
    echo json_encode(["success" => false, "message" => "El plazo para entregar esta tarea ha vencido."]);
    exit();
}

// ── 5. Verificar que el estudiante esté inscrito en el curso ──
$idCurso = intval($tarea["idCurso"]);
$sqlInscripcion = "SELECT id FROM inscripciones 
                   WHERE idEstudiante = $idEstudiante 
                     AND idCurso = $idCurso 
                     AND estado_academico = 'Activo'";
$resInscripcion = mysqli_query($conexion, $sqlInscripcion);

if (mysqli_num_rows($resInscripcion) == 0) {
    echo json_encode(["success" => false, "message" => "No estás inscrito en el curso de esta tarea."]);
    exit();
}

// ── 6. Verificar que no haya entregado ya esta tarea ──────────
// (el UNIQUE KEY unique_estudiante_tarea de la BD también lo bloquea)
$sqlEntregaExistente = "SELECT id FROM entregablesTarea 
                        WHERE idTarea = $idTarea AND idEstudiante = $idEstudiante";
$resEntregaExistente = mysqli_query($conexion, $sqlEntregaExistente);

if (mysqli_num_rows($resEntregaExistente) > 0) {
    echo json_encode(["success" => false, "message" => "Ya tienes una entrega registrada para esta tarea. Usa la opción de reemplazar entrega."]);
    exit();
}

// ── 7. Iniciar transacción ────────────────────────────────────
mysqli_begin_transaction($conexion);

try {

    // ── 8. Insertar el entregable ─────────────────────────────
    // El trigger tr_validar_fecha_entrega valida la fecha límite en la BD
    $sqlInsertEntrega = "INSERT INTO entregablesTarea (idTarea, idEstudiante, estado) 
                         VALUES ($idTarea, $idEstudiante, 'Entregado')";
    
    if (!mysqli_query($conexion, $sqlInsertEntrega)) {
        // Captura el error del trigger si la fecha ya venció
        throw new Exception(mysqli_error($conexion));
    }

    $idEntrega = mysqli_insert_id($conexion);

    // ── 9. Procesar archivos subidos ──────────────────────────
    if ($tieneArchivo) {
        $carpetaDestino = "uploads/entregables/";

        // Crear carpeta si no existe
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $totalArchivos = count($_FILES["archivos"]["name"]);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES["archivos"]["error"][$i] !== UPLOAD_ERR_OK) {
                continue; // Saltar archivos con error
            }

            $nombreOriginal = basename($_FILES["archivos"]["name"][$i]);
            $extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $tamano         = $_FILES["archivos"]["size"][$i];

            // Límite de 20MB por archivo
            if ($tamano > 20 * 1024 * 1024) {
                throw new Exception("El archivo '$nombreOriginal' supera el límite de 20MB.");
            }

            // Nombre único para evitar colisiones
            $nombreUnico  = "entrega_{$idEntrega}_{$idEstudiante}_" . time() . "_$i.$extension";
            $rutaDestino  = $carpetaDestino . $nombreUnico;

            if (!move_uploaded_file($_FILES["archivos"]["tmp_name"][$i], $rutaDestino)) {
                throw new Exception("Error al subir el archivo '$nombreOriginal'.");
            }

            // Limitar nombre visible a 50 caracteres (longitud de la columna)
            $nombreVisible = strlen($nombreOriginal) > 50 
                             ? substr($nombreOriginal, 0, 47) . "..." 
                             : $nombreOriginal;
            $nombreVisible = mysqli_real_escape_string($conexion, $nombreVisible);
            $rutaDestino   = mysqli_real_escape_string($conexion, $rutaDestino);

            $sqlArchivo = "INSERT INTO entregaArchivos (idEntrega, nombreArchivo, tipo, rutaArchivo) 
                           VALUES ($idEntrega, '$nombreVisible', 'Archivo', '$rutaDestino')";

            if (!mysqli_query($conexion, $sqlArchivo)) {
                throw new Exception("Error al registrar el archivo: " . mysqli_error($conexion));
            }
        }
    }

    // ── 10. Procesar enlace si fue enviado ────────────────────
    if ($tieneEnlace) {
        $enlace        = mysqli_real_escape_string($conexion, trim($_POST["enlace"]));
        $nombreEnlace  = isset($_POST["nombreEnlace"]) && trim($_POST["nombreEnlace"]) !== ""
                         ? mysqli_real_escape_string($conexion, trim($_POST["nombreEnlace"]))
                         : "Enlace adjunto";

        $sqlEnlace = "INSERT INTO entregaArchivos (idEntrega, nombreArchivo, tipo, rutaArchivo) 
                      VALUES ($idEntrega, '$nombreEnlace', 'Enlace', '$enlace')";

        if (!mysqli_query($conexion, $sqlEnlace)) {
            throw new Exception("Error al registrar el enlace: " . mysqli_error($conexion));
        }
    }

    // ── 11. Confirmar transacción ─────────────────────────────
    mysqli_commit($conexion);

    echo json_encode([
        "success"   => true,
        "message"   => "Tarea entregada exitosamente.",
        "idEntrega" => $idEntrega
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexion);

    // Mensaje amigable si el trigger bloquea la entrega fuera de plazo
    $mensajeError = $e->getMessage();
    if (str_contains($mensajeError, "fuera del plazo")) {
        $mensajeError = "El plazo para entregar esta tarea ha vencido.";
    }

    echo json_encode(["success" => false, "message" => $mensajeError]);
}

mysqli_close($conexion);
?>