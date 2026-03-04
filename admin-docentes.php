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
    <title>ADF |Administrar Docentes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>
<body>
   
    <header class="header">
        <div class="logo">
            <img src ="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>
        <nav class="nav">
            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav active">Docentes</a>
        
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
</body>
</html>
