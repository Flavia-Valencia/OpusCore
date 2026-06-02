// Contenidos estudiante ----------------------------
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