use db_academiadigital;
-- Crea la tabla periodoInscripcion, donde se habilitará el rango donde podrán inscribirse según el curso y fecha periodo.
CREATE TABLE `PeriodoInscripcion` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `fechaInicio` date NOT NULL,
    `fechaFin` date NOT NULL,
    `fechaInicioCiclo` DATE NOT NULL,
    `fechaFinCiclo` DATE NOT NULL,
    `estado` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cursos` 
ADD COLUMN `idPeriodo` int DEFAULT NULL;

ALTER TABLE `cursos`
ADD CONSTRAINT `fk_curso_periodo_insc` 
FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`);

CREATE TABLE `matricula` (
    `id` int PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` int NOT NULL,
    `idPeriodo` int NOT NULL,
    `monto` decimal(10,2) NOT NULL,
    `estado` enum('Pendiente','Pagado','Mora') DEFAULT 'Pendiente',
    `fechaCreacion` timestamp DEFAULT CURRENT_TIMESTAMP,
    `fechaVencimiento` date NOT NULL,
    `fechaProximaMatricula` DATE NOT NULL,
    UNIQUE KEY `unique_estudiante_periodo` (`idEstudiante`, `idPeriodo`),
    CONSTRAINT `fk_matri_estudiantes` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_matri_periodo` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hu-18 Tabla de inscripciones (después se agrega la fk de factura)
CREATE TABLE `inscripciones` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `idPeriodo` INT NOT NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `estado_academico` ENUM('Activo', 'Finalizado', 'Retirado') DEFAULT 'Activo',
    UNIQUE KEY `unique_estudiante_curso_periodo` (`idEstudiante`, `idCurso`, `idPeriodo`),
    CONSTRAINT `fk_estudiante_inscripcion` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_curso_inscripcion` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`),
    CONSTRAINT `fk_periodo_inscripcion` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mensualidades` (
    `id` int PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` int NOT NULL,
    `idCurso` int NOT NULL,
    `idPeriodo` int NOT NULL,
    `mesPagado` enum('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto',
                     'Septiembre','Octubre','Noviembre','Diciembre') NOT NULL,
    `monto` decimal(10,2) NOT NULL,
    `estado` enum('Pendiente','Pagado','Mora') DEFAULT 'Pendiente',
    `fechaVencimiento` date NOT NULL,
    UNIQUE KEY `unique_mensualidad_estudiante` (`idEstudiante`, `idCurso`, `mesPagado`),
    CONSTRAINT `fk_mens_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_mens_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`),
    CONSTRAINT `fk_mens_periodo` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tablar de métodos de pago con dos datos insertados.
