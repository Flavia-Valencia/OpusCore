// INSCRIPCIÓN DE CURSOS (estudiante-inscripciones.php)
// Gestiona: selección múltiple de cursos (máx 5), barra emergente inferior,
// modal de pago y notificaciones toast para estudiantes.

// Variables globales para rastrear cursos seleccionados
let cursosSeleccionados = []; // Array de objetos {id, nombre, costo}
let totalCursos = 0;           // Contador de cursos seleccionados
let totalCosto = 0;            // Suma total del costo de cursos

// FUNCIÓN PRINCIPAL: Toggle de selección de cursos (máximo 5)
// Cambios visuales: tarjeta → color azul, botón → "Deseleccionar"
function seleccionarCurso(button) {
    const card = button.closest('.curso-card'); // Encuentra la tarjeta padre
    if (card) {
        const cursoId = button.dataset.id; // ID del curso
        const cursoNombre = button.dataset.nombre || card.querySelector('.curso-nombre')?.textContent?.trim() || 'Curso';
        const costoText = button.dataset.costo || card.querySelector('.meta-value.price')?.textContent || '0';
        const cursoCosto = parseFloat(costoText.replace(/[^0-9.-]/g, '')); // Convierte a número

        if (card.classList.contains('seleccionado')) {
            // DESELECCIONAR: quita clase y restaura estado
            card.classList.remove('seleccionado');
            button.textContent = 'Seleccionar';
            removerCursoSeleccionado(cursoId);
        } else {
            // SELECCIONAR: verifica límite de 5 cursos
            const seleccionados = document.querySelectorAll('.curso-card.seleccionado').length;
            if (seleccionados >= 5) {
                mostrarToast('Máximo 5 cursos permitidos', 'error');
                return; // Detiene si ya hay 5 seleccionados
            }

            // Aplica selección visual
            card.classList.add('seleccionado');
            button.textContent = 'Deseleccionar';
            agregarCursoSeleccionado(cursoId, cursoNombre, cursoCosto);
        }
    }
}

// Agregar curso a la lista de seleccionados y actualizar totales
function agregarCursoSeleccionado(id, nombre, costo) {
    cursosSeleccionados.push({id, nombre, costo});
    totalCursos++;
    totalCosto += costo;
    actualizarBarraInscripcion(); // Refleja cambios en la barra inferior
}

// Remover curso de la lista y restar del total
function removerCursoSeleccionado(id) {
    const index = cursosSeleccionados.findIndex(c => c.id == id);
    if (index > -1) {
        totalCosto -= cursosSeleccionados[index].costo; // Resta costo
        cursosSeleccionados.splice(index, 1);           // Elimina del array
        totalCursos--;
        actualizarBarraInscripcion();
    }
}

