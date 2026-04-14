<?php
include('includes/conexion.php');   

// consulta que une la tabla docentes con usuarios para obtener todos los datos del docente
$sql = "SELECT
    u.id AS usuario_id,
    u.nombre, 
    u.apellido,
    u.correo,
    u.password_hash,
    u.estado,
    d.especialidad,
    d.fecha_nacimiento,
    d.genero,
    d.salario,
    d.telefono,
    d.direccion
    FROM docentes d 
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE u.rol_id = 3"; // rol_id 3 corresponde al rol de docente

$resultado = mysqli_query($conexion, $sql);

// solo muestra la tabla si hay docentes registrados, si no muestra un mensaje vacío
if (mysqli_num_rows($resultado) > 0 ){
?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Especialidad</th>
                <th>Fecha de Nacimiento</th>
                <th>Género</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr>
                <td data-label="ID"><?php echo $fila['usuario_id']; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td data-label="Apellido"><?php echo htmlspecialchars($fila['apellido']); ?></td>
                <td data-label="Especialidad"><?php echo htmlspecialchars($fila['especialidad']); ?></td>
                <td data-label="Fecha Nac."><?php echo htmlspecialchars($fila['fecha_nacimiento']); ?></td>
                <td data-label="Género"><?php echo htmlspecialchars($fila['genero']); ?></td>
                <td data-label="Teléfono"><?php echo htmlspecialchars($fila['telefono']); ?></td>
                <td data-label="Dirección"><?php echo htmlspecialchars($fila['direccion']); ?></td>
                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones-texto">
                         <!-- Los data-* pasan los datos del docente al modal de edición via JavaScript -->
                        <a 
                            href="#"
                            class="link-accion abrir-modal-docente"
                            data-id="<?php echo $fila['usuario_id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($fila['nombre']); ?>"
                            data-apellido="<?php echo htmlspecialchars($fila['apellido']); ?>"
                            data-especialidad="<?php echo htmlspecialchars($fila['especialidad']); ?>"
                            data-fecha_nacimiento="<?php echo htmlspecialchars($fila['fecha_nacimiento']); ?>"
                            data-genero="<?php echo htmlspecialchars($fila['genero']); ?>"
                            data-salario="<?php echo htmlspecialchars($fila['salario']); ?>"
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
                        
                        <!-- BOTÓN TOGGLE ESTADO -->

                        <a 
                            href="#" 
                             class="link-accion btn-toggle-estado <?php echo ($fila['estado'] == 'Activo' || $fila['estado'] == 1) ? 'estado-activo' : 'estado-inactivo'; ?>"
                        >
                            <?php echo ($fila['estado'] == 'Activo' || $fila['estado'] == 1) ? 'Activo' : 'Inactivo'; ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php 
} else {
    echo '<div class="mensaje-vacio">No hay docentes registrados.</div>';
}
?>