-- Si en caso de haber insertado datos en las tablas y se desea eliminar los datos para volver a insertar nuevos 
-- datos. 
-- Este script eliminará todos los datos de las tablas que llevaban y los que se han agregado. Reiniciará el contador 
-- de AUTO_INCREMENT para cada tabla, permitiendo que los nuevos datos se inserten con IDs comenzando desde 1.

USE db_academiadigital;

DELETE FROM prerrequisitos;
DELETE FROM cursos;
DELETE FROM docentes;
DELETE FROM estudiantes;
DELETE FROM administradores;
DELETE FROM usuarios;
DELETE FROM cursoHorario;

ALTER TABLE prerrequisitos AUTO_INCREMENT = 1;
ALTER TABLE cursos AUTO_INCREMENT = 1;
ALTER TABLE docentes AUTO_INCREMENT = 1;
ALTER TABLE estudiantes AUTO_INCREMENT = 1;
ALTER TABLE administradores AUTO_INCREMENT = 1;
ALTER TABLE usuarios AUTO_INCREMENT = 1;
ALTER TABLE cursoHorario AUTO_INCREMENT = 1;