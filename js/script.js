// Login ---------------------------------------------

// Valida que el formulario de login no tenga campos vacíos antes de enviar
const formulario = document.getElementById("formulario-inicio");
const btnEntrar = document.querySelector(".btn-entrar");

if (formulario) {
    formulario.addEventListener("submit", function (e) {

        const correo = document.getElementById('correo-inicio').value.trim();
        const contrasena = document.getElementById('contrasena').value.trim();

        // Si algún campo está vacío, cancela el envío y muestra alerta
        if (correo === "" || contrasena === "") {
            e.preventDefault();
            alert("Complete todos los campos.");
            return;
        }
    });
}

// Modales -------------------------------------------

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
// - Al aceptar, envía la petición al servidor PRIMERO antes de hacer cambios visuales
// - Si el servidor rechaza (error), muestra un toast y detiene la acción sin tocar la interfaz
// - Si el servidor aprueba, actualiza visualmente el botón, la fila y la celda de estado
// - Cambia visualmente la fila (gris si está inactivo)
// - Bloquea botones de editar y horarios cuando está inactivo
// - Al desactivar un curso, limpia visualmente la celda de docente
// - No pueden haber dos plazos activos al mismo tiempo
// - Reordena la fila dinámicamente:
//     * Inactivos se envían al final
//     * Cursos activos se reinsertan en orden alfabético por nombre
//     * Docentes y estudiantes activos se reinsertan en orden por ID
// - Mantiene estilos y bloqueos al recargar la página

document.addEventListener('click', function (e) {

    const btn = e.target.closest('.btn-toggle-estado');
    if (!btn) return;

    e.preventDefault();

    const modal = document.getElementById('customConfirmModal');
    const mTitle = document.getElementById('customModalTitle');
    const mText = document.getElementById('customModalText');
    const bCancel = document.getElementById('customBtnCancel');
    const bAccept = document.getElementById('customBtnAccept');

    const isActivo = btn.classList.contains('estado-activo');

    let tipo = 'curso';
    if (document.getElementById('buscador-docente')) tipo = 'docente';
    else if (document.getElementById('buscador-estudiante')) tipo = 'estudiante';
    else if (document.getElementById('buscador-periodo')) tipo = 'periodo'
    else if (document.getElementById('buscador-plazo')) tipo = 'plazo';


    mTitle.innerText = isActivo
        ? `¿Desactivar ${tipo}?`
        : `¿Activar ${tipo}?`;
    if (isActivo) {
        if (tipo === 'curso') {
            mText.innerText = `El curso pasará a Inactivo. Se eliminará el docente y los horarios asignados para liberar los cupos.`;
        } else {
            mText.innerText = `Pasará a Inactivo.`;
        }
    } else {
        mText.innerText = `Pasará a Activo.`;
    }

    modal.classList.add('active');

    bCancel.onclick = () => modal.classList.remove('active');
    bAccept.onclick = async function () {

        const fila = btn.closest('tr');
        const id = fila.dataset.id;

        let archivo = '';

        if (document.getElementById('buscador-docente')) archivo = '../api/admin/toggle-estado-docente.php';
        else if (document.getElementById('buscador-estudiante')) archivo = '../api/admin/toggle-estado-estudiante.php';
        else if (document.getElementById('buscador-curso')) archivo = '../api/admin/toggle-estado-curso.php';
        else if (document.getElementById('buscador-periodo')) archivo = '../api/admin/toggle-estado-periodo.php';
        else if (document.getElementById('buscador-plazo')) archivo = '../api/admin/toggle-estado-plazo.php';



        const res = await fetch(archivo, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        });
        const data = await res.json();

        if (data.error) {
            mostrarToastPremium(data.mensaje, 'error');
            modal.classList.remove('active');
            return;
        }

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

        if (isActivo && document.getElementById('buscador-curso')) {
            const celdaDocente = fila.querySelector('td[data-label="Docente"]');
            if (celdaDocente) celdaDocente.textContent = '—';
        }

        const btnEditar = fila.querySelector('.abrir-modal-periodo,.abrir-modal-curso, .abrir-modal-docente, .abrir-modal-estudiante, .abrir-modal-plazo');
        const btnHorarios = fila.querySelector('.horarios');

        if (isActivo) {
            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '#e9ecef';
                td.style.color = '#6c757d';
                td.style.opacity = '0.7';
            });
            if (btnEditar) { btnEditar.style.pointerEvents = 'none'; btnEditar.style.opacity = '0.5'; }
            if (btnHorarios) { btnHorarios.style.pointerEvents = 'none'; btnHorarios.style.opacity = '0.5'; }
        } else {
            fila.querySelectorAll('td').forEach(td => {
                td.style.backgroundColor = '';
                td.style.color = '';
                td.style.opacity = '';
            });
            if (btnEditar) { btnEditar.style.pointerEvents = ''; btnEditar.style.opacity = ''; }
            if (btnHorarios) { btnHorarios.style.pointerEvents = ''; btnHorarios.style.opacity = ''; }
        }
        const tbody = fila.parentElement;

        if (isActivo) {
            tbody.appendChild(fila);
        } else {
            const filas = Array.from(tbody.querySelectorAll('tr'));
            let insertado = false;

            if (document.getElementById('buscador-curso')) {
                const nombreNuevo = fila.cells[0].textContent.trim().toLowerCase();
                for (let f of filas) {
                    if (f === fila) continue;
                    const btnF = f.querySelector('.btn-toggle-estado');
                    if (btnF && btnF.classList.contains('estado-inactivo')) continue;
                    const nombreActual = f.cells[0].textContent.trim().toLowerCase();
                    if (nombreNuevo.localeCompare(nombreActual) < 0) {
                        tbody.insertBefore(fila, f);
                        insertado = true;
                        break;
                    }
                }
            } else {
                for (let f of filas) {
                    if (f === fila) continue;
                    const btnF = f.querySelector('.btn-toggle-estado');
                    if (btnF && btnF.classList.contains('estado-inactivo')) continue;
                    if (parseInt(fila.dataset.id) < parseInt(f.dataset.id)) {
                        tbody.insertBefore(fila, f);
                        insertado = true;
                        break;
                    }
                }
            }

            if (!insertado) {
                const primerInactivo = Array.from(tbody.querySelectorAll('tr')).find(f =>
                    f.querySelector('.btn-toggle-estado')?.classList.contains('estado-inactivo')
                );
                primerInactivo ? tbody.insertBefore(fila, primerInactivo) : tbody.appendChild(fila);
            }
        }

        modal.classList.remove('active');
        window.location.reload();
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

        const btnEditar = fila.querySelector('abrir-modal-periodo, .abrir-modal-docente, .abrir-modal-estudiante, .abrir-modal-curso, abrir-modal-plazo');
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

    // ordenar activos por nombre alafabéticamente
    activos.sort((a, b) => {
        if (document.getElementById('buscador-curso')) {
            const nombreA = a.cells[0].textContent.trim().toLowerCase();
            const nombreB = b.cells[0].textContent.trim().toLowerCase();
            return nombreA.localeCompare(nombreB);
        } else {
            return parseInt(a.dataset.id) - parseInt(b.dataset.id);
        }
    });

    // reordenar correctamente
    [...activos, ...inactivos].forEach(fila => {
        tbody.appendChild(fila);
    });

});

// --- APLICAR ESTILO Y BLOQUEO AL CARGAR ---
document.addEventListener('DOMContentLoaded', function () {

    const filas = document.querySelectorAll('tbody tr');

    filas.forEach(fila => {
        const estado = fila.querySelector('td[data-label="Estado"]');
        if (!estado) return;

        if (estado.textContent.trim() === 'Inactivo') {

            const btnEditar = fila.querySelector('.abrir-modal-periodo, .abrir-modal-curso, .abrir-modal-plazo');
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
//-- funcion para nuevo curso, nuevo docente o nuevo estudiante, dependiendo de cuál exista en la página, para evitar duplicar código al tener un botón "+ Nuevo" que abre diferentes modales según la página en la que se encuentre el admin
const btnNuevo = document.querySelector('.btn-nuevo');

if (btnNuevo) {
    btnNuevo.addEventListener('click', function () {

        const modalNuevoCurso = document.getElementById('modalNuevoCurso');
        const modalNuevoDocente = document.getElementById('modalNuevoDocente');
        const modalNuevo = document.getElementById('modalNuevo');
        const modalPeriodo = document.getElementById('modalPeriodo');

        if (modalNuevoCurso) {
            modalNuevoCurso.classList.add('activo');
            cargarPeriodos('idPeriodo')
        } else if (modalNuevoDocente) {
            modalNuevoDocente.classList.add('activo');
        } else if (modalNuevo) {
            modalNuevo.classList.add('activo');
        } else if (modalPeriodo) {
            abrirModalNuevoPeriodo();
        }

        document.body.style.overflow = 'hidden';
    });
}


// Utilidades ----------------------------------------

// --- SEGURIDAD DE SESIÓN ---

// Evita que el navegador muestre páginas del admin desde caché al retroceder sin sesión
window.onpageshow = function (event) {
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
    inputContrasena.addEventListener("input", function () {
        spanOjo.style.opacity = this.value.length > 0 ? "1" : "0";
    });
}



document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (typeof cerrarModalDocente === 'function') cerrarModalDocente();
        if (typeof cerrarModalNuevoDocente === 'function') cerrarModalNuevoDocente();
        if (typeof cerrarModal === 'function') cerrarModal();
        if (typeof cerrarModalCurso === 'function') cerrarModalCurso();
        if (typeof cerrarModalNuevoCurso === 'function') cerrarModalNuevoCurso();
        if (typeof cerrarModalPeriodo === 'function') cerrarModalPeriodo();
        if (typeof cerrarModalInscripcion === 'function') cerrarModalInscripcion();
    }
});

// Inscripciones -------------------------------------
// Lee todos los datos desde data-* del botón (el PHP los inyecta en render time).
// No requiere fetch adicional: evita una petición extra por cada clic.
let cursoInscripcionId  = null;
let btnInscripcionActual = null;

function abrirModalInscripcion(btn) {
    cursoInscripcionId  = btn.dataset.id;
    btnInscripcionActual = btn;

    document.getElementById('modalCursoNombre').textContent      = btn.dataset.nombre     || '';
    document.getElementById('modalCursoDescripcion').textContent = btn.dataset.descripcion || '';

    const elDocente = document.getElementById('modalCursoDocente');
    if (elDocente) elDocente.textContent = btn.dataset.docente || 'Sin docente asignado';

    document.getElementById('modalCursoHorario').textContent = btn.dataset.horario || 'Sin horario asignado';

    const elDias = document.getElementById('modalCursoDias');
    if (elDias) elDias.textContent = btn.dataset.dias || '—';

    document.getElementById('modalCursoAula').textContent    = btn.dataset.aula  || 'Sin aula asignada';
    document.getElementById('modalCursoFecha').textContent   = btn.dataset.fecha || '';
    document.getElementById('modalCursoCosto').textContent   = btn.dataset.costo || '';
    document.getElementById('modalCursoCupos').textContent   = btn.dataset.cupos || '';

    const modal = document.getElementById('modalInscripcion');
    if (!modal) return;
    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
}

function cerrarModalInscripcion() {
    const modal = document.getElementById('modalInscripcion');
    if (!modal) return;

    modal.classList.remove('activo');
    document.body.style.overflow = '';
}

// Cerrar modal de inscripción al hacer clic en el overlay
const modalInscripcion = document.getElementById('modalInscripcion');
if (modalInscripcion) {
    modalInscripcion.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalInscripcion();
    });
}

