<?php 
include('includes/conexion.php');

if (isset($_GET['id'])){
    $id = intval($_GET['id']); // seguridad por si alguien escribe lo que no en la URL

    $sql = "DELETE FROM docentes WHERE usuario_id = $id";

    if (mysqli_query($conexion, $sql)){
        $sql_usuario = "DELETE FROM usuarios WHERE id = $id";
        mysqli_query($conexion, $sql_usuario);

        header("Location: admin-docentes.php");
        exit();

    } else{
        echo "Error al eliminar el docente: " . mysqli_error($conexion);
    }
}
?>