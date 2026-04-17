<?php
// Obtiene los cursos activos asignados a un docente según su correo.
// Une las tablas usuarios, docentes y cursos para filtrar solo los cursos
// donde estado = 1 y retorna un arreglo con los datos de cada curso.

function getCursosDocente($conexion, $correoDocente) {
    $correo = mysqli_real_escape_string($conexion, $correoDocente);

    $query = "
        SELECT c.id, c.nombre, c.descripcion, c.costoMensual, 
               c.cupos, c.fechaInicio, c.fechaFin
        FROM cursos c
        INNER JOIN docentes d ON c.idDocente = d.id
        INNER JOIN usuarios u ON d.usuario_id = u.id
        WHERE u.correo = '$correo'
          AND c.estado = 1
    ";

    $result = mysqli_query($conexion, $query);
    $cursos = [];

    while ($curso = mysqli_fetch_assoc($result)) {
        $cursos[] = $curso;
    }

    return $cursos;
}
?>