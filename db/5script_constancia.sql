use db_academiadigital;
-- se guardará el historial de constancias generadas para estudiantes y docentes, con un código único para cada constancia, 
-- el tipo de constancia, el usuario solicitante, el usuario que generó la constancia, la ruta del PDF generado y la fecha de generación.
CREATE TABLE `constancias` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `codigoConstancia` VARCHAR(50) NOT NULL UNIQUE,
    `tipo` ENUM('Estudiante', 'Docente') NOT NULL,
    `idUsuarioSolicitante` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `fechaSolicitud` TIMESTAMP NULL,
    `idGeneradoPor` INT NOT NULL,
    `rutaPDF` VARCHAR(255) NOT NULL,
    `fechaGeneracion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_constancias_solicitante` FOREIGN KEY (`idUsuarioSolicitante`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_constancias_generador` FOREIGN KEY (`idGeneradoPor`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_constancias_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Tabla para registrar las solicitudes de constancias por parte de los estudiantes.
CREATE TABLE `solicitudConstanciaEstudiante` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `motivo` VARCHAR(255) DEFAULT 'Trámite personal', -- es el motivo, ya que no hay seleccion se dejará default, pero en un futuro podría cambiar
    `estado` ENUM('Pendiente', 'Aprobada', 'Rechazada') DEFAULT 'Pendiente',
    `fechaSolicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sol_est_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sol_est_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Tabla para registrar las solicitudes de constancias por parte de los docentes, el motivo se deja default pero se puede cambiar en un futuro.
CREATE TABLE `solicitudConstanciaDocente` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idDocente` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `motivo` VARCHAR(255) DEFAULT 'Trámite personal',
    `estado` ENUM('Pendiente', 'Aprobada', 'Rechazada') DEFAULT 'Pendiente',
    `fechaSolicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sol_doc_docente` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sol_doc_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER //
-- trigger para validar que no se puedan generar constancias si el estado de la solicitud no es aprobada
CREATE TRIGGER `tr_validar_constancia_estudiante_insert`
BEFORE INSERT ON `solicitudConstanciaEstudiante`
FOR EACH ROW
BEGIN
    DECLARE v_estado_curso VARCHAR(50);

    SELECT estadoEstudiante INTO v_estado_curso
    FROM `RegistroNotas`
    WHERE `idEstudiante` = NEW.idEstudiante AND `idCurso` = NEW.idCurso
    LIMIT 1;

    IF v_estado_curso IS NULL OR v_estado_curso <> 'Aprobado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El estudiante no tiene este curso aprobado en su Registro de Notas.';
    END IF;
END //

-- trigger para validar que el docente no pueda generar constancias si no tiene el curso asignado o no tiene el curso aprobado
CREATE TRIGGER `tr_validar_constancia_docente_insert`
BEFORE INSERT ON `solicitudConstanciaDocente`
FOR EACH ROW
BEGIN
    DECLARE v_id_docente_curso INT;
    DECLARE v_fecha_fin_curso DATE;

    SELECT idDocente, fechaFin INTO v_id_docente_curso, v_fecha_fin_curso
    FROM `cursos`
    WHERE `id` = NEW.idCurso
    LIMIT 1;

    IF v_id_docente_curso IS NULL OR v_id_docente_curso <> NEW.idDocente THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No puedes solicitar una constancia para un curso que no tienes asignado.';
    END IF;

    IF v_fecha_fin_curso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No puedes solicitar la constancia hasta que el curso haya finalizado completamente.';
    END IF;
END //
DELIMITER ;