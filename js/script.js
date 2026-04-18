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

         //obtiene los prerequisitos del curso actual
        const prerequisitos = this.dataset.prerrequisitos
                             ? this.dataset.prerrequisitos.split(",") 
                             :[];
    
    const select = document.getElementById('edit-prerrequisitos');

    //limpia opción previa
    Array.from(select.options).forEach(option =>{
        option.selected = false;
    });
    
    //Selecciona prerequisito correcto
    prerequisitos.forEach(id =>{
        const option = select.querySelector(`option[value="${id}"]`);
        if(option) option.selected = true;
    });
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

// --- TOGGLE ESTADO ---  

// - Detecta clic en el botón de estado (activo/inactivo)
// - Abre un modal de confirmación antes de cambiar el estado
// - Al aceptar, verifica la clase `estado-activo` para invertir el estado (toggle)
// - Actualiza inmediatamente el texto y estilos del botón en la interfaz
// - Cambia visualmente la fila (gris si está inactivo)
// - Bloquea botones de editar y horarios cuando está inactivo
// - Reordena la fila dinámicamente:
//     * Inactivos se envían al final
//     * Activos se insertan en su posición correcta por ID
// - Mantiene estilos y bloqueos al recargar la página
// - Envía el ID al servidor con fetch para guardar el cambio en la base de datos sin recargar
document.addEventListener('click', function(e) {

    const btn = e.target.closest('.btn-toggle-estado');
    if (!btn) return;

    e.preventDefault();

    const modal = document.getElementById('customConfirmModal');
    const mTitle = document.getElementById('customModalTitle');
    const mText = document.getElementById('customModalText');
    const bCancel = document.getElementById('customBtnCancel');
    const bAccept = document.getElementById('customBtnAccept');

    const isActivo = btn.classList.contains('estado-activo');

    // detectar tipo
    let tipo = 'curso';
    if (document.getElementById('buscador-docente')) tipo = 'docente';
    else if (document.getElementById('buscador-estudiante')) tipo = 'estudiante';

    mTitle.innerText = isActivo 
        ? `¿Desactivar ${tipo}?` 
        : `¿Activar ${tipo}?`;

    mText.innerText = isActivo 
        ? `Pasará a Inactivo.` 
        : `Pasará a Activo.`;

    modal.classList.add('active');

    bCancel.onclick = () => modal.classList.remove('active');

    bAccept.onclick = function() {

        const fila = btn.closest('tr');
        const id = fila.dataset.id;

        let archivo = '';
        if (document.getElementById('buscador-docente')) archivo = 'toggle-estado-docente.php';
        else if (document.getElementById('buscador-estudiante')) archivo = 'toggle-estado-estudiante.php';
        else if (document.getElementById('buscador-curso')) archivo = 'toggle-estado-curso.php';

        // CAMBIO VISUAL INMEDIATO
        if (document.getElementById('buscador-curso')) {
            if (isActivo) {
                btn.classList.remove('estado-activo');
                btn.classList.add('estado-inactivo');
                btn.textContent = 'Activar';
            } else {
                btn.classList.remove('estado-inactivo');
                btn.classList.add('estado-activo');
                btn.textContent = 'Desactivar';
            }
        } else {
            if (isActivo) {
                btn.classList.remove('estado-activo');
                btn.classList.add('estado-inactivo');
                btn.textContent = 'Inactivo';
            } else {
                btn.classList.remove('estado-inactivo');
                btn.classList.add('estado-activo');
                btn.textContent = 'Activo';
            }
        }

        const celdaEstado = fila.querySelector('td[data-label="Estado"]');
        if (celdaEstado) {
            celdaEstado.textContent = isActivo ? 'Inactivo' : 'Activo';
        }

        // --- COLOR GRIS Y BLOQUEO (GENERAL PARA TODOS) ---
        const btnEditar = fila.querySelector('.abrir-modal-curso, .abrir-modal-docente, .abrir-modal-estudiante');
        const btnHorarios = fila.querySelector('.horarios');

        if (isActivo) {
            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '#e9ecef';
                td.style.color = '#6c757d';
                td.style.opacity = '0.7';
            });

            if (btnEditar) {
                btnEditar.style.pointerEvents = 'none';
                btnEditar.style.opacity = '0.5';
            }

            if (btnHorarios) {
                btnHorarios.style.pointerEvents = 'none';
                btnHorarios.style.opacity = '0.5';
            }

        } else {
            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '';
                td.style.color = '';
                td.style.opacity = '';
            });

            if (btnEditar) {
                btnEditar.style.pointerEvents = '';
                btnEditar.style.opacity = '';
            }

            if (btnHorarios) {
                btnHorarios.style.pointerEvents = '';
                btnHorarios.style.opacity = '';
            }
        }

        // --- MOVER FILA (AHORA PARA TODOS) ---
        const tbody = fila.parentElement;

        if (isActivo) {
            // desactivar → abajo
            tbody.appendChild(fila);
        } else {
            // activar → ordenar por ID
            const filas = Array.from(tbody.querySelectorAll('tr'));

            let insertado = false;

            for (let f of filas) {
                if (f === fila) continue;

                const idActual = parseInt(f.dataset.id);
                const idNuevo = parseInt(id);

                if (idNuevo < idActual) {
                    tbody.insertBefore(fila, f);
                    insertado = true;
                    break;
                }
            }

            if (!insertado) {
                tbody.appendChild(fila);
            }
        }

        modal.classList.remove('active');

        fetch(archivo, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(res => res.json())
        .then(data => {
            console.log('Guardado en BD:', data);
        })
        .catch(err => {
            console.error('Error:', err);
        });
    };
});

