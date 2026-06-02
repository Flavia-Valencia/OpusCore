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
        const rutaBase = icono.getAttribute('src').replace(/ojo-(abierto|cerrado)\.svg$/, '');
        icono.src = `${rutaBase}ojo-${viendo ? "cerrado" : "abierto"}.svg`;
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