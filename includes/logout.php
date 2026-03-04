<?php #cree este archivo para que cuando el usuario le de click al icono de cerrar sesion(logout) se salga y lo redirija al login.php
session_start();
session_destroy();
header("Location: ../login.php");
exit();
?>
