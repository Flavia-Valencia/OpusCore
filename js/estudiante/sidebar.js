//  Toggle del sidebar en móvil (abrir/cerrar menú lateral)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('open');      // Activa/desactiva clase 'open'
    overlay.classList.toggle('active');    // Muestra/oculta overlay oscuro
    if (toggle) toggle.checked = sidebar.classList.contains('open'); // Sincroniza checkbox
}


//  Cerrar sidebar en móvil
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !overlay) return;
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    if (toggle) toggle.checked = false;
}

function togglePagosOnline() {
    const menu = document.getElementById('pagosOnlineMenu');
    if (!menu) return;

    // FRONTEND: permite contraer/expandir el submenu de Pagos en linea.
    // La pagina activa sigue marcada por el enlace hijo con clase "active".
    const dropdown = menu.closest('.nav-dropdown');
    const toggle = dropdown?.querySelector('.nav-dropdown-toggle');
    const estaAbierto = dropdown ? dropdown.classList.toggle('open') : menu.classList.toggle('open');

    menu.classList.toggle('open', estaAbierto);
    if (toggle) toggle.setAttribute('aria-expanded', estaAbierto ? 'true' : 'false');
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-sidebar-toggle').forEach(btn => {
        btn.addEventListener('click', toggleSidebar);
    });

    document.querySelectorAll('.js-sidebar-close, .js-sidebar-overlay').forEach(btn => {
        btn.addEventListener('click', closeSidebar);
    });

    document.querySelectorAll('.js-pagos-toggle').forEach(btn => {
        btn.addEventListener('click', togglePagosOnline);
    });

    inicializarFiltrosContenidosEstudiante();
    inicializarTareasEstudiante();
});