CREATE TABLE `MetodosPago` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `MetodosPago` (`nombre`) VALUES 
('PayPal'), ('Tarjeta de Crédito/Débito');
-- Se enviarán los datos correspondientes del pago
CREATE TABLE `pagos` (
    `id` int PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` int NOT NULL,
    `idMetodoPago` int NOT NULL,
    `monto` decimal(10,2) NOT NULL,
    `idTransaccionPasarela` varchar(100) UNIQUE, 
    `estado` enum('Procesando','Completado','Fallido') DEFAULT 'Procesando',
    `fechaPago` timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pago_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_pago_metodo` FOREIGN KEY (`idMetodoPago`) REFERENCES `MetodosPago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `facturas` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `numeroFactura` VARCHAR(20) UNIQUE NOT NULL,
    `tipoFactura` ENUM('Estudiante','Docente') NOT NULL,
    `idReceptor` INT NOT NULL,
    `tipoReceptor` ENUM('Estudiante','Docente') NOT NULL,
    `idPago` INT DEFAULT NULL,
    `metodoPago` VARCHAR(50) NOT NULL,
    `noReferencia` VARCHAR(100) DEFAULT NULL,
    `observaciones` TEXT DEFAULT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `estado` ENUM('Emitida','Anulada') DEFAULT 'Emitida',
    `fechaEmision` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `generadoPor` INT NOT NULL,
    CONSTRAINT `fk_factura_pago` FOREIGN KEY (`idPago`) 
    REFERENCES `pagos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `detalle_facturas` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idFactura` INT NOT NULL,
    `tipoOrigen` ENUM('Matricula','Inscripcion','Mensualidad','PagoDocente') NOT NULL,
    `idOrigen` INT DEFAULT NULL,
    `descripcion` VARCHAR(200) NOT NULL,
    `cantidad` INT DEFAULT 1,
    `precioUnitario` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_detalle_factura` FOREIGN KEY (`idFactura`) 
    REFERENCES `facturas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Es delimiter es una restriccion en la cual no se permite seleccionar una fecha anterior a la fecha inicio.
DELIMITER //
CREATE TRIGGER `tr_no_traslapar_periodos_insert`
BEFORE INSERT ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    -- Valida que la fecha fin no sea menor a inicio en inscripción
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La fecha de fin de inscripción no puede ser anterior a la de inicio';
    END IF;

    -- Valida que fecha fin no sea menor a inicio en periodo
    IF NEW.fechaFinCiclo < NEW.fechaInicioCiclo THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La fecha de fin de periodo no puede ser anterior a la de inicio del periodo';
    END IF;
    -- crea restricciones para insertar datos donde no se puede seleccionar un rango de algún periodo incripcion creado.
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE (NEW.fechaInicio BETWEEN fechaInicio AND fechaFin)
           OR (NEW.fechaFin BETWEEN fechaInicio AND fechaFin)
           OR (fechaInicio BETWEEN NEW.fechaInicio AND NEW.fechaFin)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: El nuevo periodo de INSCRIPCIÓN choca con fechas existentes';
    END IF;

    -- crea restricciones para insertar datos donde no se puede seleccionar un rango de algún periodo creado.
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE (NEW.fechaInicioCiclo BETWEEN fechaInicioCiclo AND fechaFinCiclo)
           OR (NEW.fechaFinCiclo BETWEEN fechaInicioCiclo AND fechaFinCiclo)
           OR (fechaInicioCiclo BETWEEN NEW.fechaInicioCiclo AND NEW.fechaFinCiclo)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: El nuevo periodo choca con fechas de un periodo existente';
    END IF;
END //

CREATE TRIGGER `tr_no_traslapar_periodos_update`
BEFORE UPDATE ON `PeriodoInscripcion`
FOR EACH ROW
BEGIN
    -- Valida que la fecha fin no sea menor a inicio en inscripción
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La fecha de fin de inscripción no puede ser anterior a la de inicio';
    END IF;

    -- Valida que fecha fin no sea menor a inicio en periodo
    IF NEW.fechaFinCiclo < NEW.fechaInicioCiclo THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La fecha de fin de periodo no puede ser anterior a la de inicio del periodo';
    END IF;

    -- crea restricciones para insertar y editar datos donde no se puede seleccionar un rango de algún periodo incripcion creado.
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE id <> NEW.id
          AND ((NEW.fechaInicio BETWEEN fechaInicio AND fechaFin)
           OR (NEW.fechaFin BETWEEN fechaInicio AND fechaFin)
           OR (fechaInicio BETWEEN NEW.fechaInicio AND NEW.fechaFin))
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La modificación de las fechas de INSCRIPCIÓN choca con otro periodo';
    END IF;
    -- crea restricciones para insertar y editar datos donde no se puede seleccionar un rango de algún periodo creado.
    IF EXISTS (
        SELECT 1 FROM `PeriodoInscripcion`
        WHERE id <> NEW.id
          AND ((NEW.fechaInicioCiclo BETWEEN fechaInicioCiclo AND fechaFinCiclo)
           OR (NEW.fechaFinCiclo BETWEEN fechaInicioCiclo AND fechaFinCiclo)
           OR (fechaInicioCiclo BETWEEN NEW.fechaInicioCiclo AND NEW.fechaFinCiclo))
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La modificación de las fechas de periodo choca con otro periodo existente';
    END IF;
END //
CREATE TRIGGER `tr_vencimiento_matricula_dinamico`
BEFORE INSERT ON `matricula`
FOR EACH ROW
BEGIN
    DECLARE v_fin_ciclo DATE;
    -- El sistema busca el fin de ciclo configurado
    SELECT fechaFinCiclo INTO v_fin_ciclo FROM `PeriodoInscripcion` WHERE id = NEW.idPeriodo;
    
    SET NEW.fechaVencimiento = v_fin_ciclo;
    SET NEW.fechaProximaMatricula = DATE_ADD(v_fin_ciclo, INTERVAL 1 DAY);
END //

CREATE TRIGGER `tr_generar_mensualidades_por_curso`
AFTER INSERT ON `inscripciones`
FOR EACH ROW
BEGIN
    DECLARE v_fecha_inicio DATE;
    DECLARE v_fecha_fin DATE;
    DECLARE v_fecha_pago DATE;
    DECLARE v_costo DECIMAL(10,2);
    DECLARE v_mes_nombre VARCHAR(20);

    -- se obtienen el costo y fechas especificas
    SELECT fechaInicio, fechaFin, costoMensual 
    INTO v_fecha_inicio, v_fecha_fin, v_costo 
    FROM `cursos` 
    WHERE id = NEW.idCurso;

    -- contador para los meses
    SET v_fecha_pago = v_fecha_inicio;
    -- crea las mensualidades por los meses del curso
    WHILE v_fecha_pago <= v_fecha_fin DO
        -- Columna mes de pago
        SET v_mes_nombre = ELT(MONTH(v_fecha_pago), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

        -- se agregan las cuotas mensuales pendientes
        INSERT INTO `mensualidades` 
        (`idEstudiante`, `idCurso`, `idPeriodo`, `mesPagado`, `monto`, `estado`, `fechaVencimiento`)
        VALUES 
        (NEW.idEstudiante, NEW.idCurso, NEW.idPeriodo, v_mes_nombre, v_costo, 'Pendiente', LAST_DAY(v_fecha_pago));

        -- se suma un mes para la siguiente vuelta del bucle
        SET v_fecha_pago = DATE_ADD(v_fecha_pago, INTERVAL 1 MONTH);
    END WHILE;
END //
DELIMITER ;