<?php                   #esto es para que cuando alguien inice sesion, la direccion de el correo cambie
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
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

                <div class="modal-campo">
                    <label>Nombre del curso</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="modal-campo full-width">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Descripción del curso">
                </div>

                <!-- Select múltiple para elegir prerrequisitos Carga cursos activos desde la BD y envía varios IDs al backend --> 

                <div class="modal-campo" style="grid-column: span 2;">
                    <label>Prerrequisitos (opcional)</label>
                    <select name="prerrequisitos[]" multiple class="select-prerrequisitos">
                        <?php
                        include("includes/conexion.php");
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

                <input type="hidden" name="idDocente" value="6"> <!-- aquí va a ir el ID del docente asignado a la materia/curso -->

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

                <div class="modal-campo">
                    <label>Nombre del curso</label>
                    <input type="text" name="nombre" id="edit-nombre-curso">
                </div>

                 <div class="modal-campo">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fechaInicio" id="edit-fecha-inicio" required>
                </div>

                <div class="modal-campo">
                    <label>Fecha Fin</label>
                    <input type="date" name="fechaFin" id="edit-fecha-fin" required>
                </div>

                <div class="modal-campo" style="grid-column: span 2;">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" id="edit-descripcion-curso">
                </div>

                <div class="modal-campo">
                    <label>Costo mensual($)</label>
                    <input type="number" step="0.25" name="costoMensual" id="edit-costo-mensual">
                </div>

                <div class="modal-campo"><label>Estado</label>
                        <select name="estado" id="editd-estado">
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

<!-- MODAL HORARIOS CURSO -->
<div id="modalHorarios" class="modal-overlay">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="cerrarModalHorarios()">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="modal-titulo">
            <i class="fas fa-clock"></i> Horarios del Curso
        </h2>

        <div id="horarios-content">
            <!-- Aquí se abriba el modal y se cargarán los horarios del curso mediante JavaScript pero aun no se agregan las funciones-->
        </div>

    </div>

<script src="js/script.js"></script>
</body>
</html>
