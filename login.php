<?php
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--PARA FUENTES-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="./css/stylesLogin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <title>login</title>
</head>

<body class="raleway-all">
    <div class="contenedor">
        <!--Lado del degradado-->
        <div class="izquierda">
            <div class="texto-arriba">
                <h2>Bienvenido/a</h2>
                <h1>Academia Futuro Digital</h1>
            </div>
            <div class="redes">
                <p>Contactos</p>
                <div class="iconos">
                    <a href="#">Facebook</a>
                    <a href="#">WhatsApp</a>
                    <a href="#">Instagram</a>
                </div>
            </div>
        </div>

        <!--Lado del formulario-->
        <div class="derecha">
            <div class="contenedor-formulario">
                <form id="formulario-inicio" role="tabpanel" action="includes/procesar_login.php" method="POST" aria-labelledby="leyenda-inicio" autocomplete="on">
                    <h2 id="inicio-sesion" class="leyenda">Inicia sesión</h2>
                    
                    <div class="fila">
                        <label for="correo-inicio">Correo electrónico</label>
                        <input id="correo-inicio" name="correo" type="email" placeholder="correo@gmail.com" required />
                    </div>
                    
                    <div class="fila">
                        <label for="contrasena">Contraseña</label>
                        <div class="input-password">
                            <input id="contrasena" name="contrasena" type="password" placeholder="••••••••" required />

                            <!-- Ícono de ojo para mostrar u ocultar la contraseña -->
                            <span class="ver-contrasena" onclick="toggleContrasena('contrasena', 'icono-ojo')">
                                <img id="icono-ojo" src="img/ojo-cerrado.svg" alt="Mostrar contraseña" width="20" height="20" />
                            </span>
                        </div>
                    </div>

                    <div class="opcion-recordar">
                        <label class="recordar-contrasena">
                            <input type="checkbox" name="recordar-contrasena"/>
                            Recordar contraseña
                        </label>
                    </div>

                    <!-- <div class="pie">
                        <p>Olvidaste tu contraseña?</p><a href="#" class="enlace">Enviar solicitud de reestablecimiento</a>
                    </div> -->

                      <?php if ($error == 1): ?>
                        <div class="mensaje-error">
                            Correo o contraseña incorrecta. Inténtalo de nuevo.
                        </div>
                        <?php elseif ($error == 2): ?>
                            <div class="mensaje-error">
                                Usuario inactivo. Contacta al administrador.
                            </div>           
                    <?php endif; ?>

                    <button class="btn-entrar" type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>