// === INICIALIZA ESTADOS AL RECARGAR ===
// Aplica gris y bloquea filas inactivas según su botón,
// ordena activos por ID y envía los inactivos al final
// sin eliminar ni modificar la tabla original.

document.addEventListener('DOMContentLoaded', function () {

    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    const filas = Array.from(tbody.querySelectorAll('tr'));

    const activos = [];
    const inactivos = [];

    filas.forEach(fila => {

        const btnEstado = fila.querySelector('.btn-toggle-estado');
        if (!btnEstado) return;

        const esInactivo = btnEstado.classList.contains('estado-inactivo');

        const btnEditar = fila.querySelector('.abrir-modal-docente, .abrir-modal-estudiante, .abrir-modal-curso');
        const btnHorarios = fila.querySelector('.horarios');

        if (esInactivo) {

            // aplicar gris
            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '#e9ecef';
                td.style.color = '#6c757d';
                td.style.opacity = '0.7';
            });

            // bloquear
            if (btnEditar) {
                btnEditar.style.pointerEvents = 'none';
                btnEditar.style.opacity = '0.5';
            }

            if (btnHorarios) {
                btnHorarios.style.pointerEvents = 'none';
                btnHorarios.style.opacity = '0.5';
            }

            inactivos.push(fila);

        } else {
            activos.push(fila);
        }
    });

    // ordenar activos por ID
    activos.sort((a, b) => {
        const idA = parseInt(a.dataset.id);
        const idB = parseInt(b.dataset.id);
        return idA - idB;
    });

    // reordenar correctamente
    [...activos, ...inactivos].forEach(fila => {
        tbody.appendChild(fila);
    });

});

