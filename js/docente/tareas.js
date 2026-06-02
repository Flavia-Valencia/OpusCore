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