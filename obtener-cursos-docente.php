<?php
// Obtiene los cursos activos asignados a un docente según su correo.
// Une las tablas usuarios, docentes y cursos para filtrar solo los cursos
// donde estado = 1 y retorna un arreglo con los datos de cada curso.

function getCursosDocente($conexion, $correoDocente) {
    $correo = mysqli_real_escape_string($conexion, $correoDocente);

    // Se agrega COUNT y LEFT JOIN para mostrar la cantidad de alumnos inscritos por curso
    // y GROUP BY Agrupa los cursos para que el COUNT funcione correctamente por cada curso
    $query = "
        SELECT c.id, c.nombre, c.descripcion, c.costoMensual, 
            c.cupos, c.fechaInicio, c.fechaFin,
            COUNT(i.id) AS alumnos_inscritos,
            p.nombre AS periodo_nombre
        FROM cursos c
        INNER JOIN docentes d ON c.idDocente = d.id
        INNER JOIN usuarios u ON d.usuario_id = u.id
        INNER JOIN PeriodoInscripcion p ON c.idPeriodo = p.id
        LEFT JOIN inscripciones i ON i.idCurso = c.id
        WHERE u.correo = '$correo'
        AND c.estado = 1
        AND CURDATE() BETWEEN p.fechaInicio AND p.fechaFin
        AND p.estado = 1
        GROUP BY c.id, c.nombre, c.descripcion, c.costoMensual,
                c.cupos, c.fechaInicio, c.fechaFin, p.nombre
    ";

    $result = mysqli_query($conexion, $query);
    $cursos = [];

    while ($curso = mysqli_fetch_assoc($result)) {
        $cursos[] = $curso;
    }

    return $cursos;
}
?>