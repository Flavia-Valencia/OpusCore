function mostrarFechaHoy() {
    const fechaHoy = document.getElementById('fecha-hoy');
    if (fechaHoy) {
        const fecha = new Date();
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        fechaHoy.textContent = fecha.toLocaleDateString('es-ES', opciones);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mostrarFechaHoy);
} else {
    mostrarFechaHoy();
}