// Actualizar barra emergente inferior con: contador, chips, total, puntos de progreso
function actualizarBarraInscripcion() {
    const barra = document.getElementById('barra-inscripcion');
    const contador = document.getElementById('barra-curso-count');
    // Contador de la pestaña lateral usada solo en responsive.
    const contadorTab = document.getElementById('barra-tab-count');
    const botonTab = document.getElementById('barra-inscripcion-tab');
    const total = document.getElementById('total-costo');
    const lista = document.getElementById('barra-cursos-nombres');
    const puntos = document.getElementById('barra-progreso-dots');
    const porcentaje = document.getElementById('barra-porcentaje');

    if (!barra || !contador || !total || !lista || !puntos || !porcentaje) return;

    if (totalCursos > 0) {
        // Mostrar barra con animación
        barra.classList.add('visible');
        document.body.classList.add('inscripcion-barra-visible');
        contador.textContent = `${totalCursos}/5`;
        if (contadorTab) contadorTab.textContent = `${totalCursos}/5 cursos`;
        fetch('verificar-matricula.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })
            .then(r => r.json())
            .then(data => {
                const extra = data.yaPayoMatricula ? 0 : 25;
                total.textContent = `$${(totalCosto + extra).toFixed(2)}`;
                const label = document.querySelector('.barra-total-label');
                if (label) label.textContent = data.yaPayoMatricula ? 'Total' : 'Total con matrícula';
            });

        // Crear chips (etiquetas) para cada curso seleccionado
        lista.innerHTML = '';
        cursosSeleccionados.forEach(curso => {
            const chip = document.createElement('span');
            chip.className = 'barra-curso-chip';
            chip.textContent = curso.nombre;
            lista.appendChild(chip);
        });

        // Crear puntos de progreso (llenos/vacíos según cantidad)
        puntos.innerHTML = Array.from({ length: 5 }, (_, index) => {
            const active = index < totalCursos ? 'activo' : '';
            return `<span class="barra-dot ${active}"></span>`;
        }).join('');

        // Calcular porcentaje: (cursos/5) * 100
        porcentaje.textContent = `${Math.round((totalCursos / 5) * 100)}%`;
    } else {
        // Ocultar barra si no hay cursos seleccionados
        barra.classList.remove('visible');
        document.body.classList.remove('inscripcion-barra-visible');
        // Cierra la gaveta móvil al limpiar la selección.
        barra.classList.remove('abierta');
        if (botonTab) botonTab.setAttribute('aria-expanded', 'false');
        if (contadorTab) contadorTab.textContent = '0/5 cursos';
        lista.innerHTML = '';
        puntos.innerHTML = '';
    }
}

// Abre/cierra la gaveta lateral en móvil; en desktop la barra sigue usando el estilo inferior.
function toggleBarraInscripcion() {
    const barra = document.getElementById('barra-inscripcion');
    const botonTab = document.getElementById('barra-inscripcion-tab');
    if (!barra || totalCursos === 0) return;

    const abierta = barra.classList.toggle('abierta');
    if (botonTab) botonTab.setAttribute('aria-expanded', abierta ? 'true' : 'false');
}

// Cancelar selección: deselecciona todos los cursos y oculta barra
function cancelarInscripcion() {
    document.querySelectorAll('.curso-card.seleccionado').forEach(card => {
        card.classList.remove('seleccionado');
        const btn = card.querySelector('.btn-inscribir[onclick*="seleccionarCurso"]');
        if (btn) btn.textContent = 'Seleccionar';
    });
    cursosSeleccionados = [];
    totalCursos = 0;
    totalCosto = 0;
    actualizarBarraInscripcion();
}

// Confirmar inscripción: abre modal de pago si hay cursos seleccionados
function confirmarInscripcion() {
    if (totalCursos === 0) {
        mostrarToast('Selecciona al menos un curso', 'error');
        return;
    }
    abrirModalPago();
}

//  Abrir modal de pago con resumen de cursos y total
// Prepara los datos que necesita el boton de PayPal.
async function abrirModalPago(){
    const modal = document.getElementById('modalPago');
    if (!modal) return;

    const listaCursos = document.getElementById('pago-lista-cursos');
    const totalPago = document.getElementById('pago-total');
    const lineaMatricula = document.getElementById('linea-matricula');

        // Consultar si ya pagó matrícula
    const ids = cursosSeleccionados.map(c => parseInt(c.id));
    const res = await fetch('verificar-matricula.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cursos: ids })
    });
    const data = await res.json();
    const yaPayoMatricula = data.yaPayoMatricula ?? false;

    // Construir lista de cursos con precios individuales
    listaCursos.innerHTML = '';
    cursosSeleccionados.forEach(curso => {
        const item = document.createElement('div');
        item.className = 'pago-curso-item';
        item.innerHTML = `<span>${curso.nombre}</span><span>$${curso.costo.toFixed(2)}</span>`;
        listaCursos.appendChild(item);
    });

    if (lineaMatricula) {
        lineaMatricula.style.display = yaPayoMatricula ? 'none' : '';
    }

    totalPago.textContent = `$${(totalCosto + (yaPayoMatricula ? 0 : 25)).toFixed(2)}`;

    modal.classList.add('activo');
    document.body.style.overflow = 'hidden';
    inicializarPayPal();
}

// Cerrar modal de pago
function cerrarModalPago() {
    const modal = document.getElementById('modalPago');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

//  Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('DOMContentLoaded', function () {
    const modalPago = document.getElementById('modalPago');
    if (modalPago) {
        modalPago.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalPago();
        });
    }
});

// ── PAYPAL ────────────────────────────────────────────────────────────────────
// Inicializa el botón de PayPal dentro del modal de pago.
// Se llama desde abrirModalPago() una sola vez gracias al flag data-rendered.
function inicializarPayPal() {
    const container = document.getElementById('paypal-button-container');
    if (!container || container.dataset.rendered) return;
    let metodoPagoSDK = 'paypal';

    paypal.Buttons({

        // Crea la orden de PayPal para el pago mostrado en el modal.
        createOrder: async function (paypalData) {
            metodoPagoSDK = normalizarFuentePagoPayPal(paypalData);
            const ids = cursosSeleccionados.map(c => parseInt(c.id));
            const res = await fetch('paypal-create-order.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ cursos: ids }),
            });
            const data = await res.json();
            if (data.error) {
                mostrarToast(data.error, 'error');
                throw new Error(data.error);
            }
            return data.id; // Order ID → PayPal abre el popup
        },

        // El comprador aprobó el pago en el popup de PayPal
        onApprove: async function (data) {
            metodoPagoSDK = normalizarFuentePagoPayPal(data) || metodoPagoSDK;
            const res = await fetch('paypal-capture-order.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ orderID: data.orderID, metodoPago: metodoPagoSDK }),
            });
            const result = await res.json();

            if (result.success) {
                cerrarModalPago();
                cancelarInscripcion();
                mostrarToast(
                    '¡Pago completado! Inscrito en ' + result.cursos + ' curso(s). Total: $' + result.totalCursos,
                     'success'
                );
                setTimeout(() => window.location.reload(), 2500);
            } else {
                mostrarToast(result.error || 'Error al procesar el pago', 'error');
            }
        },

        // El comprador cerró el popup sin pagar
        onCancel: function () {
            mostrarToast('Cancelaste el pago. Podés intentarlo cuando quieras.', 'error');
        },

        // Error técnico del SDK de PayPal
        onError: function (err) {
            console.error('PayPal SDK error:', err);
            mostrarToast('Error de PayPal. Intentá de nuevo.', 'error');
        },

        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' }

    }).render('#paypal-button-container');

    container.dataset.rendered = 'true'; // evita renderizar el botón dos veces
}