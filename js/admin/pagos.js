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
