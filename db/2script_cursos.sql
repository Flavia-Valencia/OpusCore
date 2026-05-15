-- IMPORTANTE: Antes de ejecutar este script haber ejecutado el script de usuarios.
-- Se recomienda ejecutar todo el script por primera vez una sola vez para insertar los datos en las tablas.
USE db_academiadigital;

CREATE TABLE `horarios` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `horaInicio` time COLLATE utf8mb4_general_ci NOT NULL, 
    `horaFin` time COLLATE utf8mb4_general_ci NOT NULL, 
    `etiqueta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `aulas` ( 
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    `aula` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `capacidad` int COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- tabla categorias y la insertación de algunas.
CREATE TABLE `categorias` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `descripcion` VARCHAR(250),
    `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO categorias(`nombre`, `descripcion`) VALUES 
('Desarrollo web','Creación y mantenimiento de sitios y aplicaciones web. Abarca desde el desarrollo de interfaces (Frontend) hasta la lógica del servidor y gestión de bases de datos (Backend).'),
('Programación','Desarrollo de habilidades para la resolución de problemas mediante algoritmos, estructuras de control y paradigmas de programación.'),
('English Academy','Programas de formación en el idioma inglés enfocados en la comunicación técnica y profesional en entornos globales.'),
('Inteligencia Artificial y Data Science','Estudio de algoritmos y modelos estadísticos orientados al aprendizaje automático y al análisis de datos.'),
('Infraestructura y Sistemas','Gestión, configuración y mantenimiento de sistemas operativos, servidores y redes informáticas.'),
('Diseño de Producto y UX','Diseño de productos digitales con enfoque en usabilidad, accesibilidad y experiencia del usuario.'),
('Contenido Audiovisual','Producción y edición de contenido multimedia, incluyendo video y audio para plataformas digitales.');

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
    CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`idDocente`) REFERENCES `docentes` (`id`),
    CONSTRAINT `fk_curso_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Crea una restriccion donde no se puede seleccionar una feccha fin anterior a la fecha inicio (crear y editar)
DELIMITER //
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