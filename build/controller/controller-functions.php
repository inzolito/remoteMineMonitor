<?php
require_once("controller-conexiones.php");

class systemClass
{

    function conectaDB()
    {
        $conection = new conectionClass();
        return $conection->conectaDB2();
    }

    function urlSystem()
    {
        $conection = new conectionClass();
        return $conection->urlSystem2();
    }

    function rutaDataSet()
    {
        $conection = new conectionClass();
        return $conection->rutaDataSet2();
    }

    /*
    function validarSesion()
    {
        $conn = new systemClass();

        $conn->conectaDB();

        if (session_status() == PHP_SESSION_ACTIVE) {
        } else {
            session_start();
            if ($_SESSION["user"] == false) {

                echo '<meta http-equiv="refresh" content="0; url=' . $conn->urlSystem() . 'pages/login/login.php">';
                return 1;
            }else{
                
            }
        }
    }
*/

    function validarSesion()
    {
        $conn = new systemClass();
        $conn->conectaDB();

        $urlSistema = $conn->urlSystem();
        $urlProduccion = "http://10.169.140.99/soporte/"; // Producción
        $pathProduccion = "/soporte"; // Ruta de producción

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION["user"])) {
            header("Location: " . $urlSistema . "pages/login/login.php");
            return false;
            //exit;
        }

        if ($_SESSION["permiso"] !== "Administrador") {
            $hostActual = $_SERVER['HTTP_HOST'];
            $pathActual = $_SERVER['REQUEST_URI'];

            if (strpos($pathActual, $pathProduccion) === false) {
                // No está en producción, lo redirige
                header("Location: $urlProduccion");
                exit;
            }
        }
    }





    function formatoFecha($fecha = 0, $tipoFecha = 0)
    {
        if ($fecha == 0) $fecha = date("Y-m-d H:i:s");

        date_default_timezone_set('America/Santiago');

        switch ($tipoFecha) {
            case 0:
                return $fecha;
                break;
            case 1:
            case "DB":
            case "BD":
                return date("Y-m-d", strtotime($fecha));
                break;
            case 2:
            case "vista":
                return date("d-m-Y", strtotime($fecha));
                break;
            case 3:
            case "vistaDT":
                return date("d-m-Y , H:i", strtotime($fecha));
                break;
            case 4:
            case "hs":
                return date("H:i", strtotime($fecha));
                break;
            case 5:
            case "lectura":
                $dias = array("domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado");
                $meses = array("enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");

                return $dias[date('w', strtotime($fecha))] . " " . date('d', strtotime($fecha)) . " de " . $meses[date('n', strtotime($fecha)) - 1] . " del " . date('Y', strtotime($fecha));
                break;
            case 6:
            case "vistadtlectura":
                return date("d H:i", strtotime($fecha));
                break;
            case 7:
            case "datatimeDB":

                $anio = substr($fecha, 0, 4);
                $mes = substr($fecha, 4, 2);
                $dia = substr($fecha, 6, 2);
                $hora = substr($fecha, 8, 2);
                $minuto = substr($fecha, 10, 2);
                $segundo = substr($fecha, 12, 2);

                // Formatear la fecha y hora legible
                $fechaFormateada = "{$anio}-{$mes}-{$dia} {$hora}:{$minuto}:{$segundo}";

                return $fechaFormateada;
                break;
            default:
                return $fecha;
                break;
        }
    }

    function formatNumber($number, $decimals = 0)
    {
        $formatted_number = number_format($number, $decimals, ',', '.');
        return $formatted_number;
    }

    function procesaLog($log)
    {
        $system = new systemClass();
        $datatime = array_shift($log);
        $datatime = "<p class='text-muted float-right' style='font-size:14px'><i class='fa-solid fa-rotate-right'></i> " . $system->formatoFecha($datatime, 6) . "</p>";
        return array($datatime, $log);
    }


    function pintarDiv($largo, $error = 0)
    {
        if ($error == 0) {
            if ($largo >= 2) {
                return "card card-navy";
            } else if ($largo <= 1) {
                return "card card-light ";
            }
        } else {
            if ($error == 1) {
                return "card dangerRRM";
            } else {
                return "card card-light ";
            }
        }
    }



    //-------------------------------------------------------------NuevaFuncion-----------
    function pintarDiv2($tipo, $validacion)
    {
        if ($tipo == "sumarizador") {
            if ($validacion == 1) {
                return "card card-navy";
            } else {
                return "card dangerRRM";
            }
        } elseif ($tipo == "daily") {
            if ($validacion == 1) {
                return "card card-navy";
            } else {
                return "card dangerRRM";
            }
        }
    }

    //------------------------------------------------------------------------------------

    function validarLog($log, $intervalo, $alineacion = "left")
    {
        $margin = ($alineacion == "left") ? "mr-2" : "ml-2";
        //$horaActual = date("H:i");
        //$diaActual = date("d");
        $largo = count($log);

        $contador = 0;
        for ($x = 0; $x < $largo; $x++) {
            $hora = $log[0];
            $fechaLog = $log[0];
            $contador++;
            if ($contador == 1) {
                $hAux = date('H:i', strtotime($hora));
                $dAux = date("d", strtotime($hora));
            } else {
                $contador = 0;
                break;
            }
        }

        //$horaActual = new DateTime();
        //$horaActual->sub(new DateInterval('PT5M')); // Resta 5 minutos
        $fechaActual = date("Y-m-d H:i:s");
        $fechaAct = new DateTime($fechaActual);
        $fechaLog = new DateTime($fechaLog);


        $diferencia = ($fechaAct->getTimestamp() - $fechaLog->getTimestamp()) / 60;
        //$diferenciaSegundos = $diferencia * 24 * 60 * 60; // Multiplicar por el número de segundos en un día

        //echo $horaResta = $horaActual - $hAux;

        if ($diferencia <= $intervalo) {
            return "<p class='float-$alineacion mt-1' style='font-size:10px'><i class='fa-solid text-success fa-circle $margin'></i> </p>";
        } else {
            return "<p class='float-$alineacion mt-1' style='font-size:10px'><i class='fa-solid text-danger fa-circle $margin'></i> </p>";
            echo '<audio autoplay>';
            echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
            echo '</audio>';
        }
    }

    function validarConexion($log, $logS, $intervalo)
    {
        $largo = count($log);
        $largoS = count($logS);
        for ($x = 0; $x < $largo; $x++) {
            //$hora = $log[0];
            $fechaLog = $log[0];
        }
        if ($largo >= 1 && $largoS >= 1) {
            $validarLargo = 0;
        } else {
            $validarLargo = 1;
        }
        //$horaActual = new DateTime();
        //$horaActual->sub(new DateInterval('PT5M')); // Resta 5 minutos
        $fechaActual = date("Y-m-d H:i:s");
        $fechaAct = new DateTime($fechaActual);
        $fechaLog = new DateTime($fechaLog);


        $diferencia = ($fechaAct->getTimestamp() - $fechaLog->getTimestamp()) / 60;
        //$diferenciaSegundos = $diferencia * 24 * 60 * 60; // Multiplicar por el número de segundos en un día

        //echo $horaResta = $horaActual - $hAux;

        if ($diferencia <= $intervalo && $validarLargo == 0) {
            return "class='fas fa-wifi text-success mr-2'";
        } else {
            return "class='fas fa-wifi text-danger mr-2'";
        }
    }



    function iconStatusConexionFaena($alias, $retornarBinario = 0, $servidor123 = 0)
    {
        $system = new systemClass();
        $carpeta = $alias . "/";

        $ruta = $system->rutaDataSet() . $carpeta;
        $nombreLog = "ProcesosJamsMon.log";
        if ($servidor123 == 2) $nombreLog = "ProcesosJamsSecMon.log";
        $rutaLog = $ruta . $nombreLog;

        $log = file($rutaLog);
        //echo $rutaLog;
        // if ($servidor123==2) $log=file($ruta . "ProcesosJamsSecMon.log");

        $onlineBinario = 0;
        if (file_exists($rutaLog)) {

            $fechaActual = new DateTime();
            $fechaLog = DateTime::createFromFormat("Y-m-d H:i:s", trim($log[0]));

            $diferencia = $fechaActual->diff($fechaLog)->i;

            $iconoOnline = " class='fas fa-wifi text-success mr-2' ";
            $onlineBinario = 1;
            //echo "diferencia $diferencia";
            //en minutos
            if ($diferencia > 3) {
                $iconoOnline = " class='fas fa-wifi text-danger mr-2' ";
                $onlineBinario = 0;
                //$system->alertaSonora(240000);
            } else {
                $onlineBinario = 1;
            }
        } else {
            $iconoOnline = " class='fas fa-wifi text-danger mr-2' ";
            $onlineBinario = 0;
        }
        if ($retornarBinario == 0) {
            return  $iconoOnline;
        } else {
            return $onlineBinario;
        }
    }

    function datatimeCargaDiv()
    {

        //conseguir fecha actual
        date_default_timezone_set('America/Santiago');
        $fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
        $horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
        return  $fechaActualVisual . " " . $horaActualVsual;
    }

    function validarPing($largo)
    {
        if ($largo > 6) {
            return "<p class='float-left' style='font-size:10px'><i class='fa-solid text-success fa-circle mr-3'></i> </p>";
        } else {
            return "<p class='float-left' style='font-size:10px'><i class='fa-solid text-danger fa-circle mr-3'></i> </p>";
        }
    }

    function datosUsuario($id_user)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $result = $mysqli->query("select * from usuarios where id='$id_user' ");
        if ($result->num_rows > 0) {
            $userData = $result->fetch_object();

            return $userData;
        } else {

            return false;
        }
    }


    function datosServidor($ipServer = 0)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $result = $mysqli->query("select * from servidores where ip='$ipServer' ");
        if ($result->num_rows > 0) {
            $serverData = $result->fetch_object();

            return $serverData;
        } else {

            return false;
        }
    }
    function alertaSonora($tiempo)
    {
        echo '<script>';
        echo 'var audio = new Audio("pages/support/sonido/ping_missing.mp3");';
        echo 'audio.play();';
        echo 'setTimeout(function() { audio.pause(); }, ' . $tiempo . ');';
        echo '</script>';
    }


    function alertaSistema($alias, $titulo, $mensaje)
    {
        $errores = array();

        //preguntar si el titulo ya esta en $errores de lo contrario se agrega si está no se hace nada
        if (in_array($titulo, $errores)) {
            echo 'El objeto existe en el array.';
        } else {
            $errores($alias, $titulo, $mensaje);
        }
    }

    // Funcion para enviar mensajes por Telegram
    function enviarMensajeTelegram($message)
    {
        echo "enviando";
        // Token del bot
        $token = '7167115609:AAEEihCCCRzkJmsOkFAfvOPYSwl1qr2Ts2E';

        // ID del chat (grupo)
        $chat_id = '-1002428398059'; // ID de tu grupo

        // URL de la API de Telegram
        $url = "https://api.telegram.org/bot$token/sendMessage";

        // Datos del mensaje
        $data = [
            'chat_id' => $chat_id,
            'text' => $message
        ];

        // Inicializar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Establece la ruta del certificado CA para la verificación SSL
        curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/ca-certificates.crt');
        //revisa el certificado del servidor de Telegram usando el archivo de certificados. 
        //Si todo está bien, se establece una conexión segura y el mensaje se envía sin problemas.

        // Ejecutar cURL y obtener la respuesta
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }



    function divAlert()
    {
        return "<div   >
                     <i class='fa-solid fa-triangle-exclamation' style='font-size:50px'></i>
                </div>";
    }

    function buttonClass($typeButton)
    {
        // 1 - success
        // 2 - warning
        // 3 - danger
        // 4 - disabled
        // 5 - default

        return  "btn btn-$typeButton btn-block";
    }
}
