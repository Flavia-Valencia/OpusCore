CREATE DATABASE `db_academiadigital` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_academiadigital;
--
-- Estructura de tabla para la tabla `administradores`
--
CREATE TABLE `administradores` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Estructura de tabla para la tabla `docentes`
--
CREATE TABLE `docentes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Estructura de tabla para la tabla `estudiantes`
--
CREATE TABLE `estudiantes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Estructura de tabla para la tabla `roles`
--
CREATE TABLE `roles` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Volcado de datos para la tabla `roles`
--
INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(2, 'estudiante'),
(3, 'docente');
--
-- Estructura de tabla para la tabla `usuarios`
--
CREATE TABLE `usuarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `rol_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);
--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);
--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);
--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD UNIQUE KEY `nombre` (`nombre`);
--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`);
--
-- Filtros para la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD CONSTRAINT `administradores_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD CONSTRAINT `docentes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Estructura de tabla para la tabla `horarios`, `aulas`, `cursos`, `prerrequisitos` y `CursoHorario`.
-- 
CREATE TABLE `horarios` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `horaInicio` time COLLATE utf8mb4_general_ci NOT NULL, 
    `horaFin` time COLLATE utf8mb4_general_ci NOT NULL, 
    `etiqueta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `aulas` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `aula` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `capacidad` int COLLATE utf8mb4_general_ci NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cursos` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL, 
    `descripcion` varchar(150) COLLATE utf8mb4_general_ci NOT NULL, 
    `costoMensual` decimal(8,2) COLLATE utf8mb4_general_ci NOT NULL, 
    `cupos` int COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaInicio` date COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaFin` date COLLATE utf8mb4_general_ci NOT NULL, 
    `estado` tinyint(1) DEFAULT '1', 
    `idDocente` int DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`);
  
CREATE TABLE `prerrequisitos` (
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idCursoActual` int NOT NULL,
    `idCursoPrevio` int NOT NULL,
    CONSTRAINT `fk_actual` FOREIGN KEY (`idCursoActual`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_previo` FOREIGN KEY (`idCursoPrevio`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_requisito` (`idCursoActual`, `idCursoPrevio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Datos insertados por defecto en las tablas aulas y horarios para asignar a los cursos.
INSERT INTO `aulas`(`id`, `aula`, `capacidad`) VALUES 
(1,'Aula 01',40),(2,'Aula 02',40),(3,'Aula 03',50),(4,'Aula 04',50),(5,'Aula 05',60),
(6,'Aula 06',60),(7,'Aula 07',70),(8,'Aula 08',70),(9,'Aula 09',80),(10,'Aula 10',80),
(11,'Aula 11',90),(12,'Aula 12',90),(13,'Aula 13',100),(14,'Aula 14',100);

INSERT INTO `horarios`(`horaInicio`, `horaFin`, `etiqueta`) VALUES 
('07:00:00','08:30:00','07:00 a.m. - 08:30 a.m.'),('08:40:00','10:10:00','08:40 a.m. - 10:10 a.m.'),
('10:20:00','11:50:00','10:20 a.m. - 11:50 a.m.'),('13:00:00','14:30:00','01:00 p.m. - 02:30 p.m.'),
('14:40:00','16:10:00','02:40 p.m. - 04:10 p.m.');
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

-- Insertar datos en las tablas de cursos, horarios, aulas, prerrequisitos y cursoHorario.
INSERT INTO `cursos`(`nombre`, `descripcion`, `costoMensual`, `cupos`, `fechaInicio`, `fechaFin`, `estado`, `idDocente`) VALUES 
('Desarrollo lógica de programación','Curso introductorio enfocado en el desarrollo del pensamiento lógico y resolución de problemas mediante algoritmos.',20.00,100,'2026-01-15','2026-05-15', 1, 1),
('Diseño de Páginas Web','Curso orientado a la creación de sitios web utilizando HTML, CSS y principios básicos de diseño web.',20.00,100,'2026-01-15','2026-05-15', 1, 2),
('Programación Estructurada','Curso que enseña los fundamentos de la programación utilizando estructuras de control como secuencias, decisiones y ciclos.',20.00,100,'2026-01-15','2026-05-15', 1, 1),
('Administración de Sistemas Operativos','Curso enfocado en la gestión, configuración y mantenimiento de sistemas operativos en entornos informáticos.',20.00,100,'2026-01-15','2026-05-15', 1, 2),
('Programación Orientada a Objetos','Curso que introduce los conceptos de clases, objetos, herencia y encapsulamiento para desarrollar software modular.',20.00,100,'2026-01-15','2026-05-15', 1, 1);

INSERT INTO `prerrequisitos`(`idCursoActual`, `idCursoPrevio`) VALUES (3,1),(5,3);

INSERT INTO `CursoHorario` (`idCurso`, `dia`, `idHorario`, `idAula`) VALUES 
(1,'Lunes',1,1),
(2,'Lunes',2,1),
(3,'Martes',3,2),
(4,'Miércoles',4,3),
(5,'Jueves',5,4),
(1,'Viernes',1,5);