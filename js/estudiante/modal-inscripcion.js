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