CREATE TABLE `cursos` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL, 
    `descripcion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL, 
    `costoMensual` decimal(8,2) COLLATE utf8mb4_general_ci NOT NULL, 
    `cupos` int COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaInicio` date COLLATE utf8mb4_general_ci NOT NULL, 
    `fechaFin` date COLLATE utf8mb4_general_ci NOT NULL, 
    `estado` tinyint(1) DEFAULT '1', 
    `idDocente` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`);
  
CREATE TABLE `prerrequisitos` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idCursoActual` int NOT NULL,
    `idCursoPrevio` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `prerrequisitos`
  ADD CONSTRAINT `prerrequisitos_ibfk_1` FOREIGN KEY (`idCursoActual`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `prerrequisitos_ibfk_2` FOREIGN KEY (`idCursoPrevio`) REFERENCES `cursos` (`id`);
  
CREATE TABLE `horarios` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `horaInicio` time COLLATE utf8mb4_general_ci NOT NULL, 
    `horaFin` time COLLATE utf8mb4_general_ci NOT NULL, 
    `etiqueta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `aulas` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `aula` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `capacidad` int COLLATE utf8mb4_general_ci NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `materiaHorario` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idMateria` int NOT NULL,
    `dia` enum('Lunes','Martes','Miércoles','Juves','Viernes','Sábado','Domingo') COLLATE utf8mb4_general_ci NOT NULL,
    `idHorario` int NOT NULL,
    `idAula` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `materiaHorario`
  ADD CONSTRAINT `materiaHorario_ibfk_1` FOREIGN KEY (`idMateria`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `materiaHorario_ibfk_2` FOREIGN KEY (`idHorario`) REFERENCES `horarios` (`id`),
  ADD CONSTRAINT `materiaHorario_ibfk_3` FOREIGN KEY (`idAula`) REFERENCES `aulas` (`id`);