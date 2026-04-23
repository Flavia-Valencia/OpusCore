<?php                   #esto es para que cuando alguien inice sesion, la direccion de el correo cambie
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

include("includes/conexion.php");
// Carga docentes con su id de la tabla docentes y cuenta sus cursos activos
$query_doc = "SELECT d.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
              COUNT(c.id) AS total_cursos
              FROM docentes d
              INNER JOIN usuarios u ON d.usuario_id = u.id
              LEFT JOIN cursos c ON c.idDocente = d.id AND c.estado = 1
              WHERE u.estado = 1
              GROUP BY d.id";
$res_doc = mysqli_query($conexion, $query_doc);
$docentes = [];
while($doc = mysqli_fetch_assoc($res_doc)) {
    $docentes[] = $doc;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--PARA FUENTES-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>ADF | Administrar Cursos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>
<body class="raleway-all">
   
    <header class="header">
        <div class="logo">
            <img src ="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>
      <input type="checkbox" id="menu-toggle" class="menu-checkbox">
        
     
        <label for="menu-toggle" class="menu-btn">
            <i class="fas fa-bars hamburguesa"></i>
            <i class="fas fa-times cerrar"></i>
        </label>

        <label for="menu-toggle" class="menu-overlay"></label>

        <nav class="nav">
            <!--Funciona para nombre en celu -->
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>
            <!-------------->

            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav active">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>
        
            <!--Boton para cerrar sesión en celu-->
            <a href="includes/logout.php" class="btn-salir">Cerrar sesión</a>
            <!-------------->

            <a href="includes/logout.php" style="text-decoration:none;">
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-role">Admin</span>
                        <span class="user-email"><?php echo $_SESSION["usuario"]; ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </div>
            </a>
        </nav>
    </header>

    <main class="main">
        <div class="page-header">
            <h1 class="titulo">ADMINISTRAR CURSOS</h1>
            <button class="btn-nuevo">+ Nuevo Curso</button>
        </div>

        <!--Mensaje de validación del curso-->
        <?php if(isset($_GET['error'])): ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'existe'): ?>
                <div class="toast-error">
                    El curso ya existe. Intenta con otro nombre.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'limite_docente'): ?>
                <div class="toast-error">
                    El docente ya tiene 4 cursos activos asignados.
                </div>
            <?php endif; ?>

        <?php endif; ?>
        
        <div class="card">
            <div class="toolbar">
                <input type="text" id="buscador-curso" placeholder="🔎 Buscar un curso" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-cursos.php'); ?>
            </div>
        </div>
    </main>

    <!-- MODAL NUEVO CURSO -->