// --- APLICAR ESTILO Y BLOQUEO AL CARGAR ---
document.addEventListener('DOMContentLoaded', function() {

    const filas = document.querySelectorAll('tbody tr');

    filas.forEach(fila => {
        const estado = fila.querySelector('td[data-label="Estado"]');
        if (!estado) return;

        if (estado.textContent.trim() === 'Inactivo') {

            const btnEditar = fila.querySelector('.abrir-modal-curso');
            const btnHorarios = fila.querySelector('.horarios');

            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '#e9ecef';
                td.style.color = '#6c757d';
                td.style.opacity = '0.7';
            });

            if (btnEditar) {
                btnEditar.style.pointerEvents = 'none';
                btnEditar.style.opacity = '0.5';
            }

            if (btnHorarios) {
                btnHorarios.style.pointerEvents = 'none';
                btnHorarios.style.opacity = '0.5';
            }
        }
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

// --- BUSCADOR DOCENTES ---
const buscadorDocente = document.getElementById('buscador-docente');
if (buscadorDocente) {
    buscadorDocente.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.tabla-placeholder .data-table tbody tr');

        filas.forEach(function(fila) {
          const id = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();
            const apellido = fila.cells[2].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro) || apellido.includes(filtro)) ? '' : 'none';
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
            const apellido = fila.cells[2].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro) || apellido.includes(filtro)) ? '' : 'none';
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
            const nombre = fila.cells[0].textContent.toLowerCase();

            fila.style.display = (nombre.includes(filtro)) ? '' : 'none';
        });
    });
}

