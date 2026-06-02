// Modales de estudiantes
document.querySelectorAll('.abrir-modal-estudiante').forEach(btn => {
    btn.addEventListener('click', function () {

        const modal = document.getElementById('modalEditar');
        if (!modal) return;

        document.getElementById('editd-estudiante_id').value = this.dataset.estudiante_id;
        document.getElementById('editd-usuario_id').value = this.dataset.usuario_id;
        document.getElementById('edit-nombre').value = this.dataset.nombre;
        document.getElementById('edit-apellido').value = this.dataset.apellido;
        document.getElementById('edit-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('edit-genero').value = this.dataset.genero;
        document.getElementById('edit-telefono').value = this.dataset.telefono;
        document.getElementById('edit-direccion').value = this.dataset.direccion;
        document.getElementById('edit-correo').value = this.dataset.correo;
        document.getElementById('edit-password_hash').value = this.dataset.password_hash;

        const estado = this.dataset.estado == 1 ? 'Activo' : 'Inactivo';
        document.getElementById('edit-estado').value = estado;

        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

function cerrarModal() {
    const modal = document.getElementById('modalEditar');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalEditar = document.getElementById('modalEditar');
if (modalEditar) {
    modalEditar.addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });
}

function cerrarModalNuevo() {
    const modal = document.getElementById('modalNuevo');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

const modalNuevo = document.getElementById('modalNuevo');
if (modalNuevo) {
    modalNuevo.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalNuevo();
    });
}

// Formularios de estudiantes
const formEditarEstudiante = document.querySelector('#modalEditar form');
if (formEditarEstudiante) {
    formEditarEstudiante.addEventListener('submit', async function (e) {
    e.preventDefault();

        const nombre = document.getElementById('edit-nombre').value.trim();
        const apellido = document.getElementById('edit-apellido').value.trim();
        const telefono = document.getElementById('edit-telefono').value.trim();
        const fechaNac = document.getElementById('edit-fecha_nacimiento').value.trim();
        const direccion = document.getElementById('edit-direccion').value.trim();
        const correo = document.getElementById('edit-correo').value.trim();
        const password = document.getElementById('edit-password_hash').value.trim();

        if (!nombre || !apellido || !telefono || !fechaNac || !direccion || !correo || !password) {
            e.preventDefault();
            mostrarToastPremium('Complete todos los campos');
            return;

        }
        const hoy      = new Date();
        const nacimiento = new Date(fechaNac);
        const minima   = new Date('1940-01-01');

        const anio = nacimiento.getFullYear();
        if (anio < 1000 || anio > 9999) {
            e.preventDefault();
            mostrarToastPremium('Ingresa un año válido (4 dígitos)');
            return;
        }

        let edad = hoy.getFullYear() - nacimiento.getFullYear();
        const mDiff = hoy.getMonth() - nacimiento.getMonth();
        if (mDiff < 0 || (mDiff === 0 && hoy.getDate() < nacimiento.getDate())) {
            edad--;
        }

        if (nacimiento < minima) {
            e.preventDefault();
            mostrarToastPremium('La fecha de nacimiento no puede ser anterior a 1940');
            return;
        }

        if (edad < 12) {
            e.preventDefault();
            mostrarToastPremium('El estudiante debe tener al menos 12 años');
            return;
        }

        const formData = new FormData(this);
        try {
            const res  = await fetch('../api/admin/editar-estudiante.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.error) {
                mostrarToastPremium(data.mensaje, 'error');
            } else {
                cerrarModal();
                mostrarToastPremium('Estudiante editado exitosamente', 'success');
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            mostrarToastPremium('Error de conexión', 'error');
        }
    });
}

const formNuevoEstudiante = document.querySelector('#modalNuevo form');
if (formNuevoEstudiante) {
    formNuevoEstudiante.addEventListener('submit', async function (e) {
        e.preventDefault();

        const nombre = formNuevoEstudiante.querySelector('[name="nombre"]').value.trim();
        const apellido = formNuevoEstudiante.querySelector('[name="apellido"]').value.trim();
        const telefono = formNuevoEstudiante.querySelector('[name="telefono"]').value.trim();
        const fechaNac = formNuevoEstudiante.querySelector('[name="fecha_nacimiento"]').value.trim();
        const direccion = formNuevoEstudiante.querySelector('[name="direccion"]').value.trim();
        const correo = formNuevoEstudiante.querySelector('[name="correo"]').value.trim();
        const password = formNuevoEstudiante.querySelector('[name="password_hash"]').value.trim();


        if (!nombre || !apellido || !telefono || !fechaNac || !direccion || !correo || !password) {
            e.preventDefault();
            mostrarToastPremium('Complete todos los campos');
            return;
        }
        const hoy      = new Date();
        const nacimiento = new Date(fechaNac);
        const minima   = new Date('1940-01-01');

        const anio = nacimiento.getFullYear();
        if (anio < 1000 || anio > 9999) {
            e.preventDefault();
            mostrarToastPremium('Ingresa un año válido (4 dígitos)');
            return;
        }

        let edad = hoy.getFullYear() - nacimiento.getFullYear();
        const mDiff = hoy.getMonth() - nacimiento.getMonth();
        if (mDiff < 0 || (mDiff === 0 && hoy.getDate() < nacimiento.getDate())) {
            edad--;
        }

        if (nacimiento < minima) {
            e.preventDefault();
            mostrarToastPremium('La fecha de nacimiento no puede ser anterior a 1940');
            return;
        }

        if (edad < 12) {
            e.preventDefault();
            mostrarToastPremium('El estudiante debe tener al menos 12 años');
            return;
        }

        const formData = new FormData(this);
        try {
            const res  = await fetch('../api/admin/crear-estudiante.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.error) {
                mostrarToastPremium(data.mensaje, 'error');
            } else {
                cerrarModalNuevo();
                mostrarToastPremium('Estudiante creado exitosamente', 'success');
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            mostrarToastPremium('Error de conexión', 'error');
        }
    });
}

// Buscador de estudiantes
const buscadorEstudiante = document.getElementById('buscador-estudiante');
if (buscadorEstudiante) {
    buscadorEstudiante.addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('.tabla-placeholder .data-table tbody tr');

        filas.forEach(function (fila) {
            const id = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();
            const apellido = fila.cells[2].textContent.toLowerCase();

            fila.style.display = (id.includes(filtro) || nombre.includes(filtro) || apellido.includes(filtro)) ? '' : 'none';
        });
    });
}
