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

$objController = new reporte_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class reporte_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new reporte_model($arrRolUser);
        $this->objView = new reporte_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->consultarReporte();
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

    public function consultarReporte(){
        if( isset($_POST['consultarReporte']) ){
            $fechaInicial = isset($_POST["fechaInicial"])? trim($_POST["fechaInicial"]): "";
            $fechaFinal = isset($_POST["fechaFinal"])? trim($_POST["fechaFinal"]): "";
            $usuario = intval($this->arrRolUser["ID"]);
            $pesoIdeal = getPesoIdealConfig();

            $arrControl = $this->objModel->getInfo($usuario, $fechaInicial, $fechaFinal);
            if (count($arrControl) > 0) {
                ?>
                <table id="tblControl" class="table table-bordered table-hover">
                    <thead style="background-color: #343a40; color: #fff;">
                        <tr>
                            <th style="vertical-align: middle;">Fecha</th>
                            <th style="vertical-align: middle;">Peso (lbs)</th>
                            <th style="vertical-align: middle;">IMC</th>
                            <th style="vertical-align: middle;">Categoria</th>
                            <th style="vertical-align: middle;">Diferencia (lbs)</th>
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
                            $categoria = ucwords(strtolower($val["CATEGORIA"]));
                            $diferenciaPeso = number_format($peso - $pesoIdeal,2);
                            ?>
                            <tr>
                                <td style="vertical-align: middle;"><?php print $fechaFormateada; ?></td>
                                <td style="vertical-align: middle;"><?php print $peso; ?></td>
                                <td style="vertical-align: middle;"><?php print $imc; ?></td>
                                <td style="vertical-align: middle;"><?php print $categoria; ?></td>
                                <td style="vertical-align: middle;"><?php print $diferenciaPeso; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            }else{
                ?>
                <table id="tblControl" class="table table-bordered table-hover">
                    <tr>
                        <td style="vertical-align: middle;"><center>No Existe información asociadada la búsqueda.</center></td>
                    </tr>
                </table>
                <?php
            }

            exit();
        }
    }

     
}

class reporte_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function getInfo($usuario, $fechaInicial, $fechaFinal){
        $arrControl = array();
        $sql = "SELECT C.ID_CONTROL ID,
                       C.FECHA, 
                       IFNULL(C.PESO,0) PESO, 
                       IFNULL(C.IMC,0) IMC, 
                       IFNULL(TRIM(CA.NOMBRE),'') CATEGORIA 
                  FROM CONTROLPESO.CONTROL C 
                       LEFT JOIN CONTROLPESO.CATEGORIAS CA ON C.CATEGORIA = CA.ID_CATEGORIA 
                 WHERE C.USUARIO = $usuario
                   AND C.ACTIVO = 1
                   AND C.FECHA BETWEEN '$fechaInicial' AND '$fechaFinal'
              ORDER BY C.FECHA ASC";
        $result = executeQuery($sql);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrControl[$row["ID"]]["FECHA"] = $row["FECHA"];
                $arrControl[$row["ID"]]["PESO"] = $row["PESO"];
                $arrControl[$row["ID"]]["IMC"] = $row["IMC"];
                $arrControl[$row["ID"]]["CATEGORIA"] = $row["CATEGORIA"];
            }
        }

        return $arrControl;
    }

}

class reporte_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new reporte_model($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        drawHeader($this->arrRolUser, "reporte");
        $idUsuario = intval($this->arrRolUser["ID"]);
        $pesoIdeal = getPesoIdealConfig();
        $zona_horaria = new DateTimeZone('America/Guatemala');
        $hora_actual = new DateTime('now', $zona_horaria);
        ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Reporte</h1>
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
                                                <label for="fecha">Fecha Inicial: </label>
                                                <input type="date" class="form-control" id="fechaInicial">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="fecha">Fecha Final: </label>
                                                <input type="date" class="form-control" id="fechaFinal">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="fecha"></label>
                                                <button type="button" id="btnConsultar" class="btn btn-primary" onclick="consultarReporte()">Consultar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" id="contenidoReporte"></div>
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
                location.href = "reporte.php";
            }

            function consultarReporte(){
                fechaInicial = $("#fechaInicial").val();
                fechaFinal = $("#fechaFinal").val();

                if( fechaInicial == ''){
                    alertError("Debe ingresar un valor para Fecha Inicial.");
                }else if( fechaFinal == ''){
                    alertError("Debe ingresar un valor para Fecha Final.");
                }else if( fechaInicial > fechaFinal ){
                    alertError("La Fecha Inicial no debe ser mayor a la Fecha Final.");
                    $("#contenidoReporte").html("");
                }else{
                    $.ajax({
                        url: "reporte.php",
                        data: {
                            consultarReporte: true,
                            fechaInicial: fechaInicial,
                            fechaFinal: fechaFinal
                        },
                        type: "post",
                        dataType: "html",
                        beforeSend: function(){
                            $("#btnConsultar").prop('disabled', true);
                        },
                        success: function(respuesta) {
                            $("#btnConsultar").prop('disabled', false);
                            $("#contenidoReporte").html(respuesta);
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