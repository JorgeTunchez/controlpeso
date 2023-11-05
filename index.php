<?php 
require_once("core/core.php");
boolDebug(false);
$objController = new index_controller();
$objController->runAjax();
$objController->drawContentController();


class index_controller{

    private $objModel;
    private $objView;

    public function __construct(){
        $this->objModel = new index_model();
        $this->objView = new index_view();
    }

    public function drawContentController(){
        $this->objView->drawContent();
    }

    public function runAjax(){
        $this->ajaxAuthUser();
    }

    public function ajaxAuthUser(){
        if (isset($_POST['loginUsername'])) {
            $strUser = isset($_POST['loginUsername']) ? trim($_POST['loginUsername']) : "";
            $strPassword = isset($_POST['loginPassword']) ? trim($_POST['loginPassword']) : "";
            $arrReturn = array();
            $boolRedirect = $this->objModel->redirect_dashboard($strUser, $strPassword);
            if ($boolRedirect) {
                $arrReturn["boolAuthRedirect"] = "Y";
            } else {
                $arrReturn["boolAuthRedirect"] = "N";
            }
            print json_encode($arrReturn);
            exit();
        }
    }

}

class index_model{

    public function redirect_dashboard($username, $password){
        $boolRedirect = auth_user($username, $password);
        return $boolRedirect;
    }

}

class index_view{

    private $objModel;

    public function __construct(){
        $this->objModel = new index_model();
    }

    public function drawContent(){
        ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Control peso | Login</title>

                <!-- Google Font: Source Sans Pro -->
                <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
                <!-- Font Awesome -->
                <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
                <!-- icheck bootstrap -->
                <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
                <!-- Theme style -->
                <link rel="stylesheet" href="dist/css/adminlte.min.css">
                <!-- Icon  -->
                <link rel="icon" href="images/get_fit_icon.png">
            </head>
            <body class="hold-transition login-page">
                <div class="login-box">
                <div class="login-logo">
                    <img src='images/get_fit_icon.png' height='100px'>
                    <b>Control</b>Peso
                </div>
                <!-- /.login-logo -->
                <div class="card">
                    <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/icon-loader.gif" height="250px" width="250px"></div>
                    <div class="card-body login-card-body">
                    <p class="login-box-msg">Ingresa para iniciar tu sesión.</p>

                    <form id="frmLogin" method="POST" action="javascript:void(0);">
                        <div class="input-group mb-3">
                        <input id="loginUsername" name="loginUsername" type="text" class="form-control" placeholder="Usuario">
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-user"></span>
                            </div>
                        </div>
                        </div>
                        <div class="input-group mb-3">
                        <input id="loginPassword" name="loginPassword" type="password" class="form-control" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        </div>
                        <div class="row">
                        <div class="col-12">
                            <button class="btn btn-primary btn-block"  onclick="checkForm()">Iniciar Sesion</button>
                        </div>
                        <!-- /.col -->
                        </div>
                    </form>
                    </div>
                    <!-- /.login-card-body -->
                </div>
                </div>
                <!-- /.login-box -->

                <!-- jQuery -->
                <script src="plugins/jquery/jquery.min.js"></script>
                <!-- Bootstrap 4 -->
                <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
                <!-- AdminLTE App -->
                <script src="dist/js/adminlte.min.js"></script>
                <script>
                function checkForm() {
                    var boolError = false;
                    if ($("#loginUsername").val() == '') {
                        $("#loginUsername").css('background-color', '#f4d0de');
                        boolError = true;
                    } else {
                        $("#loginUsername").css('background-color', '');
                    }

                    if ($("#loginPassword").val() == '') {
                        $("#loginPassword").css('background-color', '#f4d0de');
                        boolError = true;
                    } else {
                        $("#loginPassword").css('background-color', '');
                    }

                    if (boolError == false) {
                        var objSerialized = $("#frmLogin").find("input").serialize();
                        $.ajax({
                            url: "index.php",
                            data: objSerialized,
                            type: "post",
                            dataType: "json",
                            beforeSend: function() {
                                $("#divShowLoadingGeneralBig").css("z-index", 1050);
                                $("#divShowLoadingGeneralBig").show();
                            },
                            success: function(data) {
                                if (data.boolAuthRedirect == "Y") {
                                    $("#divShowLoadingGeneralBig").hide();
                                    location.href = "dashboard.php";
                                } else {
                                    alert("Datos incorrectos y/o usuario inactivo");
                                    $("#divShowLoadingGeneralBig").hide();
                                    $("#loginUsername").val('');
                                    $("#loginPassword").val('');
                                }
                            }
                        });
                    }
                }
            </script>
            </body>
        </html>
        <?php
    }

}

?>