<?php
include('includes/conexion.php');

$columnasPeriodo = [];
$resColumnas = mysqli_query($conexion, "SHOW COLUMNS FROM PeriodoInscripcion");
if ($resColumnas) {
    while ($columna = mysqli_fetch_assoc($resColumnas)) {
        $columnasPeriodo[] = $columna['Field'];
    }
}
$tieneFechasCiclo = in_array('fechaInicioCiclo', $columnasPeriodo, true) && in_array('fechaFinCiclo', $columnasPeriodo, true);
$selectFechasCiclo = $tieneFechasCiclo ? ', fechaInicioCiclo, fechaFinCiclo' : ", NULL AS fechaInicioCiclo, NULL AS fechaFinCiclo";

$sql = "SELECT id, nombre, fechaInicio, fechaFin, estado $selectFechasCiclo
        FROM PeriodoInscripcion 
        ORDER BY estado DESC, id DESC";

$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0):
?>

<table class="data-table mobile-cards">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Inicio inscripción</th>
            <th>Fin inscripción</th>
            <th>Inicio ciclo</th>
            <th>Fin ciclo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php while($p = mysqli_fetch_assoc($resultado)): 
            $esActivo = ($p['estado'] == 1);
        ?>
        <tr data-id="<?php echo $p['id']; ?>">
            <td data-label="ID"><?php echo $p['id']; ?></td>
            <td data-label="Nombre"><?php echo htmlspecialchars($p['nombre']); ?></td>
            <td data-label="Inicio inscripción"><?php echo htmlspecialchars($p['fechaInicio']); ?></td>
            <td data-label="Fin inscripción"><?php echo htmlspecialchars($p['fechaFin']); ?></td>
            <td data-label="Inicio ciclo"><?php echo $p['fechaInicioCiclo'] ? htmlspecialchars($p['fechaInicioCiclo']) : '—'; ?></td>
            <td data-label="Fin ciclo"><?php echo $p['fechaFinCiclo'] ? htmlspecialchars($p['fechaFinCiclo']) : '—'; ?></td>

            <td data-label="Estado">
                <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
            </td>

            <td data-label="Acciones" class="acciones-cell">
                <div class="acciones-texto">
                    <a
                        href="#"
                        class="link-accion abrir-modal-periodo"
                        data-id="<?php echo $p['id']; ?>"
                        data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                        data-fecha_inicio="<?php echo htmlspecialchars($p['fechaInicio']); ?>"
                        data-fecha_fin="<?php echo htmlspecialchars($p['fechaFin']); ?>"
                        data-fecha_inicio_ciclo="<?php echo htmlspecialchars($p['fechaInicioCiclo'] ?? ''); ?>"
                        data-fecha_fin_ciclo="<?php echo htmlspecialchars($p['fechaFinCiclo'] ?? ''); ?>"
                        onclick="return false;"
                    >
                        Editar
                    </a>

                    <a
                        href="#"
                        class="link-accion btn-toggle-estado <?php echo $esActivo ? 'estado-activo' : 'estado-inactivo'; ?>"
                    >
                        <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="mensaje-vacio">No hay períodos registrados.</div>
<?php endif; ?>
