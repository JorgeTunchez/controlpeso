<?php 
require_once("core/core.php");
boolDebug(false);
session_start();

if (isset($_SESSION['user_id'])) {
    $strRolUserSession = getRolUserSession($_SESSION['user_id']);
    $intIDUserSession = getIDUserSession($_SESSION['user_id']);

    if ($strRolUserSession != '') {
        $arrRolUser["ID"] = $intIDUserSession;
        $arrRolUser["NAME"] = $_SESSION['user_id'];
    }
} else {
    header("Location: index.php");
}

$objController = new control_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class control_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new control_model($arrRolUser);
        $this->objView = new control_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->process();
    }

    public function drawContentController(){
        $this->objView->drawContent();
    }

    public function ajaxDestroySession(){
        if (isset($_POST["destroySession"])) {
            header("Content-Type: application/json;");
            session_destroy();
            $arrReturn["Correcto"] = "Y";
            print json_encode($arrReturn);
            exit();
        }
    }

    public function process(){
        if( isset($_POST['process']) ){
            $accion = isset($_POST["accion"])? trim($_POST["accion"]): "";
            $id = isset($_POST["id"])? trim($_POST["id"]): 0;
            $fecha = isset($_POST["fecha"])? trim($_POST["fecha"]): "";
            $peso = isset($_POST["peso"])? trim($_POST["peso"]): "";
            $usuario = intval($this->arrRolUser["ID"]);

            if( $accion == 'I'){
                $boolDuplicado = $this->objModel->validarDuplicado($fecha);
                if( !$boolDuplicado ){
                    $resp = $this->objModel->crudControl($accion, $id, $fecha, $peso, $usuario);
                }else{
                    $resp = 2;
                }
            }else{
                $resp = $this->objModel->crudControl($accion, $id, $fecha, $peso, $usuario);
            }

            $arrReturn["Respuesta"] = $resp;
            print json_encode($arrReturn);
            exit();

        }
    }

     
}

class control_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function getControlPeso($usuario){
        $arrControl = array();
        $sql = "SELECT co.id_control id,
                       co.fecha, 
                       co.peso, 
                       co.imc, 
                       ca.id_categoria categoria,
                       ca.nombre categoria_nombre 
                  FROM controlpeso.control co
                       INNER JOIN controlpeso.categorias ca ON co.categoria = ca.id_categoria 
                 WHERE co.usuario = $usuario 
                   AND co.activo = 1
              ORDER BY co.fecha DESC";
        $result = executeQuery($sql);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrControl[$row["id"]]["FECHA"] = $row["fecha"];
                $arrControl[$row["id"]]["PESO"] = $row["peso"];
                $arrControl[$row["id"]]["IMC"] = $row["imc"];
                $arrControl[$row["id"]]["CATEGORIA"] = $row["categoria"];
                $arrControl[$row["id"]]["NOMBRE_CATEGORIA"] = $row["categoria_nombre"];
            }
        }

        return $arrControl;
    }

    public function validarDuplicado($fecha){
        $boolDuplicado = false;
        $conteo = 0;
        $strQuery = "SELECT COUNT(*) conteo FROM controlpeso.control WHERE fecha = '$fecha' AND activo = 1";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $conteo = intval($row["conteo"]);
            }
        }

        $boolDuplicado = ( $conteo>0 )? true:false;
        return $boolDuplicado;
    }


    public function obtenerIMC($peso){
        $altura = getAlturaConfig();
        $imc = ($peso/2.20462)/($altura*$altura);
        return $imc;
    }

    public function obtenerCategoria($imc){
        $categoria = 0;
        $sql = "SELECT id_categoria id
                  FROM controlpeso.categorias c 
                 WHERE valor_inicial <= $imc AND valor_final >= $imc";
        $result = executeQuery($sql);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categoria = intval($row["id"]);
            }
        }

        return $categoria;
    }


    public function crudControl($accion, $id, $fecha, $peso, $usuario){
        
        $imc = $this->obtenerIMC($peso);
        $categoria = $this->obtenerCategoria($imc); 
        
        // Preparar la llamada al procedimiento almacenado
        $conn = getConexion();
        $query = "CALL CrudControl(?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        // Vincular los parámetros
        $stmt->bind_param('siddisi', $accion, $id, $peso, $imc, $categoria, $fecha, $usuario);

        // Ejecutar el procedimiento almacenado
        $resp = ( $stmt->execute() )? "1":"0";

        // Cerrar la conexión
        $stmt->close();
        mysqli_close($conn);

        return $resp; 
    }

}

