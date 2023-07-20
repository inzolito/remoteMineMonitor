<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_REQUEST["id"];


//Falta validar cuando no existe la faena
$faenaDatos = $fenaCl->datos($id_faena);
$estadoCheckFaena = $fenaCl->estado($id_faena);
$checkDatos = $fenaCl->datosCheck($id_faena);

$carpeta = $faenaDatos->alias . "/";
$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;




// ------------------------------------------------

$impo = file($ruta . "ProcesoImpoMon.log");
$sqlServer = file($ruta . "ProcesoSqlMon.log");
$sql_Back = file($ruta . "ProcesoSqlBackMon.log");
$proceSumarizador = file($ruta . "ProcesosSumarizadorMon.log");
$Jamms = file($ruta . "ProcesosJamsMon.log");
$jamsSec = file($ruta . "ProcesosJamsSecMon.log");
$Ntp = file($ruta . "NtpMon.log");
$reconcile = file($ruta . "ReconcileMon.log");
$BackupSec = file($ruta . "BackupSecMon.log");
$procSumCrontab = file($ruta . "crontabSummMon.log");
$listaJams = file($ruta . "ListaJamsMon.log");



//count de lineas Impo
$largoImpo = count($impo) - 2;
//count sqlServer
$largoSqlServer = count($sqlServer) - 3;
//count sql back
$largoSqlBack = count($sql_Back) - 3;
//count Summ
$largoSummarizador = count($proceSumarizador) - 3;
//count Ntp
$largoNtp = count($Ntp) - 2;
//count reconcile
$largoReconcile = count($reconcile) - 5;

//largo sum por Crontab
$largoValidarCrontab = count($procSumCrontab);

//for para obtener si esta corriendo por crontab
for ($x = 1; $x < $largoValidarCrontab; $x++) {
    $validarCrontab = $procSumCrontab[$x];
    $validarCrontab  = substr($validarCrontab, 0, 1);
    if ($validarCrontab == "*") {
        $validarCrontabOK = "Crontab";
    }
}



