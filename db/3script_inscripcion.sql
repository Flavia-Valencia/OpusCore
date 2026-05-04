use db_academiadigital;
-- Crea la tabla periodoInscripcion, donde se habilitará el rango donde podrán inscribirse según el curso y fecha periodo.
CREATE TABLE `PeriodoInscripcion` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `fechaInicio` date NOT NULL,
    `fechaFin` date NOT NULL,
    `estado` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cursos` 
ADD COLUMN `idPeriodo` int DEFAULT NULL;

ALTER TABLE `cursos`
ADD CONSTRAINT `fk_curso_periodo_insc` 
FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`);
-- Es delimiter es una restriccion en la cual no se permite seleccionar una fecha anterior a la fecha inicio.
DELIMITER //
CREATE TRIGGER `tr_validar_fechas_periodo_insert`
BEFORE INSERT ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: La fecha de fin no puede ser anterior a la de inicio';
    END IF;
END //
CREATE TRIGGER `tr_validar_fechas_periodo_update`
BEFORE UPDATE ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: La fecha de fin no puede ser anterior a la de inicio';
    END IF;
END //
-- crea restricciones para insertar y editar datos donde no se puede seleccionar un rango de algún periodo creado.
CREATE TRIGGER `tr_no_traslapar_periodos_insert`
BEFORE INSERT ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE (NEW.fechaInicio BETWEEN fechaInicio AND fechaFin)
           OR (NEW.fechaFin BETWEEN fechaInicio AND fechaFin)
           OR (fechaInicio BETWEEN NEW.fechaInicio AND NEW.fechaFin)
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El nuevo periodo choca con fechas existentes';
    END IF;
END //
CREATE TRIGGER `tr_no_traslapar_periodos_update`
BEFORE UPDATE ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE id <> NEW.id
          AND ((NEW.fechaInicio BETWEEN fechaInicio AND fechaFin)
           OR (NEW.fechaFin BETWEEN fechaInicio AND fechaFin)
           OR (fechaInicio BETWEEN NEW.fechaInicio AND NEW.fechaFin))
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: La modificación choca con fechas de otro periodo';
    END IF;
END //
DELIMITER ;
-- Hu-18 Tabla de inscripciones (después se agrega la fk de factura)
CREATE TABLE `inscripciones` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `idPeriodo` INT NOT NULL,
    `idFactura` INT DEFAULT NULL, 
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `estado_academico` ENUM('Activo', 'Finalizado', 'Retirado') DEFAULT 'Activo',
    CONSTRAINT `fk_estudiante_inscripcion` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_curso_inscripcion` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`),
    CONSTRAINT `fk_periodo_inscripcion` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;