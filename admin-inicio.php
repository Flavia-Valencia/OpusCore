<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

// Conexion local usada por el dashboard de inicio.
$conn = new mysqli("localhost", "root", "", "db_academiadigital");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Total de estudiantes activos.
$res_estudiantes = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 2 AND estado = 1");
$total_estudiantes = $res_estudiantes->fetch_assoc()["total"];

// Total de docentes activos.
$res_docentes = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 3 AND estado = 1");
$total_docentes = $res_docentes->fetch_assoc()["total"];

// Total de cursos activos.
$res_cursos = $conn->query("SELECT COUNT(*) AS total FROM cursos WHERE estado = 1");
$total_cursos = $res_cursos->fetch_assoc()["total"];


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fuentes e iconos de la interfaz -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>ADF | Inicio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">
    <!-- Header administrativo con logo, menu movil y navegacion principal -->
    <header class="header">
        <div class="logo">
            <img src ="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>
        <!-- Menu hamburguesa para movil -->
        <input type="checkbox" id="menu-toggle" class="menu-checkbox"> 
     
        <label for="menu-toggle" class="menu-btn">
            <i class="fas fa-bars hamburguesa"></i>
            <i class="fas fa-times cerrar"></i>
        </label>
        
        <label for="menu-toggle" class="menu-overlay"></label>
        
        <nav class="nav">
            <!-- Datos del usuario visibles dentro del menu movil -->
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>
            <a href="./admin-inicio.php" class="btn-nav active">Inicio</a>
            <a href="./admin-admins.php" class="btn-nav">Administradores</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-periodos.php" class="btn-nav">Periodos</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-plazo.php" class="btn-nav">Plazo Notas</a>
            <a href="./admin-pagos.php" class="btn-nav">Pagos</a>
            <a href="./admin-facturacion.php" class="btn-nav">Facturación</a>
            <a href="./admin-constancias.php" class="btn-nav">Constancias</a>
        
            <!-- Cierre de sesion dentro del menu movil -->
            <a href="includes/logout.php" class="btn-salir">Cerrar sesión</a>

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
        <!-- Banner de bienvenida -->
        <div class="banner">
            <div class="banner-texto">
                <h1>¡Bienvenido, Admin! 👋</h1>
                <p>Panel de administración · Academia Futuro Digital</p>
            </div>
            <div class="banner-fecha">
                <strong id="fecha-hoy"></strong>
            </div>
        </div>

        <!-- Indicadores principales del sistema -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-left">
                    <h3><?php echo $total_estudiantes; ?></h3>
                    <p>Estudiantes</p>
                </div>
                <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h3><?php echo $total_docentes; ?></h3>
                    <p>Docentes</p>
                </div>
                <div class="stat-icon teal"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h3><?php echo $total_cursos; ?></h3>
                    <p>Cursos activos</p>
                </div>
                <div class="stat-icon green"><i class="fas fa-book-open"></i></div>
            </div>
        </div>
    </main>

    <script src ="js/script.js"></script>

</body>
</html>
