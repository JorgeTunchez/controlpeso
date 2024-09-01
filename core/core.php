<?php

// Funcion que permite establecer conexion con servidor y la base de datos
function getConexion(){
    $servername = "localhost:3306";
    $username = "root";
    $password = "";
    $dbname = "controlpeso";

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        return $conn;
    }
}


// Funcion que permite habilitar el debug de PHP 
function boolDebug($boolDebug){
    if( $boolDebug == true ){
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
}


// Funcion que permite ejecutar un query
function executeQuery($strQuery){
    if( $strQuery!='' ){
        $conn = getConexion();
        $result = mysqli_query($conn, $strQuery);
        mysqli_close($conn);
        return $result;
    }
}


// Funcion que permite realizar el proceso de autentificacion del usuario a la aplicacion
function auth_user($username, $password){
    if ( $username != '' && $password != '' ) {
        $arrValues = array();
        $strQuery = "SELECT password FROM usuarios WHERE nombre = '{$username}' AND activo = 1";
        $result = executeQuery($strQuery);
        if (!empty($result)) {

            while ($row = mysqli_fetch_assoc($result)) {
                $arrValues["PASSWORD"] = $row["password"];
            }

            if (isset($arrValues["PASSWORD"])) {
                if (($arrValues["PASSWORD"] == $password)) {
                    session_start();
                    $_SESSION['user_id'] = $username;
                    $strValueSession = $_SESSION['user_id'];
                    insertSession($strValueSession);
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }
}


// Funcion quer permite convertir una fecha a formato MYSQL
function convertDateMysql($strFecha){
    $strFechaConvert = "";
    if ($strFecha != '') {
        $arrExplode = explode("/", $strFecha);
        $strFechaConvert = $arrExplode[2] . '-' . $arrExplode[1] . '-' . $arrExplode[0];
    }
    return $strFechaConvert;
}


// Funcion que permite registrar el usuario y fecha en que se creo la sesion
function insertSession($strSession){
    if ($strSession != '') {
        $strQuery = "INSERT INTO session_user (nombre, fecha) VALUES ('{$strSession}', now())";
        executeQuery($strQuery);
    }
}


// Funcion quer permite obtener el rol del usuario logeado
function getRolUserSession($sessionName){
    $strRolUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT DISTINCT tipo_usuario.nombre
                       FROM usuarios 
                            INNER JOIN session_user ON session_user.nombre = usuarios.nombre 
                            INNER JOIN tipo_usuario ON usuarios.tipo = tipo_usuario.id_tipo_usuario
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strRolUserSession = $row["nombre"];
            }
        }
    }

    return $strRolUserSession;
}


// Funcion que permite obtener el id del usuario logeado
function getIDUserSession($sessionName){
    $intIDUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.id_usuario id
                       FROM usuarios 
                            INNER JOIN session_user ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $intIDUserSession = $row["id"];
            }
        }
    }

    return $intIDUserSession;
}


function getAlturaConfig(){
    $altura = 0;
    $strQuery = "SELECT altura FROM controlpeso.configuracion";
    $result = executeQuery($strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $altura = $row["altura"];
        }
    }
    
    return $altura;
}

function getPesoIdealConfig(){
    $pesoIdeal = 0;
    $strQuery = "SELECT peso_ideal FROM controlpeso.configuracion";
    $result = executeQuery($strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pesoIdeal = $row["peso_ideal"];
        }
    }
    
    return $pesoIdeal;
}


function formatoFecha($fecha) {
        // Establece el idioma en español
        setlocale(LC_TIME, 'es_ES.UTF-8');

        // Convierte la fecha al formato Unix timestamp
        $timestamp = strtotime($fecha);
    
        // Verifica si la conversión fue exitosa
        if ($timestamp === false) {
            return "Fecha inválida";
        }
    
        // Obtiene el número de mes (1-12)
        $numero_mes = date('n', $timestamp);
    
        // Convierte el número de mes en su nombre en español
        switch ($numero_mes) {
            case 1:
                $nombre_mes = 'enero';
                break;
            case 2:
                $nombre_mes = 'febrero';
                break;
            case 3:
                $nombre_mes = 'marzo';
                break;
            case 4:
                $nombre_mes = 'abril';
                break;
            case 5:
                $nombre_mes = 'mayo';
                break;
            case 6:
                $nombre_mes = 'junio';
                break;
            case 7:
                $nombre_mes = 'julio';
                break;
            case 8:
                $nombre_mes = 'agosto';
                break;
            case 9:
                $nombre_mes = 'septiembre';
                break;
            case 10:
                $nombre_mes = 'octubre';
                break;
            case 11:
                $nombre_mes = 'noviembre';
                break;
            case 12:
                $nombre_mes = 'diciembre';
                break;
            default:
                $nombre_mes = 'desconocido';
        }
    
        // Formatea la fecha en el formato "DD de mes de YYYY"
        $fecha_formateada = strftime('%d de '.$nombre_mes.' de %Y', $timestamp);
    
        return $fecha_formateada;
}

