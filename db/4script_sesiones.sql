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

INSERT INTO `sesionContenido` (`idCurso`, `titulo`, `descripcion`, `fecha`, `estado`) VALUES
(1, 'Clase I: Introducción a la Programación', 'Sesión introductoria para conocer los conceptos básicos de programación.', '2026-05-19', 1),
(1, 'Clase II: Estructuras de Control', 'Aprenderemos sobre condicionales y bucles en programación.', '2026-05-21', 1),
(2, 'Clase I: Fundamentos de Diseño Gráfico', 'Exploraremos los principios básicos del diseño gráfico y su aplicación.', '2026-05-19', 1),
(2, 'Clase II: Herramientas de Diseño', 'Conoceremos las principales herramientas utilizadas en diseño gráfico.', '2026-05-21', 1);

INSERT INTO `sesionArchivos` (`idSesion`, `nombreArchivo`, `rutaArchivo`, `tipo`) VALUES
(1, 'Introducción a la Programación.pdf', 'editarurl', 'Archivo'),
(1, 'Video de Introducción', 'https://youtu.be/rDynuZstCwU?si=SjoR8Y7QBGY32RIj', 'Enlace'),
(2, 'Fundamentos de Diseño Gráfico.pdf', 'editarurl', 'Archivo'),
(2, 'Video de Fundamentos de Diseño', 'https://youtu.be/7N2v0bpNFKA?si=I6VwB2sOqINrPdkM', 'Enlace');

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
-- Insertar datos para las sesiones ya creadas y sus archivos de apoyo en la tarea
INSERT INTO `tareas` (`idCurso`, `idSesion`, `titulo`, `descripcion`, `puntajeMaximo`, `fechaLimite`) VALUES
(1, 1, 'Tarea 1: Algoritmos Básicos', 'Desarrolla algoritmos para resolver problemas simples utilizando pseudocódigo.', 10, '2026-05-30 23:59:59'),
(1, 2, 'Tarea 2: Estructuras de Control', 'Crea programas que utilicen condicionales y bucles para resolver problemas específicos.', 10, '2026-05-30 23:59:59'),
(2, 3, 'Tarea 1: Diseño de Logotipo', 'Diseña un logotipo para una empresa ficticia utilizando los principios de diseño gráfico.', 10, '2026-05-30 23:59:59'),
(2, 4, 'Tarea 2: Prototipo de Página Web', 'Crea un prototipo de página web utilizando herramientas de diseño como Figma o Adobe XD.', 10, '2026-05-30 23:59:59');

INSERT INTO `tareasArchivos` (`idTarea`, `nombreArchivo`, `tipo`, `rutaArchivo`) VALUES
(1, 'Ejemplo de Algoritmo.pdf', 'Archivo', 'editarurl'),
(2, 'Ejemplo de Estructuras de Control.pdf', 'Archivo', 'editarurl'),
(3, 'Ejemplo de Logotipo.pdf', 'Archivo', 'editarurl'),
(4, 'Ejemplo de Prototipo Web.pdf', 'Archivo', 'editarurl');

-- Insertar tabla para los entregables de las tareas
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

-- insertar datos en entrgas del estudiante
INSERT INTO `entregablesTarea` (`idTarea`, `idEstudiante`, `estado`, `nota`, `fechaRevision`) VALUES
(1, 1, 'Revisado', 9.00, '2026-05-25 10:00:00'),
(2, 1, 'Entregado', NULL, NULL),
(3, 1, 'Revisado', 8.50, '2026-05-25 11:00:00'),
(4, 1, 'Entregado', NULL, NULL),
(1, 2, 'Pendiente', NULL, NULL),
(2, 2, 'Pendiente', NULL, NULL);

INSERT INTO `entregaArchivos` (`idEntrega`, `nombreArchivo`, `tipo`, `rutaArchivo`) VALUES
(1, 'Algoritmos-Yamileth.pdf', 'Archivo', 'editarurl'),
(2, 'EstructurasControl-Yamileth.pdf', 'Archivo', 'editarurl'),
(3, 'Logotipo-Yamileth.pdf', 'Archivo', 'editarurl'),
(4, 'Prototipo-Yamileth.pdf', 'Archivo', 'editarurl');


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

-- Insertar datos en la tabla de plazos para el registro de notas del docente
INSERT INTO `plazoNotas`(`idPeriodo`, `nombre`, `plazoInicio`, `plazoFin`, `estado`) VALUES 
(1,'Plazo Notas I-2026','2026-05-28','2026-06-08',1),
(2,'Plazo Notas II-2026','2026-06-28','2026-07-08',0);
-- NADA MÁS DE PRUEBA. insertar datos en la tabla de registro de notas para validar el disparador y diseño.
INSERT INTO `registroNotas`(`idPlazo`, `idCurso`, `idEstudiante`, `actividades`, `examenFinal`) VALUES 
(1,2,1,7,10),(1,2,2,3,5),(1,1,1,9,9),(1,1,2,8,7);
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

-- Evita editar la tarea si ya llegó al tiempo límite
CREATE TRIGGER `tr_validar_edicion_tarea`
BEFORE UPDATE ON `tareas`
FOR EACH ROW
BEGIN
    IF OLD.fechaLimite < NOW() AND OLD.estado = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: No se puede editar una tarea cuya fecha límite ya venció';
    END IF;
END //

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