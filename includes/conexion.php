
<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "db_academiadigital";

$conexion = new mysqli($server, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
// Desactiva automáticamente los cursos cuyo ciclo ya finalizó.
// Reemplaza el evento MySQL (event_scheduler) que puede estar inactivo en el servidor.
// El WHERE es selectivo: solo afecta filas que aún no han sido desactivadas.
$conexion->query("
    UPDATE cursos c
    INNER JOIN PeriodoInscripcion p ON p.id = c.idPeriodo
    SET c.estado = 0
    WHERE p.fechaFinCiclo < CURDATE()
      AND c.estado = 1
 ");
 
?>