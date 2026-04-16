-- IMPORTANTE: Antes de ejecutar este script, asegúrate de haber creado la base de dato y haber seleccionado 
-- la base de datos correcta ( se debe ejecutar después del script de academiadigital).


-- IMPORTANTE: Se recomienda ejecutar todo el script por primera vez una sola vez para insertar los datos en las tablas.
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
    `idDocente` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