// Función que valida que se pueda deseleccionar el prerrequisito sin Ctrl y evita seleccionarse a sí mismo
    document.querySelectorAll('.select-prerrequisitos').forEach(select =>{
        // Usamos pointerdown para soportar mouse y touch por igual
        select.addEventListener('pointerdown', function(e) {
        const option = e.target;
        e.preventDefault(); 

        if (option.tagName === 'OPTION') {
                const idCursoActual = document.getElementById('edit-id-curso')
                ? document.getElementById('edit-id-curso').value 
                : null;
                // --- VALIDACIÓN: NO PUEDE SER SU PROPIO PRERREQUISITO ---
            if (idCursoActual && option.value === idCursoActual) {
                mostrarToastPremium('El curso no puede ser su propio prerrequisito', 'error');
                return;
            }
            // Toggle manual de selección
            option.selected = !option.selected;
            // Disparamos evento 'change' manualmente
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
});


// SISTEMA DE NOTIFICACIONES

function mostrarToastPremium(mensaje, tipo = 'error') {
    // Eliminar toasts anteriores
    const toastsPrevios = document.querySelectorAll('.toast-premium');
    toastsPrevios.forEach(t => t.remove());
    const toast = document.createElement('div');
    toast.className = `toast-premium toast-${tipo}`;
    
    // Icono según tipo
    const icono = tipo === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-check-circle"></i>';
    
    toast.innerHTML = `${icono} <span>${mensaje}</span>`;
    document.body.appendChild(toast);
    // Animación de entrada y salida
    setTimeout(() => {
        toast.classList.add('visible');
    }, 100);
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}
// -- CATÁLOGOS HORARIOS ---
let catalogoHorarios =[];
let catalogoAulas =[];

async function cargarCatalogos(){
    if (catalogoHorarios.length > 0) return;
    try{
        const res = await fetch('obtener-horarios-aulas.php');
        const data = await res.json();
        catalogoHorarios = data.horarios;
        catalogoAulas = data.aulas;
    } catch {
        mostrarToastPremium('Error al cargar el catálogo de horarios')
    }
}

function llenarSelects(card){
    const horarioSelect = card.querySelector('.horario-select');
    const aulaSelect = card.querySelector('.aula-select');

    horarioSelect.innerHTML = '<option value="">Seleccione un rango</option>';
    aulaSelect.innerHTML    = '<option value="">Seleccione salón</option>';

    catalogoHorarios.forEach(h => {
        const opt = document.createElement('option');
        opt.value       = h.id;
        opt.textContent = h.etiqueta;
        horarioSelect.appendChild(opt);
    });

     catalogoAulas.forEach(a => {
        const opt = document.createElement('option');
        opt.value       = a.id;
        opt.textContent = a.aula;
        aulaSelect.appendChild(opt);
    });
}

// --- LÓGICA MODAL HORARIOS PREMIUM ---
function agregarBloqueHorario() {
    const container = document.getElementById('bloques-horario-container');
    const template = document.getElementById('template-horario-card');
    if (!container || !template) return;
    const clone = template.content.cloneNode(true);
    const card = clone.querySelector('.horario-card-registro');
    llenarSelects(card);
    container.appendChild(clone);
}
async function abrirModalHorarios(idCurso) {
    const modal = document.getElementById('modalHorarios');
    const container = document.getElementById('bloques-horario-container');
    if (!modal || !container) return;
    
    await cargarCatalogos();
    
    // Guardar ID del curso en el modal para referencia
    modal.dataset.idCurso = idCurso;
    
    // Limpiar container y agregar un bloque inicial
    container.innerHTML = '';
    //cargar los horarios ya guardados
    try{
        const res    = await fetch(`obtener-horarios-cursos.php?idCurso=${idCurso}`);
        const bloques = await res.json();

        if (bloques.length > 0) {
            bloques.forEach(bloque => {
                agregarBloqueHorario();
                // Seleccionar el último bloque agregado
                const cards = container.querySelectorAll('.horario-card-registro');
                const card  = cards[cards.length - 1];

                // Marcar el día
                card.querySelectorAll('.dia-tag').forEach(tag => {
                    if (bloque.dias.includes(tag.dataset.dia)){
                        tag.classList.add('active');
                    }
                });

                // Seleccionar horario y aula
                card.querySelector('.horario-select').value = bloque.idHorario;
                card.querySelector('.aula-select').value    = bloque.idAula;
            });

    }else{
         agregarBloqueHorario(); //sin horarios si falla

    }
}catch{
     agregarBloqueHorario(); //si falla muestra un bloque vacío
}
   
    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
}
function cerrarModalHorarios() {
    const modal = document.getElementById('modalHorarios');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}
// Cerrar al hacer clic fuera
const modalHorarios = document.getElementById('modalHorarios');
if (modalHorarios) {
    modalHorarios.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalHorarios();
    });
}
// Botón Agregar Bloque
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-agregar-horario')) {
        agregarBloqueHorario();
    }
});
// Botón Eliminar Bloque (X)
document.addEventListener('click', function(e) {
    const btnCerrar = e.target.closest('.horario-card-cerrar');
    if (btnCerrar) {
        const card = btnCerrar.closest('.horario-card-registro');
        const container = document.getElementById('bloques-horario-container');
        
        // No permitir borrar si es el único bloque
        if (container.querySelectorAll('.horario-card-registro').length > 1) {
            card.remove();
        } else {
            mostrarToastPremium('Debe haber al menos un bloque de horario');
        }
    }
});
// Selección de Días Tags (Delegación de eventos para bloques dinámicos)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dia-tag')) {
        e.target.classList.toggle('active');
    }
});
// Botón Guardar Horarios
const btnGuardarHorarios = document.getElementById('btn-guardar-horarios');
if (btnGuardarHorarios) {
    btnGuardarHorarios.addEventListener('click', async function() {
        const modal = document.getElementById('modalHorarios');
        const idCurso = modal.dataset.idCurso;
        const cards = document.querySelectorAll('.horario-card-registro');
        
        const bloques = [];
        let valid = true;
        cards.forEach(card => {
            const diasSeleccionados = Array.from(card.querySelectorAll('.dia-tag.active')).map(t => t.dataset.dia);
            const horario = card.querySelector('.horario-select').value;
            const aula = card.querySelector('.aula-select').value;
            if (diasSeleccionados.length === 0 || !horario || !aula) {
                valid = false;
            }
            bloques.push({
                dias: diasSeleccionados,
                horario: horario,
                aula: aula
            });
        });
        if (!valid) {
            mostrarToastPremium('Complete todos los campos de cada bloque de horario');
            return;
        }
        // Estructura de datos final
        const data = {
            idCurso: idCurso,
            bloques: bloques
        };
        console.log('Datos consolidados para Backend:', data);
        
        try {
    const res  = await fetch('guardar-horarios.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(data)
    });
    const respuesta = await res.json();

    if (respuesta.success) {
        mostrarToastPremium('Horarios guardados correctamente', 'success');
        setTimeout(() => cerrarModalHorarios(), 1500);
    } else {
        mostrarToastPremium(respuesta.message || 'Error al guardar');
    }
} catch {
    mostrarToastPremium('Error de conexión');
}
    });
}

