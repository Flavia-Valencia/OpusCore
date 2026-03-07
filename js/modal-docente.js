document.querySelectorAll('.abrir-modal-docente').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editd-id').value               = this.dataset.id;
        document.getElementById('editd-nombre').value           = this.dataset.nombre;
        document.getElementById('editd-apellido').value         = this.dataset.apellido;
        document.getElementById('editd-especialidad').value     = this.dataset.especialidad;
        document.getElementById('editd-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('editd-genero').value           = this.dataset.genero;
        document.getElementById('editd-salario').value          = this.dataset.salario;
        document.getElementById('editd-telefono').value         = this.dataset.telefono;
        document.getElementById('editd-direccion').value        = this.dataset.direccion;
        document.getElementById('editd-correo').value           = this.dataset.correo;
        document.getElementById('modalEditarDocente').classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

function cerrarModalDocente() {
    document.getElementById('modalEditarDocente').classList.remove('activo');
    document.body.style.overflow = '';
}

document.getElementById('modalEditarDocente').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalDocente();
});

document.querySelector('.btn-nuevo').addEventListener('click', function() {
    document.getElementById('modalNuevoDocente').classList.add('activo');
    document.body.style.overflow = 'hidden';
});

function cerrarModalNuevoDocente() {
    document.getElementById('modalNuevoDocente').classList.remove('activo');
    document.body.style.overflow = '';
}

document.getElementById('modalNuevoDocente').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalNuevoDocente();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModalDocente(); cerrarModalNuevoDocente(); }
});