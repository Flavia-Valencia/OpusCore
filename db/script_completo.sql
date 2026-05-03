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
    `horaInicio` time COLLATE utf8mb4_general_ci NOT NULL, 
    `horaFin` time COLLATE utf8mb4_general_ci NOT NULL, 
    `etiqueta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La tabla 'aulas' contiene la información de las aulas.
CREATE TABLE `aulas` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `aula` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `capacidad` int COLLATE utf8mb4_general_ci NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
    `estado` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La tabla cursos contiene la información de cada curso.
CREATE TABLE `cursos` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL, 
    `descripcion` varchar(150) COLLATE utf8mb4_general_ci NOT NULL, 
    `costoMensual` decimal(8,2) COLLATE utf8mb4_general_ci NOT NULL, 
    `cupos` int COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaInicio` date COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaFin` date COLLATE utf8mb4_general_ci NOT NULL, 
    `estado` tinyint(1) DEFAULT '1', 
    `idDocente` int DEFAULT NULL,
    `idCategoria` INT DEFAULT NULL,
    `idPeriodo` int DEFAULT NULL,
    CONSTRAINT `fk_docente_curso` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`),
    CONSTRAINT `fk_curso_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL,
    CONSTRAINT `fk_curso_periodo_insc`FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
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
    `idFactura` INT DEFAULT NULL, 
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `estado_academico` ENUM('Activo', 'Finalizado', 'Retirado') DEFAULT 'Activo',
    UNIQUE KEY `unique_estudiante_curso_periodo` (`idEstudiante`, `idCurso`, `idPeriodo`),
    CONSTRAINT `fk_estudiante_inscripcion` FOREIGN KEY (`idEstudiante`) REFERENCES `estudiantes` (`id`),
    CONSTRAINT `fk_curso_inscripcion` FOREIGN KEY (`idCurso`) REFERENCES `cursos` (`id`),
    CONSTRAINT `fk_periodo_inscripcion` FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Crea una restriccion donde no se puede seleccionar una feccha fin anterior a la fecha inicio (crear y editar)
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
DELIMITER ;
--
-- Insertar datos en las tablas de usuarios, administradores, estudiantes y docentes.
-- Insertar datos en las tablas de cursos, horarios, prerrequisitos y cursoHorario.
-- 
INSERT INTO `usuarios` (`nombre`, `apellido`, `correo`, `password_hash`, `estado`, `rol_id`) VALUES 
('Sabrina', 'Saravia', 'sabrina@gmail.com', 'SabriAdmin-12', 1, 1),
('Yamileth', 'Valencia', 'yamii@gmail.com', 'YamiEstudiante-19', 1, 2),
('Karla', 'Morales', 'karli@gmail.com', 'KarliDocente_22', 1, 3),
('Daniel', 'García', 'daniel@gmail.com', 'Daniel123', 1, 2),
('Yahir', 'Romero', 'yahir@gmail.com', 'Yahir123', 1, 3); 

INSERT INTO `administradores` (`usuario_id`, `fecha_nacimiento`, `genero`, `salario`, `telefono`, `direccion`) VALUES
(1, '2001-01-01', 'F', 500.00, '1234-5678', 'San Miguel');

INSERT INTO `estudiantes` (`usuario_id`, `fecha_nacimiento`, `genero`, `telefono`, `direccion`) VALUES
(2, '2001-01-01', 'F', '5678-1234', 'Usulután'), (4, '2001-01-01', 'M', '3456-7891', 'Usulután');

INSERT INTO `docentes` (`usuario_id`, `especialidad`, `fecha_nacimiento`, `genero`, `salario`, `telefono`, `direccion`) VALUES
(3, 'Desarrollo de Software', '2001-01-01', 'F', 550.00, '8765-4321', 'Usulután'), (5, 'Base de Datos', '2001-01-01', 'M', 500.00, '9834-6721', 'San Miguel');

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

INSERT INTO `PeriodoInscripcion` (`nombre`, `fechaInicio`, `fechaFin`, `estado`) VALUES 
('Periodo I - 2026', '2026-01-01', '2026-01-31', 1),
('Periodo II - 2026', '2026-06-01', '2026-06-30', 0);

-- Insertar datos en las tablas de cursos, horarios, aulas, prerrequisitos y cursoHorario.
INSERT INTO `cursos`(`nombre`, `descripcion`, `costoMensual`, `cupos`, `fechaInicio`, `fechaFin`, `estado`, `idDocente`, `idCategoria`) VALUES 
('Desarrollo lógica de programación','Curso introductorio enfocado en el desarrollo del pensamiento lógico y resolución de problemas mediante algoritmos.',20.00,100,'2026-01-15','2026-05-15', 1, 1,2),
('Diseño de Páginas Web','Curso orientado a la creación de sitios web utilizando HTML, CSS y principios básicos de diseño web.',20.00,100,'2026-01-15','2026-05-15', 1, 2,1),
('Programación Estructurada','Curso que enseña los fundamentos de la programación utilizando estructuras de control como secuencias, decisiones y ciclos.',20.00,100,'2026-01-15','2026-05-15', 1, 1,2),
('Administración de Sistemas Operativos','Curso enfocado en la gestión, configuración y mantenimiento de sistemas operativos en entornos informáticos.',20.00,100,'2026-01-15','2026-05-15', 1, 2,5),
('Programación Orientada a Objetos','Curso que introduce los conceptos de clases, objetos, herencia y encapsulamiento para desarrollar software modular.',20.00,100,'2026-01-15','2026-05-15', 1, 1,2),
('English for Developers','Curso enfocado en el uso del inglés en entornos tecnológicos, lectura de documentación, escritura técnica y comunicación.',20.00,100,'2026-01-01','2026-05-01',1,1,3),
('Machine Learning I','Curso que enseña los conceptos básicos del aprendizaje automático, modelos supervisados y análisis de datos.',20.00,100,'2026-01-15','2026-05-15', 1, 2, 4);

INSERT INTO `prerrequisitos`(`idCursoActual`, `idCursoPrevio`) VALUES (3,1),(5,3);

INSERT INTO `CursoHorario` (`idCurso`, `dia`, `idHorario`, `idAula`) VALUES 
(1,'Lunes',1,1),
(2,'Lunes',2,1),
(3,'Martes',3,2),
(4,'Miércoles',4,3),
(5,'Jueves',5,4),
(1,'Viernes',1,5);

-- Inserta datos en la tabla 'PeriodoInscripcion'  y 'inscripciones'
INSERT INTO `inscripciones` (`idEstudiante`, `idCurso`, `idPeriodo`) VALUES 
(1, 1, 1),
(2, 1, 1), 
(1, 2, 1);