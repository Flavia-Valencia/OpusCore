function cerrarModalNuevoCurso() {
    const modal = document.getElementById('modalNuevoCurso');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalNuevoCurso = document.getElementById('modalNuevoCurso');

if (modalNuevoCurso) {
    modalNuevoCurso.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalNuevoCurso();
    });
}

async function cargarPeriodos(selectId, idSeleccionado = null) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const res = await fetch('../api/obtener/obtener-periodos.php');
    const periodos = await res.json();

    select.innerHTML = '<option value="">Seleccione un periodo</option>';
    periodos.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.nombre;
        if (idSeleccionado && p.id == idSeleccionado) opt.selected = true;
        select.appendChild(opt);
    });
}

document.querySelectorAll('.abrir-modal-curso').forEach(btn => {
    btn.addEventListener('click', function () {
        const modal = document.getElementById('modalEditarCurso');
        if (!modal) return;

        document.getElementById('edit-id-curso').value = this.dataset.id;
        document.getElementById('edit-nombre-curso').value = this.dataset.nombre;

        const selectDocente = document.getElementById('edit-docente-curso');
        selectDocente.value = this.dataset.docente;
        Array.from(selectDocente.options).forEach(option => {
            if (option.value === this.dataset.docente) {
                option.disabled = false;
            } else if (option.dataset.lleno === '1') {
                option.disabled = true;
            }
        });
        document.getElementById('edit-categoria-curso').value = this.dataset.categoria;
        document.getElementById('edit-descripcion-curso').value = this.dataset.descripcion;
        document.getElementById('edit-fecha-inicio').value = this.dataset.fechainicio;
        document.getElementById('edit-fecha-fin').value = this.dataset.fechafin;
        document.getElementById('edit-costo-mensual').value = this.dataset.costo;

        if (document.getElementById('edit-cupos')) {
            document.getElementById('edit-cupos').value = this.dataset.cupos;
        }

        const prerequisitos = this.dataset.prerrequisitos
            ? this.dataset.prerrequisitos.split(",")
            : [];

        if (document.getElementById('edit-estado-curso')) {
            const estadoTexto = this.dataset.estado == 1 ? 'Activo' : 'Inactivo';
            document.getElementById('edit-estado-curso').value = estadoTexto;
        }

        const selectCategoriaEditar = document.getElementById('edit-categoria-curso');
        selectCategoriaEditar.dataset.preSeleccionado = prerequisitos[0] || '';
        selectCategoriaEditar.dispatchEvent(new Event('change'));
        cargarPeriodos('edit-idPeriodo', this.dataset.periodo);
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

const selectCategoriaNuevo = document.getElementById('nuevo-categoria-curso');
const selectPreNuevo       = document.getElementById('nuevo-prerrequisitos');

if (selectCategoriaNuevo && selectPreNuevo) {
    selectCategoriaNuevo.addEventListener('change', async function () {
        const idCategoria = this.value;

        if (!idCategoria) {
            selectPreNuevo.innerHTML = '<option value="">Ninguno</option>';
            selectPreNuevo.disabled = true;
            return;
        }

        const res    = await fetch(`../api/obtener/obtener-cursos-por-categoria.php?idCategoria=${idCategoria}`);
        const cursos = await res.json();

        selectPreNuevo.innerHTML = '<option value="">Ninguno</option>';
        cursos.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            selectPreNuevo.appendChild(opt);
        });

        selectPreNuevo.disabled = cursos.length === 0;
    });
}

const selectCategoriaEditar = document.getElementById('edit-categoria-curso');
const selectPreEditar        = document.getElementById('edit-prerrequisitos');

if (selectCategoriaEditar && selectPreEditar) {
    selectCategoriaEditar.addEventListener('change', async function () {
        const idCategoria   = this.value;
        const idCursoActual = document.getElementById('edit-id-curso').value;

        if (!idCategoria) {
            selectPreEditar.innerHTML = '<option value="">Ninguno</option>';
            selectPreEditar.disabled = true;
            return;
        }

        const res    = await fetch(`../api/obtener/obtener-cursos-por-categoria.php?idCategoria=${idCategoria}&idCursoActual=${idCursoActual}`);
        const cursos = await res.json();

        selectPreEditar.innerHTML = '<option value="">Ninguno</option>';
        cursos.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            selectPreEditar.appendChild(opt);
        });

        selectPreEditar.disabled = cursos.length === 0;

        const preGuardado = selectCategoriaEditar.dataset.preSeleccionado;
        if (preGuardado) selectPreEditar.value = preGuardado;
    });
}

