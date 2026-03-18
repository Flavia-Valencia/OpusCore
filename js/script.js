// Este codigo es para que se envien los datos de el formulario de login.php y valida que no quede ningun campo vacio.
const formulario = document.getElementById("formulario-inicio");
const btnEntrar = document.querySelector(".btn-entrar");

if (formulario) {
    formulario.addEventListener("submit", function(e) {
        
        const correo = document.getElementById('correo-inicio').value.trim();
        const contrasena = document.getElementById('contrasena').value.trim();

        if (correo === "" || contrasena === "" ) {
            e.preventDefault(); 
            alert("Complete todos los campos.");
            return;
        } 
    });
}

// Estructura creada por Yahir (Luego la comentas mejor)
// lo que estaba en el archivo modal-docente
document.querySelectorAll('.abrir-modal-docente').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditarDocente');
        if (!modal) return;

        document.getElementById('editd-id').value               = this.dataset.id;
        document.getElementById('editd-nombre').value           = this.dataset.nombre;
        document.getElementById('editd-apellido').value         = this.dataset.apellido;
        document.getElementById('editd-especialidad').value     = this.dataset.especialidad;
        document.getElementById('editd-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('editd-genero').value           = this.dataset.genero;
        document.getElementById('editd-salario').value          = this.dataset.salario;
        document.getElementById('editd-telefono').value         = this.dataset.telefono;
        document.getElementById('editd-direccion').value        = this.dataset.direccion;
        document.getElementById('editd-correo').value           = this.dataset.correo;
        document.getElementById('editd-password_hash').value    = this.dataset.password_hash;

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

function cerrarModalDocente() {
    const modal = document.getElementById('modalEditarDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalEditarDocente = document.getElementById('modalEditarDocente');

if (modalEditarDocente) {
    modalEditarDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalDocente();
    });
}

const btnNuevo = document.querySelector('.btn-nuevo');

if (btnNuevo) {
    btnNuevo.addEventListener('click', function() {

        const modalNuevoDocente = document.getElementById('modalNuevoDocente');

        if (modalNuevoDocente) {
            modalNuevoDocente.classList.add('activo');
            document.body.style.overflow = 'hidden';
        }

        const modalNuevo = document.getElementById('modalNuevo');

        if (modalNuevo) {
            modalNuevo.classList.add('activo');
            document.body.style.overflow = 'hidden';
        }
    });
}

function cerrarModalNuevoDocente() {
    const modal = document.getElementById('modalNuevoDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalNuevoDocente = document.getElementById('modalNuevoDocente');

if (modalNuevoDocente) {
    modalNuevoDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevoDocente();
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { 
        cerrarModalDocente(); 
        cerrarModalNuevoDocente(); 
    }
});

// Lo que estaba en el archivo modal-estudiante
document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditar');
        if (!modal) return;

        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nombre').value     = this.dataset.nombre;
        document.getElementById('edit-apellido').value   = this.dataset.apellido;
        document.getElementById('edit-telefono').value   = this.dataset.telefono;
        document.getElementById('edit-estado').value     = this.dataset.estado;
        document.getElementById('edit-correo').value     = this.dataset.correo;
        document.getElementById('edit-contrasena').value = this.dataset.contrasena;

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

 //Editar estudiante
function cerrarModal() {
    const modal = document.getElementById('modalEditar');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalEditar = document.getElementById('modalEditar');

if (modalEditar) {
    modalEditar.addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
}

document.addEventListener('keydown', e => { 
    if (e.key === 'Escape') cerrarModal(); 
});

function cerrarModalNuevo() {
    const modal = document.getElementById('modalNuevo');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalNuevo = document.getElementById('modalNuevo');

if (modalNuevo) {
    modalNuevo.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevo();
    });
}


// Lo que estaba en el archivo de inicio
const fechaHoy = document.getElementById('fecha-hoy');

if (fechaHoy) {
    const fecha = new Date();
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    fechaHoy.textContent = fecha.toLocaleDateString('es-ES', opciones);
}


// Lo que estaba en el archivo de admin
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.href = "login.php";
    }
};

// Función para mostrar u ocultar la contraseña

// Cambia entre ojo-abierto.svg y ojo-cerrado.svg según el estado
function verContrasena() {
    const input = document.getElementById("contrasena");
    const icono = document.getElementById("icono-ojo");
    if (input) {
        if (input.type === "password") {
            input.type = "text";
            icono.src = "img/ojo-abierto.svg";
        } else {
            input.type = "password";
            icono.src = "img/ojo-cerrado.svg";
        }
    }
}

// Muestra u oculta el ícono del ojo según si hay texto en el campo contraseña
const inputContrasena = document.getElementById("contrasena");
const spanOjo = document.querySelector(".ver-contrasena");

if (inputContrasena && spanOjo) {
    inputContrasena.addEventListener("input", function() {
        spanOjo.style.opacity = this.value.length > 0 ? "1" : "0";
    });
}

// Muestra u oculta la contraseña en el modal de editar docente
function verContrasenaDocente() {
    const input = document.getElementById("editd-password_hash");
    const icono = document.getElementById("icono-ojo-docente");

    if (input.type === "password") {
        input.type = "text";
        icono.src = "img/ojo-abierto.svg";
    } else {
        input.type = "password";
        icono.src = "img/ojo-cerrado.svg";
    }
}


// Muestra u oculta el ícono del ojo según si hay texto en el campo contraseña del modal de editar docente
const inputDocente = document.getElementById("editd-password_hash");
const ojoDocente = document.querySelector(".ver-contrasena-docente");

if (inputDocente && ojoDocente) {
    inputDocente.addEventListener("input", function() {
        ojoDocente.style.opacity = this.value.length > 0 ? "1" : "0";
    });
}

