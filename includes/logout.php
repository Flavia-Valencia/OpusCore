<?php #cree este archivo para que cuando el usuario le de click al icono de cerrar sesion(logout) se salga y lo redirija al login.php
session_start();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header("Location: ../login.php");
exit();
?>
