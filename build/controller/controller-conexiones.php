<?php

class conectionClass
{

    function conectaDB2()
    {
        $mysqli = @new mysqli('localhost', 'jigsaw', 'Jigsaw1', 'checksupport');
        if ($mysqli->connect_error) {
            die('Error de conexión: ' . $mysqli->connect_error);
        }
        return $mysqli;
    }

    function urlSystem2()
    {
       // return "http://10.40.90.99/soporte";
       return "http://10.40.90.99/monitoreoLaboratorio/";
    }

    function rutaDataSet2()
    {
        return "/home/jigsaw/monitoreoRemoto/";
        //return "/home/jigsaw/monitoreoRemotoLaboratorio/";
    }

}
