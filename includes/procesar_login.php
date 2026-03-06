<!-- Este archivo sirve para el inicio de sesion por rol, cuando el usuario inicie sesion lo enviara a el rol correspondiente que el admin le haya -->
<?php
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];

    $consulta = "SELECT * FROM usuarios WHERE correo='$correo'";
    $resultado = mysqli_query($conexion, $consulta);

    if (mysqli_num_rows($resultado) == 1) {
        $usuario = mysqli_fetch_assoc($resultado);
        
        if ($contrasena === $usuario['password_hash']) {
            $_SESSION["usuario"] = $usuario["correo"];
            $_SESSION["rol_id"] = $usuario["rol_id"];
            $_SESSION["nombre"] = $usuario["nombre"];
 
            if ($usuario["rol_id"] == 1) {
                header("Location: ../admin-inicio.php");
            } elseif ($usuario["rol_id"] == 2) {
                header("Location: ../estudiantes.php"); 
            } elseif ($usuario["rol_id"] == 3) {
                header("Location: ../docentes.php"); 
            }
            exit(); 
        
        } else {
            header("Location: ../login.php?error=1");
            exit();
        } 
    } else {
        header("Location: ../login.php?error=1");
        exit();
    }
}
?>