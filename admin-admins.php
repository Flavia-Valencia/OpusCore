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

    <title>ADF |Administrar Administradores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">
   <!-- encabezado con logo, menú hamburguesa para móvil y navegación principal -->
    <header class="header">
        <div class="logo">
            <img src ="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>
        <!-- checkbox oculto que controla la apertura del menú en móvil -->
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
            <!-------Navegación------->
            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-admins.php" class="btn-nav active">Administradores</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-periodos.php" class="btn-nav">Periodos</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-plazo.php" class="btn-nav">Plazo Notas</a>
            <a href="./admin-pagos.php" class="btn-nav">Pagos</a>
            <a href="./admin-facturacion.php" class="btn-nav">Facturación</a>
            <a href="./admin-constancias.php" class="btn-nav">Constancias</a>

            <!--Boton para cerrar sesión en celu-->
            <a href="includes/logout.php" class="btn-salir">Cerrar sesión</a>
            <!-------------->

            <!-- perfil del usuario con correo y botón de cerrar sesión en escritorio -->
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
        <!-- encabezado de la sección con título y botón para abrir el modal de nuevo administrador -->
        <div class="page-header">
            <h1 class="titulo">ADMINISTRAR ADMINISTRADORES</h1>
            <button class="btn-nuevo">+ Nuevo Administrador</button>
        </div>

        <div class="card">
            <div class="toolbar">
                <input type="text" id="buscador-admin" placeholder="🔎 Buscar un administrador" class="input-buscar">
            </div>
            <!-- tabla de administradores cargada dinámicamente desde mostrar-tabla-admin.php -->
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-admin.php'); ?>

            </div>
        </div>
    </main>


    <!-- MODAL EDITAR ADMINISTRADOR -->
    <div id="modalEditarAdministrador" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalAdministrador()"><i class="fas fa-times"></i></button>
            <h2 class="modal-titulo"><i class="fas fa-user-edit"></i> Editar Administrador</h2>

            <!-- formulario que envia los datos actualizados del administrador-->
            <form method="POST" action="editar-administrador.php">
                <input type="hidden" name="usuario_id" id="edita-usuario_id">
                <input type="hidden" name="administrador_id" id="edita-administrador_id">

                <h3 class="modal-subtitulo">Detalles del administrador</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" id="edita-nombre" required></div>
                    <div class="modal-campo"><label>Apellido</label><input type="text" name="apellido" id="edita-apellido" required></div>
                    <div class="modal-campo"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" id="edita-fecha_nacimiento" required></div>
                    <div class="modal-campo"><label>Género</label>
                        <select name="genero" id="edita-genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="modal-campo"><label>Salario</label><input type="number" step="0.01" name="salario" id="edita-salario" required></div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono" id="edita-telefono" required></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion" id="edita-direccion" required></div>
                </div>

                <h3 class="modal-subtitulo">Acceso al sistema</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="email" name="correo" id="edita-correo" required></div>
                    <div class="modal-campo"><label>Contraseña</label>
                        <div class="input-password">
                            <input type="password" name="password_hash" id="edita-password_hash" required >
                            <!-- Ícono de ojo para mostrar u ocultar la contraseña -->
                            <span class="ver-contrasena-admin" onclick="toggleContrasena('edita-password_hash', 'icono-ojo-admin')">
                                <img id="icono-ojo-admin" src="img/ojo-cerrado.svg" alt="Mostrar contraseña" width="20" height="20">
                            </span>
                        </div>
                    </div>

                         <div class="modal-campo" style="display: none;"><label>Estado</label>
                     <!-- El valor debe coincidir exactamente con "Activo"/"Inactivo" en la base de datos -->
                        <select name="estado" id="edita-estado">
                            <option value="Activo">Activo</option> <!--modifiqué aquí-->
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalAdministrador()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL NUEVO ADMINISTRADOR -->
    <div id="modalNuevoAdministrador" class="modal-overlay">
        <div class="modal-contenido">

        <!-- El botón de cerrar modal y el título del modal -->
            <button class="modal-cerrar" onclick="cerrarModalNuevoAdministrador()"><i class="fas fa-times"></i></button>
            <h2 class="modal-titulo"><i class="fas fa-user-plus"></i> Nuevo Administrador</h2>

            <form method="POST" action="crear-administrador.php">
                <!-- Campos para ingresar los detalles del nuevo administrador -->
                <h3 class="modal-subtitulo">Detalles del administrador</h3>
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
                    <div class="modal-campo"><label>Salario</label><input type="number" step="0.01" name="salario" required></div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono" required></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion" required></div>
                </div>

                <h3 class="modal-subtitulo">Detalles del usuario</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="email" name="correo" required></div>
                   <div class="modal-campo"><label>Contraseña</label>
                        <div class="input-password">
                            <!-- Se muestra como texto plano para que el admin vea la contraseña al crearla -->
                            <input type="text" name="password_hash" id="nuevo-password_hash" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalNuevoAdministrador()">Cancelar</button>
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
