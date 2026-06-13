use db_academiadigital;
-- tabla para contenidos de la clase, el estado sería habillitar/desabilitar 

CREATE TABLE `sesionContenido` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idCurso` INT NOT NULL,
    `titulo` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(200) NULL,
    `fecha` DATE NOT NULL,
    `estado` TINYINT(1) DEFAULT 1, 
    `fechaCreacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sesion_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- taabla para los multiples archivos para la clase
CREATE TABLE `sesionArchivos` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idSesion` INT NOT NULL,
    `nombreArchivo` VARCHAR(50) NOT NULL, 
    `rutaArchivo` TEXT NOT NULL, -- es para la url del archivo / enlace
    `tipo` ENUM('Archivo', 'Enlace') DEFAULT 'Archivo', -- logica si selecciona archivo debe ponerle un botón para buscar en el dispositivo, si es enlace algún espacio para ccolocarlo 
    `fechaSubida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_archivo_sesion` FOREIGN KEY (`idSesion`) REFERENCES `sesionContenido` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar tablas para las tareas y tabla de archivos de apoyo para la tarea.
CREATE TABLE `tareas` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idCurso` INT NOT NULL,
    `idSesion` INT DEFAULT NULL, -- esto va a permitir vincular la tarea a una sesion si asím lo desea
    `titulo` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(200) NOT NULL,
    `puntajeMaximo` DECIMAL(5,2) DEFAULT 10.00,
    `intentos` INT DEFAULT 1,
    `fechaLimite` DATETIME NOT NULL,
    `fechaCreacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `estado` TINYINT(1) DEFAULT 1,  -- Será para la validacion de NO editar después de q termine la fecha limite
    CONSTRAINT `fk_tarea_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tarea_sesion` FOREIGN KEY (`idSesion`) REFERENCES `sesionContenido` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tareasArchivos` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idTarea` INT NOT NULL,
    `nombreArchivo` VARCHAR(50) NOT NULL,
    `tipo` ENUM('Archivo', 'Enlace') DEFAULT 'Archivo',
    `rutaArchivo` TEXT NOT NULL,
    `fechaSubida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_archivos_tarea_doc` FOREIGN KEY (`idTarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar tabla para los entregables de las tareas
CREATE TABLE `entregablesTarea` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idTarea` INT NOT NULL,
    `idEstudiante` INT NOT NULL,
    `fechaEntrega` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    `estado` ENUM('Pendiente', 'Entregado','Revisado', 'Vencido') DEFAULT 'Pendiente', 
    `conteoIntentos` INT DEFAULT 0,
    `nota` DECIMAL(5,2) DEFAULT NULL,
    `fechaRevision` TIMESTAMP NULL DEFAULT NULL, 
    UNIQUE KEY `unique_estudiante_tarea` (`idTarea`, `idEstudiante`),
    CONSTRAINT `fk_entrega_tarea` FOREIGN KEY (`idTarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_entrega_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entregaArchivos` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idEntrega` INT NOT NULL,
    `nombreArchivo` VARCHAR(50) NOT NULL,
    `tipo` ENUM('Archivo', 'Enlace') DEFAULT 'Archivo',
    `rutaArchivo` TEXT NOT NULL,
    `fechaSubida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_archivos_entrega_est` FOREIGN KEY (`idEntrega`) 
        REFERENCES `entregablesTarea` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para definir los plazos de notas para el registro de notas del docente
CREATE TABLE `PlazoNotas` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `idPeriodo` int NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `plazoInicio` date NOT NULL,
    `plazoFin` date NOT NULL,
    `estado` tinyint(1) DEFAULT '0',
    CONSTRAINT `fk_plazo_periodo` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para registrar las calificaciones de los estudiantes
CREATE TABLE `RegistroNotas` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `idPlazo` int NOT NULL,
    `idCurso` int NOT NULL,
    `idEstudiante` int NOT NULL,
    `actividades` decimal(4,2) NOT NULL DEFAULT 0.00,
    `examenFinal` decimal(4,2) NOT NULL DEFAULT 0.00,
    `notaFinal` decimal(4,2) NOT NULL DEFAULT 0.00,
    `estadoEstudiante` enum('Aprobado','Reprobado') DEFAULT NULL,
    `fechaRegistro` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_nota_estudiante_curso` (`idPlazo`, `idCurso`, `idEstudiante`),
    CONSTRAINT `fk_notas_plazo` FOREIGN KEY (`idPlazo`) REFERENCES `PlazoNotas` (`id`),
    CONSTRAINT `fk_notas_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`),
    CONSTRAINT `fk_notas_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DELIMITER //
