<?php
include('includes/conexion.php'); 

$sql = "SELECT
    u.id AS usuario_id,
    u.nombre, 
    u.apellido, 
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
        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr>
                <td data-label="ID"><?php echo $fila['usuario_id']; ?></td>
                <td data-label="Nombre"><?php echo $fila['nombre']; ?></td>
                <td data-label="Apellido"><?php echo $fila['apellido']; ?></td>
                <td data-label="Fecha Nac."><?php echo $fila['fecha_nacimiento']; ?></td>
                <td data-label="Género"><?php echo $fila['genero']; ?></td>
                <td data-label="Teléfono"><?php echo $fila['telefono']; ?></td>
                <td data-label="Dirección"><?php echo $fila['direccion']; ?></td>
                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones">
                        <a href="editar-estudiante.php?id=<?php echo $fila['usuario_id']; ?>" class="btn-accion btn-editar">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="eliminar-estudiantes.php?id=<?php echo $fila['usuario_id']; ?>" class="btn-accion btn-eliminar" onclick="return confirm('¿Eliminar estudiante?')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php 
    }else{
        echo '<div class="mensaje-vacio">No hay estudiantes registrados.</div>';
    }
?>