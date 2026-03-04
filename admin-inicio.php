<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Inicio</title>
    <link rel="stylesheet" href="css/stylesAdmin.css">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<script src="js/admin.js"></script>

<body>
    

    <header class="header">
        <div class="logo-container">
         
            <div class="logo-icon">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="8" fill="white" fill-opacity="0.1"/>
                    <path d="M20 8L8 14V20C8 28 20 34 20 34C20 34 32 28 32 20V14L20 8Z" stroke="white" stroke-width="2" fill="none"/>
                    <path d="M20 16L14 19V23C14 27 20 30 20 30C20 30 26 27 26 23V19L20 16Z" fill="#55D9D9"/>
                </svg>
            </div>
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
                <div class="menu-user-email">admin@futurodigital.com</div>
            </div>
            
            <a href="admin-inicio.php" class="btn-nav active">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="admin-estudiantes.php" class="btn-nav">
                <i class="fas fa-user-graduate"></i> Estudiantes
            </a>
            <a href="admin-cursos.php" class="btn-nav">
                <i class="fas fa-book"></i> Cursos
            </a>
            <a href="admin-docentes.php" class="btn-nav">
                <i class="fas fa-chalkboard-teacher"></i> Docentes
            </a>
            <a href="admin-recursos.php" class="btn-nav">
                <i class="fas fa-folder-open"></i> Recursos
            </a>
            
            <!-- Botón salir al final -->
            <a href="logout.php" class="btn-salir">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
        
      
        <div class="user-profile">
            <span>Admin</span>
            <i class="fas fa-user-circle"></i>
        </div>
    </header>
    
 
    <main class="main">
        <div class="container">
            
          
            <div class="page-header">
                <div>
                    <h1 class="titulo">Panel de Control</h1>
                    <p class="subtitulo">Bienvenido al sistema administrativo</p>
                </div>
                <button class="btn-nuevo">
                    <i class="fas fa-plus"></i> Nuevo Estudiante
                </button>
            </div>
            
        
            <div class="toolbar">
                <input type="text" class="input-buscar" placeholder="🔍 Buscar estudiante...">
            </div>
            
         
            <div class="card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td data-label="ID">001</td>
                                <td data-label="Nombre">Juan Pérez López</td>
                                <td data-label="Email">juan@email.com</td>
                                <td data-label="Curso">Matemáticas</td>
                                <td data-label="Estado">
                                    <span class="badge badge-activo">Activo</span>
                                </td>
                                <td data-label="Acciones" class="acciones-cell">
                                    <div class="acciones">
                                        <a href="#" class="btn-accion btn-editar">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="#" class="btn-accion btn-eliminar">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                      
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>
    
</body>
</html>