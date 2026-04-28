<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

include('includes/conexion.php');

$sql_activo = "SELECT nombre, fechaInicio, fechaFin, estado 
               FROM PeriodoInscripcion 
               WHERE estado = 1 
               LIMIT 1";

$result_activo = mysqli_query($conexion, $sql_activo);
$periodo_activo = mysqli_fetch_assoc($result_activo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>ADF | Periodos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">

    <header class="header">
        <div class="logo">
            <img src="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
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
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>

            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-periodos.php" class="btn-nav active">Periodos</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>

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

        <div class="page-header">
            <h1 class="titulo">ADMINISTRAR PERIODO</h1>
            <button class="btn-nuevo">+ Nuevo Período</button>
        </div>

        <!-- Banner período activo — reutiliza .banner del CSS -->
        <div class="banner">
            <div class="banner-texto">
                <h1><?php echo $periodo_activo ? htmlspecialchars($periodo_activo['nombre']) : 'Sin período activo'; ?></h1>
            </div>
            <div style="display:flex; gap:2rem;">
                <div>
                    <p style="font-size:15px; font-weight:600;">
                        <?php echo $periodo_activo ? htmlspecialchars($periodo_activo['fechaInicio']) : '—'; ?>
                    </p>
                </div>
                <div>
                    <p style="font-size:15px; font-weight:600;">
                        <?php echo $periodo_activo ? htmlspecialchars($periodo_activo['fechaFin']) : '—'; ?>
                    </p>
                </div>
            </div>
            <span style="background:rgba(255,255,255,0.2); color:white; padding:6px 18px; border-radius:20px; font-size:13px; font-weight:700; border:1.5px solid rgba(255,255,255,0.4);">
                <?php echo $periodo_activo ? 'Activo' : 'Sin período'; ?>
            </span>
        </div>

        <!-- Tabla de períodos -->
        <div class="card">
            <div class="toolbar">
                <input type="text" id="buscador-periodo" placeholder="🔎 Buscar un período" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-periodos.php'); ?>
            </div>
        </div>

    </main>

    <!-- MODAL CREAR / EDITAR PERÍODO -->
    <div id="modalPeriodo" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalPeriodo()">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="modal-titulo" id="modal-periodo-titulo">
                <i class="fas fa-calendar-alt"></i> Nuevo Período
            </h2>

            <form onsubmit="return false;">
                <input type="hidden" name="id" id="periodo-id">

                <h3 class="modal-subtitulo">Detalles del período</h3>
                <div class="modal-grid">

                    <div class="modal-campo full-width">
                        <label>Nombre del período</label>
                        <input type="text" name="nombre" id="periodo-nombre"
                            placeholder="Ej: Periodo 1 — 2026" required>
                    </div>

                    <div class="modal-campo">
                        <label>Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" id="periodo-fecha-inicio" required>
                    </div>

                    <div class="modal-campo">
                        <label>Fecha de fin</label>
                        <input type="date" name="fecha_fin" id="periodo-fecha-fin" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalPeriodo()">Cancelar</button>
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save"></i> Guardar período
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js"></script>

</body>
</html>