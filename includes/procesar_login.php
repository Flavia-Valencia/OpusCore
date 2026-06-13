<?php
// Procesa el inicio de sesion, crea la sesion y redirige segun el rol del usuario.
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];

    $consulta = "SELECT * FROM usuarios WHERE BINARY correo='$correo'";
    $resultado = mysqli_query($conexion, $consulta);

    if (mysqli_num_rows($resultado) == 1) {
        $usuario = mysqli_fetch_assoc($resultado);
        if ($usuario["estado"] == 0) {
            header("Location: ../login.php?error=2");
            exit();
        }
        
        if (password_verify($contrasena, $usuario['password_hash']) || $contrasena === $usuario['password_hash']) {
            $_SESSION["usuario"] = $usuario["correo"];
            $_SESSION["rol_id"] = $usuario["rol_id"];
            $_SESSION["nombre"] = $usuario["nombre"];
 
            if ($usuario["rol_id"] == 1) {
                header("Location: ../admin-inicio.php");
            } elseif ($usuario["rol_id"] == 2) {
                // El estudiante inicia en Mis cursos para revisar su estado academico.
                header("Location: ../vista_mis_cursos.php");
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