-- Desactiva tareas cuando ya llegó a la fecha limite
CREATE EVENT `ev_desactivar_tareas_vencidas`
ON SCHEDULE EVERY 1 HOUR
DO
    UPDATE `tareas`
    SET `estado` = 0
    WHERE `fechaLimite` < NOW()
      AND `estado` = 1
//
-- No permitir entregas si la fecha límite ya venció
CREATE TRIGGER `tr_validar_fecha_entrega`
BEFORE INSERT ON `entregablesTarea`
FOR EACH ROW
BEGIN
    DECLARE v_fecha_limite DATETIME;

    SELECT fechaLimite INTO v_fecha_limite
    FROM `tareas` WHERE id = NEW.idTarea;

    IF NOW() > v_fecha_limite THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No se pueden realizar entregas fuera del plazo permitido';
    END IF;
END //
CREATE TRIGGER `tr_validar_update_entrega`
BEFORE UPDATE ON `entregablesTarea`
FOR EACH ROW
BEGIN
    DECLARE v_fecha_limite DATETIME;
    DECLARE v_puntaje_max DECIMAL(5,2);

    SELECT t.fechaLimite, t.puntajeMaximo 
    INTO v_fecha_limite, v_puntaje_max
    FROM `tareas` t WHERE t.id = NEW.idTarea;

    -- Bloquea reemplazo de entrega si ya venció
    IF NOW() > v_fecha_limite AND NEW.nota IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No se puede editar una entrega fuera del plazo permitido';
    END IF;

    -- Valida que la nota esté dentro del rango permitido
    IF NEW.nota IS NOT NULL THEN
        IF NEW.nota < 0 OR NEW.nota > v_puntaje_max THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error: La nota está fuera del rango permitido para esta tarea';
        END IF;

        -- cuando el docente califica cambia estado a Revisado y registra fecha
        IF OLD.nota IS NULL THEN
            SET NEW.estado = 'Revisado';
            SET NEW.fechaRevision = CURRENT_TIMESTAMP;
        END IF;
    END IF;
END //

-- Verifica de forma estricta el estado administrativo y las fechas vigentes del plazo
DELIMITER //
CREATE PROCEDURE `sp_validar_plazo_notas`(IN p_idPlazo INT)
BEGIN
    DECLARE v_estado_plazo TINYINT(1);
    DECLARE v_inicio DATE;
    DECLARE v_fin DATE;

    SELECT estado, plazoInicio, plazoFin INTO v_estado_plazo, v_inicio, v_fin
    FROM `PlazoNotas` WHERE id = p_idPlazo;

    -- Validar si el plazo está inactivo/cerrado
    IF v_estado_plazo = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El plazo para el registro de notas se encuentra CERRADO.';
    END IF;

    -- Validar que la fecha actual esté dentro del rango permitido
    IF CURDATE() < v_inicio OR CURDATE() > v_fin THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No se pueden procesar notas fuera del rango de fechas establecido para este plazo.';
    END IF;
END //

-- Lógica insertar notas
CREATE TRIGGER `tr_registro_notas_before_insert`
BEFORE INSERT ON `RegistroNotas`
FOR EACH ROW
BEGIN
    -- Ejecuta la validación de fechas y estado del plazo
    CALL sp_validar_plazo_notas(NEW.idPlazo);

    -- Calcula la nota final sumando las ponderaciones provistas
    SET NEW.notaFinal = (NEW.actividades * 0.30) + (NEW.examenFinal * 0.70);

    -- Determina de forma automática el estado académico del alumno
    IF NEW.notaFinal >= 6.00 THEN
        SET NEW.estadoEstudiante = 'Aprobado';
    ELSE
        SET NEW.estadoEstudiante = 'Reprobado';
    END IF;
END //

-- Lógica para actualizaciones
CREATE TRIGGER `tr_registro_notas_before_update`
BEFORE UPDATE ON `RegistroNotas`
FOR EACH ROW
BEGIN
    -- Ejecuta la validación de fechas y estado del plazo institucional
    CALL sp_validar_plazo_notas(NEW.idPlazo);

    -- Recalcula la nota final ante cualquier cambio en actividades o examen
    SET NEW.notaFinal = (NEW.actividades * 0.30) + (NEW.examenFinal * 0.70);
    IF NEW.notaFinal >= 6.00 THEN
        SET NEW.estadoEstudiante = 'Aprobado';
    ELSE
        SET NEW.estadoEstudiante = 'Reprobado';
    END IF;
END //
DELIMITER ;