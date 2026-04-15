-- Si en caso de haber insertado datos en las tablas y se desea eliminar los datos para volver a insertar nuevos 
-- datos, se debe ejecutar este script después de haber ejecutado el script de academiadigital y el de cursos. 
-- Este script eliminará todos los datos de las tablas que llevaban datos por defecto y reiniciará el contador 
-- de AUTO_INCREMENT para cada tabla, permitiendo que los nuevos datos se inserten con IDs comenzando desde 0.

USE db_academiadigital;

DELETE FROM prerrequisitos;
DELETE FROM cursos;
DELETE FROM docentes;
DELETE FROM estudiantes;
DELETE FROM administradores;
DELETE FROM usuarios;
DELETE FROM cursoHorario;

ALTER TABLE prerrequisitos AUTO_INCREMENT = 0;
ALTER TABLE cursos AUTO_INCREMENT = 0;
ALTER TABLE docentes AUTO_INCREMENT = 0;
ALTER TABLE estudiantes AUTO_INCREMENT = 0;
ALTER TABLE administradores AUTO_INCREMENT = 0;
ALTER TABLE usuarios AUTO_INCREMENT = 0;
ALTER TABLE cursoHorario AUTO_INCREMENT = 0;

-- para agrandar la descripción de cursos en caso de no volver a ejecutar el script de academiadigital 
-- y el de cursos, se debe ejecutar esta línea para modificar la columna descripción de la tabla cursos.
ALTER TABLE cursos 
MODIFY descripcion VARCHAR(150) NOT NULL;