const btnConfirmarInscripcion = document.getElementById('btnConfirmarInscripcion');
if (btnConfirmarInscripcion) {
    btnConfirmarInscripcion.addEventListener('click', function () {
        validarInscripcion(cursoInscripcionId, btnInscripcionActual);
    });
}

// -- VALIDACIÓN DE INSCRIPCIÓN
// Valida la inscripcion y mantiene el modal abierto si la respuesta trae errores.
async function validarInscripcion(idCurso, btn) {
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('curso_id', idCurso);

        const res = await fetch('validar-inscripcion.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            cerrarModalInscripcion();
            mostrarToastPremium(data.mensaje || 'Inscripción exitosa', 'success');
            setTimeout(() => window.location.reload(), 1900);
        } else {
            // Mostrar el error sin cerrar el modal para que el usuario intente de nuevo
            mostrarToastPremium(data.mensaje || 'No puedes inscribirte', 'error');
        }
    } catch (err) {
        mostrarToastPremium('Error de conexión. Ocurrió un problema. Intenta de nuevo.', 'error');
    } finally {
        btn.disabled = false;
    }
}

// 


// INSCRIPCIÓN DE CURSOS (estudiante-inscripciones.php)
// Gestiona: selección múltiple de cursos (máx 5), barra emergente inferior,
// modal de pago y notificaciones toast para estudiantes.

// Variables globales para rastrear cursos seleccionados
let cursosSeleccionados = []; // Array de objetos {id, nombre, costo}
let totalCursos = 0;           // Contador de cursos seleccionados
let totalCosto = 0;            // Suma total del costo de cursos

//  Toggle del sidebar en móvil (abrir/cerrar menú lateral)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('open');      // Activa/desactiva clase 'open'
    overlay.classList.toggle('active');    // Muestra/oculta overlay oscuro
    if (toggle) toggle.checked = sidebar.classList.contains('open'); // Sincroniza checkbox
}

//  Cerrar sidebar en móvil
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !overlay) return;
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    if (toggle) toggle.checked = false;
}

function togglePagosOnline() {
    const menu = document.getElementById('pagosOnlineMenu');
    if (!menu) return;

    // FRONTEND: permite contraer/expandir el submenu de Pagos en linea.
    // La pagina activa sigue marcada por el enlace hijo con clase "active".
    const dropdown = menu.closest('.nav-dropdown');
    const toggle = dropdown?.querySelector('.nav-dropdown-toggle');
    const estaAbierto = dropdown ? dropdown.classList.toggle('open') : menu.classList.toggle('open');

    menu.classList.toggle('open', estaAbierto);
    if (toggle) toggle.setAttribute('aria-expanded', estaAbierto ? 'true' : 'false');
}

// Contenidos estudiante -----------------------------

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-sidebar-toggle').forEach(btn => {
        btn.addEventListener('click', toggleSidebar);
    });

    document.querySelectorAll('.js-sidebar-close, .js-sidebar-overlay').forEach(btn => {
        btn.addEventListener('click', closeSidebar);
    });

    document.querySelectorAll('.js-pagos-toggle').forEach(btn => {
        btn.addEventListener('click', togglePagosOnline);
    });

    inicializarFiltrosContenidosEstudiante();
    inicializarTareasEstudiante();
});

function inicializarFiltrosContenidosEstudiante() {
    const lista = document.getElementById('contenidosLista');
    if (!lista) return;

    const buscador = document.getElementById('contenidoBuscar');
    const tipoFiltro = document.getElementById('contenidoTipoFiltro');
    const ordenFiltro = document.getElementById('contenidoOrdenFiltro');
    const empty = document.getElementById('contenidosEmptyFiltro');
    const items = Array.from(lista.querySelectorAll('.contenido-publicado-item'));

    function aplicarFiltros() {
        const termino = (buscador?.value || '').trim().toLowerCase();
        const tipo = (tipoFiltro?.value || '').toLowerCase();

        const orden = ordenFiltro?.value || 'recientes';
        items
            .sort((a, b) => {
                const fechaA = new Date(a.dataset.date || '1900-01-01').getTime();
                const fechaB = new Date(b.dataset.date || '1900-01-01').getTime();
                return orden === 'antiguos' ? fechaA - fechaB : fechaB - fechaA;
            })
            .forEach(item => lista.appendChild(item));

        let visibles = 0;
        items.forEach(item => {
            const coincideTexto = !termino || (item.dataset.title || '').includes(termino);
            const coincideTipo = !tipo || item.dataset.type === tipo;
            const visible = coincideTexto && coincideTipo;
            item.classList.toggle('is-hidden', !visible);
            if (visible) visibles++;
        });

        if (empty) empty.classList.toggle('is-visible', visibles === 0);
    }

    buscador?.addEventListener('input', aplicarFiltros);
    tipoFiltro?.addEventListener('change', aplicarFiltros);
    ordenFiltro?.addEventListener('change', aplicarFiltros);
}

