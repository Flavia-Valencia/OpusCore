document.addEventListener('DOMContentLoaded', function () {

    const modalPlazo = document.getElementById('modalPlazo');
    const btnNuevoPlazo = document.querySelector('.btn-nuevo');
    const formPlazo = modalPlazo ? modalPlazo.querySelector('form') : null;

    const inputId = document.getElementById('plazo-id');
    const inputNombre = document.getElementById('plazo-nombre');
    const inputPeriodo = document.getElementById('plazo-periodo');
    const inputInicio = document.getElementById('plazo-fecha-inicio');
    const inputFin = document.getElementById('plazo-fecha-fin');
    const modalTitulo = document.getElementById('modal-plazo-titulo');

    if (!modalPlazo) return;

    function abrirModal() {
        modalPlazo.classList.add('activo');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        modalPlazo.classList.remove('activo');
        document.body.style.overflow = '';

        if (formPlazo) formPlazo.reset();
        if (inputId) inputId.value = '';
    }

    window.cerrarModalPlazo = cerrarModal;

    modalPlazo.addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });

    if (btnNuevoPlazo) {
        btnNuevoPlazo.addEventListener('click', function () {

            if (modalTitulo) {
                modalTitulo.innerHTML = '<i class="fas fa-calendar-alt"></i> Nuevo Plazo';
            }

            if (inputId) inputId.value = '';
            if (formPlazo) formPlazo.reset();

            abrirModal();
        });
    }

    document.addEventListener('click', function (e) {

        const btn = e.target.closest('.abrir-modal-plazo');

        if (!btn) return;

        e.preventDefault();

        if (modalTitulo) {
            modalTitulo.innerHTML = '<i class="fas fa-edit"></i> Editar Plazo';
        }

        if (inputId) inputId.value = btn.dataset.id || '';
        if (inputNombre) inputNombre.value = btn.dataset.nombre || '';
        if (inputPeriodo) inputPeriodo.value = btn.dataset.idperiodo || '';
        if (inputInicio) inputInicio.value = btn.dataset.plazoInicio || '';
        if (inputFin) inputFin.value = btn.dataset.plazoFin || '';

        abrirModal();
    });

    if (inputPeriodo) {

        inputPeriodo.addEventListener('change', async function () {

            const idPeriodo = this.value;
            if (!idPeriodo) return;

            try {
                const res = await fetch('../api/obtener/obtener-fechas-periodo.php?id=' + idPeriodo);
                const data = await res.json();

                if (!data.success) {
                    mostrarToastPremium(data.message || 'Error al cargar periodo');
                    return;
                }

                inputInicio.value = data.inicio;
                inputFin.value = data.fin;

                inputInicio.max = data.fin;
                inputFin.max = data.fin;

            } catch (err) {
                console.error(err);
                mostrarToastPremium('Error al cargar periodo');
            }
        });
    }

    if (formPlazo) {

        formPlazo.addEventListener('submit', async function (e) {

            e.preventDefault();

            const id = inputId.value.trim();
            const nombre = inputNombre.value.trim();
            const idPeriodo = inputPeriodo.value.trim();
            const inicio = inputInicio.value.trim();
            const fin = inputFin.value.trim();

            if (!nombre || !idPeriodo || !inicio || !fin) {
                mostrarToastPremium('Complete todos los campos');
                return;
            }

            if (fin < inicio) {
                mostrarToastPremium('La fecha final no puede ser menor a la inicial');
                return;
            }

            const mesFin    = fin.substring(0, 7);
            const mesInicio = inicio.substring(0, 7);

            if (mesInicio !== mesFin) {
                mostrarToastPremium('El inicio del plazo debe ser dentro del mes de cierre del período');
                return;
            }

            const body = new URLSearchParams({
                id,
                nombre,
                idPeriodo,
                plazoInicio: inicio,
                plazoFin: fin
            });

            const endpoint = id
                ? 'admin-editar-plazo.php'
                : 'admin-guardar-plazo.php';

            try {

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body
                });

                const text = await res.text();
                console.log("RESPUESTA PHP:", text);

                const data = JSON.parse(text);

                if (data.success) {
                    cerrarModal();
                    mostrarToastPremium(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mostrarToastPremium(data.message);
                }

            } catch (err) {
                console.error("ERROR REAL:", err);
                mostrarToastPremium('Error en JS o JSON inválido');
            }
        });
    }

    const buscador = document.getElementById('buscador-plazo');

    if (buscador) {

        buscador.addEventListener('keyup', function () {

            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('.data-table tbody tr');

            filas.forEach(fila => {
                fila.style.display =
                    fila.textContent.toLowerCase().includes(filtro)
                        ? ''
                        : 'none';
            });
        });
    }

});
