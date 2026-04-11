<?php
include('includes/conexion.php');   

$sql = "SELECT c.id, c.nombre, c.descripcion, c.duracion, c.precio FROM cursos c";
$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0 ){
?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Duración</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr>
                <td data-label="ID"><?php echo $fila['id']; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td data-label="Descripción"><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                <td data-label="Duración"><?php echo htmlspecialchars($fila['duracion']); ?></td>
                <td data-label="Precio">$<?php echo htmlspecialchars($fila['precio']); ?></td>

                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones-texto">
                        
                        <!-- BOTÓN EDITAR -->
                        <a 
                            href="#"
                            class="link-accion abrir-modal-curso"
                            data-id="<?php echo $fila['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($fila['nombre']); ?>"
                            data-descripcion="<?php echo htmlspecialchars($fila['descripcion']); ?>"
                            data-duracion="<?php echo htmlspecialchars($fila['duracion']); ?>"
                            data-precio="<?php echo htmlspecialchars($fila['precio']); ?>"
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