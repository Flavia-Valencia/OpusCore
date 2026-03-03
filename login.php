<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylesLogin.css">
    <title>Document</title>
</head>

<body>

    <div class="contenedor">
        <!--Lado del degradado-->
        <div class="izquierda">
            <h2>Bienvenido/a</h2>
            <h1>Academia Futuro Digital</h1>
        </div>
        <!--Lado del formulario-->
        <div class="derecha">
            <div class="contenedor-formulario">
                <form id="formulario-inicio" role="tabpanel" aria-labelledby="leyenda-inicio" autocomplete="on">
                    <h2 id="inicio-sesion" class="leyenda">Inicia sesión</h2>
                    
                    <div class="fila">
                        <label for="correo-inicio">Correo electrónico</label>
                        <input id="correo-inicio" name="correo" type="email" placeholder="correo@gmail.com" required />
                    </div>
                    
                    <div class="fila">
                        <label for="contrasena">Contraseña</label>
                        <div class="input-password">
                            <input id="contrasena" name="contrasena" type="password" placeholder="••••••••" required />
                            <span class="ver-contrasena" onclick="verContrasena()">👁️‍🗨️</span>
                        </div>
                    </div>

                    <div class="opcion-recordar">
                        <label class="recordar-contrasena">
                            <input type="checkbox" name="recordar-contrasena"/>
                            Recordar contraseña
                        </label>
                    </div>

                    <div class="pie">
                        <p>Olvidaste tu contraseña</p><a href="#" class="enlace">Enviar solicitud de reestablecimiento</a>
                    </div>

                    <button class="btn-entrar" type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/scriptLogin.js"></script>
</body>
</html>