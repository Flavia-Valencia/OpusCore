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


<!-- CONTENEDOR PRINCIPAL: divide sidebar (izquierda) y contenido (derecha) -->
<div class="layout">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar">

        <!-- Logo + título del módulo -->
        <div class="logo">
            <i class="fas fa-user-graduate"></i> <!-- Icono -->
            <h2>Docente</h2> <!-- Texto -->
        </div>

        <!-- Menú de navegación lateral -->
        <nav>
            <ul>

                <!-- Opción activa (pantalla actual) -->
                <li class="active">
                    <i class="fas fa-book"></i> Mis Cursos
                </li>

                <!-- Otras opciones del sistema -->
                <li>
                    <i class="fas fa-chart-line"></i> Calificaciones
                </li>

                <li>
                    <i class="fas fa-envelope"></i> Mensajes
                </li>

                <li>
                    <i class="fas fa-cog"></i> Configuración
                </li>

            </ul>
        </nav>

    </aside>
    <!-- =============== FIN SIDEBAR =============== -->


    <!-- ================= CONTENIDO ================= -->
    <div class="content">

        <!-- HEADER SUPERIOR -->
        <header class="header">

            <!-- Marca / nombre del sistema -->
            <div class="brand">
                <img src="img/logo.svg" alt="Logo Academia" class="logo-img">

                <!-- Nombre institucional -->
                <div>
                    <strong>ACADEMIA</strong><br>
                    FUTURO DIGITAL
                </div>
            </div>

            <!-- PERFIL DEL USUARIO + LOGOUT -->
            <!-- Este botón permite cerrar sesión -->
            <a href="includes/logout.php" class="user-profile">

                <!-- Información del usuario -->
                <div class="user-info">

                    <!-- Rol del usuario (Docente) -->
                    <span class="user-role">
                        <?php echo isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : "Docente"; ?>
                    </span>

                    <!-- Correo del usuario -->
                    <span class="user-email">
                        <?php echo isset($_SESSION["usuario"]) ? htmlspecialchars($_SESSION["usuario"]) : ""; ?>
                    </span>

                </div>

                <!-- Icono de cerrar sesión -->
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>

            </a>

        </header>
        <!-- =============== FIN HEADER =============== -->


        <!-- ================= BIENVENIDA ================= -->
        <section class="welcome">

            <!-- Saludo dinámico con el nombre del usuario -->
            <h1>
                ¡Bienvenido/a, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
            </h1>

            <!-- Texto informativo -->
            <p>
                Aquí puedes gestionar tus cursos del semestre activo.
            </p>

        </section>
      


        
        <section class="courses">

    <!--
        CARDS TEMPORALES (FRONTEND)
        Estas tarjetas son solo para mostrar el diseño.
        En el futuro, el backend (PHP + MySQL) generará estas cards dinámicamente.
    --> 
        
    <!-- CARD 1 -->
    <div class="card"></div>

    <!-- CARD 2 -->
    <div class="card"></div>

    <!-- CARD 3 -->
    <div class="card"></div>

    <!-- CARD 4 -->
    <div class="card"></div>

</section>
      


    </div>
    

</div>

</body>
</body>
</html>