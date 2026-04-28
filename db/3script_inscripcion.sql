use db_academiadigital;

CREATE TABLE `PeriodoInscripcion` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `fechaInicio` date NOT NULL,
    `fechaFin` date NOT NULL,
    `estado` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cursos` 
ADD COLUMN `idPeriodo` int DEFAULT NULL;

ALTER TABLE `cursos`
ADD CONSTRAINT `fk_curso_periodo_insc` 
FOREIGN KEY (`idPeriodo`) REFERENCES `PeriodoInscripcion` (`id`);