function getMenu(){
    $arrMenu = array();
    $sql = "SELECT TRIM(m.nombre) menu, 
                   TRIM(m.nombre_icono) icono,
                   TRIM(s.nombre) submenu, 
                   TRIM(s.archivo) archivo
              FROM controlpeso.submenu s
                   INNER JOIN controlpeso.menu m ON s.menu = m.id_menu 
             WHERE s.activo = 1
          ORDER BY m.nombre, s.nombre";
    $result = executeQuery($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $menu = ucwords($row["menu"]);
        $icono = $row["icono"];
        $submenu = ucwords($row["submenu"]);
        $archivo = $row["archivo"];

        if( isset($arrMenu[$menu]) ){
            $arrMenu[$menu]["ICONO"] = $icono;
            $arrMenu[$menu]["SUBMENU"][$submenu]["ARCHIVO"] = $archivo;
            
        }else{
            $arrMenu[$menu] = array();
            $arrMenu[$menu]["ICONO"] = $icono;
            $arrMenu[$menu]["SUBMENU"][$submenu]["ARCHIVO"] = $archivo;
        }
    }  

    return $arrMenu;
}

// Funcion que permite obtener el nombre del colaborador que esta logeado en usuario
function getNombreUserSession($sessionName){
    $strNameUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.nombrecolaborador
                       FROM usuarios 
                            INNER JOIN session_user ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strNameUserSession = $row["nombrecolaborador"];
            }
        }
    }

    return $strNameUserSession;
}

// Funcion que permite generar un password aleatorio segun el numero de caracteres como parametro 
function generatePassword($length = 8){
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }
    return $result;
}


// Funcion que permite convertir reemplazar tildes en mayusculas
function upper_tildes($strString, $boolProper = false){
    if ($boolProper) {
        $strString = ucwords($strString);
    } else {
        $strString = strtoupper($strString);
        $strString = str_replace("á", "Á", $strString);
        $strString = str_replace("é", "É", $strString);
        $strString = str_replace("í", "Í", $strString);
        $strString = str_replace("ó", "Ó", $strString);
        $strString = str_replace("ú", "Ú", $strString);
        $strString = str_replace("ä", "Ä", $strString);
        $strString = str_replace("ë", "Ë", $strString);
        $strString = str_replace("ï", "Ï", $strString);
        $strString = str_replace("ö", "Ö", $strString);
        $strString = str_replace("ü", "Ü", $strString);
        $strString = str_replace("ñ", "Ñ", $strString);
    }

    return $strString;
}


