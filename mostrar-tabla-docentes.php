<?php
include('includes/conexion.php'); 

$sql = "SELECT
    u.id AS usuario_id,
    u.nombre, 
    u.apellido, 
    d.especialidad,
    d.fecha_nacimiento,
    d.genero,
    d.salario,
    d.telefono,
    d.direccion
    FROM docentes d
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE u.rol_id = 3";

$resultado = mysqli_query($conexion, $sql);
 if (mysqli_num_rows($resultado) > 0 ){
    ?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Especialidad</th>
                <th>Fecha Nac.</th>
                <th>Género</th>
                <th>Salario</th>
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
                <td data-label="Especialidad"><?php echo $fila['especialidad']; ?></td>
                <td data-label="Fecha Nac."><?php echo $fila['fecha_nacimiento']; ?></td>
                <td data-label="Género"><?php echo $fila['genero']; ?></td>
                <td data-label="Salario"><?php echo $fila['salario']; ?></td>
                <td data-label="Teléfono"><?php echo $fila['telefono']; ?></td>
                <td data-label="Dirección"><?php echo $fila['direccion']; ?></td>
                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones">
                        <a href="editar-docente.php?id=<?php echo $fila['usuario_id']; ?>" class="btn-accion btn-editar">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="eliminar-docentes.php?id=<?php echo $fila['usuario_id']; ?>" class="btn-accion btn-eliminar" onclick="return confirm('¿Eliminar docente?')">
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
        echo '<div class="mensaje-vacio">No hay docentes registrados.</div>';
    }
?>