// Funcion para enviar mensajes por Telegram
function enviarMensajeTelegram($message)
{
    $botToken = "6098434713:AAEuvoUJKnUwzW_Wx2h4e2LnYCAkoW1iB-I"; //Token del bot Telegram
    $chatId = "-1001966529185"; // Reemplazar con el chat ID del usuario o grupo al que se le quiere enviar el mensaje

    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = array(
        'chat_id' => $chatId,
        'text' => $message
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
}

//Funcion par enviar mensaje por correo
function enviarEmail($subject, $message)
{
    $to = 'soportechile@Hexmet.onmicrosoft.com';
    $headers = 'From: @gmail.com' . "\r\n" .
        'Reply-To: @gmail.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}

//conseguir fecha actual
date_default_timezone_set('America/Santiago');
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual . " " . $horaActualVsual;


//validar Jams
$largoJams = count($Jamms);

if (strpos($Jamms, "JAMSRouter start") !== false && strpos($Jamms, "JAMSCluster run") !== false) {
    $validarJams = 1;
} else if(strpos($Jamms,"JAMSRun -config config.jams -log JAMS,error")!==false || $largoJams < 2) {
    $validarJams = 2;
}

//validar Jams Sec
$largoJamsSec = count($jamsSec);

if (strpos($jamsSec, "JAMSRouter start") !== false && strpos($jamsSec, "JAMSCluster run") !== false) {
    $validarJamsSec = 1;
} else {
    $validarJamsSec = 2;
}

//validar ntp
if (strpos($Ntp, "/usr/sbin/ntpd -p") !== false) {
    $validacionNtp = 1;
} else {
    $validacionNtp = 2;
}

//validar manual 
if (strpos("-force -start", $proceSumarizador) !== false) {
    $validarManual = 1;
} else {
    $validarManual = 2;
}

//validar reconcile
if(strpos("ERROR",strtolower($reconcile)) !== false || strpos("warning",strtolower($reconcile)) !== false){
    $validarReconcile = 1;
}else{
    $validarReconcile = 0;
}

?>



<!-- PS AUX , importadores , sumarizador, jams etc -->
<div class="col-md-12">
    <div class='<?php echo $system->pintarDiv($largoJams) ?>'>
        <div class="card-header">
            <h3 class="card-title">Procesos </h3>
            <div class="card-tools">
                <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
            </div>
        </div>

        <div class="card-body">
            <!-- ----------- Row Datos procesos------------->
            <div class="row">

                <!-- JAMS -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success";
                if ($validarJams == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'problemas en el Proceso Jams';
                    //enviarEmail($subject, $message);

                }

                //if($validarJams == 2){
                    //echo '<audio autoplay>';
                    //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    //echo '</audio>';

                    //sleep(90);
                //}
                ?>
                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divJamsVista')">

                    <div class="small-box <?php echo $colorAux ?>">
                        <div class="inner">
                            <h5>JAMS Primario <?php echo $system->validarLog($Jamms, 6, "right") ?></h5>
                            <p><?php echo $valorAux ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>

                <!-- JAMS Sec -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success";
                if ($validarJamsSec == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'problemas en el Proceso Jams';
                    //enviarEmail($subject, $message);
                    //echo '<audio autoplay>';
                    //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    //echo '</audio>';
                }
                if($carpeta == "capcnn/"){
                    $colorAux = "bg-gray";
                    $valorAux = "Stopped";
                }
                ?>
                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divJamsSecVista')">

                    <div class="small-box <?php echo $colorAux ?>">
                        <div class="inner">
                            <?php if($carpeta == "capcnn/"){echo "<h6>JAMS Secundario</h6>";} else{?>
                            <h6>JAMS Secundario <?php echo $system->validarLog($jamsSec, 6, "right") ?></h6>
                            <p><?php } echo $valorAux ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Importadores -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoImpo;
                if ($largoAux >= 3 && $largoAux <= 4) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if ($largoAux >= 5 || $largoAux < 0) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //echo $system->alerta("Error en Importadores","Demaciados procesos Impo");
                    $system->alertaSonora(120000);
                }

                /*if($largoAux >= 7){
                    echo '<audio autoplay>';
                    echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    echo '</audio>';

                    sleep(90);
                }*/
                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divImpoVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Importadores <?php echo $system->validarLog($impo, 6, "right") ?></h6>
                            <p> <?php echo $valorAux . $largoImpo ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-solid fa-cubes-stacked" style="font-size:48px"> </i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- SQLServer -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSqlServer;
                if ($largoAux > 1 ) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if ($largoAux < 0 || $largoAux > 4) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    $system->alertaSonora(120000);    
                }

                /*if($largoAux >= 6){
                    echo '<audio autoplay>';
                    echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    echo '</audio>';

                    sleep(90);
                }*/
                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSqlServerVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>SQL server <?php echo $system->validarLog($sqlServer, 6, "right") ?></h5>
                            <p><?php echo $valorAux . $largoSqlServer ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas  fa-database " style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

            

                <!-- Sumarizador -->
            
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSummarizador;
                if ($largoAux > 2 || $largoAux < 2) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if ($largoAux >= 6 || $largoAux <= 2 && $validarCrontab == "*" || $validarManual == 1) {
                    $colorAux = "bg-success";
                    $valorAux = "Success: ";
                }
                if ($largoAux >= 6 || $largoAux < 2 && $validarCrontab != "*") {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //$message = "Problemas con el proceso de sumarizado en " . $carpeta;
                    //echo $system->enviarMensajeTelegram($message);

                    //$subject = 'Problemas con '.$carpeta;
                    //$message = 'Error en el Proceso de sumarizado';
                    //enviarEmail($subject, $message);
                    $system->alertaSonora(120000);
                }

                /*if($largoAux >= 8 || $largoAux < 2 && $validarCrontab != "*"){
                    echo '<audio autoplay>';
                    echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    echo '</audio>';

                    sleep(90);
                }*/
                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSummVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Sumarizador <?php echo $system->validarLog($proceSumarizador, 6, "right") ?></h6>
                            <p><?php echo $valorAux . $largoSummarizador ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-sharp fa-regular fa-bars-staggered" style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

                <!-- NTP -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoNtp;
                if ($validacionNtp == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'Problemas con el NTP';
                    //enviarEmail($subject, $message);
                    $system -> alertaSonora(120000);
                }

                /*if($validacionNtp == 2){
                    echo '<audio autoplay>';
                    echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    echo '</audio>';

                    sleep(90);
                }*/
                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divNtpVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Ntp <?php echo $system->validarLog($Ntp, 6, "right") ?></h6>
                            <p> <?php echo $valorAux . $largoNtp ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-regular fa-clock" style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">
                <!-- Sql_Back -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSqlBack;
                if ($largoAux > 1 && $largoAux < 6) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if ($largoAux < 0 || $largoAux >= 6) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    $system->alertaSonora(120000);
                }

                /*if($largoAux >= 8 || $largoAux < 0){
                    echo '<audio autoplay>';
                    echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
                    echo '</audio>';

                    sleep(90);
                }*/
                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSqlBackVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>sql_back <?php echo $system->validarLog($sql_Back, 6, "right") ?></h5>
                            <p> <?php echo $valorAux . $largoSqlBack ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas  fa-database " style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

                <!-- Reconcile-->
                <?php
                $colorAux = "bg-success";
                $textoAux = "Success";
                $largoAux = $largoReconcile;
                if($largoReconcile < 2){
                    $colorAux = "bg-warning";
                    $textoAux = "Warning";
                }
                ?>
                
                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divReconcileVista')">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>Reconcile  <?php echo $system->validarLog($reconcile, 6, "right") ?></h5>
                            <p><?php echo $textoAux?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>



                <!-- ################################### Display none  ###################################-->
                

                <?php
                $colorAux = "bg-success";
                ?>
                <!-- Backup sec -->
                <div class="col-lg-3 col-6" style="display:none">

                    <div class="small-box <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>Backup Secundario</h5>
                            <p>Success</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>

            </div>





























            <div class="row" style="display:none">

                <!-- Fin row datos procesos -->


                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">JAMS </b><button type="button" class="btn btn-outline-info" onclick="verInfo('divJamsVista')">Ver Info</button>

                        <div class="row" id="divJamsVista" tittle="JAMS corriendo." style="overflow:auto;">
                            <div class="col-md-12">

                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($Jamms as $x) {
                                        $ProcesoJamms = $Jamms;
                                        echo "<li>" . trim($x) . "</li>";
                                    }   ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">JAMS Secundario </b><button type="button" class="btn btn-outline-info" onclick="verInfo('divJamsSecVista')">Ver Info</button>

                        <div class="row" id="divJamsSecVista" tittle="JAMS corriendo." style="overflow:auto;">
                            <div class="col-md-12">

                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($jamsSec as $x) {
                                        $ProcesoJammsSec = $jamsSec;
                                        echo "<li>" . trim($x) . "</li>";
                                    }   ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Importadores -->

                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $colorAux = "bg-success";
                        if ($largoImpo > 1 && $largoImpo < 9)  $colorAux = "bg-warning";
                        if ($largoImpo >= 9)  $colorAux = "bg-danger";
                        ?>
                        <b class="d-block">Importadores: <?php echo "<span class='badge " . $colorAux . "''>" . $largoImpo . "</span>"  ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divImpoVista')">Ver Info</button>

                        <div class="row" id="divImpoVista" tittle="Importadores." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($impo as $x) {
                                        $importadores = $impo;
                                        echo "<li>" . trim($x) . "</li>";
                                    }

                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- SqlServer -->

                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $colorAux = "bg-success";
                        if ($largoSqlServer > 1 && $largoSqlServer < 9)  $colorAux = "bg-warning";
                        if ($largoSqlServer >= 9)  $colorAux = "bg-danger";
                        ?>
                        <b class="d-block">SqlServer: <?php echo "<span class='badge " . $colorAux . "''>" . $largoSqlServer . "</span>" ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divSqlServerVista')">Ver Info</button>
                        <div class="row" id="divSqlServerVista" tittle="Procesos SQL Server." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($sqlServer as $x) {
                                        $sqlServer = $sqlServer;;
                                        echo "<li>" . trim($x) . "</li>";
                                    }  ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- sql_back -->

                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $colorAux = "bg-success";
                        if ($largoSqlBack > 1 && $largoSqlBack <= 5)  $colorAux = "bg-warning";
                        if ($largoSqlBack >= 6)  $colorAux = "bg-danger";
                        ?>
                        <b class="d-block">sql_back: <?php echo "<span class='badge " . $colorAux . "''>" . $largoSqlBack . "</span>" ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divSqlBackVista')">Ver Info</button>
                        <div class="row" id="divSqlBackVista" tittle="Procesos sql_back." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($sql_Back as $x) {
                                        $sql_Back = $sql_Back;;
                                        echo "<li>" . trim($x) . "</li>";
                                    }  ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>



                <!-- Summarizador -->
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">Summarizador: <?php echo $largoSummarizador ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divSummVista')">Ver Info</button>
                        <div class="row" id="divSummVista" tittle="Summarizador" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($proceSumarizador as $x) {
                                        $proceSumarizador   = $proceSumarizador;;
                                        echo "<li>" . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- NTP -->
                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $colorAux = "bg-success";
                        if ($largoNtp > 3 && $largoNtp < 3)  $colorAux = "bg-warning";
                        if ($largoNtp >= 6 || $largoNtp < 2)  $colorAux = "bg-danger";
                        ?>
                        <b class="d-block">Ntp: <?php echo "<span class='badge " . $colorAux . "''>" . $largoNtp . "</span>" ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divNtpVista')">Ver Info</button>

                        <div class="row" id="divNtpVista" tittle="NTP" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($Ntp as $x) {
                                        $Ntp   = $Ntp;
                                        echo " <li>" . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reconcile -->
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">Reconcile: <?php echo $largoReconcile ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divReconcileVista')">Ver Info</button>
                        <div class="row" id="divReconcileVista" tittle="Reconcilie" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($reconcile as $x) {
                                        $reconcile = $reconcile;;
                                        echo " <li> " . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!--BackupSec-->
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">Backup Secundario</b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divBackupSecVista')">Ver Info</button>
                        <div class="row" id="divBackupSecVista" tittle="Backup Secundario" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($BackupSec as $x) {
                                        $BackupSec = $BackupSec;;
                                        echo " - " . trim($x) . "<br>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


    </div>

</div>








<script>
    $("#divJamsVista").fadeOut()
    $("#divImpoVista").fadeOut()
    $("#divSqlServerVista").fadeOut()
    $("#divSqlBackVista").fadeOut()
    $("#divSummVista").fadeOut()
    $("#divNtpVista").fadeOut()
    $("#divReconcileVista").fadeOut()
    $("#divBackupSecVista").fadeOut()
    $("#divJamsSecVista").fadeOut()


    function verInfo(id) {
        // $("#" + id + "").toggle() //cambiar luego a fadeIn
        $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();
        $("#modalLargeBody").html($("#" + id).html());
        $("#modalLargeTittle").html($("#" + id).attr("tittle"));
    }
</script>