// Registro de notas ---------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // Evita errores si no existe el curso
    if (typeof cursoId === 'undefined') return;

    const storageKey = `opus_grades_curso_${cursoId}`;
    // Aquí se almacenan temporalmente las notas
    let gradesData = {};
    // Cargar notas guardadas en localStorage
    try {
        const saved = localStorage.getItem(storageKey);

        if (saved) {
            gradesData = JSON.parse(saved);
        }
    } catch (e) {
        console.error('Error al cargar las notas', e);
    }
    const inputs = document.querySelectorAll('.nota-input');
    const rows = document.querySelectorAll('.estudiante-row');

    // Restaurar notas guardadas y agregar eventos
    inputs.forEach(input => {
        const inscId = input.dataset.inscId;
        const notaNum = input.dataset.notaNum;

        if (
            gradesData[inscId] &&
            gradesData[inscId][`nota${notaNum}`] !== undefined
        ) {
            input.value = gradesData[inscId][`nota${notaNum}`];
        }
        // Guardar automáticamente al salir del input
        input.addEventListener('blur', function () {
            guardarNota(this);
        });
        // También guardar con Enter
        input.addEventListener('keydown', function (e) {

            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
    // Calcular promedios iniciales
    recalcularTodosLosPromedios();
    // Buscador de estudiantes
    const buscador = document.getElementById('buscarEstudiantes');

    if (buscador) {
        buscador.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();

            rows.forEach(row => {

                const searchVal = row.dataset.search;

                row.style.display =
                    searchVal.includes(term) ? '' : 'none';
            });
        });
    }
    // Guardar nota del estudiante
    function guardarNota(input) {

        const inscId = input.dataset.inscId;
        const notaNum = input.dataset.notaNum;

        const container =
            input.closest('.grade-input-container');

        const indicator =
            container.querySelector('.save-indicator');

        let valor = input.value.trim();

        input.classList.remove('input-error');
        // Si el campo queda vacío
        if (valor === '') {
            actualizarStorage(inscId, notaNum, undefined);

            mostrarIndicadorGuardado(
                indicator,
                'saved',
                '<i class="fas fa-check"></i>'
            );
            recalcularPromedioFila(inscId);
            recalcularKPIs();

            return;
        }

        const num = parseFloat(valor);
        // Validar rango permitido
        if (isNaN(num) || num < 0 || num > 10) {
            input.classList.add('input-error');

            if (typeof mostrarToastPremium === 'function') {
                mostrarToastPremium(
                    'La nota debe estar entre 1 y 10',
                    'error'
                );
            } else {
                alert('La nota debe estar entre 1 y 10');
            }

            mostrarIndicadorGuardado(
                indicator,
                'error',
                '<i class="fas fa-times"></i>'
            );
            // Restaurar valor anterior
            if (
                gradesData[inscId] &&
                gradesData[inscId][`nota${notaNum}`] !== undefined
            ) {
                input.value =
                    gradesData[inscId][`nota${notaNum}`];
            } else {
                input.value = '';
            }
            return;
        }

        // Mostrar animación de guardado
        mostrarIndicadorGuardado(
            indicator,
            'saving',
            '<i class="fas fa-circle-notch fa-spin"></i>'
        );

        setTimeout(() => {
            const finalVal = parseFloat(num.toFixed(2));
            input.value = finalVal.toFixed(2);

            actualizarStorage(inscId, notaNum, finalVal);

            mostrarIndicadorGuardado(
                indicator,
                'saved',
                '<i class="fas fa-check"></i>'
            );
            recalcularPromedioFila(inscId);
            recalcularKPIs();
        }, 500);
    }

    // Actualizar notas en localStorage provisional hasta implementar guardado definitivo
    function actualizarStorage(inscId, notaNum, valor) {

        if (!gradesData[inscId]) {
            gradesData[inscId] = {};
        }

        if (valor === undefined) {
            delete gradesData[inscId][`nota${notaNum}`];

            if (Object.keys(gradesData[inscId]).length === 0) {
                delete gradesData[inscId];
            }
        } else {
            gradesData[inscId][`nota${notaNum}`] = valor;
        }
        localStorage.setItem(
            storageKey,
            JSON.stringify(gradesData)
        );
    }

    // Mostrar estado visual del guardado
    function mostrarIndicadorGuardado(
        indicator,
        estado,
        iconHTML
    ) {
        indicator.className = `save-indicator ${estado}`;
        indicator.innerHTML = iconHTML;

        if (estado === 'saved' || estado === 'error') {
            setTimeout(() => {
                if (indicator.classList.contains(estado)) {
                    indicator.className = 'save-indicator';
                }
            }, 2000);
        }
    }
    // Calcular promedio individual
    function recalcularPromedioFila(inscId) {
        const badge =
            document.getElementById(`promedio-${inscId}`);

        if (!badge) return;

        const row = badge.closest('tr');

        const inputsFila =
            row.querySelectorAll('.nota-input');

        let suma = 0;
        let count = 0;

        inputsFila.forEach(inp => {
            const val = inp.value.trim();

            if (val !== '') {
                suma += parseFloat(val);
                count++;
            }
        });

        badge.className = 'promedio-badge';

        // Si ambas notas están completas
        if (count === 2) {
             const nota1 = parseFloat(inputsFila[0].value.trim());
            const nota2 = parseFloat(inputsFila[1].value.trim());
            const prom  = parseFloat(((nota1 * 0.30) + (nota2 * 0.70)).toFixed(2));
            badge.textContent = prom.toFixed(2);

            if (prom >= 7.0) {
            badge.classList.add('promedio-aprobado');
        } else if (prom >= 6.0) {
            badge.classList.add('promedio-alerta');
        } else {
            badge.classList.add('promedio-reprobado');
        }
        } else {
            badge.textContent = '—';
            badge.classList.add('promedio-vacio');
        }
    }

    // Cargar promedio grupal real desde BD si existe
if (typeof promedioGrupalInicial !== 'undefined' && promedioGrupalInicial !== null) {
    const kpiPromedio = document.getElementById('kpi-promedio-grupal');
    if (kpiPromedio) kpiPromedio.textContent = parseFloat(promedioGrupalInicial).toFixed(2);
}
    // Recalcular todos los promedios
    function recalcularTodosLosPromedios() {
        inputs.forEach(inp => {
            const inscId = inp.dataset.inscId;
            recalcularPromedioFila(inscId);
        });
        recalcularKPIs();
    }
    // Actualizar métricas generales del curso
   // Actualizar métricas generales del curso
function recalcularKPIs() {
    const badges = document.querySelectorAll('[id^="promedio-"]');

    let sumaPromedios = 0;
    let aprobados = 0;
    let promediosValidosCount = 0;

    badges.forEach(b => {
        const text = b.textContent.trim();

        if (text !== '—') {
            const val = parseFloat(text);
            sumaPromedios += val;
            promediosValidosCount++;

            if (val >= 7.00) aprobados++;
        }
    });

    const kpiPromedio  = document.getElementById('kpi-promedio-grupal');
    const kpiAprobacion = document.getElementById('kpi-porcentaje-aprobacion');

    if (promediosValidosCount > 0) {
        const promedioGrupal = (sumaPromedios / promediosValidosCount).toFixed(2);
        const tasaAprobacion = Math.round((aprobados / promediosValidosCount) * 100);

        if (kpiPromedio)   kpiPromedio.textContent   = promedioGrupal;
        if (kpiAprobacion) kpiAprobacion.textContent = `${tasaAprobacion}%`;
    } else {
        if (kpiPromedio)   kpiPromedio.textContent   = '0.00';
        if (kpiAprobacion) kpiAprobacion.textContent = '0%';
    }
}
    
   // Botones de guardado manual
   // Botón Editar
document.querySelectorAll('.btn-nota-editar').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!plazoActivo) {
            mostrarToastPremium('El plazo de notas está cerrado. No se puede editar.', 'error');
            return;
        }
        const row = this.closest('tr');
        row.querySelectorAll('.nota-input').forEach(inp => inp.removeAttribute('readonly'));
        row.querySelectorAll('.nota-input')[0].focus();
        
        const btnGuardar = row.querySelector('.btn-guardar-nota');
        btnGuardar.disabled = false;
        btnGuardar.style.display = '';
        this.style.display = 'none';
    });
});

