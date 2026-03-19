<?php
include('includes/conexion.php');   

$sql = "SELECT
    u.id AS usuario_id,
    u.nombre, 
    u.apellido,
    u.correo,
    u.password_hash,
    u.estado,
    e.fecha_nacimiento,
    e.genero,
    e.telefono,
    e.direccion
    FROM estudiantes e 
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.rol_id = 2";
    
$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0 ){
?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Fecha de Nacimiento</th>
                <th>Género</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <!-- Editar estudiante-->
        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr>
                <td data-label="ID"><?php echo $fila['usuario_id']; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td data-label="Apellido"><?php echo htmlspecialchars($fila['apellido']); ?></td>
                <td data-label="Fecha Nac."><?php echo htmlspecialchars($fila['fecha_nacimiento']); ?></td>
                <td data-label="Género"><?php echo htmlspecialchars($fila['genero']); ?></td>
                <td data-label="Teléfono"><?php echo htmlspecialchars($fila['telefono']); ?></td>
                <td data-label="Dirección"><?php echo htmlspecialchars($fila['direccion']); ?></td>
                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones-texto">
                        <a 
                            href="#"
                            class="link-accion abrir-modal-estudiante"
                            data-id="<?php echo $fila['usuario_id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($fila['nombre']); ?>"
                            data-apellido="<?php echo htmlspecialchars($fila['apellido']); ?>"
                            data-fecha_nacimiento="<?php echo htmlspecialchars($fila['fecha_nacimiento']); ?>"
                            data-genero="<?php echo htmlspecialchars($fila['genero']); ?>"
                            data-telefono="<?php echo htmlspecialchars($fila['telefono']); ?>"
                            data-direccion="<?php echo htmlspecialchars($fila['direccion']); ?>"
                            data-correo="<?php echo htmlspecialchars($fila['correo']); ?>"
                            data-password_hash="<?php echo htmlspecialchars($fila['password_hash']); ?>"
                            data-estado="<?php echo htmlspecialchars($fila['estado']); ?>"
                            onclick="return false;"
                        >
                            Editar
                        </a>

                        <span class="separador-acciones">|</span>

                        <a 
                            href="eliminar-estudiantes.php?id=<?php echo $fila['usuario_id']; ?>" 
                            class="link-accion eliminar"
                            onclick="return confirm('¿Eliminar estudiante?')"
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
    echo '<div class="mensaje-vacio">No hay estudiantes registrados.</div>';
}
?>