<div id="modalNuevoCurso" class="modal-overlay">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="cerrarModalNuevoCurso()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-book"></i> Nuevo Curso
        </h2>

        <form method="POST" action="crear-curso.php">

            <h3 class="modal-subtitulo">Detalles del curso</h3>
            <div class="modal-grid">

                <div class="modal-campo full-width">
                    <label>Nombre del curso</label>
                    <input type="text" name="nombre" required>
                </div>
                
                <div class="modal-campo full-width">
                    <label>Docente asignado</label>
                    <!-- Select cargado desde BD, deshabilita docentes que ya tienen 4 cursos activos -->
                    <select name="idDocente" required>
                        <option value="">Seleccione un docente</option>
                        <?php foreach($docentes as $doc): 
                            $lleno = $doc['total_cursos'] >= 4; ?>
                            <option value="<?php echo $doc['id']; ?>" <?php echo $lleno ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($doc['nombre_completo']); ?>
                                <?php echo $lleno ? '(máx. cursos)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-campo full-width">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Descripción del curso">
                </div>

                <!-- Select para elegir prerrequisitos Carga cursos activos desde la BD y envía varios IDs al backend (luego lo arreglan)) --> 

                <div class="modal-campo" style="grid-column: span 2;">
                    <label>Prerrequisitos (opcional)</label>
                    <select name="idPrerrequisitos"  id="nuevo-prerrequisitos">
                        <?php
                        $query = "SELECT id, nombre FROM cursos WHERE estado = 1";
                        $result = mysqli_query($conexion, $query);
                        while($curso = mysqli_fetch_assoc($result)) { ?>
                            <option value="<?php echo $curso['id']; ?>">
                                <?php echo htmlspecialchars($curso['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="modal-campo">
                    <label>Costo Mensual ($)</label>
                    <input type="number" step="0.25" name="costoMensual" required>
                </div>

                 <div class="modal-campo">
                    <label>Cupos</label>
                    <input type="number" name="cupos" required>
                </div>                

                <div class="modal-campo">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fechaInicio" required>
                </div>

                <div class="modal-campo">
                    <label>Fecha Fin</label>
                    <input type="date" name="fechaFin" required>
                </div>

             </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModalNuevoCurso()">Cancelar</button>
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL EDITAR CURSO -->
<div id="modalEditarCurso" class="modal-overlay">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="cerrarModalCurso()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-edit"></i> Editar Curso
        </h2>

        <form method="POST" action="editar-curso.php">

            <!-- ID oculto -->
            <input type="hidden" name="id" id="edit-id-curso">

            <h3 class="modal-subtitulo">Detalles del curso</h3>

            <div class="modal-grid">

                <div class="modal-campo full-width">
                    <label>Nombre del curso</label>
                    <input type="text" name="nombre" id="edit-nombre-curso" required>
                </div>

                <div class="modal-campo full-width">
                    <label>Docente asignado</label>
                    <!-- Reutiliza el arreglo $docentes cargado arriba, deshabilita docentes con 4 cursos activos -->
                    <select name="idDocente" id="edit-docente-curso" required>
                        <option value="">Seleccione un docente</option>
                        <?php foreach($docentes as $doc): 
                            $lleno = $doc['total_cursos'] >= 4; ?>
                            <option value="<?php echo $doc['id']; ?>" <?php echo $lleno ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($doc['nombre_completo']); ?>
                                <?php echo $lleno ? '(máx. cursos)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-campo full-width" style="grid-column: span 2;">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" id="edit-descripcion-curso" placeholder="Descripción del curso">
                </div>

                <div class="modal-campo" style="grid-column: span 2;">
                    <label>Prerrequisitos (opcional)</label>
                    <select name="idPrerrequisitos" id="edit-prerrequisitos">
                        <option value =""> Seleccione un prerrequisito</option>
                        <?php
                        $query = "SELECT id, nombre FROM cursos WHERE estado = 1";
                        $result = mysqli_query($conexion, $query);
                        while($curso = mysqli_fetch_assoc($result)) { ?>
                            <option value="<?php echo $curso['id']; ?>">
                                <?php echo htmlspecialchars($curso['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="modal-campo">
                    <label>Costo Mensual ($)</label>
                    <input type="number" step="0.25" name="costoMensual" id="edit-costo-mensual" required>
                </div>

                 <div class="modal-campo">
                    <label>Cupos</label>
                    <input type="number" name="cupos" id="edit-cupos" required>
                </div>     

                <div class="modal-campo">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fechaInicio" id="edit-fecha-inicio" required>
                </div>

                <div class="modal-campo">
                    <label>Fecha Fin</label>
                    <input type="date" name="fechaFin" id="edit-fecha-fin" required>
                </div>                

                <div class="modal-campo" style="display: none;"><label>Estado</label>
                    <select name="estado" id="editd-estado-curso">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModalCurso()">Cancelar</button>
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL PARA HORARIOS CURSO -->
<div id="modalHorarios" class="modal-overlay">
    <div class="modal-contenido modal-horarios-premium">
        <button class="modal-cerrar" onclick="cerrarModalHorarios()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-book"></i> Horario
        </h2>

       <h3 class="modal-subtitulo">Configuracion de bloque</h3>

        <div id="bloques-horario-container">
            <!-- Los bloques de horario se cargarán aquí dinámicamente -->
        </div>

            <!-- Template para nuevos bloques de horario (oculto) -->
            <template id="template-horario-card">
                <div class="horario-card-registro" style="margin-top: 15px;">
                    <button type="button" class="horario-card-cerrar"><i class="fas fa-times"></i></button>
                    
                    <div class="horario-campo">
                        <label><i class="fas fa-calendar-day"></i> DÍAS</label>
                        <div class="dias-selector">
                            <button type="button" class="dia-tag" data-dia="Lunes">Lunes</button>
                            <button type="button" class="dia-tag" data-dia="Martes">Martes</button>
                            <button type="button" class="dia-tag" data-dia="Miércoles">Miércoles</button>
                            <button type="button" class="dia-tag" data-dia="Jueves">Jueves</button>
                            <button type="button" class="dia-tag" data-dia="Viernes">Viernes</button>
                            <button type="button" class="dia-tag" data-dia="Sábado">Sábado</button>
                            <button type="button" class="dia-tag" data-dia="Domingo">Domingo</button>
                        </div>
                    </div>
                    <div class="horario-grid">
                        <div class="horario-campo">
                            <label>HORARIO ESTABLECIDO</label>
                            <div class="select-wrapper">
                                <i class="fas fa-clock icon-input"></i>
                                <select class="premium-select horario-select">
                                    <option value="">Seleccione un rango</option>
                                    <option value="08:50 - 10:40">08:50 - 10:40 (Ejemplo)</option>
                                </select>
                            </div>
                        </div>
                        <div class="horario-campo">
                            <label>AULA</label>
                            <div class="select-wrapper">
                                <i class="fas fa-door-open icon-input"></i>
                                <select class="premium-select aula-select">
                                    <option value="">Seleccione salón</option>
                                    <option value="Aula 101">Aula 101 (Ejemplo)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <button class="btn-agregar-horario">
                <i class="fas fa-plus"></i> AGREGAR HORARIO
            </button>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModalHorarios()">Cancelar</button>
                <button type="button" class="btn-guardar-premium" id="btn-guardar-horarios">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>

    </div>

    <!-- Librería SweetAlert2 para mostrar alertas personalizadas en la interfaz -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="js/script.js"></script>

</body>
</html>