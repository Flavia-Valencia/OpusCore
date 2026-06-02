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