function cerrarModalCurso() {
    const modal = document.getElementById('modalEditarCurso');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalEditarCurso = document.getElementById('modalEditarCurso');
if (modalEditarCurso) {
    modalEditarCurso.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalCurso();
    });
}

const buscadorCurso = document.getElementById('buscador-curso');

if (buscadorCurso) {
    buscadorCurso.addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.data-table tbody tr');

        console.log('Filas encontradas:', filas.length);

        filas.forEach(function (fila) {
            const nombre = fila.cells[0].textContent.toLowerCase();
            fila.style.display = nombre.includes(filtro) ? '' : 'none';
        });
    });
}

let catalogoHorarios = [];
let catalogoAulas = [];

async function cargarCatalogos() {
    if (catalogoHorarios.length > 0) return;
    try {
        const res = await fetch('../api/obtener/obtener-horarios-aulas.php');
        const data = await res.json();
        catalogoHorarios = data.horarios;
        catalogoAulas = data.aulas;
    } catch {
        mostrarToastPremium('Error al cargar el catálogo de horarios')
    }
}

function llenarSelects(card) {
    const horarioSelect = card.querySelector('.horario-select');
    const aulaSelect = card.querySelector('.aula-select');

    horarioSelect.innerHTML = '<option value="">Seleccione un rango</option>';
    aulaSelect.innerHTML = '<option value="">Seleccione salón</option>';

    catalogoHorarios.forEach(h => {
        const opt = document.createElement('option');
        opt.value = h.id;
        opt.textContent = h.etiqueta;
        horarioSelect.appendChild(opt);
    });

    catalogoAulas.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = a.aula;
        aulaSelect.appendChild(opt);
    });
}

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

    modal.dataset.idCurso = idCurso;
    container.innerHTML = '';

    try {
        const res = await fetch(`../api/obtener/obtener-horarios-cursos.php?idCurso=${idCurso}`);
        const bloques = await res.json();

        if (bloques.length > 0) {
            bloques.forEach(bloque => {
                agregarBloqueHorario();
                const cards = container.querySelectorAll('.horario-card-registro');
                const card = cards[cards.length - 1];

                card.querySelectorAll('.dia-tag').forEach(tag => {
                    if (bloque.dias.includes(tag.dataset.dia)) {
                        tag.classList.add('active');
                    }
                });

                card.querySelector('.horario-select').value = bloque.idHorario;
                card.querySelector('.aula-select').value = bloque.idAula;
            });

        } else {
            agregarBloqueHorario();

        }
    } catch {
        agregarBloqueHorario();
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

const modalHorarios = document.getElementById('modalHorarios');
if (modalHorarios) {
    modalHorarios.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalHorarios();
    });
}

document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-agregar-horario')) {
        agregarBloqueHorario();
    }
});

document.addEventListener('click', function (e) {
    const btnCerrar = e.target.closest('.horario-card-cerrar');
    if (btnCerrar) {
        const card = btnCerrar.closest('.horario-card-registro');
        const container = document.getElementById('bloques-horario-container');

        if (container.querySelectorAll('.horario-card-registro').length > 1) {
            card.remove();
        } else {
            mostrarToastPremium('Debe haber al menos un bloque de horario');
        }
    }
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('dia-tag')) {
        e.target.classList.toggle('active');
    }
});

const btnGuardarHorarios = document.getElementById('btn-guardar-horarios');
if (btnGuardarHorarios) {
    btnGuardarHorarios.addEventListener('click', async function () {
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

        const data = {
            idCurso: idCurso,
            bloques: bloques
        };
        console.log('Datos consolidados para Backend:', data);

        try {
            const res = await fetch('../api/admin/guardar-horarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
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
