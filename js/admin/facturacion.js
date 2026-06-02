document.addEventListener('DOMContentLoaded', function () {
    const modalFactura = document.getElementById('modalNuevaFactura');
    const btnNuevaFactura = document.getElementById('btnNuevaFactura');
    const btnCerrarFactura = document.getElementById('cerrarModalFactura');
    const btnCancelarFactura = document.getElementById('cancelarFactura');
    const formNuevaFactura = document.getElementById('formNuevaFactura');
    const tablaFacturas = document.getElementById('tablaFacturas');
    const selectDocenteFactura = document.getElementById('factura-docente-id');

    if (!modalFactura && !tablaFacturas) return;

    const abrirModalFactura = () => {
        if (!modalFactura) return;
        modalFactura.classList.add('activo');
        document.body.style.overflow = 'hidden';

        const campoFecha = document.getElementById('factura-fecha');
        if (campoFecha && !campoFecha.value) {
            campoFecha.value = new Date().toISOString().slice(0, 10);
        }
    };

    const cerrarModalFactura = () => {
        if (!modalFactura) return;
        modalFactura.classList.remove('activo');
        document.body.style.overflow = '';
        if (formNuevaFactura) {
            formNuevaFactura.reset();
            formNuevaFactura.querySelectorAll('.facturacion-campo-error').forEach(campo => {
                campo.classList.remove('facturacion-campo-error');
            });
        }
        const correoInput = document.getElementById('factura-correo');
        if (correoInput) correoInput.value = '';
        if (typeof resetDetalle === 'function') resetDetalle();
    };

    if (btnNuevaFactura) btnNuevaFactura.addEventListener('click', abrirModalFactura);
    if (btnCerrarFactura) btnCerrarFactura.addEventListener('click', cerrarModalFactura);
    if (btnCancelarFactura) btnCancelarFactura.addEventListener('click', cerrarModalFactura);

    if (modalFactura) {
        modalFactura.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalFactura();
        });
    }

    if (formNuevaFactura) {
        formNuevaFactura.addEventListener('submit', function (e) {
            e.preventDefault();

            const requeridos = formNuevaFactura.querySelectorAll('[required]');
            let valido = true;

            requeridos.forEach(campo => {
                const vacio = !campo.value.trim();
                const montoInvalido = campo.type === 'number' && Number(campo.value) <= 0;

                campo.classList.toggle('facturacion-campo-error', vacio || montoInvalido);
                if (vacio || montoInvalido) valido = false;
            });

            if (!valido) {
                mostrarToastPremium('Complete los campos requeridos de la factura');
                return;
            }

            const filas = document.querySelectorAll('#detalleBody tr');
            const items = [];
            filas.forEach(fila => {
                const inputs  = fila.querySelectorAll('input');
                const desc    = inputs[0]?.value.trim();
                const cantidad = inputs[1]?.value;
                const precio  = inputs[2]?.value;
                if (desc && parseFloat(precio) > 0) {
                    items.push({ descripcion: desc, cantidad, precio });
                }
            });

            if (items.length === 0) {
                mostrarToastPremium('Agrega al menos un ítem con monto válido');
                return;
            }

            const idDocente    = document.getElementById('factura-docente-id').value;
            const metodoPago   = document.getElementById('factura-metodo').value.trim();
            const noReferencia = document.getElementById('factura-referencia').value.trim();
            const observ       = document.getElementById('factura-observaciones').value.trim();
            const fechaEmis    = document.getElementById('factura-fecha').value;

            const fd = new FormData();
            fd.append('idDocente',     idDocente);
            fd.append('metodoPago',    metodoPago);
            fd.append('noReferencia',  noReferencia);
            fd.append('observaciones', observ);
            fd.append('fechaEmision',  fechaEmis);
            items.forEach((it, i) => {
                fd.append(`items[${i}][descripcion]`, it.descripcion);
                fd.append(`items[${i}][cantidad]`,    it.cantidad);
                fd.append(`items[${i}][precio]`,      it.precio);
            });

            const btnSubmit = formNuevaFactura.querySelector('button[type="submit"]');
            if (btnSubmit) { btnSubmit.disabled = true; btnSubmit.textContent = 'Generando...'; }

            fetch('../includes/generar-factura-docente.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        cerrarModalFactura();
                        mostrarToastPremium(`Factura ${data.numeroFactura} generada correctamente. Total: $${data.total}`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToastPremium(data.error || 'Error al generar la factura');
                    }
                })
                .catch(err => mostrarToastPremium('Error de red: ' + err.message))
                .finally(() => {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="fas fa-file-circle-plus"></i> Generar factura';
                    }
                });
        });

        formNuevaFactura.addEventListener('input', function (e) {
            if (e.target.classList.contains('facturacion-campo-error')) {
                e.target.classList.remove('facturacion-campo-error');
            }
        });
    }

    const filtros = {
        busqueda: document.getElementById('factura-buscador'),
        destino: document.getElementById('factura-destino'),
        concepto: document.getElementById('factura-concepto'),
        desde: document.getElementById('factura-fecha-desde'),
        hasta: document.getElementById('factura-fecha-hasta')
    };

    const aplicarFiltrosFactura = () => {

        if (!tablaFacturas) return;

        const filas = Array.from(
            tablaFacturas.querySelectorAll('tbody tr:not(.facturacion-sin-resultados)')
        );

        const texto = (filtros.busqueda?.value || '').toLowerCase().trim();

        const destino = (filtros.destino?.value || '')
            .toLowerCase()
            .trim();

        const concepto = (filtros.concepto?.value || '')
            .toLowerCase()
            .trim();

        const desde = filtros.desde?.value || '';
        const hasta = filtros.hasta?.value || '';

        let visibles = 0;

        filas.forEach(fila => {

            const dataBusqueda = (fila.dataset.busqueda || '')
                .toLowerCase()
                .trim();

            const dataDestino = (fila.dataset.destino || '')
                .toLowerCase()
                .trim();

            const dataConcepto = (fila.dataset.concepto || '')
                .toLowerCase()
                .trim();

            const fecha = fila.dataset.fecha || '';

            const coincideTexto =
                !texto || dataBusqueda.includes(texto);

            const coincideDestino =
                !destino || dataDestino.includes(destino);

            const coincideConcepto =
                !concepto || dataConcepto.includes(concepto);

            const coincideDesde =
                !desde || fecha >= desde;

            const coincideHasta =
                !hasta || fecha <= hasta;

            const mostrar =
                coincideTexto &&
                coincideDestino &&
                coincideConcepto &&
                coincideDesde &&
                coincideHasta;

            fila.style.display = mostrar ? '' : 'none';

            if (mostrar) visibles++;
        });

        const sinResultados = document.getElementById('facturasSinResultados');

        if (sinResultados) {
            sinResultados.style.display =
                visibles === 0 ? '' : 'none';
        }
    };

    Object.values(filtros).forEach(control => {
        if (control) {
            control.addEventListener('input', aplicarFiltrosFactura);
            control.addEventListener('change', aplicarFiltrosFactura);
        }
    });

    if (selectDocenteFactura) {
        selectDocenteFactura.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            const campos = {
                'factura-correo': option?.dataset.correo || ''
            };

            Object.entries(campos).forEach(([id, valor]) => {
                const campo = document.getElementById(id);
                if (campo) campo.value = valor;
            });
        });
    }
});

