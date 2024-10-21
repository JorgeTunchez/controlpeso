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

$objController = new categorias_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class categorias_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new categorias_model($arrRolUser);
        $this->objView = new categorias_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController(){
        $this->objView->drawContent();
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->process();
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
            $nombre = isset($_POST["nombre"])? trim($_POST["nombre"]): "";
            $valorInicial = isset($_POST["valorinicial"])? trim($_POST["valorinicial"]): "";
            $valorFinal = isset($_POST["valorfinal"])? trim($_POST["valorfinal"]): "";

            $boolDuplicado = $this->objModel->validarDuplicado(strtolower($nombre));
            if( !$boolDuplicado ){
                $resp = $this->objModel->crudCategoria($accion, $id, $nombre, $valorInicial, $valorFinal);
            }else{
                $resp = 2;
            }

            $arrReturn["Respuesta"] = $resp;
            print json_encode($arrReturn);
            exit();

        }
    }
}

class categorias_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function getCategorias(){
        $arrCategorias = array();
        $strQuery = "SELECT id_categoria id, 
                            nombre,
                            valor_inicial,
                            valor_final
                       FROM controlpeso.categorias
                      WHERE activo = 1
                   ORDER BY valor_inicial";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrCategorias[$row["id"]]["NOMBRE"] = $row["nombre"];
                $arrCategorias[$row["id"]]["VALOR_INICIAL"] = $row["valor_inicial"];
                $arrCategorias[$row["id"]]["VALOR_FINAL"] = $row["valor_final"];
            }
        }

        return $arrCategorias;
    }

    public function validarDuplicado($nombre){
        $boolDuplicado = false;
        $conteo = 0;
        $strQuery = "SELECT COUNT(*) conteo FROM controlpeso.categorias WHERE LOWER(TRIM(nombre)) = '$nombre' AND activo = 1";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $conteo = intval($row["conteo"]);
            }
        }

        $boolDuplicado = ( $conteo>0 )? true:false;
        return $boolDuplicado;
    }

    public function crudCategoria($accion, $id, $nombre, $valorInicial, $valorFinal){

        $nombre = strtolower($nombre);
        // Preparar la llamada al procedimiento almacenado
        $conn = getConexion();
        $query = "CALL CrudCategoria(?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        // Vincular los parámetros
        $stmt->bind_param('sisdd', $accion, $id, $nombre, $valorInicial, $valorFinal);

        // Ejecutar el procedimiento almacenado
        $resp = ( $stmt->execute() )? "1":"0";

        // Cerrar la conexión
        $stmt->close();
        mysqli_close($conn);

        return $resp; 
    }

}

