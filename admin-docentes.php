<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Estudiantes | Academia Futuro Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
</head>
<body>
   
    <header class="header">
        <div class="logo">
            <span class="logo-small">ACADEMIA</span>
            <span class="logo-big">FUTURO DIGITAL</span>
        </div>
        <nav class="nav">
            <a href="#" class="btn-nav">Inicio</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav active">Docentes</a>
            
            <div class="user-profile">
                <div class="user-info">
                    <span class="user-role">Admin</span>
                    <span class="user-email">admin@academia.com</span>
                </div>
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
            </div>
        </nav>
    </header>

    <main class="main">
    
        <div class="page-header">
            <h1 class="titulo">ADMINISTRAR DOCENTES</h1>
            <button class="btn-nuevo">+ Nuevo Docente</button>
        </div>

        <div class="card">
            <div class="toolbar">
                <input type="text" placeholder="Buscar un estudiante" class="input-buscar">
            </div>
            <div class="tabla-placeholder">

            </div>

        </div>
    </main>
</body>
</html>