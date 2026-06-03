CREATE DATABASE `db_academiadigital` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_academiadigital;

-- Estructura de tabla para la tabla `roles`
CREATE TABLE `roles` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `roles` existiendo solo 3 roles: admin, estudiante y docente.
INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(2, 'estudiante'),
(3, 'docente');

-- Estructura de tabla para la tabla `usuarios`
CREATE TABLE `usuarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `rol_id` int NOT NULL,
  UNIQUE KEY `correo` (`correo`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `fk_usuarios_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `administradores`
CREATE TABLE `administradores` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_administradores_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `docentes`
CREATE TABLE `docentes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_docentes_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `estudiantes`
CREATE TABLE `estudiantes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_estudiantes_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `horarios`, `aulas`, `cursos`, `prerrequisitos` y `CursoHorario`.
-- La tabla 'horarios' contiene la información de los horarios disponibles para los cursos.
CREATE TABLE `horarios` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `horaInicio` time NOT NULL, 
    `horaFin` time NOT NULL, 
    `etiqueta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La tabla 'aulas' contiene la información de las aulas.
CREATE TABLE `aulas` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `aula` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `capacidad` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categorias` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `descripcion` VARCHAR(250),
    `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `PeriodoInscripcion`, la fecha disponible para inscribirse a los cursos.
CREATE TABLE `PeriodoInscripcion` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `fechaInicio` date NOT NULL,
    `fechaFin` date NOT NULL,
    `fechaInicioCiclo` DATE NOT NULL,
    `fechaFinCiclo` DATE NOT NULL,
    `estado` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla métodos de pago
CREATE TABLE `MetodosPago` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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


-- La tabla cursos contiene la información de cada curso.
CREATE TABLE `cursos` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL, 
    `descripcion` varchar(150) COLLATE utf8mb4_general_ci NOT NULL, 
    `costoMensual` decimal(8,2) NOT NULL, 
    `cupos` int NOT NULL, 
    `fechaInicio` date NOT NULL, 
    `fechaFin` date NOT NULL, 
    `estado` tinyint(1) DEFAULT '1', 
    `idDocente` int DEFAULT NULL,
    `idCategoria` INT DEFAULT NULL,
    `idPeriodo` int DEFAULT NULL,
    CONSTRAINT `fk_docente_curso` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`),
    CONSTRAINT `fk_curso_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL,
    CONSTRAINT `fk_curso_periodo_insc` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
-- La tabla 'prerrequisitos' estable una relación entre un curso actual y un curso previo.
CREATE TABLE `prerrequisitos` (
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idCursoActual` int NOT NULL,
    `idCursoPrevio` int NOT NULL,
    CONSTRAINT `fk_actual` FOREIGN KEY (`idCursoActual`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_previo` FOREIGN KEY (`idCursoPrevio`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_requisito` (`idCursoActual`, `idCursoPrevio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- La tabla 'CursoHorario' une aulas, horarios y cursos, permitiendo asignar horarios y aulas a cada curso.
CREATE TABLE `CursoHorario` (
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idCurso` int NOT NULL,
    `dia` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
    `idHorario` int NOT NULL,
    `idAula` int NOT NULL,
    CONSTRAINT `fk_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_horario` FOREIGN KEY (`idHorario`) REFERENCES `horarios` (`id`),
    CONSTRAINT `fk_aula` FOREIGN KEY (`idAula`) REFERENCES `aulas` (`id`)
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

-- Tabla de facturas y detalle de facturas para registrar las transacciones de pagos, matrículas e inscripciones.
-- Se deberá automatizar solo si el pago fue aprobado en estduaintes
CREATE TABLE `facturas` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `numeroFactura` VARCHAR(20) UNIQUE NOT NULL,
    `tipoFactura` ENUM('Estudiante','Docente') NOT NULL,
    `idReceptor` INT NOT NULL,
    `tipoReceptor` ENUM('Estudiante','Docente') NOT NULL,
    `idPago` INT DEFAULT NULL,
    `metodoPago` VARCHAR(50) DEFAULT NULL,
    `noReferencia` VARCHAR(100) DEFAULT NULL,
    `observaciones` TEXT DEFAULT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `estado` ENUM('Emitida','Anulada') DEFAULT 'Emitida',
    `fechaEmision` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `generadoPor` INT NULL,
    CONSTRAINT `fk_factura_pago` FOREIGN KEY (`idPago`) REFERENCES `pagos` (`id`),
    CONSTRAINT `fk_factura_usuario` FOREIGN KEY (`generadoPor`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registra la cantidad de cada concepto, ya sea para el docente o estudiante, colocando cantidad, precio unitario 
-- y subtotal para cada concepto registrado en la factura.
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
-- se guardará el historial de constancias generadas para estudiantes y docentes, con un código único para cada constancia, 
-- el tipo de constancia, el usuario solicitante, el usuario que generó la constancia, la ruta del PDF generado y la fecha de generación.
CREATE TABLE `constancias` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `codigoConstancia` VARCHAR(50) NOT NULL UNIQUE,
    `tipo` ENUM('Estudiante', 'Docente') NOT NULL,
    `idUsuarioSolicitante` INT NOT NULL,
    `idGeneradoPor` INT NOT NULL,
    `rutaPDF` VARCHAR(255) NOT NULL,
    `fechaGeneracion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_constancias_solicitante` FOREIGN KEY (`idUsuarioSolicitante`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_constancias_generador` FOREIGN KEY (`idGeneradoPor`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Tabla para registrar las solicitudes de constancias por parte de los estudiantes.
CREATE TABLE `solicitudConstanciaEstudiante` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `idEstudiante` INT NOT NULL,
    `idCurso` INT NOT NULL,
    `motivo` VARCHAR(255) DEFAULT 'Trámite personal', -- es el motivo, ya que no hay seleccion se dejará default, pero en un futuro podría cambiar
    `estado` ENUM('Pendiente', 'Aprobada', 'Rechazada') DEFAULT 'Pendiente',
    `fechaSolicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sol_est_estudiante` FOREIGN KEY (`idEstudiante`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
    CONSTRAINT `fk_sol_doc_docente` FOREIGN KEY (`idDocente`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sol_doc_curso` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DELIMITER //

-- Triger para validar y evitar traslapes al insertar un nuevo periodo
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

-- Crea una restriccion donde no se puede seleccionar una fecha fin anterior a la fecha inicio (crear y editar)
CREATE TRIGGER `tr_validar_fechas_insert`
BEFORE INSERT ON `cursos`
FOR EACH ROW
BEGIN
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: La fecha de fin no puede ser anterior a la de inicio';
    END IF;
END //
CREATE TRIGGER `tr_validar_fechas_update`
BEFORE UPDATE ON `cursos`
FOR EACH ROW
BEGIN
    IF NEW.fechaFin < NEW.fechaInicio THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: La fecha de fin no puede ser anterior a la de inicio';
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
-- trigger para validar que no se puedan generar constancias si el estado de la solicitud no es aprobada
CREATE TRIGGER `tr_validar_constancia_estudiante_insert`
BEFORE INSERT ON `solicitudConstanciaEstudiante`
FOR EACH ROW
BEGIN
    DECLARE v_estado_curso VARCHAR(50);

    SELECT estado INTO v_estado_curso
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
--
-- Insertar datos en las tablas de usuarios, administradores, estudiantes y docentes.
-- Insertar datos en las tablas de cursos, horarios, prerrequisitos y cursoHorario.
-- 
INSERT INTO `usuarios` (`nombre`, `apellido`, `correo`, `password_hash`, `estado`, `rol_id`) VALUES 
('Sabrina', 'Saravia', 'sabrina@gmail.com', 'SabriAdmin-12', 1, 1),
('Yamileth', 'Valencia', 'yamiiacademia3@gmail.com', 'YamiEstudiante-19', 1, 2),
('Karla', 'Morales', 'karladocente19@gmail.com', 'KarliDocente_22', 1, 3),
('Daniel', 'García', 'daniel@gmail.com', 'Daniel123', 1, 2),
('Yahir', 'Romero', 'yahir@gmail.com', 'Yahir123', 1, 3),
('Keyri', 'Sanchez', 'keyri@gmail.com', 'keyri123', 1, 3); 

INSERT INTO `administradores` (`usuario_id`, `fecha_nacimiento`, `genero`, `salario`, `telefono`, `direccion`) VALUES
(1, '2001-01-01', 'F', 500.00, '1234-5678', 'San Miguel');

INSERT INTO `estudiantes` (`usuario_id`, `fecha_nacimiento`, `genero`, `telefono`, `direccion`) VALUES
(2, '2001-01-01', 'F', '5678-1234', 'Usulután'), (4, '2001-01-01', 'M', '3456-7891', 'Usulután');

INSERT INTO `docentes` (`usuario_id`, `especialidad`, `fecha_nacimiento`, `genero`, `salario`, `telefono`, `direccion`) VALUES
(3, 'Programación', '2001-01-01', 'F', 550.00, '8765-4321', 'Usulután'), (5, 'Inglés', '2001-01-01', 'M', 500.00, '9834-6721', 'San Miguel'),
(6, 'Diseño UI / UX', '2001-01-01', 'F', 500.00, '7634-8732', 'Usulután');

-- Datos insertados por defecto en las tablas aulas y horarios para asignar a los cursos.
-- las tablas aulas y horarios se mantienen por defecto.
INSERT INTO `aulas`(`id`, `aula`, `capacidad`) VALUES 
(1,'Aula 01',40),(2,'Aula 02',40),(3,'Aula 03',50),(4,'Aula 04',50),(5,'Aula 05',60),
(6,'Aula 06',60),(7,'Aula 07',70),(8,'Aula 08',70),(9,'Aula 09',80),(10,'Aula 10',80),
(11,'Aula 11',90),(12,'Aula 12',90),(13,'Aula 13',100),(14,'Aula 14',100);

INSERT INTO `horarios`(`horaInicio`, `horaFin`, `etiqueta`) VALUES 
('07:00:00','08:30:00','07:00 a.m. - 08:30 a.m.'),('08:40:00','10:10:00','08:40 a.m. - 10:10 a.m.'),
('10:20:00','11:50:00','10:20 a.m. - 11:50 a.m.'),('13:00:00','14:30:00','01:00 p.m. - 02:30 p.m.'),
('14:40:00','16:10:00','02:40 p.m. - 04:10 p.m.');

INSERT INTO categorias(`nombre`, `descripcion`) VALUES 
('Desarrollo web','Creación y mantenimiento de sitios y aplicaciones web. Abarca desde el desarrollo de interfaces (Frontend) hasta la lógica del servidor y gestión de bases de datos (Backend).'),
('Programación','Desarrollo de habilidades para la resolución de problemas mediante algoritmos, estructuras de control y paradigmas de programación.'),
('English Academy','Programas de formación en el idioma inglés enfocados en la comunicación técnica y profesional en entornos globales.'),
('Inteligencia Artificial y Data Science','Estudio de algoritmos y modelos estadísticos orientados al aprendizaje automático y al análisis de datos.'),
('Infraestructura y Sistemas','Gestión, configuración y mantenimiento de sistemas operativos, servidores y redes informáticas.');

INSERT INTO `PeriodoInscripcion` (`nombre`, `fechaInicio`, `fechaFin`,`fechaInicioCiclo`,`fechaFinCiclo`,`estado`) VALUES 
('Periodo I - 2026', '2026-05-01', '2026-05-31','2026-01-01','2026-06-30', 1),
('Periodo II - 2026', '2026-07-01', '2026-07-31','2026-07-01','2026-12-31', 0);

-- Insertar datos en las tablas de cursos, horarios, aulas, prerrequisitos y cursoHorario.
-- Nota: Asegurarse de que idDocente coincida con los ids existentes en la tabla docentes (1 o 2 en una base limpia)
INSERT INTO `cursos`(`nombre`, `descripcion`, `costoMensual`, `cupos`, `fechaInicio`, `fechaFin`, `estado`, `idDocente`, `idCategoria`, `idPeriodo`) VALUES 
('Desarrollo lógica de programación','Curso introductorio enfocado en el desarrollo del pensamiento lógico y resolución de problemas mediante algoritmos.',20.00,100,'2026-01-15','2026-05-31', 1, 1,2,1),
('Diseño de Páginas Web','Curso orientado a la creación de sitios web utilizando HTML, CSS y principios básicos de diseño web.',20.00,100,'2026-01-15','2026-05-31', 1, 1,1,1),
('Programación Estructurada','Curso que enseña los fundamentos de la programación utilizando estructuras de control como secuencias, decisiones y ciclos.',20.00,100,'2026-07-15','2026-12-15', 1, 1,2,2),
('Administración de Sistemas Operativos','Curso enfocado en la gestión, configuración y mantenimiento de sistemas operativos en entornos informáticos.',20.00,100,'2026-01-15','2026-06-15', 1, 1,5,1),
('Programación Orientada a Objetos','Curso que introduce los conceptos de clases, objetos, herencia y encapsulamiento para desarrollar software modular.',20.00,100,'2026-07-15','2026-12-15', 1, 3,2,2),
('English for Developers','Curso enfocado en el uso del inglés en entornos tecnológicos, lectura de documentación, escritura técnica y comunicación.',20.00,100,'2026-01-01','2026-06-15',1,2,3,1),
('Machine Learning I','Curso que enseña los conceptos básicos del aprendizaje automático, modelos supervisados y análisis de datos.',20.00,100,'2026-01-15','2026-06-15', 1, 2, 4,1),
('Diseño UI/UX Fundamentos','Curso introductorio sobre principios de diseño de interfaces y experiencia de usuario aplicados a productos digitales.',20.00,100,'2026-01-15','2026-06-15', 1, 3, 1, 1),
('Figma para Diseñadores','Curso práctico de diseño de prototipos e interfaces utilizando Figma como herramienta principal.',20.00,100,'2026-01-15','2026-06-15', 1, 3, 1, 1),
('English for Beginners','Curso de inglés básico orientado a quienes inician desde cero, con énfasis en vocabulario y conversación cotidiana.',20.00,100,'2026-01-15','2026-06-15', 1, 2, 3, 1);

INSERT INTO `prerrequisitos`(`idCursoActual`, `idCursoPrevio`) VALUES (3,1),(5,3);

INSERT INTO `CursoHorario` (`idCurso`, `dia`, `idHorario`, `idAula`) VALUES 
(1,'Lunes',1,1),
(2,'Lunes',2,1),
(3,'Martes',3,2),
(4,'Miércoles',4,3),
(5,'Jueves',5,4),
(1,'Viernes',1,5);
-- Inserta dstos en la tablaMetodosPago
INSERT INTO `MetodosPago` (`nombre`) VALUES 
('PayPal'), ('Tarjeta de Crédito/Débito');

-- Insetar valores en las tablas de sesiones y archivos de sesiones.
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
