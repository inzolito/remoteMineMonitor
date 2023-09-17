<?php

class conectionClass
{
    private $config;

    function __construct()
    {
        $configFile = '../../.config.json';

        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            echo $configFile ;
            die('El archivo de configuración no existe.');
        }
    }
 
    function conectaDB2()
    {
        $mysqli = @new mysqli(
            $this->config['DB_HOST'],
            $this->config['DB_USER'],
            $this->config['DB_PASS'],
            $this->config['DB_NAME']
        );
        if ($mysqli->connect_error) {
            die('Error de conexión: ' . $mysqli->connect_error);
        }
        return $mysqli;
    }

    function urlSystem2()
    {
       // return "http://10.40.90.99/soporte";
       return $this->config['APP_INDEX'];
    }

    function rutaDataSet2()
    {
        return $this->config['APP_RUTADATASET'];
        //return "/home/jigsaw/monitoreoRemoto/";
        //return "/home/jigsaw/monitoreoRemotoLaboratorio/";
    }

}