class categorias_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new categorias_model($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        drawHeader($this->arrRolUser, "categorias");
        $nombreOpcion = ucwords(strtolower(getNombreOpcion(basename(__FILE__))));
        ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            <!-- Modal Agregar-->
            <div class="modal fade" id="modal-agregar">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Agregar Categoria</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" placeholder="Nombre">
                                </div>
                                <div class="form-group">
                                    <label for="valorinicial">Valor Inicial</label>
                                    <input type="text" class="form-control" id="valorinicial" placeholder="Valor Inicial">
                                </div>
                                <div class="form-group">
                                    <label for="valorfinal">Valor Final</label>
                                    <input type="text" class="form-control" id="valorfinal" placeholder="Valor Final">
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
                            <h4 class="modal-title">Editar Categoria</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="hidden" class="form-control" id="editid">
                                    <input type="text" class="form-control" id="editnombre" placeholder="Nombre">
                                </div>
                                <div class="form-group">
                                    <label for="valorinicial">Valor Inicial</label>
                                    <input type="text" class="form-control" id="editvalorinicial" placeholder="Valor Inicial">
                                </div>
                                <div class="form-group">
                                    <label for="valorfinal">Valor Final</label>
                                    <input type="text" class="form-control" id="editvalorfinal" placeholder="Valor Final">
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
                            <h4 class="modal-title">Eliminar Categoria</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card-body">
                                <div class="form-group text-center">
                                    <p><h3>¿Desea eliminar la categoria?</h3></p>
                                    <input type="hidden" class="form-control" id="elimid">
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
                                            <h3 class="card-title">Gestion de categorias de IMC.</h3>
                                        </div>
                                        <div class="col-2">
                                            <button class="btn btn-success btn-block" data-toggle="modal" data-target="#modal-agregar"><i class="far fas fa-plus-circle"></i>&nbsp;Agregar</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <?php 
                                    $arrCategorias = $this->objModel->getCategorias();
                                    if (count($arrCategorias) > 0) {
                                        ?>
                                        <table id="tblCategorias" class="table table-bordered table-hover">
                                            <thead style="background-color: #343a40; color: #fff;">
                                                <tr>
                                                    <th style="vertical-align: middle;">Nombre</th>
                                                    <th style="vertical-align: middle;">Valor Inicial</th>
                                                    <th style="vertical-align: middle;">Valor Final</th>
                                                    <th style="vertical-align: middle;">Editar</th>
                                                    <th style="vertical-align: middle;">Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach( $arrCategorias as $key => $val ){
                                                    $id = $key;
                                                    $nombre = $val["NOMBRE"];
                                                    $valorInicial = $val["VALOR_INICIAL"];
                                                    $valorFinal = $val["VALOR_FINAL"];
                                                    ?>
                                                    <tr>
                                                        <td style="vertical-align: middle;"><?php print $nombre; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $valorInicial; ?></td>
                                                        <td style="vertical-align: middle;"><?php print $valorFinal; ?></td>
                                                        <td style="vertical-align: middle;">
                                                            <button class="btn btn-info" onclick="openModalEditar('<?php print $id; ?>','<?php print $nombre; ?>', '<?php print $valorInicial; ?>','<?php print $valorFinal; ?>')">
                                                                <i class="far fas fa-edit"></i>&nbsp;Editar
                                                            </button>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <button class="btn btn-danger" onclick="openModalEliminar('<?php print $id; ?>')">
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
                                                    <th>Nombre</th>
                                                    <th>Valor Inicial</th>
                                                    <th>Valor Final</th>
                                                    <th class="text-center">Editar</th>
                                                    <th class="text-center">Eliminar</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <?php
                                    }else {
                                        print "No se han registrado categorias";
                                    }
                                    ?>
                                    
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <?php
        drawFooter();
        ?>
        <script>

            function destroySession() {
                $.ajax({
                    url: "categorias.php",
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
                location.href = "categorias.php";
            }

            function registrar(){
                nombre = $("#nombre").val();
                valorinicial = $("#valorinicial").val();
                valorfinal = $("#valorfinal").val();

                if( nombre == '' ){
                    alertError("Debe ingresar un nombre");
                }else if( valorinicial == '' ){
                    alertError("Debe ingresar un valor inicial.");
                }else if( valorinicial <= 0 ){
                    alertError("El valor inicial debe ser mayor a cero.");
                }else if( valorfinal == '' ){
                    alertError("Debe ingresar un valor final.");
                }else if( valorfinal <= 0 ){
                    alertError("El valor final debe ser mayor a cero.");
                }else{
                    $.ajax({
                        url: "categorias.php",
                        data: {
                            process: true,
                            accion: "I",
                            id: "",
                            nombre: nombre,
                            valorinicial: valorinicial,
                            valorfinal: valorfinal
                        },
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data.Respuesta == 1) {
                                alertSuccessWithFunction('Agregar', 'Registro agregado exitosamente.', reloadPage);
                            }else if(data.Respuesta == 2){
                                alertError("Ya existe una categoria con ese nombre.");
                            }
                        }
                    });
                }
            }

            function openModalEditar(id, nombre, valorinicial, valorfinal){
                $('#modal-editar').modal('show');
                $("#editid").val(id);
                $("#editnombre").val(nombre);
                $("#editvalorinicial").val(parseFloat(valorinicial));
                $("#editvalorfinal").val(parseFloat(valorfinal));
            }

            function editar(){
                id = $("#editid").val();
                nombre = $("#editnombre").val();
                valorinicial = $("#editvalorinicial").val();
                valorfinal = $("#editvalorfinal").val();

                if( nombre == '' ){
                    alertError("Debe ingresar un nombre.");
                }else if( valorinicial == '' ){
                    alertError("Debe ingresar un valor inicial.");
                }else if( valorinicial <= 0 ){
                    alertError("El valor inicial debe ser mayor a cero.");
                }else if( valorfinal == '' ){
                    alertError("Debe ingresar un valor final.");
                }else if( valorfinal <= 0 ){
                    alertError("El valor final debe ser mayor a cero.");
                }else{
                    $.ajax({
                        url: "categorias.php",
                        data: {
                            process: true,
                            accion: "U",
                            id: id,
                            nombre: nombre,
                            valorinicial: valorinicial,
                            valorfinal: valorfinal
                        },
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data.Respuesta == 1) { 
                                alertSuccessWithFunction('Editar', 'Registro editado exitosamente.', reloadPage);                         
                            }
                        }
                    });
                }
            }

            function openModalEliminar(id){
                $('#modal-eliminar').modal('show');
                $("#elimid").val(id);
            }

            function eliminar(){
                id = $("#elimid").val();
                $.ajax({
                    url: "categorias.php",
                    data: {
                        process: true,
                        accion: "D",
                        id: id,
                        nombre: "",
                        valorinicial: "",
                        valorfinal: ""
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