// Botón Guardar
document.querySelectorAll('.btn-guardar-nota').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!plazoActivo) {
            mostrarToastPremium('El plazo de notas está cerrado. No se pueden guardar cambios.', 'error');
            return;
        }

        const row = this.closest('tr');
        const inputsFila = row.querySelectorAll('.nota-input');
        const nota1 = inputsFila[0].value.trim();
        const nota2 = inputsFila[1].value.trim();

        if (nota1 === '' || nota2 === '') {
            mostrarToastPremium('Debes ingresar ambas notas antes de guardar.', 'error');
            return;
        }

        const estudianteId = inputsFila[0].dataset.estudianteId;
        const inscId       = inputsFila[0].dataset.inscId;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Guardando...';

        fetch('guardar-nota.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id:      cursoId,
                estudiante_id: parseInt(estudianteId),
                actividades:   parseFloat(nota1),
                examen_final:  parseFloat(nota2),
                plazo_id:      plazoActivo.id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarToastPremium(data.message, 'success');
                inputsFila.forEach(inp => inp.setAttribute('readonly', true));

                const badge = document.getElementById(`promedio-${inscId}`);
                if (badge && data.nota_final !== undefined) {
                    const nf = parseFloat(data.nota_final).toFixed(2);
                    badge.textContent = nf;
                    badge.className = 'promedio-badge';
                    badge.classList.add(parseFloat(data.nota_final) >= 6 ? 'promedio-aprobado' : 'promedio-reprobado');
                }

               
                let btnEditar = row.querySelector('.btn-nota-editar');
                if (!btnEditar) {
                    btnEditar = document.createElement('button');
                    btnEditar.className = 'btn-nota-editar';
                    btnEditar.dataset.accion = 'editar';
                    btnEditar.innerHTML = '<i class="fas fa-pen"></i> Editar';
                    btnEditar.addEventListener('click', function () {
                        if (!plazoActivo) {
                            mostrarToastPremium('El plazo de notas está cerrado. No se puede editar.', 'error');
                            return;
                        }
                        row.querySelectorAll('.nota-input').forEach(inp => inp.removeAttribute('readonly'));
                        row.querySelectorAll('.nota-input')[0].focus();
                        row.querySelector('.btn-guardar-nota').disabled = false;
                        this.style.display = 'none';
                    });
                    row.querySelector('.acciones-nota-group').prepend(btnEditar);
                } else {
                    btnEditar.style.display = '';
                }
                this.style.display = 'none';
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-save"></i> Guardar';

                recalcularKPIs();
            } else {
                mostrarToastPremium(data.message, 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Guardar';
            }
        })
        .catch(() => {
            mostrarToastPremium('Error de conexión al guardar', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save"></i> Guardar';
        });
    });
});
});

