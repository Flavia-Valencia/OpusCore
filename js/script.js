// Valida que el formulario de login no tenga campos vacíos antes de enviar
const formulario = document.getElementById("formulario-inicio");
const btnEntrar = document.querySelector(".btn-entrar");

if (formulario) {
    formulario.addEventListener("submit", function(e) {
        
        const correo = document.getElementById('correo-inicio').value.trim();
        const contrasena = document.getElementById('contrasena').value.trim();

        // Si algún campo está vacío, cancela el envío y muestra alerta
        if (correo === "" || contrasena === "" ) {
            e.preventDefault(); 
            alert("Complete todos los campos.");
            return;
        } 
    });
}

// --- MODAL EDITAR DOCENTE ---

// Abre el modal de edición de docentes y carga los datos del docente seleccionado en el formulario
document.querySelectorAll('.abrir-modal-docente').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditarDocente');
        if (!modal) return;

        // rellena cada campo del formulario con los datos del docente
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

        // Convierte el valor numérico de estado a texto para que coincida con el select
        const estado = this.dataset.estado == 1 ? 'Activo' : 'Inactivo'; 
        document.getElementById('editd-estado').value = estado;
        // Mostrar el modal
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

// Cierra el modal de edición de docente y restaura el scroll
function cerrarModalDocente() {
    const modal = document.getElementById('modalEditarDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Cierra el modal de docente al hacer clic fuera de el
const modalEditarDocente = document.getElementById('modalEditarDocente');
if (modalEditarDocente) {
    modalEditarDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalDocente();
    });
}


// --- MODAL NUEVO DOCENTE / NUEVO ESTUDIANTE ---


function cerrarModalNuevoDocente() {
    const modal = document.getElementById('modalNuevoDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}


// Cierra el modal de nuevo docente al hacer clic fuera de el
const modalNuevoDocente = document.getElementById('modalNuevoDocente');

if (modalNuevoDocente) {
    modalNuevoDocente.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevoDocente();
    });
}



// --- MODAL EDITAR ESTUDIANTE ---

// Abre el modal de edición de estudiantes y carga datos
document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditar');
        if (!modal) return;
        
        // Rellena cada campo del formulario con los datos del estudiante
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nombre').value     = this.dataset.nombre;
        document.getElementById('edit-apellido').value   = this.dataset.apellido;
        document.getElementById('edit-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('edit-genero').value           = this.dataset.genero;
        document.getElementById('edit-telefono').value   = this.dataset.telefono;
        document.getElementById('edit-direccion').value = this.dataset.direccion;
        document.getElementById('edit-correo').value     = this.dataset.correo;   
        document.getElementById('edit-password_hash').value = this.dataset.password_hash;

        // Convierte el valor numérico de estado a texto para que coincida con el select
        const estado = this.dataset.estado == 1 ? 'Activo' : 'Inactivo';
        document.getElementById('edit-estado').value = estado;

        // Muestra el modal y bloquea el scroll del fondo
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});


// Cierra el modal de edición de estudiante
function cerrarModal() {
    const modal = document.getElementById('modalEditar');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Cierra el modal de edición de estudiante al hacer clic fuera de él
const modalEditar = document.getElementById('modalEditar');
if (modalEditar) {
    modalEditar.addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
}

// Cierra el modal de nuevo estudiante
function cerrarModalNuevo() {
    const modal = document.getElementById('modalNuevo');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Cierra el modal de nuevo estudiante al hacer clic fuera de él
const modalNuevo = document.getElementById('modalNuevo');
if (modalNuevo) {
    modalNuevo.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevo();
    });
}


// MODAL nuevo curso



function cerrarModalNuevoCurso() {
    const modal = document.getElementById('modalNuevoCurso');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalNuevoCurso = document.getElementById('modalNuevoCurso');

if (modalNuevoCurso) {
    modalNuevoCurso.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalNuevoCurso();
    });
}

// bueno

// --- MODAL EDITAR CURSO ---

document.querySelectorAll('.abrir-modal-curso').forEach(btn => {
    btn.addEventListener('click', function() {

        const modal = document.getElementById('modalEditarCurso');
        if (!modal) return;

        // Rellenar datos
        document.getElementById('edit-id-curso').value = this.dataset.id;
        document.getElementById('edit-nombre-curso').value = this.dataset.nombre;
        document.getElementById('edit-descripcion-curso').value = this.dataset.descripcion;
        document.getElementById('edit-duracion-curso').value = this.dataset.duracion;
        document.getElementById('edit-precio-curso').value = this.dataset.precio;

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

// Cierra el modal de edición de curso
function cerrarModalCurso() {
    const modal = document.getElementById('modalEditarCurso');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// cerrar al hacer clic fuera
const modalEditarCurso = document.getElementById('modalEditarCurso');
if (modalEditarCurso) {
    modalEditarCurso.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalCurso();
    });
}


//-- funcion para nuevo curso, nuevo docente o nuevo estudiante, dependiendo de cuál exista en la página, para evitar duplicar código al tener un botón "+ Nuevo" que abre diferentes modales según la página en la que se encuentre el admin
    const btnNuevo = document.querySelector('.btn-nuevo');

    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {

            const modalNuevoCurso = document.getElementById('modalNuevoCurso');
            const modalNuevoDocente = document.getElementById('modalNuevoDocente');
            const modalNuevo = document.getElementById('modalNuevo');

            if (modalNuevoCurso) {
                modalNuevoCurso.classList.add('activo');
            } else if (modalNuevoDocente) {
                modalNuevoDocente.classList.add('activo');
            } else if (modalNuevo) {
                modalNuevo.classList.add('activo');
            }

            document.body.style.overflow = 'hidden';
        });
    }


// --- PÁGINA DE INICIO ---

// Muestra la fecha actual en formato largo en español en la página de inicio
const fechaHoy = document.getElementById('fecha-hoy');
if (fechaHoy) {
    const fecha = new Date();
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    fechaHoy.textContent = fecha.toLocaleDateString('es-ES', opciones);
}


// --- SEGURIDAD DE SESIÓN ---

// Evita que el navegador muestre páginas del admin desde caché al retroceder sin sesión
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.href = "login.php";
    }
};


// --- TOGGLE CONTRASEÑA ---

// Función para mostrar u ocultar la contraseña en todos los formularios que tengan el ícono de ojo, usando el mismo código para evitar duplicación
// Recibe el ID del input y del ícono para funcionar en múltiples formularios sin duplicar código
function toggleContrasena(inputId, iconoId) {
    const input = document.getElementById(inputId);
    const icono = document.getElementById(iconoId);

    if (input && icono) {
        const viendo = input.type === "text";
        input.type = viendo ? "password" : "text";
        icono.src = `img/ojo-${viendo ? "cerrado" : "abierto"}.svg`;
    }
}

// Muestra u oculta el ícono del ojo según si hay texto escrito en el campo contraseña
const inputContrasena = document.getElementById("contrasena");
const spanOjo = document.querySelector(".ver-contrasena");

if (inputContrasena && spanOjo) {
    inputContrasena.addEventListener("input", function() {
        spanOjo.style.opacity = this.value.length > 0 ? "1" : "0";
    });
}



document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { 
        cerrarModalDocente(); 
        cerrarModalNuevoDocente();
        cerrarModal(); 
        cerrarModalCurso(); 
        cerrarModalNuevoCurso(); 
    }
});
