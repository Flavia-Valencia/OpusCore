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

// Abre el modal de edición de docentes y carga los datos
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
        // Mostrar modal
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

// Cerrar al hacer clic fuera del modal
const modalEditarDocente = document.getElementById('modalEditarDocente');
if (modalEditarDocente) {
    modalEditarDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalDocente();
    });
}


// Botón "+ Nuevo" abre el modal correspondiente
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


// Cerrar al hacer clic fuera del modal
const modalNuevoDocente = document.getElementById('modalNuevoDocente');

if (modalNuevoDocente) {
    modalNuevoDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevoDocente();
    });
}

// Cierra modales con la tecla Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { 
        cerrarModalDocente(); 
        cerrarModalNuevoDocente(); 
    }
});

// Lo que estaba en el archivo modal-estudiante
// Abre el modal de edición de estudiantes y carga datos
document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditar');
        if (!modal) return;
        
        // Cargar datos en el formulario
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nombre').value     = this.dataset.nombre;
        document.getElementById('edit-apellido').value   = this.dataset.apellido;
        document.getElementById('edit-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('edit-genero').value           = this.dataset.genero;
        document.getElementById('edit-telefono').value   = this.dataset.telefono;
        document.getElementById('edit-direccion').value = this.dataset.direccion;
        document.getElementById('edit-estado').value     = this.dataset.estado;
        document.getElementById('edit-correo').value     = this.dataset.correo;   
        document.getElementById('edit-password_hash').value = this.dataset.password_hash;

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

// Función para mostrar u ocultar la contraseña en todos los formularios que tengan el ícono de ojo, usando el mismo código para evitar duplicación

// Muestra u oculta la contraseña según el input e ícono que se le pase
function toggleContrasena(inputId, iconoId) {
    const input = document.getElementById(inputId);
    const icono = document.getElementById(iconoId);

    if (input && icono) {
        const viendo = input.type === "text";
        input.type = viendo ? "password" : "text";
        icono.src = `img/ojo-${viendo ? "cerrado" : "abierto"}.svg`;
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

