<?php
include('includes/conexion.php');

$sql = "SELECT id, nombre, fechaInicio, fechaFin, estado 
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
            <th>Fecha inicio</th>
            <th>Fecha fin</th>
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
            <td data-label="Fecha inicio"><?php echo htmlspecialchars($p['fechaInicio']); ?></td>
            <td data-label="Fecha fin"><?php echo htmlspecialchars($p['fechaFin']); ?></td>

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