(function () {
    let filaId = 1;
 
    window.recalcFila = function (id) {
        const cant   = parseFloat(document.getElementById('cant-'   + id)?.value) || 0;
        const precio = parseFloat(document.getElementById('precio-' + id)?.value) || 0;
        const celda  = document.getElementById('sub-' + id);
        if (celda) celda.textContent = '$' + (cant * precio).toFixed(2);
        recalcTotal();
    };
 
    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('[id^="sub-"]').forEach(el => {
            total += parseFloat(el.textContent.replace('$', '')) || 0;
        });
        const label = document.getElementById('facturaTotal');
        if (label) label.textContent = '$' + total.toFixed(2);
    }
 
    window.agregarFila = function () {
        filaId++;
        const id    = filaId;
        const tbody = document.getElementById('detalleBody');
        if (!tbody) return;
 
        const tr = document.createElement('tr');
        tr.dataset.fila = id;
        tr.innerHTML = `
            <td><input type="text" placeholder="Descripción" oninput="recalcFila(${id})"></td>
            <td><input type="number" id="cant-${id}" value="1" min="1" step="1" oninput="recalcFila(${id})"></td>
            <td><input type="number" id="precio-${id}" placeholder="0.00" min="0" step="0.01" oninput="recalcFila(${id})"></td>
            <td class="subtotal-cell" id="sub-${id}">$0.00</td>
            <td>
                <button type="button" class="btn-eliminar-fila" onclick="eliminarFila(${id})" aria-label="Eliminar fila">
                    <i class="fas fa-trash"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    };
 
    window.eliminarFila = function (id) {
        if (document.querySelectorAll('#detalleBody tr').length <= 1) return;
        const tr = document.querySelector(`[data-fila="${id}"]`);
        if (tr) { tr.remove(); recalcTotal(); }
    };
 
    window.resetDetalle = function () {
        filaId = 1;
        const tbody = document.getElementById('detalleBody');
        if (!tbody) return;
        tbody.innerHTML = `
            <tr data-fila="1">
                <td><input type="text" placeholder="Ej: Clases mayo 2026" oninput="recalcFila(1)"></td>
                <td><input type="number" id="cant-1" value="1" min="1" step="1" oninput="recalcFila(1)"></td>
                <td><input type="number" id="precio-1" placeholder="0.00" min="0" step="0.01" oninput="recalcFila(1)"></td>
                <td class="subtotal-cell" id="sub-1">$0.00</td>
                <td>
                    <button type="button" class="btn-eliminar-fila" onclick="eliminarFila(1)" aria-label="Eliminar fila">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        const label = document.getElementById('facturaTotal');
        if (label) label.textContent = '$0.00';
    };
})();
