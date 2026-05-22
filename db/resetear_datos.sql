USE db_academiadigital;

-- Si en caso de haber insertado datos en las tablas y se desea eliminar los datos para volver a insertar nuevos
-- datos.
-- Este script eliminará todos los datos de las tablas que llevaban y los que se han agregado. Reiniciará el contador
-- de AUTO_INCREMENT para cada tabla, permitiendo que los nuevos datos se inserten con IDs comenzando desde 1.
DELETE FROM facturas;
DELETE FROM detalle_facturas;
DELETE FROM mensualidades;
DELETE FROM matricula;
DELETE FROM pagos;
DELETE FROM inscripciones;
DELETE FROM cursoHorario;
DELETE FROM prerrequisitos;
DELETE FROM cursos; -- se eliminan en cascada las sesiones, tareas y archivos  relacionados con los cursos.
DELETE FROM PeriodoInscripcion;
DELETE FROM docentes;
DELETE FROM estudiantes;
DELETE FROM administradores;
DELETE FROM usuarios;

ALTER TABLE facturas AUTO_INCREMENT = 1;
ALTER TABLE detalle_facturas AUTO_INCREMENT = 1;
ALTER TABLE mensualidades AUTO_INCREMENT = 1;
ALTER TABLE matricula AUTO_INCREMENT = 1;
ALTER TABLE pagos AUTO_INCREMENT = 1;
ALTER TABLE inscripciones AUTO_INCREMENT = 1;
ALTER TABLE cursoHorario AUTO_INCREMENT = 1;
ALTER TABLE prerrequisitos AUTO_INCREMENT = 1;
ALTER TABLE sesionContenido AUTO_INCREMENT = 1;
ALTER TABLE sesionArchivos AUTO_INCREMENT = 1;
ALTER TABLE tareas AUTO_INCREMENT = 1;
ALTER TABLE tareasArchivos AUTO_INCREMENT = 1;
ALTER TABLE cursos AUTO_INCREMENT = 1;
ALTER TABLE PeriodoInscripcion AUTO_INCREMENT = 1;
ALTER TABLE docentes AUTO_INCREMENT = 1;
ALTER TABLE estudiantes AUTO_INCREMENT = 1;
ALTER TABLE administradores AUTO_INCREMENT = 1;
ALTER TABLE usuarios AUTO_INCREMENT = 1;