-- En este script se encuentran todos los datos que se insertarán por defecto en las tablas de la base de datos.
-- Se debe ejecutar este script después de haber ejecutado el script de academiadigital y el de cursos para 
-- insertar los datos en las tablas correspondientes.
-- Insertar datos en las tablas de usuarios, administradores, estudiantes y docentes.
USE db_academiadigital;

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