// Abre el modal de edición de docentes y carga los datos del docente seleccionado en el formulario
document.querySelectorAll('.abrir-modal-docente').forEach(btn => {
    btn.addEventListener('click', function () {

        const modal = document.getElementById('modalEditarDocente');
        if (!modal) return;

        // rellena cada campo del formulario con los datos del docente
        document.getElementById('editd-docente_id').value = this.dataset.docente_id;
        document.getElementById('editd-usuario_id').value = this.dataset.usuario_id;
        document.getElementById('editd-nombre').value = this.dataset.nombre;
        document.getElementById('editd-apellido').value = this.dataset.apellido;
        document.getElementById('editd-especialidad').value = this.dataset.especialidad;
        document.getElementById('editd-fecha_nacimiento').value = this.dataset.fecha_nacimiento;
        document.getElementById('editd-genero').value = this.dataset.genero;
        document.getElementById('editd-salario').value = this.dataset.salario;
        document.getElementById('editd-telefono').value = this.dataset.telefono;
        document.getElementById('editd-direccion').value = this.dataset.direccion;
        document.getElementById('editd-correo').value = this.dataset.correo;
        document.getElementById('editd-password_hash').value = this.dataset.password_hash;

        // Convierte el valor numérico de estado a texto para que coincida con el select
        const estado = this.dataset.estado == 1 ? 'Activo' : 'Inactivo';
        document.getElementById('editd-estado').value = estado;
        // Mostrar el modal
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    });
});

// Cierra el modal de edición de docente y restaura el scroll
function cerrarModalDocente() {
    const modal = document.getElementById('modalEditarDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Cierra el modal de docente al hacer clic fuera de el
const modalEditarDocente = document.getElementById('modalEditarDocente');
if (modalEditarDocente) {
    modalEditarDocente.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalDocente();
    });
}

function cerrarModalNuevoDocente() {
    const modal = document.getElementById('modalNuevoDocente');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Cierra el modal de nuevo docente al hacer clic fuera de el
const modalNuevoDocente = document.getElementById('modalNuevoDocente');

if (modalNuevoDocente) {
    modalNuevoDocente.addEventListener('click', function (e) {
        if (e.target === this) cerrarModalNuevoDocente();
    });
}

// VALIDACIÓN DE CAMPOS EN MODAL EDITAR DOCENTE
const formEditarDocente = document.querySelector('#modalEditarDocente form');
if (formEditarDocente) {
    formEditarDocente.addEventListener('submit', async function (e) {
        e.preventDefault();

        const nombre = document.getElementById('editd-nombre').value.trim();
        const apellido = document.getElementById('editd-apellido').value.trim();
        const especialidad = document.getElementById('editd-especialidad').value.trim();
        const fechaNac = document.getElementById('editd-fecha_nacimiento').value.trim();
        const salario = document.getElementById('editd-salario').value.trim();
        const telefono = document.getElementById('editd-telefono').value.trim();
        const direccion = document.getElementById('editd-direccion').value.trim();
        const correo = document.getElementById('editd-correo').value.trim();
        const password = document.getElementById('editd-password_hash').value.trim();

        if (!nombre || !apellido || !especialidad || !fechaNac || !salario || !telefono || !direccion || !correo || !password) {
            e.preventDefault();
            mostrarToastPremium('Complete todos los campos');
            return;
        }

        const hoy      = new Date();
        const nacimiento = new Date(fechaNac);
        const minima   = new Date('1950-01-01');

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
            mostrarToastPremium('La fecha de nacimiento no puede ser anterior a 1950');
            return;
        }

        if (edad < 18) {
            e.preventDefault();
            mostrarToastPremium('El docente debe tener al menos 18 años');
            return;
        }

        const formData = new FormData(this);
        try {
            const res  = await fetch('../api/admin/editar-docente.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.error) {
                mostrarToastPremium(data.mensaje, 'error');
            } else {
                cerrarModalDocente();
                mostrarToastPremium('Docente editado exitosamente', 'success');
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            mostrarToastPremium('Error de conexión', 'error');
        }
    });
}

// VALIDACIÓN DE CAMPOS EN MODAL NUEVO DOCENTE
const formNuevoDocente = document.querySelector('#modalNuevoDocente form');
if (formNuevoDocente) {
    formNuevoDocente.addEventListener('submit', async function (e) {
        e.preventDefault();

        const nombre = formNuevoDocente.querySelector('[name="nombre"]').value.trim();
        const apellido = formNuevoDocente.querySelector('[name="apellido"]').value.trim();
        const especialidad = formNuevoDocente.querySelector('[name="especialidad"]').value.trim();
        const fechaNac = formNuevoDocente.querySelector('[name="fecha_nacimiento"]').value.trim();
        const salario = formNuevoDocente.querySelector('[name="salario"]').value.trim();
        const telefono = formNuevoDocente.querySelector('[name="telefono"]').value.trim();
        const direccion = formNuevoDocente.querySelector('[name="direccion"]').value.trim();
        const correo = formNuevoDocente.querySelector('[name="correo"]').value.trim();
        const password = formNuevoDocente.querySelector('[name="password_hash"]').value.trim();

        if (!nombre || !apellido || !especialidad || !fechaNac || !salario || !telefono || !direccion || !correo || !password) {
            e.preventDefault();
            mostrarToastPremium('Complete todos los campos');
            return;
        }

        const hoy      = new Date();
        const nacimiento = new Date(fechaNac);
        const minima   = new Date('1950-01-01');

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
            mostrarToastPremium('La fecha de nacimiento no puede ser anterior a 1950');
            return;
        }

        if (edad < 18) {
            e.preventDefault();
            mostrarToastPremium('El docente debe tener al menos 18 años');
            return;
        }

        const formData = new FormData(this);
        try {
            const res  = await fetch('../api/admin/crear-docente.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.error) {
                mostrarToastPremium(data.mensaje, 'error');
            } else {
                cerrarModalNuevoDocente();
                mostrarToastPremium('Docente creado exitosamente', 'success');
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            mostrarToastPremium('Error de conexión', 'error');
        }
    });
}

// --- BUSCADOR DOCENTES ---
const buscadorDocente = document.getElementById('buscador-docente');
if (buscadorDocente) {
    buscadorDocente.addEventListener('keyup', function () {
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
