<?php
//Este archivo gestiona la entrega de tareas por parte de los estudiantes.
//Valida la sesión activa y verifica que el usuario tenga rol de estudiante.
//Comprueba que la tarea exista, esté activa y el plazo no haya vencido.
//Verifica que el estudiante esté inscrito en el curso y no haya entregado ya.
//Valida que se adjunte al menos un archivo o enlace antes de procesar.
//Procesa la subida de archivos con nombre único, respetando el límite de 20MB.
//Inicializa conteoIntentos en 1 al registrar la primera entrega.
//Utiliza transacciones para guardar la entrega de forma segura y responde en formato JSON.
session_start();
include("includes/conexion.php");

header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    if (!isset($_SESSION["usuario"]) || $_SESSION["rol_id"] != 2) {
        throw new Exception("Acceso no autorizado.");
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $idTarea = isset($_POST["idTarea"]) ? intval($_POST["idTarea"]) : 0;

    if ($idTarea <= 0) {
        throw new Exception("Tarea no válida.");
    }

    $tieneArchivo = (
        isset($_FILES["archivos"]) &&
        isset($_FILES["archivos"]["name"]) &&
        count($_FILES["archivos"]["name"]) > 0 &&
        !empty($_FILES["archivos"]["name"][0])
    );

    $tieneEnlace = (
        isset($_POST["enlace"]) &&
        trim($_POST["enlace"]) !== ""
    );

    if (!$tieneArchivo && !$tieneEnlace) {
        throw new Exception("Debes adjuntar al menos un archivo o enlace.");
    }

    $correo = mysqli_real_escape_string($conexion, $_SESSION["usuario"]);

    $sqlEstudiante = "
        SELECT e.id
        FROM estudiantes e
        INNER JOIN usuarios u ON u.id = e.usuario_id
        WHERE u.correo = '$correo'
        LIMIT 1
    ";

    $resEstudiante = mysqli_query($conexion, $sqlEstudiante);

    if (mysqli_num_rows($resEstudiante) <= 0) {
        throw new Exception("Estudiante no encontrado.");
    }

    $estudiante = mysqli_fetch_assoc($resEstudiante);
    $idEstudiante = intval($estudiante["id"]);

    $sqlTarea = "
        SELECT id, idCurso, fechaLimite, estado, intentos
        FROM tareas
        WHERE id = $idTarea
        LIMIT 1
    ";

    $resTarea = mysqli_query($conexion, $sqlTarea);

    if (mysqli_num_rows($resTarea) <= 0) {
        throw new Exception("La tarea no existe.");
    }

    $tarea = mysqli_fetch_assoc($resTarea);

    if (
        intval($tarea["estado"]) === 0 ||
        strtotime($tarea["fechaLimite"]) < time()
    ) {
        throw new Exception("El plazo para entregar esta tarea ha vencido.");
    }

    $idCurso = intval($tarea["idCurso"]);

    $sqlInscripcion = "
        SELECT id
        FROM inscripciones
        WHERE idEstudiante = $idEstudiante
        AND idCurso = $idCurso
        AND estado_academico = 'Activo'
        LIMIT 1
    ";

    $resInscripcion = mysqli_query($conexion, $sqlInscripcion);

    if (mysqli_num_rows($resInscripcion) <= 0) {
        throw new Exception("No estás inscrito en el curso de esta tarea.");
    }

    $sqlExiste = "
        SELECT id
        FROM entregablesTarea
        WHERE idTarea = $idTarea
        AND idEstudiante = $idEstudiante
        LIMIT 1
    ";

    $resExiste = mysqli_query($conexion, $sqlExiste);

    if (mysqli_num_rows($resExiste) > 0) {
        throw new Exception("Ya entregaste esta tarea.");
    }

    mysqli_begin_transaction($conexion);

    $sqlEntrega = "
        INSERT INTO entregablesTarea
        (idTarea, idEstudiante, estado, conteoIntentos)
        VALUES
        ($idTarea, $idEstudiante, 'Entregado', 1)
    ";

    mysqli_query($conexion, $sqlEntrega);

    $idEntrega = mysqli_insert_id($conexion);

    if ($tieneArchivo) {

        $carpetaDestino = "uploads/entregables/";

        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $totalArchivos = count($_FILES["archivos"]["name"]);

        for ($i = 0; $i < $totalArchivos; $i++) {

            if ($_FILES["archivos"]["error"][$i] !== UPLOAD_ERR_OK) {
                throw new Exception("Error al subir archivo.");
            }

            $nombreOriginal = basename($_FILES["archivos"]["name"][$i]);

            $extension = strtolower(
                pathinfo($nombreOriginal, PATHINFO_EXTENSION)
            );

            $tamano = $_FILES["archivos"]["size"][$i];

            // Máx 20MB
            if ($tamano > (20 * 1024 * 1024)) {
                throw new Exception("El archivo '$nombreOriginal' supera 20MB.");
            }

            $nombreUnico =
                "entrega_" .
                $idEntrega . "_" .
                $idEstudiante . "_" .
                time() . "_" .
                $i . "." . $extension;

            $rutaDestino = $carpetaDestino . $nombreUnico;

            if (!move_uploaded_file(
                $_FILES["archivos"]["tmp_name"][$i],
                $rutaDestino
            )) {
                throw new Exception("No se pudo guardar el archivo.");
            }

            $nombreVisible = strlen($nombreOriginal) > 50
                ? substr($nombreOriginal, 0, 47) . "..."
                : $nombreOriginal;

            $nombreVisible = mysqli_real_escape_string($conexion, $nombreVisible);
            $rutaDestino   = mysqli_real_escape_string($conexion, $rutaDestino);
            $sqlArchivo = "
                INSERT INTO entregaArchivos
                (idEntrega, nombreArchivo, tipo, rutaArchivo)
                VALUES
                ($idEntrega, '$nombreVisible', 'Archivo', '$rutaDestino')
            ";

            mysqli_query($conexion, $sqlArchivo);
        }
    }

    if ($tieneEnlace) {

        $enlace = mysqli_real_escape_string(
            $conexion,
            trim($_POST["enlace"])
        );

        $nombreEnlace = (
            isset($_POST["nombreEnlace"]) &&
            trim($_POST["nombreEnlace"]) !== ""
        )
            ? mysqli_real_escape_string(
                $conexion,
                trim($_POST["nombreEnlace"])
            )
            : "Enlace adjunto";

        $sqlEnlace = "
            INSERT INTO entregaArchivos
            (idEntrega, nombreArchivo, tipo, rutaArchivo)
            VALUES
            ($idEntrega, '$nombreEnlace', 'Enlace', '$enlace')
        ";

        mysqli_query($conexion, $sqlEnlace);
    }

    mysqli_commit($conexion);

    echo json_encode([
        "success"            => true,
        "message"            => "Tarea entregada exitosamente.",
        "idEntrega"          => $idEntrega,
        "conteoIntentos"     => 1,
        "intentosPermitidos" => intval($tarea["intentos"])
    ]);

} catch (Exception $e) {

    mysqli_rollback($conexion);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

mysqli_close($conexion);

?>