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



