function mostrarToastPremium(mensaje, tipo = 'error') {
    // Eliminar toast anterior si existe
    const anterior = document.getElementById('toastPremium');
    if (anterior) anterior.remove();

    const icono = tipo === 'success'
        ? '<i class="fa-solid fa-circle-check"></i>'
        : '<i class="fa-solid fa-circle-exclamation"></i>';

    const toast = document.createElement('div');
    toast.id = 'toastPremium';
    toast.className = `toast-premium toast-${tipo}`;
    toast.innerHTML = `${icono} ${mensaje}`;

    document.body.appendChild(toast);

    // Forzar reflow para que la transición funcione
    toast.getBoundingClientRect();
    toast.classList.add('visible');

    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}
