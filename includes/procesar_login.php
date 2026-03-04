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

            if ($usuario["rol_id"] == 1) {
                header("Location: ../admin-inicio.php");
            } elseif ($usuario["rol_id"] == 2) {
                header("Location: ../admin-estudiantes.php"); 
            } elseif ($usuario["rol_id"] == 3) {
                header("Location: ../admin-estudiantes.php"); 
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