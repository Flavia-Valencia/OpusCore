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
    <table border = "1">
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
        <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
        <tr>
            <td><?php echo $fila['usuario_id']; ?></td>
            <td><?php echo $fila['nombre']; ?></td>
            <td><?php echo $fila['apellido']; ?></td>
            <td><?php echo $fila['fecha_nacimiento']; ?></td>
            <td><?php echo $fila['genero']; ?></td>
            <td><?php echo $fila['telefono']; ?></td>
            <td><?php echo $fila['direccion']; ?></td>
            <td>

                <a href="editar-estudiante.php?id=<?php echo $fila['usuario_id']; ?>">Editar</a>
                <a href="eliminar-estudiante.php?id=<?php echo $fila['usuario_id']; ?>">Eliminar</a>

            </td>
        </tr>
        <?php } ?>
    </table>
<?php 
    }else{
        echo "No hay estudiantes registrados.";
    }
?>