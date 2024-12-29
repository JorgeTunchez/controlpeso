<?php 
require_once("core/core.php");
boolDebug(true);
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
        $this->generarReporte();
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

    public function generarReporte(){
        if( isset($_POST["generarReporte"]) ){
            $fechaInicial = isset($_POST["fechaInicial"])? trim($_POST["fechaInicial"]): "";
            $fechaFinal = isset($_POST["fechaFinal"])? trim($_POST["fechaFinal"]): "";
            $usuario = intval($this->arrRolUser["ID"]);
            $pesoIdeal = getPesoIdealConfig();

            $arrControl = $this->objModel->getInfo($usuario, $fechaInicial, $fechaFinal);
            if (count($arrControl) > 0) {

                // Directorio donde se guardan los reportes
                $directorio = 'reportes/';

                // Eliminar los archivos existentes en el directorio
                foreach (glob($directorio . '*') as $archivo) {
                    if (is_file($archivo)) {
                        unlink($archivo); // Eliminar el archivo
                    }
                }

                // Crear una nueva instancia de FPDF
                $pdf = new FPDF();
                $pdf->AddPage(); // Agregar una página
                $pdf->SetFont('Arial', 'B', 12); // Establecer fuente, estilo y tamaño

                // Texto Introduccion
                $fechaInicialFormateada = formatoFecha($fechaInicial);
                $fechaFinalFormateada = formatoFecha($fechaFinal).".";
                $pdf->Cell(0, 10, utf8_decode("Control Peso $fechaInicialFormateada al $fechaFinalFormateada"), 0, 1, 'L');
                $pdf->Ln(0); // Espacio entre el texto y la tabla

                // Texto de peso configurado
                $pesoTexto = number_format($pesoIdeal,0);
                $pdf->SetFont('Arial', 'B', 10); 
                $pdf->Cell(0, 10, utf8_decode("Meta de peso ideal $pesoTexto lbs."), 0, 1, 'L');
                $pdf->Ln(5); // Espacio entre el texto y la tabla

                // Encabezado de la tabla
                $pdf->SetFont('Arial', 'B', 12); 
                $pdf->Cell(50, 10, utf8_decode("Fecha"), 1, 0, 'C');
                $pdf->Cell(30, 10, utf8_decode("Peso (lbs)"), 1, 0, 'C');
                $pdf->Cell(30, 10, utf8_decode("IMC"), 1, 0, 'C');
                $pdf->Cell(40, 10, utf8_decode("Categoría"), 1, 0, 'C');
                $pdf->Cell(40, 10, utf8_decode("Diferencia (lbs)"), 1, 1, 'C');

                $pdf->SetFont('Arial', '', 10); // Cambiar a texto normal
                foreach( $arrControl as $key => $val ){
                    $fecha = $val["FECHA"];
                    $fechaFormateada = formatoFecha($fecha);
                    $peso = $val["PESO"];
                    $imc = $val["IMC"];
                    $categoria = ucwords(strtolower($val["CATEGORIA"]));
                    $diferenciaPeso = number_format($peso - $pesoIdeal,2);

                    $pdf->Cell(50, 10, utf8_decode($fechaFormateada), 1, 0, 'C');
                    $pdf->Cell(30, 10, $peso, 1, 0, 'C');
                    $pdf->Cell(30, 10, $imc, 1, 0, 'C');
                    $pdf->Cell(40, 10, utf8_decode($categoria), 1, 0, 'C');
                    $pdf->Cell(40, 10, $diferenciaPeso, 1, 1, 'C');
                    
                }
                
                // Guardar el PDF en el navegador
                $nombreArchivo = 'reporte_' . time() . '.pdf';
                $rutaArchivo = 'reportes/'.$nombreArchivo;
                $pdf->Output('F', $rutaArchivo); // Guardar el PDF en el servidor

                // Responder con el estado y la ruta del archivo
                echo json_encode([
                    'ESTADO' => '1',
                    'URL' => 'reportes/' . $nombreArchivo
                ], JSON_UNESCAPED_SLASHES);
                
            }else{
                echo json_encode([
                    'ESTADO' => '0'
                ]);
            }

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
                        foreach( $arrControl as $key => $val ){
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
                <script>
                    $("#btnGenerar").prop('disabled', true);
                </script>
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
        $nombreOpcion = ucwords(strtolower(getNombreOpcion(basename(__FILE__))));
        $zona_horaria = new DateTimeZone('America/Guatemala');

        $fechaInicial = strtotime('first day of this month', time());
        $fechaInicial = date('Y-m-d', $fechaInicial);
        $fechaFinal = date('Y-m-d');
        ?>
        <div class="content-wrapper">
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
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="fecha">Fecha Inicial: </label>
                                                <input type="date" class="form-control" id="fechaInicial" value="<?php echo $fechaInicial; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="fecha">Fecha Final: </label>
                                                <input type="date" class="form-control" id="fechaFinal" value="<?php echo $fechaFinal; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="fecha"></label>
                                                <button type="button" id="btnConsultar" class="btn btn-primary" onclick="consultarReporte()">Consultar</button>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="generar"></label>
                                                <button type="button" id="btnGenerar" class="btn btn-success" onclick="generarReporte()">Generar</button>
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

            $(document).ready(function() {
                $("#btnGenerar").prop('disabled', true);
            });

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

            function generarReporte(){
                fechaInicial = $("#fechaInicial").val();
                fechaFinal = $("#fechaFinal").val();
                $.ajax({
                    url: "reporte.php",
                    data: {
                        generarReporte: true,
                        fechaInicial: fechaInicial,
                        fechaFinal: fechaFinal
                    },
                    type: "post",
                    dataType: "json",
                    success: function(respuesta) {
                        if( respuesta.ESTADO == '0'){
                            alertError("No se logro generar el reporte.");
                        }else {
                            // Redirigir al archivo PDF generado
                            window.open(respuesta.URL, '_blank');
                        }
                    }
                });
            }

            function consultarReporte(){
                fechaInicial = $("#fechaInicial").val();
                fechaFinal = $("#fechaFinal").val();
                $("#btnGenerar").prop('disabled', true);

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
                            $("#btnGenerar").prop('disabled', true);
                        },
                        success: function(respuesta) {
                            $("#btnConsultar").prop('disabled', false);
                            $("#btnGenerar").prop('disabled', false);
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