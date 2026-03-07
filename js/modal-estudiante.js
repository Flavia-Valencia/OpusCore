document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit-nombre').value     = this.dataset.nombre;
        document.getElementById('edit-contacto').value   = this.dataset.contacto;
        document.getElementById('edit-curso').value      = this.dataset.curso;
        document.getElementById('edit-estado').value     = this.dataset.estado;
        document.getElementById('edit-usuario').value    = this.dataset.usuario;
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