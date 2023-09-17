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

    function validarSesion()
    {
        $conn = new systemClass();

        $conn->conectaDB();

        if (session_status() == PHP_SESSION_ACTIVE) {
        } else {
            session_start();
            if ($_SESSION["user"] == false) {
                echo '<meta http-equiv="refresh" content="0; url=' . $conn->urlSystem() . '/pages/login/login.php">';
            }
        }
    }

    function formatoFecha($fecha, $tipoFecha)
    {

        date_default_timezone_set('America/Santiago');

        switch ($tipoFecha) {
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


    function pintarDiv($largo)
    {
        if ($largo >= 2) {
            return "card card-navy";
        } else if ($largo <= 1) {
            return "card card-light ";
        } //else{return "card card-navy disable";}

    }

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



    function iconStatusConexionFaena($alias)
    {
        $carpeta = $alias . "/";
        $ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;
        $log = file($ruta . "ProcesosJamsMon.log");

        if (file_exists($ruta . "/ProcesosJamsMon.log")) {

            $fechaActual = new DateTime();
            $fechaLog = DateTime::createFromFormat("Y-m-d H:i:s", trim($log[0]));
            $diferencia = $fechaActual->diff($fechaLog)->i;

            $iconoOnline = " class='fas fa-wifi text-success mr-2' ";
            if ($diferencia > 6) {
                $iconoOnline = " class='fas fa-wifi text-danger mr-2' ";
                //$system->alertaSonora(240000);
            }
        } else {
            $iconoOnline = " class='fas fa-wifi text-danger mr-2' ";
        }
        return  $iconoOnline;
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

    function divAlert()
    {
        return "<div   >
                     <i class='fa-solid fa-triangle-exclamation' style='font-size:50px'></i>
                </div>";
    }
}
