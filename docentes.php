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

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <!-- 🔥 NUEVO LOGO -->
            <div class="logo">
                <img src="img/logo.svg" alt="Logo Academia" class="logo-img-sidebar">
                <div class="logo-text">
                    <strong>ACADEMIA</strong><br>
                    FUTURO DIGITAL
                </div>
            </div>

            <nav>
                <ul>
                    <li class="active">
                        <i class="fas fa-book"></i> Mis Cursos
                    </li>

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

        <!-- CONTENIDO -->
        <div class="content">

            <header class="header">

                <!-- BOTÓN HAMBURGUESA -->
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- PERFIL / LOGOUT -->
                <a href="includes/logout.php" class="user-profile">
                    <div class="user-info">
                        <span class="user-role">
                            <?php echo isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : "Docente"; ?>
                        </span>

                        <span class="user-email">
                            <?php echo isset($_SESSION["usuario"]) ? htmlspecialchars($_SESSION["usuario"]) : ""; ?>
                        </span>
                    </div>

                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </a>
            </header>

            <!-- BIENVENIDA -->
            <section class="welcome">
                <h1>
                    ¡Bienvenido/a, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
                </h1>

                <p>
                    Aquí puedes gestionar tus cursos del semestre activo.
                </p>
            </section>
            
            <!-- CARDS -->
            <section class="courses">
                <div class="card"></div>
                <div class="card"></div>
                <div class="card"></div>
                <div class="card"></div>
            </section>
        
        </div>
    </div>

    <!-- OVERLAY -->
    <div class="overlay"></div>

    <!-- SCRIPT -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {

        const toggle = document.querySelector(".menu-toggle");
        const sidebar = document.querySelector(".sidebar");
        const overlay = document.querySelector(".overlay");

        toggle.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");
        });

        overlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });

        const links = document.querySelectorAll(".sidebar li");

        links.forEach(link => {
            link.addEventListener("click", () => {
                sidebar.classList.remove("active");
                overlay.classList.remove("active");
            });
        });

    });
    </script>

</body>
</html>