function inicializarTareasEstudiante() {
    const lista = document.getElementById('tareasLista');
    if (!lista) return;

    // Elementos del submodulo de tareas del estudiante.
    const buscador = document.getElementById('tareaBuscar');
    const estadoFiltro = document.getElementById('tareaEstadoFiltro');
    const ordenFiltro = document.getElementById('tareaOrdenFiltro');
    const empty = document.getElementById('tareasEmptyFiltro');
    const modal = document.getElementById('modalEntregaTarea');
    const form = document.getElementById('formEntregaTarea');
    const listaAdjuntos = document.getElementById('listaEntregaAdjuntos');
    const btnArchivo = document.getElementById('btnEntregaArchivo');
    const btnEnlace = document.getElementById('btnEntregaEnlace');
    const tareaId = document.getElementById('entregaTareaId');
    const tareaNombre = document.getElementById('entregaTareaNombre');
    const tareaMeta = document.getElementById('entregaTareaMeta');
    const tareaModo = document.getElementById('entregaTareaModo');
    const tareaTituloModal = document.getElementById('entregaTareaTitulo');
    const tareaSubmit = document.getElementById('entregaTareaSubmit');
    let tareaActiva = null;
    let entregaAdjuntoCount = 0;

    const items = Array.from(lista.querySelectorAll('.tarea-estudiante-item'));

    function fechaHoraEntregaActual() {
        return new Intl.DateTimeFormat('es-SV', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date());
    }

    // Filtra y ordena las tareas visibles.
    function aplicarFiltros() {
        const termino = (buscador?.value || '').trim().toLowerCase();
        const estado = (estadoFiltro?.value || '').toLowerCase();
        const orden = ordenFiltro?.value || 'proximas';

        items
            .sort((a, b) => {
                const fechaA = new Date(a.dataset.date || '1900-01-01').getTime();
                const fechaB = new Date(b.dataset.date || '1900-01-01').getTime();
                return orden === 'recientes' ? fechaB - fechaA : fechaA - fechaB;
            })
            .forEach(item => lista.appendChild(item));

        let visibles = 0;
        items.forEach(item => {
            const coincideTexto = !termino || (item.dataset.title || '').includes(termino);
            const coincideEstado = !estado || item.dataset.status === estado;
            const visible = coincideTexto && coincideEstado;
            item.classList.toggle('is-hidden', !visible);
            if (visible) visibles++;
        });

        if (empty) empty.classList.toggle('is-visible', visibles === 0);
    }

    // Cierra el modal y limpia los adjuntos temporales.
    function cerrarModalEntrega() {
        modal?.classList.remove('activo');
        modal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        form?.reset();
        if (listaAdjuntos) listaAdjuntos.innerHTML = '';
        tareaActiva = null;
    }

    // Crea un adjunto de archivo o enlace con el mismo patron visual del docente.
    function crearAdjuntoEntrega(tipo) {
        entregaAdjuntoCount++;
        const id = `entrega_adj_${entregaAdjuntoCount}`;
        const item = document.createElement('div');
        item.className = 'adjunto-item';
        item.dataset.id = id;
        item.dataset.tipo = tipo;

        if (tipo === 'Archivo') {
            item.innerHTML = `
                <span class="adjunto-tipo"><i class="fas fa-paperclip"></i> Archivo</span>
                <label class="adjunto-file-label">
                    <i class="fas fa-folder-open"></i>
                    <span class="adjunto-file-texto">Seleccionar archivo</span>
                    <input type="file"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                        class="adjunto-file-input">
                </label>
                <button type="button" class="adjunto-remove" data-id="${id}" title="Quitar adjunto">
                    <i class="fas fa-times"></i>
                </button>
            `;

            item.querySelector('.adjunto-file-input')?.addEventListener('change', function () {
                const texto = item.querySelector('.adjunto-file-texto');
                if (texto) texto.textContent = this.files[0]?.name || 'Seleccionar archivo';
            });
        } else {
            item.innerHTML = `
                <span class="adjunto-tipo"><i class="fas fa-link"></i> Enlace</span>
                <input type="text" class="entrega-enlace-nombre" placeholder="Nombre del enlace">
                <input type="url" class="entrega-enlace-url" placeholder="https://...">
                <button type="button" class="adjunto-remove" data-id="${id}" title="Quitar adjunto">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }

        return item;
    }

    // Requiere al menos un archivo seleccionado o un enlace completo.
    function tieneAdjuntoEntregaValido() {
        if (!listaAdjuntos) return false;
        return Array.from(listaAdjuntos.querySelectorAll('.adjunto-item')).some(item => {
            if (item.dataset.tipo === 'Archivo') {
                return item.querySelector('.adjunto-file-input')?.files?.length > 0;
            }

            const nombre = item.querySelector('.entrega-enlace-nombre')?.value.trim();
            const url = item.querySelector('.entrega-enlace-url')?.value.trim();
            return Boolean(nombre && url);
        });
    }

    // Prepara el modal con textos distintos para una entrega nueva o un reemplazo visual.
    lista.addEventListener('click', function (event) {
        const btn = event.target.closest('.btn-entregar-tarea');
        if (!btn || btn.disabled) return;

        const esReemplazo = btn.dataset.accion === 'reemplazar';
        const intentos = parseInt(btn.dataset.intentos || '0', 10);
        const intentosMax = parseInt(btn.dataset.intentosMax || '3', 10);
        const siguienteIntento = Math.min(intentos + 1, intentosMax);
        tareaActiva = btn.closest('.tarea-estudiante-item');
        if (tareaId) tareaId.value = btn.dataset.tareaId || '';
        if (tareaNombre) tareaNombre.textContent = btn.dataset.titulo || 'Tarea seleccionada';
        if (tareaMeta) {
            tareaMeta.textContent = `Entrega: ${btn.dataset.fecha || 'Por definir'} · ${btn.dataset.puntaje || '0'} pts · Intento ${siguienteIntento}/${intentosMax}`;
        }
        if (tareaTituloModal) {
            tareaTituloModal.innerHTML = `<i class="fas fa-file-arrow-up"></i> ${esReemplazo ? 'Reemplazar entrega' : 'Entregar tarea'}`;
        }
        if (tareaModo) {
            tareaModo.textContent = esReemplazo
                ? `Selecciona el nuevo archivo o enlace. Te quedarían ${Math.max(intentosMax - siguienteIntento, 0)} intento(s) después de este reemplazo.`
                : `Agrega el archivo o enlace que enviarás para esta tarea. Tienes ${intentosMax} intentos en total.`;
        }
        if (tareaSubmit) tareaSubmit.textContent = esReemplazo ? 'Reemplazar entrega' : 'Marcar como entregada';
        if (form) form.dataset.accion = esReemplazo ? 'reemplazar' : 'entregar';

        modal?.classList.add('activo');
        modal?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (listaAdjuntos && !listaAdjuntos.children.length) {
            listaAdjuntos.appendChild(crearAdjuntoEntrega('Archivo'));
        }
    });

    // Agrega adjuntos nuevos a la entrega.
    btnArchivo?.addEventListener('click', function () {
        listaAdjuntos?.appendChild(crearAdjuntoEntrega('Archivo'));
    });

    btnEnlace?.addEventListener('click', function () {
        listaAdjuntos?.appendChild(crearAdjuntoEntrega('Enlace'));
    });

    // Quita un adjunto si el estudiante se equivoca.
    listaAdjuntos?.addEventListener('click', function (event) {
        const btn = event.target.closest('.adjunto-remove');
        if (!btn) return;
        listaAdjuntos.querySelector(`.adjunto-item[data-id="${btn.dataset.id}"]`)?.remove();
    });

    document.querySelectorAll('.js-cerrar-entrega-tarea').forEach(btn => {
        btn.addEventListener('click', cerrarModalEntrega);
    });

    modal?.addEventListener('click', function (event) {
        if (event.target === modal) cerrarModalEntrega();
    });

    // Construye el envio de entrega desde los adjuntos visibles en el modal.
    form?.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!tieneAdjuntoEntregaValido()) {
            mostrarToastPremium('Agrega un archivo o enlace para entregar la tarea');
            return;
        }

        const idTarea = tareaId?.value;
        if (!idTarea) return;
        const accionActual = form?.dataset.accion || 'entregar';

        const formData = new FormData();
        formData.append('idTarea', idTarea);

        // Agrega archivos y enlaces al FormData usando los controles dinamicos del modal.
        listaAdjuntos?.querySelectorAll('.adjunto-item').forEach(item => {
            if (item.dataset.tipo === 'Archivo') {
                const archivo = item.querySelector('.adjunto-file-input')?.files?.[0];
                if (archivo) formData.append('archivos[]', archivo);
            } else {
                const nombre = item.querySelector('.entrega-enlace-nombre')?.value.trim() || 'Enlace adjunto';
                const url    = item.querySelector('.entrega-enlace-url')?.value.trim();
                if (url) {
                    formData.append('enlace', url);
                    formData.append('nombreEnlace', nombre);
                }
            }
        });

        try {
            const endpoint = accionActual === 'reemplazar'
                ? '../Estudiante/estudiante-reemplazar-entregable.php'
                : '../Estudiante/estudiante-subir-entregable.php';

            const resp = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success) {
                // Refresca la tarjeta para que el estudiante vea el cambio de estado al instante.
                const fechaEntrega = fechaHoraEntregaActual();
                if (tareaActiva) {
                    tareaActiva.dataset.status = 'entregada';
                    const estado = tareaActiva.querySelector('.tarea-estado');
                    const boton  = tareaActiva.querySelector('.btn-entregar-tarea');
                    const intentosBadge = tareaActiva.querySelector('.tarea-intentos');
                    const fechaBadge = tareaActiva.querySelector('.tarea-fecha-entrega');
                    if (estado) { estado.textContent = 'Entregada'; estado.className = 'tarea-estado entregada'; }
                    if (boton) {
                        const intentosNuevos = parseInt(data.intentos || '1', 10);
                        const intentosMaximos = parseInt(boton.dataset.intentosMax || '3', 10);
                        boton.textContent = 'Reemplazar';
                        boton.classList.add('is-replace');
                        boton.dataset.accion = 'reemplazar';
                        boton.dataset.intentos = String(intentosNuevos);
                        if (intentosNuevos >= intentosMaximos) {
                            boton.textContent = 'Intentos agotados';
                            boton.classList.add('is-disabled');
                            boton.disabled = true;
                        }
                        if (intentosBadge) {
                            intentosBadge.innerHTML = `<i class="fas fa-rotate-right"></i> Intentos ${intentosNuevos}/${intentosMaximos}`;
                            intentosBadge.classList.toggle('agotado', intentosNuevos >= intentosMaximos);
                        }
                        if (fechaBadge) {
                            fechaBadge.classList.remove('is-hidden');
                            fechaBadge.innerHTML = `<i class="fas fa-clock"></i> Entregada: ${fechaEntrega}`;
                        }
                    }
                }
                cerrarModalEntrega();
                aplicarFiltros();
                const tituloToast = accionActual === 'reemplazar' ? 'Entrega actualizada' : 'Entrega registrada';
                mostrarToastPremium(`${tituloToast}. Fecha y hora: ${fechaEntrega}`, 'success');
            } else {
                mostrarToastPremium(data.message || 'Error al entregar la tarea.');
            }
        } catch (err) {
            mostrarToastPremium('Error de conexión: ' + err.message);
        }
    });

    buscador?.addEventListener('input', aplicarFiltros);
    estadoFiltro?.addEventListener('change', aplicarFiltros);
    ordenFiltro?.addEventListener('change', aplicarFiltros);
}

// Pagos ---------------------------------------------

// Abre el modal de pago para cancelar una mensualidad pendiente del estudiante.
// Obtiene el id, nombre del curso y monto desde el botón seleccionado,
// muestra el resumen en pantalla y prepara el botón de PayPal para procesar la cuota.
function pagarTramitePendiente(btn) {
    mensualidadSeleccionada = {
        id: btn.dataset.id,
        curso: btn.dataset.curso,
        monto: parseFloat(btn.dataset.monto)
    };

    const modal = document.getElementById('modalPago');
    const listaCursos = document.getElementById('pago-lista-cursos');
    const totalPago = document.getElementById('pago-total');

    listaCursos.innerHTML = `
        <div class="pago-curso-item">
            <span>${mensualidadSeleccionada.curso}</span>
            <span>$${mensualidadSeleccionada.monto.toFixed(2)}</span>
        </div>
    `;

    totalPago.textContent = `$${mensualidadSeleccionada.monto.toFixed(2)}`;

    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
    inicializarPayPalMensualidad();
}

function normalizarFuentePagoPayPal(data) {
    const fuente = (data?.fundingSource || data?.paymentSource || '').toLowerCase();
    if (!fuente) return '';
    return ['card', 'credit'].includes(fuente) ? 'tarjeta' : 'paypal';
}

// Inicializa el boton de PayPal para el pago de mensualidades.
// Crea una orden con el id de la mensualidad y procesa el resultado aprobado.
function inicializarPayPalMensualidad() {
    const container = document.getElementById('paypal-button-container');
    if (!container || container.dataset.rendered) return;
    let metodoPagoSDK = 'paypal';

    paypal.Buttons({

        createOrder: async function (paypalData) {
            metodoPagoSDK = normalizarFuentePagoPayPal(paypalData);
            const res = await fetch('paypal-create-mensualidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mensualidadId: mensualidadSeleccionada.id
                })
            });

            const data = await res.json();

            if (data.error) {
                mostrarToast(data.error, 'error');
                throw new Error(data.error);
            }

            return data.id;
        },

        onApprove: async function (data) {
            metodoPagoSDK = normalizarFuentePagoPayPal(data) || metodoPagoSDK;
            const res = await fetch('paypal-capture-mensualidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    orderID: data.orderID,
                    metodoPago: metodoPagoSDK
                })
            });

            const result = await res.json();

            if (result.success) {
                cerrarModalPago();
                mostrarToast('¡Mensualidad pagada correctamente!', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                mostrarToast(result.error || 'Error al procesar pago', 'error');
            }
        },

        onCancel: function () {
            mostrarToast('Pago cancelado', 'error');
        },

        onError: function (err) {
            console.error(err);
            mostrarToast('Error de PayPal', 'error');
        },

        style: {
            layout: 'vertical',
            color: 'blue',
            shape: 'pill',
            label: 'pay'
        }

    }).render('#paypal-button-container');

    container.dataset.rendered = 'true';
}

function cerrarModalPagoCuota() {
    const modal = document.getElementById('modalPagoCuota');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

function inicializarPayPalCuota() {
    const container = document.getElementById('paypal-cuota-button-container');
    if (!container || !tramitePendienteSeleccionado) return;

    // front: se limpia para evitar duplicar botones si el modal se abre varias veces.
    container.innerHTML = '';

    paypal.Buttons({
        createOrder: function (data, actions) {
            const monto = parseFloat(tramitePendienteSeleccionado.monto || '0').toFixed(2);

            // front: crea la orden desde el SDK con el monto mostrado en pantalla.
            // No llama endpoints PHP ni guarda datos en la base.
            return actions.order.create({
                purchase_units: [{
                    description: `Cuota pendiente - ${tramitePendienteSeleccionado.curso}`,
                    amount: {
                        currency_code: 'USD',
                        value: monto
                    }
                }],
                application_context: {
                    brand_name: 'Academia Futuro Digital',
                    user_action: 'PAY_NOW'
                }
            });
        },

        onApprove: function (data, actions) {
            return actions.order.capture().then(function () {
                cerrarModalPagoCuota();
                // Muestra el comprobante en pantalla despues de aprobar el pago.
                mostrarToast('Pago aprobado en PayPal. Revisa tus tramites pendientes.', 'success');
            });
        },

        onCancel: function () {
            mostrarToast('Cancelaste el pago. Podés intentarlo cuando quieras.', 'error');
        },

        onError: function (err) {
            console.error('PayPal cuota SDK error:', err);
            mostrarToast('Error de PayPal. Intentá de nuevo.', 'error');
        },

        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' }
    }).render('#paypal-cuota-button-container');
}

document.addEventListener('DOMContentLoaded', function () {
    const modalPagoCuota = document.getElementById('modalPagoCuota');
    if (modalPagoCuota) {
        modalPagoCuota.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalPagoCuota();
        });
    }
});

// Inscripciones -------------------------------------

//  Buscador de cursos por nombre o descripción (filtro en tiempo real)
document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('buscador-curso');
    if (buscador) {
        buscador.addEventListener('input', function () {
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('.curso-card').forEach(card => {
                const nombre = card.querySelector('.curso-nombre')?.textContent.toLowerCase() || '';
                const desc = card.querySelector('.curso-desc')?.textContent.toLowerCase() || '';
                // Oculta/muestra tarjetas según coincida con el filtro
                card.style.display = (nombre.includes(filtro) || desc.includes(filtro)) ? '' : 'none';
            });
        });
    }
});
// Filtra los cursos por categoría seleccionada
const filtroCategoria = document.getElementById('filtro-categoria');
if (filtroCategoria) {
    filtroCategoria.addEventListener('change', function () {
        const categoria = this.value.toLowerCase();
        const filtro = document.getElementById('buscador-curso')?.value.toLowerCase() || '';

        document.querySelectorAll('.curso-card').forEach(card => {
            const nombre = card.querySelector('.curso-nombre')?.textContent.toLowerCase() || '';
            const desc = card.querySelector('.curso-desc')?.textContent.toLowerCase() || '';
            const cat = card.querySelector('.meta-value')?.textContent.toLowerCase() || '';
            const categoriaSeleccionada = document.getElementById('filtro-categoria')?.value.toLowerCase() || '';

            const coincideTexto = nombre.includes(filtro) || desc.includes(filtro);
            const coincideCategoria = categoria === '' || cat.includes(categoria);

            card.style.display = coincideTexto && coincideCategoria ? '' : 'none';
        });
    });
}

// FUNCIÓN PRINCIPAL: Toggle de selección de cursos (máximo 5)
// Cambios visuales: tarjeta → color azul, botón → "Deseleccionar"
function seleccionarCurso(button) {
    const card = button.closest('.curso-card'); // Encuentra la tarjeta padre
    if (card) {
        const cursoId = button.dataset.id; // ID del curso
        const cursoNombre = button.dataset.nombre || card.querySelector('.curso-nombre')?.textContent?.trim() || 'Curso';
        const costoText = button.dataset.costo || card.querySelector('.meta-value.price')?.textContent || '0';
        const cursoCosto = parseFloat(costoText.replace(/[^0-9.-]/g, '')); // Convierte a número

        if (card.classList.contains('seleccionado')) {
            // DESELECCIONAR: quita clase y restaura estado
            card.classList.remove('seleccionado');
            button.textContent = 'Seleccionar';
            removerCursoSeleccionado(cursoId);
        } else {
            // SELECCIONAR: verifica límite de 5 cursos
            const seleccionados = document.querySelectorAll('.curso-card.seleccionado').length;
            if (seleccionados >= 5) {
                mostrarToast('Máximo 5 cursos permitidos', 'error');
                return; // Detiene si ya hay 5 seleccionados
            }

            // Aplica selección visual
            card.classList.add('seleccionado');
            button.textContent = 'Deseleccionar';
            agregarCursoSeleccionado(cursoId, cursoNombre, cursoCosto);
        }
    }
}

// Agregar curso a la lista de seleccionados y actualizar totales
function agregarCursoSeleccionado(id, nombre, costo) {
    cursosSeleccionados.push({id, nombre, costo});
    totalCursos++;
    totalCosto += costo;
    actualizarBarraInscripcion(); // Refleja cambios en la barra inferior
}

// Remover curso de la lista y restar del total
function removerCursoSeleccionado(id) {
    const index = cursosSeleccionados.findIndex(c => c.id == id);
    if (index > -1) {
        totalCosto -= cursosSeleccionados[index].costo; // Resta costo
        cursosSeleccionados.splice(index, 1);           // Elimina del array
        totalCursos--;
        actualizarBarraInscripcion();
    }
}

// Actualizar barra emergente inferior con: contador, chips, total, puntos de progreso
function actualizarBarraInscripcion() {
    const barra = document.getElementById('barra-inscripcion');
    const contador = document.getElementById('barra-curso-count');
    // Contador de la pestaña lateral usada solo en responsive.
    const contadorTab = document.getElementById('barra-tab-count');
    const botonTab = document.getElementById('barra-inscripcion-tab');
    const total = document.getElementById('total-costo');
    const lista = document.getElementById('barra-cursos-nombres');
    const puntos = document.getElementById('barra-progreso-dots');
    const porcentaje = document.getElementById('barra-porcentaje');

    if (!barra || !contador || !total || !lista || !puntos || !porcentaje) return;

    if (totalCursos > 0) {
        // Mostrar barra con animación
        barra.classList.add('visible');
        document.body.classList.add('inscripcion-barra-visible');
        contador.textContent = `${totalCursos}/5`;
        if (contadorTab) contadorTab.textContent = `${totalCursos}/5 cursos`;
        fetch('verificar-matricula.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })
            .then(r => r.json())
            .then(data => {
                const extra = data.yaPayoMatricula ? 0 : 25;
                total.textContent = `$${(totalCosto + extra).toFixed(2)}`;
                const label = document.querySelector('.barra-total-label');
                if (label) label.textContent = data.yaPayoMatricula ? 'Total' : 'Total con matrícula';
            });

        // Crear chips (etiquetas) para cada curso seleccionado
        lista.innerHTML = '';
        cursosSeleccionados.forEach(curso => {
            const chip = document.createElement('span');
            chip.className = 'barra-curso-chip';
            chip.textContent = curso.nombre;
            lista.appendChild(chip);
        });

        // Crear puntos de progreso (llenos/vacíos según cantidad)
        puntos.innerHTML = Array.from({ length: 5 }, (_, index) => {
            const active = index < totalCursos ? 'activo' : '';
            return `<span class="barra-dot ${active}"></span>`;
        }).join('');

        // Calcular porcentaje: (cursos/5) * 100
        porcentaje.textContent = `${Math.round((totalCursos / 5) * 100)}%`;
    } else {
        // Ocultar barra si no hay cursos seleccionados
        barra.classList.remove('visible');
        document.body.classList.remove('inscripcion-barra-visible');
        // Cierra la gaveta móvil al limpiar la selección.
        barra.classList.remove('abierta');
        if (botonTab) botonTab.setAttribute('aria-expanded', 'false');
        if (contadorTab) contadorTab.textContent = '0/5 cursos';
        lista.innerHTML = '';
        puntos.innerHTML = '';
    }
}

// Abre/cierra la gaveta lateral en móvil; en desktop la barra sigue usando el estilo inferior.
function toggleBarraInscripcion() {
    const barra = document.getElementById('barra-inscripcion');
    const botonTab = document.getElementById('barra-inscripcion-tab');
    if (!barra || totalCursos === 0) return;

    const abierta = barra.classList.toggle('abierta');
    if (botonTab) botonTab.setAttribute('aria-expanded', abierta ? 'true' : 'false');
}

// Cancelar selección: deselecciona todos los cursos y oculta barra
function cancelarInscripcion() {
    document.querySelectorAll('.curso-card.seleccionado').forEach(card => {
        card.classList.remove('seleccionado');
        const btn = card.querySelector('.btn-inscribir[onclick*="seleccionarCurso"]');
        if (btn) btn.textContent = 'Seleccionar';
    });
    cursosSeleccionados = [];
    totalCursos = 0;
    totalCosto = 0;
    actualizarBarraInscripcion();
}

// Confirmar inscripción: abre modal de pago si hay cursos seleccionados
function confirmarInscripcion() {
    if (totalCursos === 0) {
        mostrarToast('Selecciona al menos un curso', 'error');
        return;
    }
    abrirModalPago();
}

//  Abrir modal de pago con resumen de cursos y total
// Prepara los datos que necesita el boton de PayPal.
async function abrirModalPago(){
    const modal = document.getElementById('modalPago');
    if (!modal) return;

    const listaCursos = document.getElementById('pago-lista-cursos');
    const totalPago = document.getElementById('pago-total');
    const lineaMatricula = document.getElementById('linea-matricula');

        // Consultar si ya pagó matrícula
    const ids = cursosSeleccionados.map(c => parseInt(c.id));
    const res = await fetch('verificar-matricula.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cursos: ids })
    });
    const data = await res.json();
    const yaPayoMatricula = data.yaPayoMatricula ?? false;

    // Construir lista de cursos con precios individuales
    listaCursos.innerHTML = '';
    cursosSeleccionados.forEach(curso => {
        const item = document.createElement('div');
        item.className = 'pago-curso-item';
        item.innerHTML = `<span>${curso.nombre}</span><span>$${curso.costo.toFixed(2)}</span>`;
        listaCursos.appendChild(item);
    });

    if (lineaMatricula) {
        lineaMatricula.style.display = yaPayoMatricula ? 'none' : '';
    }

    totalPago.textContent = `$${(totalCosto + (yaPayoMatricula ? 0 : 25)).toFixed(2)}`;

    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
    inicializarPayPal();
}

// Cerrar modal de pago
function cerrarModalPago() {
    const modal = document.getElementById('modalPago');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

//  Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('DOMContentLoaded', function () {
    const modalPago = document.getElementById('modalPago');
    if (modalPago) {
        modalPago.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalPago();
        });
    }
});

//  Mostrar notificación toast (mensaje temporal en la esquina)
function mostrarToast(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `toast-premium toast-${tipo}`;
    toast.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
        <span>${mensaje}</span>
    `;
    document.body.appendChild(toast);

    // Animar entrada
    setTimeout(() => {
        toast.classList.add('visible');
    }, 100);

    // Animar salida y eliminar
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── PAYPAL ────────────────────────────────────────────────────────────────────
// Inicializa el botón de PayPal dentro del modal de pago.
// Se llama desde abrirModalPago() una sola vez gracias al flag data-rendered.
function inicializarPayPal() {
    const container = document.getElementById('paypal-button-container');
    if (!container || container.dataset.rendered) return;
    let metodoPagoSDK = 'paypal';

    paypal.Buttons({

        // Crea la orden de PayPal para el pago mostrado en el modal.
        createOrder: async function (paypalData) {
            metodoPagoSDK = normalizarFuentePagoPayPal(paypalData);
            const ids = cursosSeleccionados.map(c => parseInt(c.id));
            const res = await fetch('paypal-create-order.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ cursos: ids }),
            });
            const data = await res.json();
            if (data.error) {
                mostrarToast(data.error, 'error');
                throw new Error(data.error);
            }
            return data.id; // Order ID → PayPal abre el popup
        },

        // El comprador aprobó el pago en el popup de PayPal
        onApprove: async function (data) {
            metodoPagoSDK = normalizarFuentePagoPayPal(data) || metodoPagoSDK;
            const res = await fetch('paypal-capture-order.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ orderID: data.orderID, metodoPago: metodoPagoSDK }),
            });
            const result = await res.json();

            if (result.success) {
                cerrarModalPago();
                cancelarInscripcion();
                mostrarToast(
                    '¡Pago completado! Inscrito en ' + result.cursos + ' curso(s). Total: $' + result.totalCursos,
                     'success'
                );
                setTimeout(() => window.location.reload(), 2500);
            } else {
                mostrarToast(result.error || 'Error al procesar el pago', 'error');
            }
        },

        // El comprador cerró el popup sin pagar
        onCancel: function () {
            mostrarToast('Cancelaste el pago. Podés intentarlo cuando quieras.', 'error');
        },

        // Error técnico del SDK de PayPal
        onError: function (err) {
            console.error('PayPal SDK error:', err);
            mostrarToast('Error de PayPal. Intentá de nuevo.', 'error');
        },

        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' }

    }).render('#paypal-button-container');

    container.dataset.rendered = 'true'; // evita renderizar el botón dos veces
}

// Contenidos docente --------------------------------
// Abre y cierra el modal para crear o editar contenidos
// Valida los campos obligatorios del formulario
// Gestiona archivos y enlaces adjuntos dinámicamente
// Actualiza filas y estados de contenidos en la tabla
// Filtra contenidos por búsqueda, curso y estado
// Actualiza contadores de contenidos publicados y deshabilitados
// Guarda y actualiza contenidos mediante peticiones fetch
// Habilita y deshabilita contenidos con persistencia en base de datos
// Elimina archivos físicos del servidor y registros de enlaces desde el modal de edición

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalContenido');
    const form = document.getElementById('formContenidoClase');
    const btnNuevo = document.getElementById('btnNuevoContenido');
    const btnCerrar = document.getElementById('cerrarModalContenido');
    const btnCancelar = document.getElementById('cancelarContenido');
    const tbody = document.getElementById('tablaContenidosBody');
    const buscar = document.getElementById('buscarContenido');
    const filtroEstado = document.getElementById('filtroEstadoContenido');
    const adjuntosActuales = document.getElementById('adjuntosActuales');
    

    if (!modal || !form || !tbody) return;

    const campos = {
        id: document.getElementById('contenidoId'),
        curso: document.getElementById('contenidoCurso'),
        sesion: document.getElementById('contenidoSesion'),
        titulo: document.getElementById('contenidoTitulo'),
        descripcion: document.getElementById('contenidoDescripcion'),
        fecha: document.getElementById('contenidoFecha'),
        estado: document.getElementById('contenidoEstado'),
        modalTitulo: document.getElementById('contenidoModalTitulo')
    };

    function abrirModalContenido(modo = 'crear', fila = null) {
        form.reset();
        limpiarValidacionContenido();
        document.getElementById('listaAdjuntos').innerHTML = ''; 
        if (adjuntosActuales) adjuntosActuales.innerHTML = '';
        campos.id.value = '';
        campos.modalTitulo.textContent = modo === 'editar' ? 'Editar contenido' : 'Nuevo contenido';

        if(modo === 'crear'){
            const totalSesiones = tbody.querySelectorAll('tr:not(.contenido-empty)').length;
            const siguienteNumero = totalSesiones + 1;
            campos.sesion.value = `Sesión ${String(siguienteNumero).padStart(2, '0')}`;
        }

        if (fila) {
            campos.id.value        = fila.dataset.id || '';
            campos.curso.value     = fila.dataset.curso || '';
            campos.sesion.value    = fila.dataset.sesion || '';
            campos.titulo.value    = fila.dataset.titulo || '';
            campos.descripcion.value = fila.dataset.descripcion || '';
            campos.fecha.value     = fila.dataset.fecha || '';
            campos.estado.value    = fila.dataset.estado || 'Publicado';
            renderAdjuntosActuales(fila);
        }

        modal.classList.add('activo');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalContenido() {
        modal.classList.remove('activo');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        document.getElementById('listaAdjuntos').innerHTML = '';
        if (adjuntosActuales) adjuntosActuales.innerHTML = '';
    }

    function limpiarValidacionContenido() {
        form.querySelectorAll('.contenido-field').forEach(field => field.classList.remove('is-invalid'));
    }

    function validarContenido() {
    limpiarValidacionContenido();
    let valido = true;
    let mensajeError = '';

    const requeridos = [campos.curso, campos.sesion, campos.titulo, campos.descripcion, campos.fecha, campos.estado];
    requeridos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.closest('.contenido-field')?.classList.add('is-invalid');
            valido = false;
            if (!mensajeError) mensajeError = 'Complete los campos obligatorios del contenido';
        }
    });

    if (campos.fecha.value) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fechaSel = new Date(campos.fecha.value + 'T00:00:00');
        if (fechaSel < hoy) {
            campos.fecha.closest('.contenido-field')?.classList.add('is-invalid');
            mensajeError = 'La fecha de la publicación no puede ser menor que la fecha actual';
            valido = false;
        }
    }

    if (!valido) mostrarToastPremium(mensajeError);
    return valido;
}

    function crearBadgeEstado(estado) {
        const clase = estado.toLowerCase();
        return `<span class="contenido-badge estado-${clase}">${escapeContenido(estado)}</span>`;
    }

    function nombreArchivoVisible(fila, inputArchivo) {
        if (inputArchivo?.files?.length) return inputArchivo.files[0].name;
        return fila?.dataset.archivo || '';
    }

    function renderArchivo(nombre) {
        if (!nombre) return '<span class="contenido-muted">Sin archivo</span>';
        return `<span class="contenido-archivo"><i class="fas fa-paperclip"></i>${escapeContenido(nombre)}</span>`;
    }

    function obtenerAdjuntosFila(fila) {
        const nombresArchivos = (fila?.dataset.archivo || '').split(',').map(s => s.trim()).filter(Boolean);
        const idsArchivos     = (fila?.dataset.archivoIds || '').split(',').map(s => s.trim()).filter(Boolean);
        const nombresEnlaces  = (fila?.dataset.enlaces || '').split(',').map(s => s.trim()).filter(Boolean);
        const idsEnlaces      = (fila?.dataset.enlaceIds || '').split(',').map(s => s.trim()).filter(Boolean);

        const archivos = nombresArchivos.map((nombre, i) => ({ tipo: 'Archivo', nombre, id: idsArchivos[i] || '' }));
        const enlaces  = nombresEnlaces.map((nombre, i) => ({ tipo: 'Enlace', nombre, id: idsEnlaces[i] || '' }));

        return [...archivos, ...enlaces];
    }

    function renderAdjuntosActuales(fila) {
        if (!adjuntosActuales) return;

        const adjuntos = obtenerAdjuntosFila(fila);
        if (!adjuntos.length) {
            adjuntosActuales.innerHTML = '<span class="contenido-muted">Sin adjuntos guardados</span>';
            return;
        }

        adjuntosActuales.innerHTML = adjuntos.map(adjunto => `
            <div class="adjunto-item adjunto-existente">
                <span class="adjunto-tipo">
                    <i class="fas ${adjunto.tipo === 'Enlace' ? 'fa-link' : 'fa-paperclip'}"></i>
                    ${escapeContenido(adjunto.nombre)}
                </span>
                <button type="button" class="adjunto-remove adjunto-remove-existente" 
                    data-id-archivo="${adjunto.id}" title="Eliminar adjunto">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    function escapeContenido(valor) {
        return String(valor || '').replace(/[&<>"']/g, caracter => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[caracter]));
    }

    function actualizarFila(fila, usarFormulario = true) {
        if (usarFormulario) {
            fila.dataset.curso       = campos.curso.value;
            fila.dataset.sesion      = formatearSesion(campos.sesion.value);
            fila.dataset.titulo      = campos.titulo.value.trim();
            fila.dataset.descripcion = campos.descripcion.value.trim();
            fila.dataset.fecha       = campos.fecha.value;
            fila.dataset.estado      = campos.estado.value;
            
        }
        if (fila.dataset.estado === 'Deshabilitado') {
    fila.classList.add('fila-deshabilitada');
    tbody.appendChild(fila);
} else {
    fila.classList.remove('fila-deshabilitada');

    const filasActivas = Array.from(
        tbody.querySelectorAll('tr:not(.contenido-empty):not(.fila-deshabilitada)')
    ).filter(f => f !== fila);

    let insertado = false;

    for (const otraFila of filasActivas) {
        const idActual = parseInt(fila.dataset.id);
        const otroId = parseInt(otraFila.dataset.id);

        if (idActual < otroId) {
            tbody.insertBefore(fila, otraFila);
            insertado = true;
            break;
        }
    }

    if (!insertado) {
        tbody.appendChild(fila);
    }
}

        const archivo = fila.dataset.archivo || '';

        fila.innerHTML = `
                <td data-label="ID">${escapeContenido(fila.dataset.id)}</td>
                <td data-label="Sesión">${escapeContenido(fila.dataset.sesion)}</td>
                <td data-label="Título">
                    <strong>${escapeContenido(fila.dataset.titulo)}</strong>
                    <span class="contenido-desc">${escapeContenido(fila.dataset.descripcion)}</span>
                </td>
                <td data-label="Fecha publicación">${escapeContenido(formatearFechaContenido(fila.dataset.fecha))}</td>
                <td data-label="Archivo">${renderArchivo(archivo)}</td>
                <td data-label="Estado">${crearBadgeEstado(fila.dataset.estado)}</td>
                <td data-label="Acciones">
                    <div class="contenido-acciones">
                        <button type="button" class="contenido-icon-btn editar-contenido" title="Editar contenido">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="contenido-toggle ${fila.dataset.estado === 'Deshabilitado' ? 'btn-habilitar' : ''}" data-action="toggle">
                            ${fila.dataset.estado === 'Deshabilitado' ? 'Habilitar' : 'Deshabilitar'}
                        </button>
                    </div>
                </td>`;
    }
    

    function crearFila() {
        tbody.querySelector('.contenido-empty')?.remove();
        const ids = Array.from(tbody.querySelectorAll('tr:not(.contenido-empty)')).map(row => parseInt(row.dataset.id, 10) || 0);
        const nuevoId = Math.max(0, ...ids) + 1;
        const fila = document.createElement('tr');
        fila.dataset.id = nuevoId;
        tbody.prepend(fila);
        actualizarFila(fila);
    }

    function formatearFechaContenido(valor) {
        if (!valor) return '';
        const partes = valor.split('-');
        if (partes.length !== 3) return valor;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatearSesion(valor) {
        const numero = parseInt(valor, 10);
        if (!numero || numero < 1) return '';
        return `Sesión ${String(numero).padStart(2, '0')}`;
    }

    function obtenerNumeroSesion(valor) {
        const coincidencia = String(valor || '').match(/\d+/);
        return coincidencia ? String(parseInt(coincidencia[0], 10)) : '';
    }

    function filtrarContenidos() {
        const texto = (buscar?.value || '').toLowerCase().trim();
        const estado = filtroEstado?.value || '';

        tbody.querySelectorAll('tr:not(.contenido-empty)').forEach(fila => {
            const coincideTexto = !texto || [
                fila.dataset.titulo,
                fila.dataset.sesion,
                fila.dataset.descripcion
            ].some(valor => (valor || '').toLowerCase().includes(texto));
            const coincideEstado = !estado || fila.dataset.estado === estado;

            fila.style.display = coincideTexto && coincideEstado ? '' : 'none';
        });
    }
    let adjuntoCount = 0;

    function crearItemAdjunto(tipo) {
        adjuntoCount++;
        const id = `adj_${adjuntoCount}`;
        const div = document.createElement('div');
        div.className = 'adjunto-item';
        div.dataset.tipo = tipo;
        div.dataset.id = id;

        if (tipo === 'Archivo') {
            div.innerHTML = `
                    <span class="adjunto-tipo"><i class="fas fa-paperclip"></i> Archivo</span>
                    <label class="adjunto-file-label">
                        <i class="fas fa-folder-open"></i>
                        <span class="adjunto-file-texto">Seleccionar archivo</span>
                        <input type="file" name="archivos[]"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                            class="adjunto-file-input">
                    </label>
                    <button type="button" class="adjunto-remove" data-id="${id}">
                        <i class="fas fa-times"></i>
                    </button>`;

                div.querySelector('.adjunto-file-input').addEventListener('change', function () {
                    const texto = div.querySelector('.adjunto-file-texto');
                    texto.textContent = this.files[0]?.name || 'Seleccionar archivo';
                });
        } else {
            div.innerHTML = `
                <span class="adjunto-tipo"><i class="fas fa-link"></i> Enlace</span>
                <input type="text" name="enlaces[${adjuntoCount}][nombre]" placeholder="Nombre del enlace">
                <input type="url"  name="enlaces[${adjuntoCount}][url]"    placeholder="https://...">
                <button type="button" class="adjunto-remove" data-id="${id}">
                    <i class="fas fa-times"></i>
                </button>`;
        }
        return div;
    }

    document.getElementById('btnAgregarArchivo')?.addEventListener('click', () => {
        document.getElementById('listaAdjuntos').appendChild(crearItemAdjunto('Archivo'));
    });

    document.getElementById('btnAgregarEnlace')?.addEventListener('click', () => {
        document.getElementById('listaAdjuntos').appendChild(crearItemAdjunto('Enlace'));
    });

    document.getElementById('listaAdjuntos')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.adjunto-remove');
        if (btn) {
            document.querySelector(`.adjunto-item[data-id="${btn.dataset.id}"]`)?.remove();
        }
    });

    adjuntosActuales?.addEventListener('click', (e) => {
        const btn = e.target.closest('.adjunto-remove-existente');
        if (!btn) return;

        const idArchivo = btn.dataset.idArchivo;


        if (idArchivo) {
            fetch('/OpusCore/eliminar-adjunto.php', {
                method: 'POST',
                body: new URLSearchParams({ id: idArchivo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    btn.closest('.adjunto-existente')?.remove();
                    if (!adjuntosActuales.querySelector('.adjunto-existente')) {
                        adjuntosActuales.innerHTML = '<span class="contenido-muted">Sin adjuntos guardados</span>';
                    }
                } else {
                    mostrarToastPremium('Error al eliminar adjunto.');
                }
            })
            .catch(() => mostrarToastPremium('Error de conexión.'));
        } else {
            btn.closest('.adjunto-existente')?.remove();
        }
    });
    btnNuevo?.addEventListener('click', () => abrirModalContenido('crear'));
    btnCerrar?.addEventListener('click', cerrarModalContenido);
    btnCancelar?.addEventListener('click', cerrarModalContenido);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrarModalContenido();
    });

    tbody.addEventListener('click', function (e) {
        const btnEditar = e.target.closest('.editar-contenido');
        const btnToggle = e.target.closest('.contenido-toggle');
        const fila = e.target.closest('tr');
        if (!fila) return;

        if (btnEditar) {
            abrirModalContenido('editar', fila);
            return;
        }

        if (btnToggle) {
            const deshabilitado = fila.dataset.estado === 'Deshabilitado';
            const nuevoEstado   = deshabilitado ? 'Publicado' : 'Deshabilitado';
            const nuevoValor    = deshabilitado ? 1 : 0;

            fetch('toggle_contenido.php', {
                method: 'POST',
                body: new URLSearchParams({
                    id: fila.dataset.id,
                    estado: nuevoValor
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    fila.dataset.estado = nuevoEstado;
                    actualizarFila(fila, false);
                    filtrarContenidos();
                    actualizarContadores();

                } else {
                    mostrarToastPremium('Error al cambiar estado.');
                }
            })
            .catch(() => mostrarToastPremium('Error de conexión.'));
        }
    });
    function actualizarContadores() {
        const filas = Array.from(tbody.querySelectorAll('tr:not(.contenido-empty)'));
        const publicados     = filas.filter(f => f.dataset.estado === 'Publicado').length;
        const deshabilitados = filas.filter(f => f.dataset.estado === 'Deshabilitado').length;

        const metricas = document.querySelectorAll('.organizacion-metricas div strong');
        if (metricas[0]) metricas[0].textContent = String(publicados).padStart(2, '0');
        if (metricas[1]) metricas[1].textContent = String(deshabilitados).padStart(2, '0');
    }
    tbody.querySelectorAll('tr:not(.contenido-empty)').forEach(fila => {
        if (fila.dataset.estado === 'Deshabilitado') {
            fila.classList.add('fila-deshabilitada');
            tbody.appendChild(fila);
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!validarContenido()) return;

        const formData = new FormData(form);
        formData.set('id', campos.id.value || 0);
        formData.set('idCurso', document.getElementById('contenidoCursoId').value);
        formData.set('titulo',      campos.titulo.value.trim());
        formData.set('descripcion', campos.descripcion.value.trim());
        formData.set('fecha',       campos.fecha.value);
        formData.set('estado',      campos.estado.value === 'Publicado' ? 1 : 0);

        try {
            const resp = await fetch('/OpusCore/guardar_contenido.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.ok) {
                mostrarToastPremium('Contenido guardado correctamente.', 'success');
                cerrarModalContenido();
                setTimeout(() => location.reload(), 1500); 
            } else {
                mostrarToastPremium('Error: ' + data.msg);
            }
        } catch (err) {
            mostrarToastPremium('Error de conexión: ' + err.message);
        }
    });
    
    // Buscador y filtro estado
if (buscar) buscar.addEventListener('input', filtrarContenidos);
if (filtroEstado) filtroEstado.addEventListener('change', filtrarContenidos);
});

// Tareas --------------------------------------------

// Interacciones frontend para gestion de tareas del docente
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalTarea');
    const form = document.getElementById('formTareaDocente');
    const tbody = document.getElementById('tablaTareasBody');
    const btnNueva = document.getElementById('btnNuevaTarea');
    const btnCerrar = document.getElementById('cerrarModalTarea');
    const btnCancelar = document.getElementById('cancelarTarea');
    const total = document.getElementById('tareasTotal');
    const tareaArchivoTexto = document.getElementById('tareaArchivoTexto');
    const limpiarArchivoTarea = document.getElementById('limpiarArchivoTarea');

    if (!modal || !form || !tbody) return;

    let filaEditando = null;

    const campos = {
    id: document.getElementById('tareaId'),
    cursoId: document.getElementById('tareaCursoId'),
    titulo: document.getElementById('tareaTitulo'),
    descripcion: document.getElementById('tareaDescripcion'),
    fecha: document.getElementById('tareaFecha'),
    puntaje: document.getElementById('tareaPuntaje'),
    intentos: document.getElementById('tareaIntentos'),
    estado: document.getElementById('tareaEstado'),
    archivo: document.getElementById('tareaArchivo'),
    modalTitulo: document.getElementById('tareaModalTitulo')
};

   

   function abrirModalTarea(fila = null) {
    form.reset();
    limpiarValidacionTarea();
    filaEditando = fila;
    campos.modalTitulo.textContent = fila ? 'Editar tarea' : 'Nueva tarea';
    if (tareaArchivoTexto) tareaArchivoTexto.textContent = 'Seleccionar archivo';
     const selectSesion = document.getElementById('tareaSesion');
     if(selectSesion) selectSesion.value = '';
    if (campos.id) campos.id.value = '';

    if (fila) {
       
        if (campos.id) campos.id.value = fila.dataset.id || '';

        campos.titulo.value      = fila.dataset.titulo      || '';
        campos.descripcion.value = fila.dataset.descripcion || '';
        campos.puntaje.value     = fila.dataset.puntaje     || '';
        campos.intentos.value = fila.dataset.intentos || '1';
        campos.estado.value = fila.dataset.estado === 'Activa' ? '1' : '0';
        campos.fecha.value = (fila.dataset.fecha || '').replace(' ', 'T').substring(0, 16);

        const selectSesion = document.getElementById('tareaSesion');
        if(selectSesion){
            selectSesion.value = fila.dataset.sesionId || '';
        }

        if (tareaArchivoTexto && fila.dataset.archivo) {
            tareaArchivoTexto.textContent = fila.dataset.archivo;
        }
    }

    modal.classList.add('activo');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

    function cerrarModalTarea() {
        modal.classList.remove('activo');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        filaEditando = null;
    }

    function limpiarValidacionTarea() {
        form.querySelectorAll('.contenido-field').forEach(field => field.classList.remove('is-invalid'));
    }

// VALIDAR FECHA
   function validarTarea() {
    limpiarValidacionTarea();
    let valido = true;
    let mensajeError = '';

    const requeridos = [
        campos.titulo,
        campos.descripcion,
        campos.fecha,
        campos.puntaje,
        campos.estado
    ];

    requeridos.forEach(campo => {
        if (!campo) return;
        const vacio          = !campo.value.trim();
        const numeroInvalido = campo === campos.puntaje && parseInt(campo.value, 10) < 1;

        if (vacio || numeroInvalido) {
            campo.closest('.contenido-field')?.classList.add('is-invalid');
            valido = false;
            if (!mensajeError) mensajeError = 'Complete los campos obligatorios de la tarea';
        }
    });

    if (campos.fecha.value) {
        const partes           = campos.fecha.value.split('T');
        const [anio, mes, dia] = partes[0].split('-').map(Number);
        const [hora, minuto]   = partes[1] ? partes[1].split(':').map(Number) : [0, 0];

        const fechaSel  = new Date(anio, mes - 1, dia, hora, minuto, 0);
        const esEdicion = campos.id && campos.id.value && campos.id.value !== '0';

        const hoyInicio = new Date();
        hoyInicio.setHours(0, 0, 0, 0);

        console.log('fechaSel:', fechaSel);
        console.log('hoyInicio:', hoyInicio);
        console.log('fechaSel < hoyInicio:', fechaSel < hoyInicio);
        console.log('esEdicion:', esEdicion);
        console.log('valor raw:', campos.fecha.value);

        if (fechaSel < hoyInicio) {
            campos.fecha.closest('.contenido-field')?.classList.add('is-invalid');
            mensajeError = esEdicion
                ? 'La fecha límite no puede ser de un día anterior a hoy'
                : 'La fecha límite no puede ser anterior a la fecha actual';
            valido = false;
        }
    }

    if (!valido) mostrarToastPremium(mensajeError);
    return valido;
}

    function escapeTarea(valor) {
        return String(valor || '').replace(/[&<>"']/g, caracter => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[caracter]));
    }

    function formatearFechaTarea(valor) {
        if (!valor) return '';
        const normalizada = valor.replace(' ', 'T');
        const [fecha, hora = '00:00'] = normalizada.split('T');
        const partes = fecha.split('-');
        if (partes.length !== 3) return valor;
        return `${partes[2]}/${partes[1]}/${partes[0]} ${hora.substring(0, 5)}`;
    }

    function renderArchivoTarea(nombre) {
        if (!nombre) return '<span class="contenido-muted">Opcional</span>';
        return `<span class="contenido-archivo"><i class="fas fa-paperclip"></i>${escapeTarea(nombre)}</span>`;
    }

    function renderFilaTarea(fila) {
        fila.innerHTML = `
            <td data-label="Título">
                <strong>${escapeTarea(fila.dataset.titulo)}</strong>
                <span class="contenido-desc">${escapeTarea(fila.dataset.descripcion)}</span>
            </td>
            <td data-label="Fecha límite">${escapeTarea(formatearFechaTarea(fila.dataset.fecha))}</td>
            <td data-label="Puntaje">${escapeTarea(fila.dataset.puntaje)} pts</td>
            <td data-label="Apoyo">${renderArchivoTarea(fila.dataset.archivo)}</td>
            <td data-label="Estado">
                <span class="contenido-badge estado-${String(fila.dataset.estado || '').toLowerCase()}">
                    ${escapeTarea(fila.dataset.estado)}
                </span>
            </td>
            <td data-label="Acciones">
                <div class="contenido-acciones">
                    <button type="button" class="contenido-icon-btn editar-tarea" title="Editar tarea">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
            </td>`;
    }

    function guardarFilaTarea(idReal = null) {
    const fila        = filaEditando || document.createElement('tr');
    const archivoNuevo = campos.archivo.files?.[0]?.name || '';

    if (idReal && !filaEditando) {
        fila.dataset.id = idReal; 
    }

    fila.dataset.titulo      = campos.titulo.value.trim();
    fila.dataset.descripcion = campos.descripcion.value.trim();
    fila.dataset.fecha       = campos.fecha.value;
    fila.dataset.puntaje     = campos.puntaje.value;
    fila.dataset.intentos = campos.intentos.value;
    fila.dataset.estado      = campos.estado.value === '1' ? 'Activa' : 'Borrador';
    fila.dataset.archivo     = archivoNuevo || fila.dataset.archivo || '';
    fila.dataset.sesionId = document.getElementById('tareaSesion')?.value || '';

    renderFilaTarea(fila);

    if (!filaEditando) {
        tbody.prepend(fila);
        if (total) total.textContent = String(tbody.querySelectorAll('tr').length);
    }
}
    btnNueva?.addEventListener('click', () => abrirModalTarea());
    btnCerrar?.addEventListener('click', cerrarModalTarea);
    btnCancelar?.addEventListener('click', cerrarModalTarea);

    campos.archivo?.addEventListener('change', function () {
        if (tareaArchivoTexto) {
            tareaArchivoTexto.textContent = this.files[0]?.name || 'Seleccionar archivo';
        }
    });

    limpiarArchivoTarea?.addEventListener('click', async function () {
    if (campos.archivo) campos.archivo.value = '';
    if (tareaArchivoTexto) tareaArchivoTexto.textContent = 'Seleccionar archivo';


    const idsArchivos = filaEditando?.dataset.idsArchivos || '';
    if (!filaEditando || !idsArchivos) return;

    const idArchivo = idsArchivos.split(',')[0]; 
    if (!idArchivo) return;

    try {
        const formData = new FormData();
        formData.append('id', idArchivo);

        const res  = await fetch('eliminar-archivo-tarea.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.ok) {
          
            filaEditando.dataset.archivo     = '';
            filaEditando.dataset.idsArchivos = '';
            mostrarToastPremium('Archivo eliminado correctamente', 'success');
        } else {
            mostrarToastPremium(data.mensaje || 'Error al eliminar el archivo', 'error');
        }
    } catch {
        mostrarToastPremium('Error de conexión al eliminar el archivo', 'error');
    }
});

    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrarModalTarea();
    });

    tbody.addEventListener('click', function (e) {
        const btnEditar = e.target.closest('.editar-tarea');
        if (!btnEditar) return;
        abrirModalTarea(btnEditar.closest('tr'));
    });

   form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!validarTarea()) return;

    const btnSubmit = form.querySelector('button[type="submit"]');
    const textoOriginal = btnSubmit?.textContent || 'Guardar tarea';
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Guardando...';
    }

    const formData = new FormData();
    formData.append('id',            document.getElementById('tareaId')?.value || '0');
    formData.append('idCurso',       document.getElementById('tareaCursoId')?.value || '');
    formData.append('titulo',        document.getElementById('tareaTitulo')?.value?.trim() || '');
    formData.append('descripcion',   document.getElementById('tareaDescripcion')?.value?.trim() || '');
    formData.append('fechaLimite',   document.getElementById('tareaFecha')?.value || '');
    formData.append('puntajeMaximo', document.getElementById('tareaPuntaje')?.value || '');
    formData.append('intentos',      document.getElementById('tareaIntentos')?.value || '1');
    formData.append('estado',        document.getElementById('tareaEstado')?.value || '0');
    formData.append('idSesion',      document.getElementById('tareaSesion')?.value || '0');

    const archivoInput = document.getElementById('tareaArchivo');
    if (archivoInput?.files?.length > 0) {
        formData.append('archivo', archivoInput.files[0]);
    }

    try {
        const res  = await fetch('guardar-tarea.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.error) {
            mostrarToastPremium(data.mensaje, 'error');
        } else {
            guardarFilaTarea(data.id);
            cerrarModalTarea();
            mostrarToastPremium(data.mensaje, 'success');
            setTimeout(() => window.location.reload(), 1500);
        }
    } catch {
        mostrarToastPremium('Error de conexión al guardar la tarea', 'error');
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = textoOriginal;
        }
    }
});
});

// Entregas ------------------------------------------

// Filtros visuales para la pagina de entregas del docente.
document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.getElementById('tablaEntregasDocente');
    if (!tabla) return;

    const buscar = document.getElementById('buscarEntregasDocente');
    const filtroEstado = document.getElementById('filtroEntregasEstado');
    const empty = document.getElementById('entregasDocenteEmpty');
    const total = document.getElementById('entregasTotalVisible');
    const filas = Array.from(tabla.querySelectorAll('.entrega-docente-row'));

    // Aplica busqueda por texto y estado.
    function filtrarEntregasDocente() {
        const termino = (buscar?.value || '').trim().toLowerCase();
        const estado = (filtroEstado?.value || '').toLowerCase();
        let visibles = 0;

        filas.forEach(fila => {
            const coincideTexto = !termino || (fila.dataset.search || '').includes(termino);
            const coincideEstado = !estado || fila.dataset.estado === estado;
            const visible = coincideTexto && coincideEstado;
            fila.classList.toggle('is-hidden', !visible);
            if (visible) visibles++;
        });

        if (empty) empty.classList.toggle('is-visible', visibles === 0);
        if (total) total.textContent = String(visibles);
    }

    buscar?.addEventListener('input', filtrarEntregasDocente);
    filtroEstado?.addEventListener('change', filtrarEntregasDocente);
});

// Calificacion de entregas por parte del docente
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-calificar-tarea');
    if (!btn) return;

    const fila      = btn.closest('tr');
    const input     = fila?.querySelector('.tarea-calificacion input');
    const nota      = parseFloat(input?.value ?? '');
    const idEntrega = btn.dataset.idEntrega || fila?.dataset.idEntrega;

    const max = parseFloat(input?.getAttribute('max') ?? '100');
    if (!input || isNaN(nota) || nota < 0 || nota > max) {
        mostrarToastPremium(`La calificación debe estar entre 0 y ${max}`);
        input?.focus();
        return;
    }

    if (!idEntrega || idEntrega === '0') {
        mostrarToastPremium('Esta entrega no tiene registro en la base de datos', 'error');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    try {
        const formData = new FormData();
        formData.append('idEntrega', idEntrega);
        formData.append('nota', nota);

        const res  = await fetch('calificar-entrega.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.error) {
            mostrarToastPremium(data.mensaje, 'error');
            btn.disabled    = false;
            btn.textContent = 'Calificar';
        } else {
            // Actualizar UI
            const td = btn.closest('td');
            const idEntrega = btn.dataset.idEntrega || fila?.dataset.idEntrega;
            if (td) td.innerHTML = `
                <div class="tarea-calificacion">
                    <span class="nota-valor">${nota.toFixed(2)} pts</span>
                    <button type="button" class="btn-editar-nota" title="Editar nota">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>`;
            const estadoBadge = fila?.querySelector('.contenido-badge');
            if (estadoBadge) {
                estadoBadge.textContent = 'Revisado';
                estadoBadge.className = 'contenido-badge estado-revisado';
            }
            mostrarToastPremium('Calificación registrada correctamente', 'success');
        }
    } catch {
        mostrarToastPremium('Error de conexión al calificar', 'error');
        btn.disabled    = false;
        btn.textContent = 'Calificar';
    }
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-editar-nota');
    if (!btn) return;

    const td = btn.closest('td');
    const fila = btn.closest('tr');
    const idEntrega = btn.dataset.idEntrega || fila?.dataset.idEntrega;
    const notaActual = parseFloat(td.querySelector('.nota-valor')?.textContent) || 0;
    const max = parseFloat(fila?.querySelector('.tarea-calificacion input')?.getAttribute('max') ?? '100');

    td.innerHTML = `
        <div class="tarea-calificacion">
            <input type="number" min="0" max="${max}" step="0.01" value="${notaActual}">
            <button type="button" class="btn-calificar-tarea" data-id-entrega="${idEntrega}">
                Guardar
            </button>
        </div>`;
});

// Registro de notas ---------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // Evita errores si no existe el curso
    if (typeof cursoId === 'undefined') return;

    const storageKey = `opus_grades_curso_${cursoId}`;
    // Aquí se almacenan temporalmente las notas
    let gradesData = {};
    // Cargar notas guardadas en localStorage
    try {
        const saved = localStorage.getItem(storageKey);

        if (saved) {
            gradesData = JSON.parse(saved);
        }
    } catch (e) {
        console.error('Error al cargar las notas', e);
    }
    const inputs = document.querySelectorAll('.nota-input');
    const rows = document.querySelectorAll('.estudiante-row');

    // Restaurar notas guardadas y agregar eventos
    inputs.forEach(input => {
        const inscId = input.dataset.inscId;
        const notaNum = input.dataset.notaNum;

        if (
            gradesData[inscId] &&
            gradesData[inscId][`nota${notaNum}`] !== undefined
        ) {
            input.value = gradesData[inscId][`nota${notaNum}`];
        }
        // Guardar automáticamente al salir del input
        input.addEventListener('blur', function () {
            guardarNota(this);
        });
        // También guardar con Enter
        input.addEventListener('keydown', function (e) {

            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
    // Calcular promedios iniciales
    recalcularTodosLosPromedios();
    // Buscador de estudiantes
    const buscador = document.getElementById('buscarEstudiantes');

    if (buscador) {
        buscador.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();

            rows.forEach(row => {

                const searchVal = row.dataset.search;

                row.style.display =
                    searchVal.includes(term) ? '' : 'none';
            });
        });
    }
    // Guardar nota del estudiante
    function guardarNota(input) {

        const inscId = input.dataset.inscId;
        const notaNum = input.dataset.notaNum;

        const container =
            input.closest('.grade-input-container');

        const indicator =
            container.querySelector('.save-indicator');

        let valor = input.value.trim();

        input.classList.remove('input-error');
        // Si el campo queda vacío
        if (valor === '') {
            actualizarStorage(inscId, notaNum, undefined);

            mostrarIndicadorGuardado(
                indicator,
                'saved',
                '<i class="fas fa-check"></i>'
            );
            recalcularPromedioFila(inscId);
            recalcularKPIs();

            return;
        }

        const num = parseFloat(valor);
        // Validar rango permitido
        if (isNaN(num) || num < 0 || num > 10) {
            input.classList.add('input-error');

            if (typeof mostrarToastPremium === 'function') {
                mostrarToastPremium(
                    'La nota debe estar entre 1 y 10',
                    'error'
                );
            } else {
                alert('La nota debe estar entre 1 y 10');
            }

            mostrarIndicadorGuardado(
                indicator,
                'error',
                '<i class="fas fa-times"></i>'
            );
            // Restaurar valor anterior
            if (
                gradesData[inscId] &&
                gradesData[inscId][`nota${notaNum}`] !== undefined
            ) {
                input.value =
                    gradesData[inscId][`nota${notaNum}`];
            } else {
                input.value = '';
            }
            return;
        }

        // Mostrar animación de guardado
        mostrarIndicadorGuardado(
            indicator,
            'saving',
            '<i class="fas fa-circle-notch fa-spin"></i>'
        );

        setTimeout(() => {
            const finalVal = parseFloat(num.toFixed(2));
            input.value = finalVal.toFixed(2);

            actualizarStorage(inscId, notaNum, finalVal);

            mostrarIndicadorGuardado(
                indicator,
                'saved',
                '<i class="fas fa-check"></i>'
            );
            recalcularPromedioFila(inscId);
            recalcularKPIs();
        }, 500);
    }

    // Actualizar notas en localStorage provisional hasta implementar guardado definitivo
    function actualizarStorage(inscId, notaNum, valor) {

        if (!gradesData[inscId]) {
            gradesData[inscId] = {};
        }

        if (valor === undefined) {
            delete gradesData[inscId][`nota${notaNum}`];

            if (Object.keys(gradesData[inscId]).length === 0) {
                delete gradesData[inscId];
            }
        } else {
            gradesData[inscId][`nota${notaNum}`] = valor;
        }
        localStorage.setItem(
            storageKey,
            JSON.stringify(gradesData)
        );
    }

    // Mostrar estado visual del guardado
    function mostrarIndicadorGuardado(
        indicator,
        estado,
        iconHTML
    ) {
        indicator.className = `save-indicator ${estado}`;
        indicator.innerHTML = iconHTML;

        if (estado === 'saved' || estado === 'error') {
            setTimeout(() => {
                if (indicator.classList.contains(estado)) {
                    indicator.className = 'save-indicator';
                }
            }, 2000);
        }
    }
    // Calcular promedio individual
    function recalcularPromedioFila(inscId) {
        const badge =
            document.getElementById(`promedio-${inscId}`);

        if (!badge) return;

        const row = badge.closest('tr');

        const inputsFila =
            row.querySelectorAll('.nota-input');

        let suma = 0;
        let count = 0;

        inputsFila.forEach(inp => {
            const val = inp.value.trim();

            if (val !== '') {
                suma += parseFloat(val);
                count++;
            }
        });

        badge.className = 'promedio-badge';

        // Si ambas notas están completas
        if (count === 2) {
             const nota1 = parseFloat(inputsFila[0].value.trim());
            const nota2 = parseFloat(inputsFila[1].value.trim());
            const prom  = parseFloat(((nota1 * 0.30) + (nota2 * 0.70)).toFixed(2));
            badge.textContent = prom.toFixed(2);

            if (prom >= 7.0) {
            badge.classList.add('promedio-aprobado');
        } else if (prom >= 6.0) {
            badge.classList.add('promedio-alerta');
        } else {
            badge.classList.add('promedio-reprobado');
        }
        } else {
            badge.textContent = '—';
            badge.classList.add('promedio-vacio');
        }
    }

    // Cargar promedio grupal real desde BD si existe
if (typeof promedioGrupalInicial !== 'undefined' && promedioGrupalInicial !== null) {
    const kpiPromedio = document.getElementById('kpi-promedio-grupal');
    if (kpiPromedio) kpiPromedio.textContent = parseFloat(promedioGrupalInicial).toFixed(2);
}
    // Recalcular todos los promedios
    function recalcularTodosLosPromedios() {
        inputs.forEach(inp => {
            const inscId = inp.dataset.inscId;
            recalcularPromedioFila(inscId);
        });
        recalcularKPIs();
    }
    // Actualizar métricas generales del curso
   // Actualizar métricas generales del curso
function recalcularKPIs() {
    const badges = document.querySelectorAll('[id^="promedio-"]');

    let sumaPromedios = 0;
    let aprobados = 0;
    let promediosValidosCount = 0;

    badges.forEach(b => {
        const text = b.textContent.trim();

        if (text !== '—') {
            const val = parseFloat(text);
            sumaPromedios += val;
            promediosValidosCount++;

            if (val >= 7.00) aprobados++;
        }
    });

    const kpiPromedio  = document.getElementById('kpi-promedio-grupal');
    const kpiAprobacion = document.getElementById('kpi-porcentaje-aprobacion');

    if (promediosValidosCount > 0) {
        const promedioGrupal = (sumaPromedios / promediosValidosCount).toFixed(2);
        const tasaAprobacion = Math.round((aprobados / promediosValidosCount) * 100);

        if (kpiPromedio)   kpiPromedio.textContent   = promedioGrupal;
        if (kpiAprobacion) kpiAprobacion.textContent = `${tasaAprobacion}%`;
    } else {
        if (kpiPromedio)   kpiPromedio.textContent   = '0.00';
        if (kpiAprobacion) kpiAprobacion.textContent = '0%';
    }
}
    
   // Botones de guardado manual
   // Botón Editar
document.querySelectorAll('.btn-nota-editar').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!plazoActivo) {
            mostrarToastPremium('El plazo de notas está cerrado. No se puede editar.', 'error');
            return;
        }
        const row = this.closest('tr');
        row.querySelectorAll('.nota-input').forEach(inp => inp.removeAttribute('readonly'));
        row.querySelectorAll('.nota-input')[0].focus();
        
        const btnGuardar = row.querySelector('.btn-guardar-nota');
        btnGuardar.disabled = false;
        btnGuardar.style.display = '';
        this.style.display = 'none';
    });
});

// Botón Guardar
document.querySelectorAll('.btn-guardar-nota').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!plazoActivo) {
            mostrarToastPremium('El plazo de notas está cerrado. No se pueden guardar cambios.', 'error');
            return;
        }

        const row = this.closest('tr');
        const inputsFila = row.querySelectorAll('.nota-input');
        const nota1 = inputsFila[0].value.trim();
        const nota2 = inputsFila[1].value.trim();

        if (nota1 === '' || nota2 === '') {
            mostrarToastPremium('Debes ingresar ambas notas antes de guardar.', 'error');
            return;
        }

        const estudianteId = inputsFila[0].dataset.estudianteId;
        const inscId       = inputsFila[0].dataset.inscId;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Guardando...';

        fetch('guardar-nota.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id:      cursoId,
                estudiante_id: parseInt(estudianteId),
                actividades:   parseFloat(nota1),
                examen_final:  parseFloat(nota2),
                plazo_id:      plazoActivo.id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarToastPremium(data.message, 'success');
                inputsFila.forEach(inp => inp.setAttribute('readonly', true));

                const badge = document.getElementById(`promedio-${inscId}`);
                if (badge && data.nota_final !== undefined) {
                    const nf = parseFloat(data.nota_final).toFixed(2);
                    badge.textContent = nf;
                    badge.className = 'promedio-badge';
                    badge.classList.add(parseFloat(data.nota_final) >= 6 ? 'promedio-aprobado' : 'promedio-reprobado');
                }

               
                let btnEditar = row.querySelector('.btn-nota-editar');
                if (!btnEditar) {
                    btnEditar = document.createElement('button');
                    btnEditar.className = 'btn-nota-editar';
                    btnEditar.dataset.accion = 'editar';
                    btnEditar.innerHTML = '<i class="fas fa-pen"></i> Editar';
                    btnEditar.addEventListener('click', function () {
                        if (!plazoActivo) {
                            mostrarToastPremium('El plazo de notas está cerrado. No se puede editar.', 'error');
                            return;
                        }
                        row.querySelectorAll('.nota-input').forEach(inp => inp.removeAttribute('readonly'));
                        row.querySelectorAll('.nota-input')[0].focus();
                        row.querySelector('.btn-guardar-nota').disabled = false;
                        this.style.display = 'none';
                    });
                    row.querySelector('.acciones-nota-group').prepend(btnEditar);
                } else {
                    btnEditar.style.display = '';
                }
                this.style.display = 'none';
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-save"></i> Guardar';

                recalcularKPIs();
            } else {
                mostrarToastPremium(data.message, 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Guardar';
            }
        })
        .catch(() => {
            mostrarToastPremium('Error de conexión al guardar', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save"></i> Guardar';
        });
    });
});
});

