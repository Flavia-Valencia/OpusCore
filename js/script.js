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


// MODAL NUEVO CURSO



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
        document.getElementById('edit-fecha-inicio').value = this.dataset.fechainicio;
        document.getElementById('edit-fecha-fin').value = this.dataset.fechafin; // aqui lo que hice fue eliminar el dato de duracion ya que era redundante y cambiarlo por fecha inicio/fin - Yahir
        document.getElementById('edit-costo-mensual').value = this.dataset.costo; // lo mismo con el precio, lo cambié por costo mensual - Yahir

        // Valida que el elemento exista antes de usarlo y asigna sus valores dinámicamente
        if(document.getElementById('edit-cupos')) {
            document.getElementById('edit-cupos').value = this.dataset.cupos;
        }
        if(document.getElementById('edit-estado-curso')) {
            const estadoTexto = this.dataset.estado == 1 ? 'Activo' : 'Inactivo';
            document.getElementById('edit-estado-curso').value = estadoTexto;
        }

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});


// --- NUEVO UI MODAL (Inyectado en body) esto sirve para generar modal de advertencia dinámicamente y reutilizar el mismo modal en diferentes acciones sin duplicar código en el HTML

const customModalHTML = `
<div class="custom-modal-overlay" id="customConfirmModal">
    <div class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-title" id="customModalTitle">Modal Title</div>
            <div class="custom-modal-text" id="customModalText">Lorem ipsum dolor sit amet...</div>
            <div class="custom-modal-actions">
                <button class="custom-btn custom-btn-cancel" id="customBtnCancel">Cancelar</button>
                <button class="custom-btn custom-btn-accept" id="customBtnAccept">Aceptar</button>
            </div>
        </div>
    </div>
</div>
`;
document.body.insertAdjacentHTML('beforeend', customModalHTML);
// Lógica para botones de Estado
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-toggle-estado')) {
        e.preventDefault();
        const btn = e.target;
        
        const isActivo = btn.classList.contains('estado-activo');
        
        const modal = document.getElementById('customConfirmModal');
        const mTitle = document.getElementById('customModalTitle');
        const mText = document.getElementById('customModalText');
        const bCancel = document.getElementById('customBtnCancel');
        const bAccept = document.getElementById('customBtnAccept');
        if (isActivo) {
            // Confirmación de DESACTIVAR
            mTitle.innerText = '¿Desactivar registro?';
            mText.innerText = 'El registro pasará a estado Inactivo. Podrás volver a habilitarlo luego.';
        } else {
            // Confirmación de ACTIVAR
            mTitle.innerText = '¿Activar registro?';
            mText.innerText = 'El registro pasará a estado Activo y volverá a ser visible en el sistema.';
        }
        modal.classList.add('active');
        // Limpiar eventos anteriores para no duplicarlos
        const newCancel = bCancel.cloneNode(true);
        const newAccept = bAccept.cloneNode(true);
        bCancel.parentNode.replaceChild(newCancel, bCancel);
        bAccept.parentNode.replaceChild(newAccept, bAccept);
        newCancel.addEventListener('click', function() {
            modal.classList.remove('active');
        });
        newAccept.addEventListener('click', function() {
            if (isActivo) {
                // Pasa de activo a Inactivo
                btn.classList.remove('estado-activo');
                btn.classList.add('estado-inactivo');
                btn.innerText = 'Inactivo';
            } else {
                // Pasa de inactivo a Activo
                btn.classList.remove('estado-inactivo');
                btn.classList.add('estado-activo');
                btn.innerText = 'Activo';
            }
            modal.classList.remove('active');
        });
    }
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

// --- BUSCADOR DOCENTES ---
const buscadorDocente = document.getElementById('buscador-docente');
if (buscadorDocente) {
    buscadorDocente.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.tabla-placeholder .data-table tbody tr');

        filas.forEach(function(fila) {
            const id = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro)) ? '' : 'none';
        });
    });
}

// --- BUSCADOR ESTUDIANTES ---
const buscadorEstudiante = document.getElementById('buscador-estudiante');
if (buscadorEstudiante) {
    buscadorEstudiante.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.tabla-placeholder .data-table tbody tr');

        filas.forEach(function(fila) {
            const id = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro)) ? '' : 'none';
        });
    });
}
// --- BUSCADOR CURSOS ---
const buscadorCurso = document.getElementById('buscador-curso');
if (buscadorCurso) {
    buscadorCurso.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.data-table tbody tr');
        
        console.log('Filas encontradas:', filas.length); // para ver si encuentra las filas

        filas.forEach(function(fila) {
            const id = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro)) ? '' : 'none';
        });
    });
}


