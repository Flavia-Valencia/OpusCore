const buscadorPago = document.getElementById('buscador-pago');
if (buscadorPago) {
    buscadorPago.addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.tabla-placeholder .data-table tbody tr');

        filas.forEach(function (fila) {
            fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });
}

// Pagos ---------------------------------------------

// Abre el modal de pago para cancelar una mensualidad pendiente del estudiante.
// Obtiene el id, nombre del curso y monto desde el botón seleccionado,
// muestra el resumen en pantalla y prepara el botón de PayPal para procesar la cuota.
function pagarTramitePendiente(btn) {
    mensualidadSeleccionada = {
        id: btn.dataset.id,
        curso: btn.dataset.curso,
        monto: parseFloat(btn.dataset.monto)
    };

    const modal = document.getElementById('modalPago');
    const listaCursos = document.getElementById('pago-lista-cursos');
    const totalPago = document.getElementById('pago-total');

    listaCursos.innerHTML = `
        <div class="pago-curso-item">
            <span>${mensualidadSeleccionada.curso}</span>
            <span>$${mensualidadSeleccionada.monto.toFixed(2)}</span>
        </div>
    `;

    totalPago.textContent = `$${mensualidadSeleccionada.monto.toFixed(2)}`;

    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
    inicializarPayPalMensualidad();
}

function normalizarFuentePagoPayPal(data) {
    const fuente = (data?.fundingSource || data?.paymentSource || '').toLowerCase();
    if (!fuente) return '';
    return ['card', 'credit'].includes(fuente) ? 'tarjeta' : 'paypal';
}

// Inicializa el boton de PayPal para el pago de mensualidades.
// Crea una orden con el id de la mensualidad y procesa el resultado aprobado.
function inicializarPayPalMensualidad() {
    const container = document.getElementById('paypal-button-container');
    if (!container || container.dataset.rendered) return;
    let metodoPagoSDK = 'paypal';

    paypal.Buttons({

        createOrder: async function (paypalData) {
            metodoPagoSDK = normalizarFuentePagoPayPal(paypalData);
            const res = await fetch('paypal-create-mensualidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mensualidadId: mensualidadSeleccionada.id
                })
            });

            const data = await res.json();

            if (data.error) {
                mostrarToast(data.error, 'error');
                throw new Error(data.error);
            }

            return data.id;
        },

        onApprove: async function (data) {
            metodoPagoSDK = normalizarFuentePagoPayPal(data) || metodoPagoSDK;
            const res = await fetch('paypal-capture-mensualidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    orderID: data.orderID,
                    metodoPago: metodoPagoSDK
                })
            });

            const result = await res.json();

            if (result.success) {
                cerrarModalPago();
                mostrarToast('¡Mensualidad pagada correctamente!', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                mostrarToast(result.error || 'Error al procesar pago', 'error');
            }
        },

        onCancel: function () {
            mostrarToast('Pago cancelado', 'error');
        },

        onError: function (err) {
            console.error(err);
            mostrarToast('Error de PayPal', 'error');
        },

        style: {
            layout: 'vertical',
            color: 'blue',
            shape: 'pill',
            label: 'pay'
        }

    }).render('#paypal-button-container');

    container.dataset.rendered = 'true';
}

function cerrarModalPagoCuota() {
    const modal = document.getElementById('modalPagoCuota');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

function inicializarPayPalCuota() {
    const container = document.getElementById('paypal-cuota-button-container');
    if (!container || !tramitePendienteSeleccionado) return;

    // front: se limpia para evitar duplicar botones si el modal se abre varias veces.
    container.innerHTML = '';

    paypal.Buttons({
        createOrder: function (data, actions) {
            const monto = parseFloat(tramitePendienteSeleccionado.monto || '0').toFixed(2);

            // front: crea la orden desde el SDK con el monto mostrado en pantalla.
            // No llama endpoints PHP ni guarda datos en la base.
            return actions.order.create({
                purchase_units: [{
                    description: `Cuota pendiente - ${tramitePendienteSeleccionado.curso}`,
                    amount: {
                        currency_code: 'USD',
                        value: monto
                    }
                }],
                application_context: {
                    brand_name: 'Academia Futuro Digital',
                    user_action: 'PAY_NOW'
                }
            });
        },

        onApprove: function (data, actions) {
            return actions.order.capture().then(function () {
                cerrarModalPagoCuota();
                // Muestra el comprobante en pantalla despues de aprobar el pago.
                mostrarToast('Pago aprobado en PayPal. Revisa tus tramites pendientes.', 'success');
            });
        },

        onCancel: function () {
            mostrarToast('Cancelaste el pago. Podés intentarlo cuando quieras.', 'error');
        },

        onError: function (err) {
            console.error('PayPal cuota SDK error:', err);
            mostrarToast('Error de PayPal. Intentá de nuevo.', 'error');
        },

        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' }
    }).render('#paypal-cuota-button-container');
}

document.addEventListener('DOMContentLoaded', function () {
    const modalPagoCuota = document.getElementById('modalPagoCuota');
    if (modalPagoCuota) {
        modalPagoCuota.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalPagoCuota();
        });
    }
});
