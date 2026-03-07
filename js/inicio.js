const fecha = new Date();
const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
document.getElementById('fecha-hoy').textContent = fecha.toLocaleDateString('es-ES', opciones);