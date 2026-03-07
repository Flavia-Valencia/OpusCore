document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nombre').value     = this.dataset.nombre;
        document.getElementById('edit-apellido').value   = this.dataset.apellido;
        document.getElementById('edit-telefono').value   = this.dataset.telefono;
        document.getElementById('edit-estado').value     = this.dataset.estado;
        document.getElementById('edit-usuario').value    = this.dataset.correo;
        document.getElementById('edit-contrasena').value = this.dataset.contrasena;
        document.getElementById('modalEditar').classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

function cerrarModal() {
    document.getElementById('modalEditar').classList.remove('activo');
    document.body.style.overflow = '';
}

document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });


// Nuevo estudiante
document.querySelector('.btn-nuevo').addEventListener('click', function() {
    document.getElementById('modalNuevo').classList.add('activo');
    document.body.style.overflow = 'hidden';
});

function cerrarModalNuevo() {
    document.getElementById('modalNuevo').classList.remove('activo');
    document.body.style.overflow = '';
}

document.getElementById('modalNuevo').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalNuevo();
});