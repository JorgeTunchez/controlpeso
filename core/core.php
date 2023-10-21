<?php

/* Funcion que permite establecer conexion con servidor y la base de datos */
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

function boolDebug($boolDebug){
    if( $boolDebug == true ){
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
}

/* Funcion que permite ejecutar un query */
function executeQuery($strQuery){
    if( $strQuery!='' ){
        $conn = getConexion();
        $result = mysqli_query($conn, $strQuery);
        mysqli_close($conn);
        return $result;
    }
}

/* Funcion que permite realizar el proceso de autentificacion del usuario a la aplicacion */
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

/* Funcion quer permite convertir una fecha a formato MYSQL */
function convertDateMysql($strFecha)
{
    $strFechaConvert = "";
    if ($strFecha != '') {
        $arrExplode = explode("/", $strFecha);
        $strFechaConvert = $arrExplode[2] . '-' . $arrExplode[1] . '-' . $arrExplode[0];
    }
    return $strFechaConvert;
}

/* Funcion que permite registrar el usuario y fecha en que se creo la sesion */
function insertSession($strSession)
{
    if ($strSession != '') {
        $strQuery = "INSERT INTO session_user (nombre, fecha) VALUES ('{$strSession}', now())";
        executeQuery($strQuery);
    }
}

/* Funcion quer permite obtener el rol del usuario logeado */
function getRolUserSession($sessionName)
{
    $strRolUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT DISTINCT tipo_usuario.nombre
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                            INNER JOIN tipo_usuario 
                                    ON usuarios.tipo = tipo_usuario.id_tipo_usuario
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

/* Funcion que permite obtener el id del usuario logeado */
function getIDUserSession($sessionName)
{
    $intIDUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.id_usuario id
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
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

/* Funcion que permite obtener el nombre del colaborador que esta logeado en usuario */
function getNombreUserSession($sessionName)
{
    $strNameUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.nombrecolaborador
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
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

/* Funcion que permite generar un password aleatorio segun el numero de caracteres como parametro */
function generatePassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }
    return $result;
}

/* Funcion que permite convertir reemplazar tildes en mayusculas */
function upper_tildes($strString, $boolProper = false)
{
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

/* Funcion que permite filtrar caracterers especiales y tildes en un query */
function getFilterQuery($strFieldsSearch, $strFilterText, $boolAddAnd = true, $boolSepararPorEspacios = true)
{

    $strSearchString = "";
    $strFilterText = upper_tildes(trim($strFilterText));
    $strFilterText = str_replace(array("Á", "É", "Í", "Ó", "Ú"), array("A", "E", "I", "O", "U"), $strFilterText);
    $mixedFieldsSearch = explode(",", $strFieldsSearch);

    if (count($mixedFieldsSearch) > 1) {

        if ($boolSepararPorEspacios) {
            $arrFilterText = explode(" ", $strFilterText);
        } else {
            $arrFilterText[] = $strFilterText;
        }

        while ($arrTMP = each($arrFilterText)) {

            $strSearchString .= (empty($strSearchString)) ? "" : " AND ";
            $strSearchString .= " ( ";

            $intContador = 0;
            reset($mixedFieldsSearch);
            while ($arrFields = each($mixedFieldsSearch)) {
                $strWord = db_escape($arrTMP["value"]);
                $intContador++;
                if ($intContador > 1) $strSearchString .= " OR ";
                $strSearchString .= " UPPER(replace({$arrFields["value"]}, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE '%{$strWord}%' ";
            }

            $strSearchString .= " ) ";
        }
    } else {
        $strSearchString .= " UPPER(replace({$strFieldsSearch}, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE '%{$strFilterText}%' ";
    }

    if ($boolAddAnd) {
        $strSearchString = " AND " . $strSearchString;
    }

    return $strSearchString;
}

/* Funcion que permite el envio de correo electronico */
function sendEmail($destinatario, $asunto, $cuerpo)
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: CRM Televentas <jorge.tunchez@gmail.com>\r\n";
    mail($destinatario, $asunto, $cuerpo, $headers);
}


/* Funcion que permite dibujar el menu principal de aplicacion */
function draMenu($arrRolUser, $strName = '', $intNivel = 0)
{
?>
    <!-- Menu -->
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon rotate-n-15">
                <i class="fas fa-phone-volume"></i>
            </div>
            <div class="sidebar-brand-text mx-3">CRM Televentas</div>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider my-0">
        <!-- Nav Item - Dashboard -->
        <!-- Divider -->
        <hr class="sidebar-divider">
        <!-- Heading -->
        <?php
        if ((isset($arrRolUser["MASTER"]) &&  $arrRolUser["MASTER"] == true) || (isset($arrRolUser["NORMAL"]) &&  $arrRolUser["NORMAL"] == true)) {
        ?>
            <div class="sidebar-heading">
                Menu Principal
            </div>
            <!-- Nav Item - Pages Collapse Menu -->
            <li class="<?php print ($strName == 'dashboard.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Dashboard</span></a>
            </li>
            <!-- Divider -->
            <!-- Nav Item - Pages Collapse Menu -->
            <li class="<?php print ($strName == 'leads_asignados.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="leads_asignados.php">
                    <i class="fas fa-user-plus fa-address-card"></i>
                    <span>Leads Asignados</span></a>
            </li>
            <li class="<?php print ($strName == 'no_precalifica.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="no_precalifica.php">
                    <i class="fa fa-user-alt-slash"></i>
                    <span>No Precalifica</span></a>
            </li>
            <li class="<?php print ($strName == 'contacto_imposible.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="contacto_imposible.php">
                    <i class="fa fa-phone-slash"></i>
                    <span>Contacto Imposible</span></a>
            </li>
            <li class="<?php print ($strName == 'posible_cliente.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="posible_cliente.php">
                    <i class="fa fa-user-check"></i>
                    <span>Posible Cliente</span></a>
            </li>
            <li class="<?php print ($strName == 'cliente_final.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="cliente_final.php">
                    <i class="fa fa-hand-holding-usd"></i>
                    <span>Cliente Final</span></a>
            </li>
            <!-- Divider -->
        <?php
        }
        if (isset($arrRolUser["MASTER"]) &&  $arrRolUser["MASTER"] == true) {
        ?>
            <hr class="sidebar-divider">
            <!-- Heading -->
            <div class="sidebar-heading">
                Administrador
            </div>
            <!-- Nav Item - Charts -->
            <li class="<?php print ($strName == 'carga_archivos.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="carga_archivos.php">
                    <i class="fa fa-upload"></i>
                    <span>Carga de Archivos</span></a>
            </li>
            <!-- Nav Item - Charts -->
            <li class="<?php print ($strName == 'cat_estados.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="cat_estados.php">
                    <i class="fas fa-list"></i>
                    <span>Catálogo de Estados</span></a>
            </li>
            <!-- Nav Item - Charts -->
            <li class="<?php print ($strName == 'cat_usuarios.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="cat_usuarios.php">
                    <i class="fas fa-users"></i>
                    <span>Catálogo de Usuarios</span></a>
            </li>
            <!-- Nav Item - Charts -->
            <li class="<?php print ($strName == 'migracion_leads.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="migracion_leads.php">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Migración Leads</span></a>
            </li>
            <!-- Nav Item - Charts -->
            <li class="<?php print ($strName == 'reporte_gestiones.php') ? 'nav-item active' : 'nav-item'; ?>">
                <a class="nav-link" href="reporte_gestiones.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Reporte Gestiones</span></a>
            </li>
            <!-- Divider -->
            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">
            <!-- Sidebar Toggler (Sidebar) -->
        <?php
        }
        ?>
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>
    <!-- End of Sidebar -->
<?php
}
?>