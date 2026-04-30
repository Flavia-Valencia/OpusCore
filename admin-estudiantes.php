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
            <a href="./admin-periodos.php" class="btn-nav">Periodos</a>
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
                <input type="text" id="buscador-estudiante" placeholder="🔎 Buscar un estudiante" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-estudiantes.php'); ?>

            </div>
        </div>
    </main>

      <!-- Modal para editar estudiante -->
    <div id="modalEditar" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
             <h2 class="modal-titulo"><i class="fas fa-user-edit"></i> Editar Estudiante</h2>
            <!-- Formulario para registrar un nuevo estudiante en el sistema -->
            <form method="POST" action="editar-estudiante.php">
                <!-- ID oculto para identificar qué estudiante se está editando -->
                <input type="hidden" name="usuario_id" id="editd-usuario_id">
                <input type="hidden" name="estudiante_id" id="editd-estudiante_id">

                <h3 class="modal-subtitulo">Detalles del estudiante</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" id="edit-nombre" required></div>
                    <div class="modal-campo"><label>Apellido</label><input type="text" name="apellido" id="edit-apellido" required></div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono" id="edit-telefono" required></div>
                    <div class="modal-campo"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" id="edit-fecha_nacimiento" required></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion" id="edit-direccion" required></div>
                    <div class="modal-campo"><label>Género</label>
                        <select name="genero" id="edit-genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                </div>  <!-- ajuste de diseño para que se muestre igual que los otros modales-->

                <h3 class="modal-subtitulo">Acceso al sistema</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="text" name="correo" id="edit-correo"></div>
                    <div class="modal-campo">
                        <label>Contraseña</label>
                        <div class="input-password">
                            <input type="password" name="password_hash" id="edit-password_hash"required>
                            <span class="ver-contrasena-estudiante" onclick="toggleContrasena('edit-password_hash', 'icono-ojo-estudiante')">
                                <img id="icono-ojo-estudiante" src="img/ojo-cerrado.svg" width="20" height="20">
                            </span>
                        </div>
                    </div>

                     <div class="modal-campo" style="display: none;"><label>Estado</label>
                        <!-- El valor debe coincidir exactamente con "Activo"/"Inactivo" en la base de datos -->
                        <select name="estado" id="edit-estado">
                            <option value="Activo">Activo</option>    <!-- cambio de minuscula a mayuscula ya que el valor en la base de datos es "Activo" -->
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

        <!-- MODAL NUEVO ESTUDIANTE -->
    <div id="modalNuevo" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalNuevo()"><i class="fas fa-times"></i></button>
            <h2 class="modal-titulo"><i class="fas fa-user-plus"></i> Nuevo Estudiante</h2>

            <form method="POST" action="crear-estudiante.php">

                <h3 class="modal-subtitulo">Detalles del estudiante</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" required></div>
                    <div class="modal-campo"><label>Apellido</label><input type="text" name="apellido" required></div>
                    <div class="modal-campo"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" required></div>
                    <div class="modal-campo"><label>Género</label>
                        <select name="genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono" required></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion" required></div>
                </div>

                <h3 class="modal-subtitulo">Detalles del usuario</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="email" name="correo" required></div>
                    <div class="modal-campo">
                        <label>Contraseña</label>
                        <div class="input-password">
                            <!-- Se muestra como texto plano para que el admin vea la contraseña al crearla -->
                            <input type="text" name="password_hash" id="nuevo-contrasena-est" required> <!-- cambio para el tipo de password a text para que se muestre la contraseña al escribirla, ya que es un nuevo usuario y el admin necesita ver lo que escribe -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalNuevo()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Librería SweetAlert2 para mostrar alertas personalizadas en la interfaz -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="js/script.js"></script>
</body>
</html>
