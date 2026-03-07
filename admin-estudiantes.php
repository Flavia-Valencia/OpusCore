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

    <title>ADF | Administrar Estudiantes</title>
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
            <!--Funciona para nombre el celu -->
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>
            <!-------------->

            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-estudiantes.php" class="btn-nav active">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
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
            <h1 class="titulo">ADMINISTRAR ESTUDIANTES</h1>
            <button class="btn-nuevo">+ Nuevo Estudiante</button>
        </div>

        <div class="card">
            <div class="toolbar">
                <input type="text" placeholder="🔎 Buscar un estudiante" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-estudiantes.php'); ?>

            </div>
        </div>
    </main>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
             <h2 class="modal-titulo"><i class="fas fa-user-edit"></i> Editar Estudiante</h2>

            <form method="POST" action="includes/editar-estudiante.php">
                <input type="hidden" name="id" id="edit-id">

                <h3 class="modal-subtitulo">Detalles del estudiante</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" id="edit-nombre"></div>
                    <div class="modal-campo"><label>Contacto</label><input type="text" name="contacto" id="edit-contacto"></div>
                    <div class="modal-campo"><label>Curso</label><input type="text" name="curso" id="edit-curso"></div>
                    <div class="modal-campo"><label>Estado</label><input type="text" name="estado" id="edit-estado"></div>
                </div>

                <h3 class="modal-subtitulo">Detalles del usuario</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Usuario</label><input type="text" name="usuario" id="edit-usuario"></div>
                    <div class="modal-campo"><label>Contraseña</label><input type="password" name="contrasena" id="edit-contrasena"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/modal-estudiante.js"></script>
</body>
</html>