class control_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new control_model($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        drawHeader($this->arrRolUser, "control");
        $nombreOpcion = ucwords(strtolower(getNombreOpcion(basename(__FILE__))));
        $idUsuario = intval($this->arrRolUser["ID"]);
        $pesoIdeal = getPesoIdealConfig();
        $zona_horaria = new DateTimeZone('America/Guatemala');
        $hora_actual = new DateTime('now', $zona_horaria);
        ?>
        <div class="content-wrapper">

            <!-- Modal Agregar-->
            <div class="modal fade" id="modal-agregar">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Agregar Registro</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" class="form-control" id="fecha" value="<?php echo $hora_actual->format("Y-m-d");?>">
                                </div>
                                <div class="form-group">
                                    <label for="peso">Peso</label>
                                    <input type="text" class="form-control" id="peso" placeholder="Peso">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="registrar()">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Modal Editar -->
            <div class="modal fade" id="modal-editar">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Editar Registro</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input type="hidden" class="form-control" id="editid">
                                    <input type="date" class="form-control" id="editFecha">
                                </div>
                                <div class="form-group">
                                    <label for="peso">Peso</label>
                                    <input type="text" class="form-control" id="editPeso" placeholder="Peso">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="editar()">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Eliminar -->
            <div class="modal fade" id="modal-eliminar">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Eliminar Registro</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group text-center">
                                    <p><h3>¿Desea eliminar el registro?</h3></p>
                                    <input type="hidden" class="form-control" id="elimid">
                                    <input type="hidden" class="form-control" id="elimfecha">
                                    <input type="hidden" class="form-control" id="elimpeso">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="eliminar()">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>



            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?php print $nombreOpcion; ?></h1>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row text-justify">
                                        <div class="col-10">
                                            <h3>Peso Ideal: <?php print $pesoIdeal;?> lbs.</h3>
                                        </div>
                                        <div class="col-2">
                                            <button class="btn btn-success btn-block" onclick="openModalAgregar()"><i class="far fas fa-plus-circle"></i>&nbsp;Agregar</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    
                                    $arrControl = $this->objModel->getControlPeso($idUsuario);
                                    if (count($arrControl) > 0) {
                                        ?>
                                        <table id="tblControl" class="table table-bordered table-hover">
                                            <thead style="background-color: #343a40; color: #fff;">
                                                <tr>
                                                    <th></th>
                                                    <th style="vertical-align: middle;">Fecha</th>
                                                    <th style="vertical-align: middle;">Peso (lbs)</th>
                                                    <th style="vertical-align: middle;">IMC</th>
                                                    <th style="vertical-align: middle;">Categoria</th>
                                                    <th style="vertical-align: middle;">Diferencia Peso</th>
                                                    <th style="vertical-align: middle;">Editar</th>
                                                    <th style="vertical-align: middle;">Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $cont = 0;
                                                foreach( $arrControl as $key => $val ){
                                                    $cont++;
                                                    $id = $key;
                                                    $fecha = $val["FECHA"];
                                                    $fechaFormateada = formatoFecha($fecha);
                                                    $peso = $val["PESO"];
                                                    $imc = $val["IMC"];
                                                    $categoria = $val["CATEGORIA"];
                                                    $nombreCategoria = $val["NOMBRE_CATEGORIA"];
                                                    $strNombreCategoria = ucwords($nombreCategoria);
                                                    $diferenciaPeso = number_format($peso - $pesoIdeal,2);

                                                    if( $imc>=16 && $imc<=18.5){
                                                        $strLeyenda = "Poco Peso";
                                                        $strColor = "#3498db";
                                                    }elseif( $imc>=18.6 && $imc<=25 ){
                                                        $strLeyenda = "Normal";
                                                        $strColor = "#27ae60";
                                                    }elseif( $imc>=25.1 && $imc<=40 ){
                                                        $strLeyenda = "Sobrepeso";
                                                        $strColor = "#e74c3c";
                                                    }

                                                    ?>
                                                    <tr>
                                                        <td style="vertical-align: middle;"><input id="color_<?php print $cont; ?>" title="<?php print $strLeyenda; ?>" type="color" class="form-control" value="<?php print $strColor; ?>"></td>
                                                        <td style="vertical-align: middle;"><?php print $fechaFormateada; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $peso; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $imc; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $strNombreCategoria; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $diferenciaPeso; ?></td>
                                                        <td style="vertical-align: middle;">
                                                            <button class="btn btn-info" onclick="openModalEditar('<?php print $id; ?>','<?php print $fecha;?>','<?php print $peso; ?>')">
                                                                <i class="far fas fa-edit"></i>&nbsp;Editar
                                                            </button>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <button class="btn btn-danger" onclick="openModalEliminar('<?php print $id; ?>','<?php print $fecha;?>','<?php print $peso; ?>')">
                                                                <i class="far fas fa-trash-alt"></i>&nbsp;Eliminar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot style="background-color: #343a40; color: #fff;">
                                                <tr>
                                                    <th></th>
                                                    <th style="vertical-align: middle;">Fecha</th>
                                                    <th style="vertical-align: middle;">Peso (lbs)</th>
                                                    <th style="vertical-align: middle;">IMC</th>
                                                    <th style="vertical-align: middle;">Categoria</th>
                                                    <th style="vertical-align: middle;">Diferencia Peso</th>
                                                    <th style="vertical-align: middle;">Editar</th>
                                                    <th style="vertical-align: middle;">Eliminar</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php
        drawFooter();
        ?>
        <script>

            $(document).ready(function() {
                // Selecciona todos los input con id que empiece por "color_"
                $('input[id^="color_"]').each(function() {
                    // Desactivar el selector de color
                    $(this).attr('readonly', true);

                    // Asignar función para desactivar el selector de color al hacer clic
                    $(this).click(function(event) {
                        event.preventDefault();
                    });
                });
            });

            function destroySession() {
                $.ajax({
                    url: "control.php",
                    data: {
                        destroySession: true
                    },
                    type: "post",
                    dataType: "json",
                    success: function(data) {
                        if (data.Correcto == "Y") {
                            location.href = "index.php";
                        }
                    }
                });
            }

            function reloadPage(){
                location.href = "control.php";
            }

            function openModalAgregar(){
                $('#modal-agregar').modal('show');
            }

            function registrar(){
                fecha = $("#fecha").val();
                peso = $("#peso").val();

                if( fecha == ''){
                    alertError("Debe ingresar un valor para fecha.");
                }else if( peso == ''){
                    alertError("Debe ingresar un valor para peso.");
                }else if( peso <= 0){
                    alertError("El peso debe ser mayor a cero.");
                }else{
                    $.ajax({
                        url: "control.php",
                        data: {
                            process: true,
                            accion: "I",
                            id: "",
                            fecha: fecha,
                            peso: peso
                        },
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data.Respuesta == 1) {
                                alertSuccessWithFunction('Agregar', 'Registro agregado exitosamente.', reloadPage);
                            }else if(data.Respuesta == 2){
                                alertError("Ya existe un registro con esa fecha.");
                            }
                        }
                    });
                }
            }

            function openModalEditar(id, fecha, peso){
                $('#modal-editar').modal('show');
                $("#editid").val(id);
                $("#editFecha").val(fecha);
                $("#editPeso").val(peso);
            }

            function editar(){
                id = $("#editid").val();
                fecha = $("#editFecha").val();
                peso = $("#editPeso").val();

                if( peso == ''){
                    alertError("Debe ingresar un valor para peso.");
                }else if( peso <= 0){
                    alertError("El peso debe ser mayor a cero.");
                }else{
                    $.ajax({
                        url: "control.php",
                        data: {
                            process: true,
                            accion: "U",
                            id: id,
                            fecha: fecha,
                            peso: peso
                        },
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data.Respuesta == 1) {
                                alertSuccessWithFunction('Editar', 'Registro editado exitosamente.', reloadPage);
                            }else if(data.Respuesta == 2){
                                alertError("Ya existe un registro con esa fecha.");
                            }
                        }
                    });
                }
            }


            function openModalEliminar(id, fecha, peso){
                $('#modal-eliminar').modal('show');
                $("#elimid").val(id);
                $("#elimfecha").val(fecha);
                $("#elimpeso").val(peso);
            }

            function eliminar(){
                id = $("#elimid").val();
                fecha = $("#elimfecha").val();
                peso = $("#elimpeso").val();
                $.ajax({
                    url: "control.php",
                    data: {
                        process: true,
                        accion: "D",
                        id: id,
                        fecha: fecha,
                        peso: peso
                    },
                    type: "post",
                    dataType: "json",
                    success: function(data) {
                        if (data.Respuesta == 1) {
                            alertErrorWithFunction('Eliminar', 'Registro eliminado exitosamente', reloadPage);
                        }
                    }
                });
            }

        </script>
        <?php
        drawFooterEnd();
    }
}


?>