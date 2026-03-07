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

    <title>ADF |Administrar Docentes</title>
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
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav active">Docentes</a>

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
            <h1 class="titulo">ADMINISTRAR DOCENTES</h1>
            <button class="btn-nuevo">+ Nuevo Docente</button>
        </div>

        <div class="card">
            <div class="toolbar">
                <input type="text" placeholder="🔎 Buscar un docente" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-docentes.php'); ?>

            </div>
        </div>
    </main>


    <!-- MODAL EDITAR DOCENTE -->
    <div id="modalEditarDocente" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalDocente()"><i class="fas fa-times"></i></button>
            <h2 class="modal-titulo"><i class="fas fa-user-edit"></i> Editar Docente</h2>

            <form method="POST" action="editar-docente.php">
                <input type="hidden" name="id" id="editd-id">

                <h3 class="modal-subtitulo">Detalles del docente</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" id="editd-nombre"></div>
                    <div class="modal-campo"><label>Apellido</label><input type="text" name="apellido" id="editd-apellido"></div>
                    <div class="modal-campo"><label>Especialidad</label><input type="text" name="especialidad" id="editd-especialidad"></div>
                    <div class="modal-campo"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" id="editd-fecha_nacimiento"></div>
                    <div class="modal-campo"><label>Género</label>
                        <select name="genero" id="editd-genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="modal-campo"><label>Salario</label><input type="number" step="0.01" name="salario" id="editd-salario"></div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono" id="editd-telefono"></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion" id="editd-direccion"></div>
                </div>

                <h3 class="modal-subtitulo">Detalles del usuario</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="email" name="correo" id="editd-correo"></div>
                    <div class="modal-campo"><label>Contraseña</label><input type="password" name="password_hash" id="editd-password_hash"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalDocente()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL NUEVO DOCENTE -->
    <div id="modalNuevoDocente" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalNuevoDocente()"><i class="fas fa-times"></i></button>
            <h2 class="modal-titulo"><i class="fas fa-user-plus"></i> Nuevo Docente</h2>

            <form method="POST" action="crear-docente.php">

                <h3 class="modal-subtitulo">Detalles del docente</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Nombre</label><input type="text" name="nombre" required></div>
                    <div class="modal-campo"><label>Apellido</label><input type="text" name="apellido" required></div>
                    <div class="modal-campo"><label>Especialidad</label><input type="text" name="especialidad"></div>
                    <div class="modal-campo"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento"></div>
                    <div class="modal-campo"><label>Género</label>
                        <select name="genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="modal-campo"><label>Salario</label><input type="number" step="0.01" name="salario"></div>
                    <div class="modal-campo"><label>Teléfono</label><input type="text" name="telefono"></div>
                    <div class="modal-campo"><label>Dirección</label><input type="text" name="direccion"></div>
                </div>

                <h3 class="modal-subtitulo">Detalles del usuario</h3>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Correo</label><input type="email" name="correo" required></div>
                    <div class="modal-campo"><label>Contraseña</label><input type="password" name="contrasena" required></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalNuevoDocente()">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/modal-docente.js"></script>
</body>

</body>
</html>
