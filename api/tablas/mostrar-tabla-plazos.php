<?php
include("../../includes/conexion.php");

$columnasPlazo = [];
$resColumnas = mysqli_query($conexion, "SHOW COLUMNS FROM PlazoNotas");
if ($resColumnas) {
    while ($columna = mysqli_fetch_assoc($resColumnas)) {
        $columnasPlazo[] = $columna['Field'];
    }
}
$tienePlazo = in_array('plazoInicio', $columnasPlazo, true) && in_array('plazoFin', $columnasPlazo, true);
$selectPlazo = $tienePlazo ? ', pn.plazoInicio, pn.plazoFin' : ", NULL AS plazoInicio, NULL AS plazoFin";

$sql = "SELECT pn.id, pn.idPeriodo, pn.nombre, pi.nombre AS periodo_nombre, pn.estado $selectPlazo
        FROM PlazoNotas pn
        INNER JOIN PeriodoInscripcion pi ON pn.idPeriodo = pi.id
        ORDER BY pn.estado DESC, pn.id DESC";

$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0):
?>

<table class="data-table mobile-cards">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Periodo</th>
            <th>Inicio plazo</th>
            <th>Fin plazo</th>
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
            <td data-label="Periodo"><?php echo htmlspecialchars($p['periodo_nombre']); ?></td>
            <td data-label="Inicio plazo"><?php echo htmlspecialchars($p['plazoInicio']); ?></td>
            <td data-label="Fin plazo"><?php echo htmlspecialchars($p['plazoFin']); ?></td>
            <td data-label="Estado">
                <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
            </td>

            <td data-label="Acciones" class="acciones-cell">
                <div class="acciones-texto">
                    <a
                        href="#"
                        class="link-accion abrir-modal-plazo"
                        data-id="<?php echo $p['id']; ?>"
                        data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                        data-idperiodo="<?php echo $p['idPeriodo']; ?>"
                        data-plazo-inicio="<?php echo htmlspecialchars($p['plazoInicio']); ?>"
                        data-plazo-fin="<?php echo htmlspecialchars($p['plazoFin']); ?>"
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
