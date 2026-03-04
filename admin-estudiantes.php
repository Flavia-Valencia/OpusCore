<?php

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
    <title>Administrar Estudiantes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<script src="js/admin.js"></script>

<body>
   
    <header class="header">
        <div class="logo">
            <img src="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo-img">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>
        

        <input type="checkbox" id="menu-toggle" class="menu-checkbox">
        
   
        <label for="menu-toggle" class="menu-btn">
            <i class="fas fa-bars hamburguesa">☰</i>
            <i class="fas fa-times cerrar"></i>
        </label>
        

        <label for="menu-toggle" class="menu-overlay"></label>
        
    
        <nav class="nav">

            <div class="menu-user">
                <div class="menu-user-name">Administrador</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>
            
            <a href="./admin-inicio.php" class="btn-nav">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="./admin-estudiantes.php" class="btn-nav active">
                <i class="fas fa-user-graduate"></i> Estudiantes
            </a>
            <a href="./admin-cursos.php" class="btn-nav">
                <i class="fas fa-book"></i> Cursos
            </a>
            <a href="./admin-docentes.php" class="btn-nav">
                <i class="fas fa-chalkboard-teacher"></i> Docentes
            </a>

            <a href="includes/logout.php" class="btn-salir">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
        

        <a href="includes/logout.php" style="text-decoration:none;">
            <div class="user-profile">
                <div class="user-info">
                    <span class="user-role">Admin</span>
                    <span class="user-email"><?php echo $_SESSION["usuario"]; ?></span>
                </div>
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
            </div>
        </a>
    </header>


    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1 class="titulo">ADMINISTRAR ESTUDIANTES</h1>
                <button class="btn-nuevo">
                    <i class="fas fa-plus"></i> Nuevo Estudiante
                </button>
            </div>

            <div class="card">
                <div class="toolbar">
                    <input type="text" placeholder="🔎 Buscar un estudiante" class="input-buscar">
                </div>
                <div class="tabla-placeholder">
                    <?php include('mostrar-tabla-estudiantes.php'); ?>
                </div>
            </div>
        </div>
    </main>
    
</body>
</html>