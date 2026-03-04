/// Función para mostrar u ocultar la contraseña
function verContrasena() {
    const input = document.getElementById("contrasena");

    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}

// Este codigo es para que se envien los datos de el formulario de login.php y valida que no quede ningun campo vacio.
const formulario = document.getElementById("formulario-inicio");
const btnEntrar = document.querySelector(".btn-entrar");


formulario.addEventListener("submit", function(e) {
    
    const correo = document.getElementById('correo-inicio').value.trim();
    const contrasena = document.getElementById('contrasena').value.trim();

    if (correo === "" || contrasena === "" ) {
        e.preventDefault(); 
        alert("Complete todos los campos.")
        return;
    } 
});





