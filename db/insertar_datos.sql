-- En este script se encuentran todos los datos que se insertarán por defecto en las tablas de la base de datos.
-- Se debe ejecutar este script después de haber ejecutado el script de academiadigital y el de cursos para 
-- insertar los datos en las tablas correspondientes.
-- Insertar datos en las tablas de usuarios, administradores, estudiantes y docentes.
USE db_academiadigital;

-- En este script se encuentran todos los datos que se insertarán por defecto en las tablas de la base de datos.
-- Se debe ejecutar este script después de haber ejecutado el script de academiadigital y el de cursos para 
-- insertar los datos en las tablas correspondientes.
-- Insertar datos en las tablas de usuarios, administradores, estudiantes y docentes.
USE db_academiadigital;

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

INSERT INTO `PeriodoInscripcion` (`nombre`, `fechaInicio`, `fechaFin`,`fechaInicioCiclo`,`fechaFinCiclo`,`estado`) VALUES 
('Periodo I - 2026', '2026-05-01', '2026-05-15','2026-01-01','2026-06-30', 1),
('Periodo II - 2026', '2026-05-16', '2026-05-30','2026-07-01','2026-12-31', 0);

-- Insertar datos en las tablas de cursos, horarios, aulas, prerrequisitos y cursoHorario.
INSERT INTO `cursos`(`nombre`, `descripcion`, `costoMensual`, `cupos`, `fechaInicio`, `fechaFin`, `estado`, `idDocente`, `idCategoria`, `idPeriodo`) VALUES 
('Desarrollo lógica de programación','Curso introductorio enfocado en el desarrollo del pensamiento lógico y resolución de problemas mediante algoritmos.',20.00,100,'2026-01-15','2026-05-31', 1, 1,2,1),
('Diseño de Páginas Web','Curso orientado a la creación de sitios web utilizando HTML, CSS y principios básicos de diseño web.',20.00,100,'2026-01-15','2026-05-31', 1, 3,1,1),
('Programación Estructurada','Curso que enseña los fundamentos de la programación utilizando estructuras de control como secuencias, decisiones y ciclos.',20.00,100,'2026-01-15','2026-05-31', 1, 1,2,2),
('Administración de Sistemas Operativos','Curso enfocado en la gestión, configuración y mantenimiento de sistemas operativos en entornos informáticos.',20.00,100,'2026-01-15','2026-05-31', 1, 2,5,1),
('Programación Orientada a Objetos','Curso que introduce los conceptos de clases, objetos, herencia y encapsulamiento para desarrollar software modular.',20.00,100,'2026-01-15','2026-05-31', 1, 1,2,2),
('English for Developers','Curso enfocado en el uso del inglés en entornos tecnológicos, lectura de documentación, escritura técnica y comunicación.',20.00,100,'2026-01-01','2026-05-31',1,2,3,1),
('Machine Learning I','Curso que enseña los conceptos básicos del aprendizaje automático, modelos supervisados y análisis de datos.',20.00,100,'2026-01-15','2026-05-31', 1, 2, 4,1),
('Diseño UI/UX Fundamentos','Curso introductorio sobre principios de diseño de interfaces y experiencia de usuario aplicados a productos digitales.',20.00,100,'2026-01-15','2026-05-31', 1, 3, 1, 1),
('Figma para Diseñadores','Curso práctico de diseño de prototipos e interfaces utilizando Figma como herramienta principal.',20.00,100,'2026-01-15','2026-05-31', 1, 3, 1, 1),
('English for Beginners','Curso de inglés básico orientado a quienes inician desde cero, con énfasis en vocabulario y conversación cotidiana.',20.00,100,'2026-01-15','2026-05-31', 1, 2, 3, 1);


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