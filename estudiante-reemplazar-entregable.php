<?php
//Este archivo gestiona el reemplazo de entregas de tareas por parte de estudiantes.
//Valida la sesión activa, verifica que la tarea exista y el plazo no haya vencido.
//Comprueba la inscripción del estudiante en el curso y que exista una entrega previa.
//Valida que el conteoIntentos no haya alcanzado el límite definido por el docente en la tarea.
//Elimina archivos físicos anteriores y limpia registros de entregaArchivos.
//Registra los nuevos archivos o enlaces e incrementa conteoIntentos en la entrega.
//Utiliza transacciones para actualizar la entrega de forma segura y responde en formato JSON.
session_start();
include("includes/conexion.php");

header('Content-Type: application/json');

if (!isset($_SESSION["usuario"]) || $_SESSION["rol_id"] != 2) {
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

$idTarea = isset($_POST["idTarea"]) ? intval($_POST["idTarea"]) : 0;

if ($idTarea <= 0) {
    echo json_encode(["success" => false, "message" => "Tarea no válida."]);
    exit();
}

$tieneArchivo = isset($_FILES["archivos"]) && !empty($_FILES["archivos"]["name"][0]);
$tieneEnlace  = isset($_POST["enlace"]) && trim($_POST["enlace"]) !== "";

if (!$tieneArchivo && !$tieneEnlace) {
    echo json_encode(["success" => false, "message" => "Debes adjuntar al menos un archivo o enlace."]);
    exit();
}

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

$sqlTarea = "SELECT id, idCurso, fechaLimite, estado, intentos 
             FROM tareas 
             WHERE id = $idTarea";
$resTarea = mysqli_query($conexion, $sqlTarea);

if (mysqli_num_rows($resTarea) == 0) {
    echo json_encode(["success" => false, "message" => "La tarea no existe."]);
    exit();
}
$tarea = mysqli_fetch_assoc($resTarea);

if ($tarea["estado"] == 0 || strtotime($tarea["fechaLimite"]) < time()) {
    echo json_encode(["success" => false, "message" => "El plazo para reemplazar esta entrega ha vencido."]);
    exit();
}

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

$sqlEntrega = "SELECT id, conteoIntentos  FROM entregablesTarea 
               WHERE idTarea = $idTarea AND idEstudiante = $idEstudiante";
$resEntrega = mysqli_query($conexion, $sqlEntrega);

if (mysqli_num_rows($resEntrega) == 0) {
    echo json_encode(["success" => false, "message" => "No tienes una entrega registrada para esta tarea. Usa la opción de entregar tarea."]);
    exit();
}
$entrega = mysqli_fetch_assoc($resEntrega);
$idEntrega = intval($entrega["id"]);
$conteoActual = intval($entrega["conteoIntentos"]);
$intentosPermitidos = intval($tarea["intentos"]); 

if ($conteoActual >= $intentosPermitidos) {
    echo json_encode(["success" => false, "message" => "Has alcanzado el límite de $intentosPermitidos entrega(s) permitidas para esta tarea."]);
    exit();
}

$sqlArchivosActuales = "SELECT id, rutaArchivo, tipo 
                        FROM entregaArchivos 
                        WHERE idEntrega = $idEntrega";
$resArchivosActuales = mysqli_query($conexion, $sqlArchivosActuales);
$archivosAEliminar = [];
while ($row = mysqli_fetch_assoc($resArchivosActuales)) {
    $archivosAEliminar[] = $row;
}

mysqli_begin_transaction($conexion);

try {

    foreach ($archivosAEliminar as $archivo) {
        if ($archivo["tipo"] === "Archivo" && !empty($archivo["rutaArchivo"])) {
            if (file_exists($archivo["rutaArchivo"])) {
                unlink($archivo["rutaArchivo"]);
            }
        }
    }

    $sqlDeleteArchivos = "DELETE FROM entregaArchivos WHERE idEntrega = $idEntrega";
    if (!mysqli_query($conexion, $sqlDeleteArchivos)) {
        throw new Exception("Error al limpiar archivos anteriores: " . mysqli_error($conexion));
    }

    $nuevoConteo = $conteoActual + 1;
    $sqlUpdateEntrega = "UPDATE entregablesTarea 
                         SET estado = 'Entregado', conteoIntentos = $nuevoConteo
                         WHERE id = $idEntrega";
    if (!mysqli_query($conexion, $sqlUpdateEntrega)) {
        throw new Exception("Error al actualizar la entrega: " . mysqli_error($conexion));
    }

    if ($tieneArchivo) {
        $carpetaDestino = "uploads/entregables/";

        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $totalArchivos = count($_FILES["archivos"]["name"]);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES["archivos"]["error"][$i] !== UPLOAD_ERR_OK) continue;

            $nombreOriginal = basename($_FILES["archivos"]["name"][$i]);
            $extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $tamano         = $_FILES["archivos"]["size"][$i];

            if ($tamano > 20 * 1024 * 1024) {
                throw new Exception("El archivo '$nombreOriginal' supera el límite de 20MB.");
            }

            $nombreUnico = "entrega_{$idEntrega}_{$idEstudiante}_" . time() . "_$i.$extension";
            $rutaDestino = $carpetaDestino . $nombreUnico;

            if (!move_uploaded_file($_FILES["archivos"]["tmp_name"][$i], $rutaDestino)) {
                throw new Exception("Error al subir el archivo '$nombreOriginal'.");
            }

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

    if ($tieneEnlace) {
        $enlace       = mysqli_real_escape_string($conexion, trim($_POST["enlace"]));
        $nombreEnlace = isset($_POST["nombreEnlace"]) && trim($_POST["nombreEnlace"]) !== ""
                        ? mysqli_real_escape_string($conexion, trim($_POST["nombreEnlace"]))
                        : "Enlace adjunto";

        $sqlEnlace = "INSERT INTO entregaArchivos (idEntrega, nombreArchivo, tipo, rutaArchivo) 
                      VALUES ($idEntrega, '$nombreEnlace', 'Enlace', '$enlace')";

        if (!mysqli_query($conexion, $sqlEnlace)) {
            throw new Exception("Error al registrar el enlace: " . mysqli_error($conexion));
        }
    }

    mysqli_commit($conexion);

    $intentosRestantes = $intentosPermitidos - $nuevoConteo;
    echo json_encode([
        "success"           => true,
        "message"           => "Entrega reemplazada exitosamente.",
        "intentos"          => $nuevoConteo,
        "intentosRestantes" => $intentosRestantes
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexion);

    $mensajeError = $e->getMessage();
    if (str_contains($mensajeError, "fuera del plazo")) {
        $mensajeError = "El plazo para reemplazar esta entrega ha vencido.";
    }

    echo json_encode(["success" => false, "message" => $mensajeError]);
}

mysqli_close($conexion);
?>