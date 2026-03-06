<?php #esto es para que cuando alguien inice sesion, la direccion de el correo cambie
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
    
    <title>ADF | Panel Docente</title>
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="stylesheet" href="./css/styles-docentes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head> 

<body class="raleway-all">

    <header class="header">
        <div class="logo">
            <img src="img/logo.svg" alt="Logo" class="logo-img">
            <div class="logo-text">
                <span>¡Bienvenido/a!</span>
                <!--Para que se coloque el nombre del usuario de la credencial-->
                <h2 class="user-nombre"><?php echo $_SESSION["nombre"];?></h2>
            </div>
        </div>

        <a href="includes/logout.php" style="text-decoration:none;">
            <div class="user-profile">
                <div class="user-info">
                    <span class="user-role">Docente</span>
                    <span class="user-email"><?php echo $_SESSION["usuario"]; ?></span>
                </div>
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
            </div>
        </a>
    </header>

    <main class="main">
        <h1 class="titulo">Panel del Docente</h1>
        <section class="cards-container">
            <a href="#" class="card-opcion">
                <i class="fas fa-book icono"></i>
                <span>Cursos</span>
            </a>

            <a href="#" class="card-opcion">
                <i class="fas fa-graduation-cap icono"></i>
                <span>Calificaciones</span>
            </a>
        </section>
    </main>

</body>
</html>