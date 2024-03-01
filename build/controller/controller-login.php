<?php
require_once("controller-functions.php");

class login
{ 
    
    function logearse($user,$pass)
    {
        $system= new systemClass();
        $mysqli=$system->conectaDB();

        $result=$mysqli->query("select u.id id, id_permiso, permiso, u.nombre nombre, u.apellido apellido, usuario,mail from usuarios u join permisos p on(u.id_permiso=p.id) where usuario='$user' and password='$pass' ");
        if($result->num_rows>0)
        {
            // $userData=$result->fetch_object();
            $userData=$result->fetch_assoc();
            session_start();
            $_SESSION['id']=$userData["id"];
            $_SESSION['id_permiso']=$userData["id_permiso"];
            $_SESSION['permiso']=$userData["permiso"];
            $_SESSION['nombre']=$userData["nombre"];
            $_SESSION['apellido']=$userData["apellido"];
            $_SESSION['user']=$userData["usuario"];
            $_SESSION['mail']=$userData["mail"];
            
            return true;

        }else{

            return false;
        }
        $result->close();
        unset($obj);
        unset($userData);
    }

    function closeSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();       
    }
    

}