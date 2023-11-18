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

$objController = new dashboard_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class dashboard_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new dashboard_model($arrRolUser);
        $this->objView = new dashboard_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController(){
        $this->objView->drawContent();
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->ajaxObtenerDatosGrafica();
    }

    public function ajaxDestroySession(){
        if ( isset($_POST["destroySession"]) ) {
            header("Content-Type: application/json;");
            session_destroy();
            $arrReturn["Correcto"] = "Y";
            print json_encode($arrReturn);
            exit();
        }
    }

    public function ajaxObtenerDatosGrafica(){
        if ( isset($_POST["obtenerDataGrafica"]) ) {
            $usuario = intval($this->arrRolUser["ID"]);
            $arrDatos = $this->objModel->obtenerDatosGrafica($usuario);
            print json_encode($arrDatos);
            exit();
        }
    }

}

class dashboard_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function obtenerValores(){
        $arrValores = array();
        $sql = "SELECT c.peso, 
                       c.imc,
                       c.peso - (SELECT x.peso_ideal FROM controlpeso.configuracion x) diferencia
                  FROM controlpeso.control c
                 WHERE c.usuario = 1
                   AND c.activo = 1
              ORDER BY c.id_control DESC LIMIT 1";
        $result = executeQuery($sql);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrValores["PESO_ACTUAL"] = $row["peso"];
                $arrValores["IMC"] = $row["imc"];
                $arrValores["DIFERENCIA"] = $row["diferencia"];
            }
        }

        return $arrValores;
    }


    public function obtenerDatosGrafica($usuario){
        $arrDatos = array();
        $sql = "SELECT c.id_control id, c.fecha, c.peso
                  FROM controlpeso.control c
                 WHERE c.usuario = $usuario
                   AND c.activo = 1
              ORDER BY c.id_control DESC LIMIT 10";
        $result = executeQuery($sql);
        if (!empty($result)) {
            $cont = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $cont++;
                $arrDatos[$cont]["FECHA"] = date('d M y', strtotime($row["fecha"]));
                $arrDatos[$cont]["PESO"] = $row["peso"];
            }
        }

        return $arrDatos;
    }

}

class dashboard_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new dashboard_model($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        drawHeader($this->arrRolUser, "dashboard");
        $arrValores = $this->objModel->obtenerValores();
        ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    <!-- Small boxes (Stat box) -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php print $arrValores["PESO_ACTUAL"]; ?></h3>
                                    <p>Peso Actual (lbs)</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php print $arrValores["IMC"]; ?></h3>
                                    <p>IMC</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php print $arrValores["DIFERENCIA"]; ?></h3>
                                    <p>Lbs demás</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.row -->
                </div>
            </section>
            <br><br>
            <section class="content">
            <div class="card-body">
                <div class="position-relative mb-4">
                  <canvas id="visitors-chart" height="250"></canvas>
                </div>
                <div class="d-flex flex-row justify-content-end">
                  <span class="mr-2">
                    <i class="fas fa-square text-primary"></i> Peso Registrado
                  </span>
                </div>
            </div>
            </section>
        </div>
        <!-- /.content-wrapper -->
        <?php
        drawFooter();
        ?>
        <script>


            function obtenerDataGrafica() {
                return new Promise(function(resolve, reject) {
                    var arrData = [];
                    $.ajax({
                        url: "dashboard.php",
                        data: {
                            obtenerDataGrafica: true
                        },
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data) {
                                resolve(data); // Resuelve la promesa con los datos obtenidos
                            } else {
                                reject('El arreglo está vacío'); // Rechaza la promesa si el arreglo está vacío
                            }
                        }
                    });
                });
            }

            obtenerDataGrafica()
                .then(function(data) {

                    const fechas = [];
                    const pesos = [];

                    // Recorrer el objeto JSON y llenar los arreglos
                    for (const key in data) {
                        if (data.hasOwnProperty(key)) {
                            const item = data[key];
                            fechas.push(item.FECHA);
                            pesos.push(item.PESO);
                        }
                    }

                    fechas.reverse();
                    pesos.reverse();
                    
                    var ticksStyle = {
                        fontColor: '#495057',
                        fontStyle: 'bold'
                    }

                    var mode = 'index'
                    var intersect = true

                    var $visitorsChart = $('#visitors-chart')

                    // eslint-disable-next-line no-unused-vars
                    var visitorsChart = new Chart($visitorsChart, {
                        data: {
                            labels: fechas,
                            datasets: [{
                                type: 'line',
                                data: pesos,
                                backgroundColor: 'transparent',
                                borderColor: '#007bff',
                                pointBorderColor: '#007bff',
                                pointBackgroundColor: '#007bff',
                                fill: false,
                                pointHoverBackgroundColor: '#007bff',
                                pointHoverBorderColor: '#007bff'
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            tooltips: {
                                mode: mode,
                                intersect: intersect
                            },
                            hover: {
                                mode: mode,
                                intersect: intersect
                            },
                            legend: {
                                display: false
                            },
                            scales: {
                                yAxes: [{
                                   display: true,
                                gridLines: {
                                    display: true,
                                    lineWidth: '5px',
                                    color: 'rgba(0, 0, 0, .2)',
                                    zeroLineColor: 'transparent'
                                },
                                ticks: $.extend({
                                    beginAtZero: false,
                                    suggestedMin: 190,
                                    suggestedMax: 205
                                }, ticksStyle)
                                }],
                                xAxes: [{
                                display: true,
                                gridLines: {
                                    display: false
                                },
                                ticks: ticksStyle
                                }]
                            }
                        }
                    })

                })
                .catch(function(error) {
                    console.error('Error:', error);
                });


            function destroySession() {
                $.ajax({
                    url: "dashboard.php",
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

        </script>
        <?php
        drawFooterEnd();
    }

    


}


?>