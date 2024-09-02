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

$objController = new configuracion_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class configuracion_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new configuracion_model($arrRolUser);
        $this->objView = new configuracion_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->guardarConfig();
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

    public function guardarConfig(){
        if( isset($_POST['guardarConfig']) ){
            $altura = isset($_POST["altura"])? trim($_POST["altura"]): "";
            $pesoIdeal = isset($_POST["pesoIdeal"])? trim($_POST["pesoIdeal"]): "";

            $result = $this->objModel->actualizarInfo($altura, $pesoIdeal);
            $respuesta = ($result)? 1:0;
            $arrReturn["Respuesta"] = $respuesta;
            print json_encode($arrReturn);
            exit();
        }
    }

     
}

class configuracion_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function getInfo(){
        $arrInfo = array();
        $arrInfo["ALTURA"] = 0;
        $arrInfo["PESO_IDEAL"] = 0;
        $sql = "SELECT IFNULL(ALTURA,0) ALTURA, IFNULL(PESO_IDEAL,0) PESO_IDEAL FROM CONTROLPESO.CONFIGURACION";
        $result = executeQuery($sql);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrInfo["ALTURA"] = $row["ALTURA"];
                $arrInfo["PESO_IDEAL"] = $row["PESO_IDEAL"];
            }
        }

        return $arrInfo;
    }

    public function actualizarInfo($altura, $pesoIdeal){
        $sql = "UPDATE CONTROLPESO.CONFIGURACION SET ALTURA = '$altura', PESO_IDEAL = '$pesoIdeal'";
        $result = executeQuery($sql);
        return $result;
    }

}

class configuracion_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new configuracion_model($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        drawHeader($this->arrRolUser, "Configuración");
        $idUsuario = intval($this->arrRolUser["ID"]);
        $arrInfo = $this->objModel->getInfo();
        $altura = $arrInfo["ALTURA"];
        $pesoIdeal = $arrInfo["PESO_IDEAL"];
        ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <?php 
                            $nombreArchivo = basename(__FILE__);
                            $nombreOpcion = getNombreOpcion($nombreArchivo);
                            ?>
                            <h1 class="m-0"><?php print ucwords(strtolower($nombreOpcion)); ?></h1>
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
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="altura">Altura (mts): </label>
                                                <input type="number" class="form-control" id="altura" value="<?php print $altura; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="pesoIdeal">Peso Ideal: </label>
                                                <input type="number" class="form-control" id="pesoIdeal" value="<?php print $pesoIdeal; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="guardar"></label>
                                                <button type="button" id="btnGuardar" class="btn btn-primary" onclick="guardarConfig()">Guardar</button>
                                            </div>
                                        </div>
                                    </div>
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

            $(document).ready(function() {});

            function destroySession() {
                $.ajax({
                    url: "reporte.php",
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
                location.href = "configuracion.php";
            }

            function guardarConfig(){
                altura = $("#altura").val();
                pesoIdeal = $("#pesoIdeal").val();

                if( altura == ''){
                    alertError("Debe ingresar un valor para el campo Altura.");
                }else if( altura <= 0){
                    alertError("El campo Altura no puede ser menor o igual a cero (0).");
                }else if( pesoIdeal == ''){
                    alertError("Debe ingresar un valor para el campo Peso Ideal.");
                }else if( pesoIdeal <= 0){
                    alertError("El campo Peso Ideal no puede ser menor o igual a cero(0).");
                }else{
                    $.ajax({
                        url: "configuracion.php",
                        data: {
                            guardarConfig: true,
                            altura: altura,
                            pesoIdeal: pesoIdeal
                        },
                        type: "post",
                        dataType: "json",
                        beforeSend: function(){
                            $("#btnGuardar").prop('disabled', true);
                        },
                        success: function(data) {
                            if (data.Respuesta == 1) {
                                alertSuccessWithFunction('Editar', 'Registro(s) actualizado(s) exitosamente.', reloadPage);
                            }else{
                                alertError("Error al guardar los datos, consulte al administrador.");
                                $("#btnGuardar").prop('disabled', false);
                            }
                        }
                    });
                }
            }

        </script>
        <?php
        drawFooterEnd();
    }
}


?>