<?php
include('includes/conexion.php');   

$sql = "SELECT c.id,
               c.nombre, 
               c.descripcion, 
               c.fechaInicio, 
               c.fechaFin, 
               c.costoMensual, 
               c.cupos, 
               c.estado,
               c.idDocente,
               c.idPeriodo,
               pi.nombre AS periodo_nombre,
               CONCAT(u.nombre, ' ', u.apellido) AS docente_full,
               GROUP_CONCAT(p.idCursoPrevio) AS prerrequisitos
        FROM cursos c
        LEFT JOIN prerrequisitos p ON c.id = p.idCursoActual
        LEFT JOIN docentes d ON c.idDocente = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
        GROUP BY c.id
        ORDER BY c.estado DESC, c.nombre ASC"; // Ordena alfebéticamente las tablas

$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0 ){
?>
    <table class="data-table mobile-cards">
        <thead>
            <tr>
                <th>Nombre</th>
                <th class ="col-docente">Docente</th>
                <th>Periodo</th>
                <th>Descripción</th>
                <th class= "col-fecha">Fecha Inicio</th>
                <th class= "col-fecha">Fecha Fin</th>
                <th>Costo Mensual</th>
                <th>Cupos</th>
                <th>Estado</th>
                <th style="width: 250px; text-align: center;">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>
            <tr data-id="<?php echo $fila['id']; ?>">
                <td data-label="Nombre" class="col-nombre"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td data-label="Docente" class= "col-docente"><?php echo htmlspecialchars($fila['docente_full'] ?? 'Sin asignar'); ?></td>
                <td data-label="Periodo" class="col-periodo"><?php echo htmlspecialchars($fila['periodo_nombre'] ?? 'Sin asignar'); ?></td>
                <td data-label="Descripción" class="col-descripcion"><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                <td data-label="Fecha Inicio" class="col-fecha"><?php echo $fila['fechaInicio']; ?></td>
                <td data-label="Fecha Fin" class="col-fecha"><?php echo $fila['fechaFin']; ?></td>
                <td data-label="Costo Mensual">$<?php echo $fila['costoMensual']; ?></td>
                <td data-label="Cupos"><?php echo $fila['cupos']; ?></td>
                <td data-label="Estado"><?php echo $fila['estado'] == 1 ? 'Activo' : 'Inactivo'; ?></td>

                <td data-label="Acciones" class="acciones-cell">
                    <div class="acciones-texto">
                        
                        <!-- BOTÓN EDITAR -->
                         <?php
                         $idCurso = $fila['id'];
                        $sqlPre = "SELECT idCursoPrevio FROM prerrequisitos WHERE idCursoActual = '$idCurso'";
                        $resPre = mysqli_query($conexion, $sqlPre);

                        $ids = [];

                        while ($rowPre = mysqli_fetch_assoc($resPre)) {
                        $ids[] = $rowPre['idCursoPrevio'];
                        }

                        $prerrequisitosString = implode(",", $ids);
                        ?>
                        <a 
                            href="#"
                            class="link-accion abrir-modal-curso"
                            data-id="<?php echo $fila['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($fila['nombre']); ?>"
                            data-docente="<?php echo $fila['idDocente']; ?>"
                            data-periodo="<?php echo $fila['idPeriodo']; ?>"
                            data-descripcion="<?php echo htmlspecialchars($fila['descripcion']); ?>"
                            data-fechainicio="<?php echo $fila['fechaInicio']; ?>"
                            data-fechafin="<?php echo $fila['fechaFin']; ?>"
                            data-costo="<?php echo $fila['costoMensual']; ?>"
                            data-cupos="<?php echo $fila['cupos']; ?>"
                            data-prerrequisitos="<?php echo $prerrequisitosString; ?>"
                            data-estado="<?php echo $fila['estado']; ?>"
                            onclick="return false;"
                        >
                            Editar
                        </a>

                        <!-- BOTÓN de estado -->
                        <a 
                            href="javascript:void(0);" 
                            class="link-accion btn-toggle-estado <?php echo $fila['estado'] == 1 ? 'estado-activo' : 'estado-inactivo'; ?>"
                        >
                            <?php echo $fila['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>
                        </a>

                        <!-- BOTÓN HORARIOS -->
                        <a 
                            href="#"
                            class="link-accion horarios"
                            onclick="abrirModalHorarios(<?php echo $fila['id']; ?>); return false;"
                        >
                            Horarios 🕒
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