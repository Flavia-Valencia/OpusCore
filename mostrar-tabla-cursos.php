<?php
include('includes/conexion.php');   

$sql = "SELECT c.id, c.nombre, c.descripcion, c.fechaInicio, c.fechaFin, c.costoMensual, c.cupos, c.estado FROM cursos c";
$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0 ){
?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Costo Mensual</th>
                <th>Cupos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr>
                <td data-label="ID"><?php echo $fila['id']; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td data-label="Descripción"><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                <td data-label="Fecha Inicio"><?php echo $fila['fechaInicio']; ?></td>
                <td data-label="Fecha Fin"><?php echo $fila['fechaFin']; ?></td>
                <td data-label="Costo Mensual">$<?php echo $fila['costoMensual']; ?></td>
                <td data-label="Cupos"><?php echo $fila['cupos']; ?></td>
                <td data-label="Estado"><?php echo $fila['estado'] == 1 ? 'Activo' : 'Inactivo'; ?></td>

                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones-texto">
                        
                        <!-- BOTÓN EDITAR -->
                        <a 
                            href="#"
                            class="link-accion abrir-modal-curso"
                            data-id="<?php echo $fila['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($fila['nombre']); ?>"
                            data-descripcion="<?php echo htmlspecialchars($fila['descripcion']); ?>"
                            data-fechainicio="<?php echo $fila['fechaInicio']; ?>"
                            data-fechafin="<?php echo $fila['fechaFin']; ?>"
                            data-costo="<?php echo $fila['costoMensual']; ?>"
                            data-cupos="<?php echo $fila['cupos']; ?>"
                            data-estado="<?php echo $fila['estado']; ?>"
                            onclick="return false;"
                        >
                            Editar
                        </a>

                        <span class="separador-acciones">|</span>

                        <!-- BOTÓN ELIMINAR -->
                        <a 
                            href="eliminar-curso.php?id=<?php echo $fila['id']; ?>" 
                            class="link-accion eliminar"
                            onclick="return confirm('¿Eliminar curso?')"
                        >
                            Eliminar
                        </a>

                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

<?php 
} else {
    echo '<div class="mensaje-vacio">No hay cursos registrados.</div>';
}
?>