function abrirModalNuevoPeriodo() {
    document.getElementById('modal-periodo-titulo').innerHTML =
        '<i class="fas fa-calendar-alt" style="color:#069DBF; margin-right:8px"></i> Nuevo Período';
    document.getElementById('periodo-id').value = '';
    document.getElementById('periodo-nombre').value = '';
    document.getElementById('periodo-fecha-inicio').value = '';
    document.getElementById('periodo-fecha-fin').value = '';
    document.getElementById('periodo-fecha-inicio-ciclo').value = '';
    document.getElementById('periodo-fecha-fin-ciclo').value = '';

    const modal = document.getElementById('modalPeriodo');
    if (modal) {
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    }
}

document.querySelectorAll('.abrir-modal-periodo').forEach(btn => {
    btn.addEventListener('click', function () {

        const modal = document.getElementById('modalPeriodo');
        if (!modal) return;

        document.getElementById('modal-periodo-titulo').innerHTML =
            '<i class="fas fa-edit" style="color:#069DBF; margin-right:8px"></i> Editar Período';

        document.getElementById('periodo-id').value = this.dataset.id;
        document.getElementById('periodo-nombre').value = this.dataset.nombre;
        document.getElementById('periodo-fecha-inicio').value = this.dataset.fecha_inicio;
        document.getElementById('periodo-fecha-fin').value = this.dataset.fecha_fin;
        document.getElementById('periodo-fecha-inicio-ciclo').value = this.dataset.fecha_inicio_ciclo || '';
        document.getElementById('periodo-fecha-fin-ciclo').value = this.dataset.fecha_fin_ciclo || '';

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

function cerrarModalPeriodo() {
    const modal = document.getElementById('modalPeriodo');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const formPeriodo = document.querySelector('#modalPeriodo form');
if (formPeriodo) {
    formPeriodo.addEventListener('submit', function (e) {
        e.preventDefault();

        const nombre = document.getElementById('periodo-nombre').value.trim();
        const fechaInicio = document.getElementById('periodo-fecha-inicio').value.trim();
        const fechaFin = document.getElementById('periodo-fecha-fin').value.trim();
        const fechaInicioCiclo = document.getElementById('periodo-fecha-inicio-ciclo').value.trim();
        const fechaFinCiclo = document.getElementById('periodo-fecha-fin-ciclo').value.trim();

        if (!nombre || !fechaInicio || !fechaFin || !fechaInicioCiclo || !fechaFinCiclo) {
            mostrarToastPremium('Complete todos los campos');
            return;
        }

        const id = document.getElementById('periodo-id').value;
        const archivo = id ? '../api/admin/editar-periodo.php' : '../api/admin/crear-periodo.php';

        const body = new URLSearchParams({
            id: id,
            nombre: nombre,
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            fechaInicioCiclo: fechaInicioCiclo,
            fechaFinCiclo: fechaFinCiclo
        });

        fetch(archivo, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
            .then(res => res.text())
            .then(text => {
                console.log("RESPUESTA DEL SERVIDOR:", text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    throw new Error("Respuesta no es JSON");
                }

                if (data.success) {
                    cerrarModalPeriodo();
                    const mensaje = id ? 'Periodo guardado correctamente' : 'Periodo creado exitosamente';
                    mostrarToastPremium(mensaje, 'success');
                    setTimeout(() => window.location.reload(), 1500);

                } else if (data.error === 'existe') {
                    mostrarToastPremium('Ya existe un período con este nombre: Intenta con otro nombre');

                } else if (data.error === 'fechas') {
                    mostrarToastPremium('La fecha de fin no puede ser menor a la de inicio');

                } else if (data.error === 'traslape') {
                    mostrarToastPremium('Las fechas ingresadas coinciden con otro período existente. Intenta con otras fechas');

                } else {
                    console.error(data);
                    mostrarToastPremium('Error al guardar');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarToastPremium('Error de conexión');
            });
    });
}

const modalPeriodo = document.getElementById('modalPeriodo');
if (modalPeriodo) {
    modalPeriodo.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalPeriodo();
    });
}

const buscadorPeriodo = document.getElementById('buscador-periodo');
if (buscadorPeriodo) {
    buscadorPeriodo.addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('.data-table tbody tr').forEach(function (fila) {
            fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });
}