// Funcion que permite dibujar el header de la aplicacion
function drawHeader($arrRolUser, $nombre){
    ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Control peso | <?php print ucwords($nombre); ?></title>

            <!-- Google Font: Source Sans Pro -->
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
            <!-- Font Awesome -->
            <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
            <!-- Ionicons -->
            <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
            <!-- Tempusdominus Bootstrap 4 -->
            <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
            <!-- iCheck -->
            <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
            <!-- JQVMap -->
            <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
            <!-- Theme style -->
            <link rel="stylesheet" href="dist/css/adminlte.min.css">
            <!-- overlayScrollbars -->
            <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
            <!-- Daterange picker -->
            <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
            <!-- summernote -->
            <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
            <!-- Icon  -->
            <link rel="icon" href="images/get_fit_icon.png">
            <!-- sweetalert2  -->
            <link rel="stylesheet" href="dist/css/adminlte.min/sweetalert2.min.css">
        </head>
        <body class="hold-transition sidebar-mini layout-fixed">
            
            <div class="wrapper">

                <!-- Navbar -->
                <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                    <!-- Left navbar links -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                    </ul>
                </nav>
                <!-- /.navbar -->

                <div class="modal fade" id="modal-default">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Cerrar Sesión</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <p><h3>¿Desea cerrar la sesión?</h3></p>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" onclick="destroySession()">Confirmar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Sidebar Container -->
                <aside class="main-sidebar sidebar-dark-primary elevation-4">
                    <!-- Brand Logo -->
                    <a href="dashboard.php" class="brand-link">
                        <img src="dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                        <span class="brand-text font-weight-light">Control Peso</span>
                    </a>

                    <!-- Sidebar -->
                    <div class="sidebar">
                        <!-- Sidebar user panel (optional) -->
                        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                            <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
                            <div class="info"><a href="#" class="d-block"><?php print strtoupper($arrRolUser["NAME"]);?></a></div>
                        </div>

                        <!-- Sidebar Menu -->
                        <nav class="mt-2">
                            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                <?php
                                $arrMenu = getMenu();
                                if( count($arrMenu)>0 ){
                                    foreach( $arrMenu as $key => $val ){
                                        $menu = $key;
                                        $icono = $val["ICONO"];
                                        ?>
                                        <li class="nav-item">
                                            <a href="#" class="nav-link">
                                                <i class="nav-icon <?php print $icono; ?>"></i>
                                                <p><?php print $menu; ?><i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                            <?php 
                                            foreach( $val["SUBMENU"] as $key2 => $val2 ){
                                                $submenu = $key2;
                                                $archivo = $val2["ARCHIVO"];
                                                ?>
                                                <li class="nav-item">
                                                    <a href="<?php print $archivo; ?>" class="nav-link active">
                                                        <i class="far fa-circle nav-icon"></i>
                                                        <p><?php print $submenu;?></p>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                            </ul>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="modal" data-target="#modal-default">
                                            <i class="nav-icon fas fa-sign-out-alt"></i>
                                            <p>Cerrar Sesión</p>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </nav>
                        <!-- /.sidebar-menu -->

                    </div>
                    <!-- /.sidebar -->
                </aside>
    <?php
}

function drawFooterEnd(){
    ?>
        </body>
    </html>
    <?php
}

// Funcion que permite dibujar el footer de la aplicacion
function drawFooter(){
    ?>
                <footer class="main-footer">
                    <strong>Copyright &copy; <?php print date("Y"); ?></strong>
                    <div class="float-right d-none d-sm-inline-block"><b>Version</b> 1</div>
                </footer>

                <!-- Control Sidebar -->
                <aside class="control-sidebar control-sidebar-dark">
                    <!-- Control sidebar content goes here -->
                </aside>
                <!-- /.control-sidebar -->
            </div>
            <!-- ./wrapper -->

            <!-- jQuery -->
            <script src="plugins/jquery/jquery.min.js"></script>
            <!-- jQuery UI 1.11.4 -->
            <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
            <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
            <script>
            $.widget.bridge('uibutton', $.ui.button)
            </script>
            <!-- Bootstrap 4 -->
            <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
            <!-- ChartJS -->
            <script src="plugins/chart.js/Chart.min.js"></script>
            <!-- Sparkline -->
            <script src="plugins/sparklines/sparkline.js"></script>
            <!-- JQVMap -->
            <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
            <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
            <!-- jQuery Knob Chart -->
            <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
            <!-- daterangepicker -->
            <script src="plugins/moment/moment.min.js"></script>
            <script src="plugins/daterangepicker/daterangepicker.js"></script>
            <!-- Tempusdominus Bootstrap 4 -->
            <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
            <!-- Summernote -->
            <script src="plugins/summernote/summernote-bs4.min.js"></script>
            <!-- overlayScrollbars -->
            <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
            <!-- AdminLTE App -->
            <script src="dist/js/adminlte.js"></script>
            <!-- AdminLTE for demo purposes -->
            <script src="dist/js/demo.js"></script>
            <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
            <script src="dist/js/pages/dashboard.js"></script>
            <!-- SweetAlert -->
            <script src="build/js/Sweetalert2@10.js"></script>
            <!-- Core -->
            <script src="build/js/core.js"></script>
    <?php
}


?>