<?php
require_once("controller-functions.php");

class login
{ 
    
    function logearse($user,$pass)
    {
        $system= new systemClass();
        $mysqli=$system->conectaDB();

        $result=$mysqli->query("select * from usuarios u join permisos p on(u.id_permiso=p.id) where usuario='$user' and password='$pass' ");
        if($result->num_rows>0)
        {
            // $userData=$result->fetch_object();
            $userData=$result->fetch_assoc();
            session_start();
            $_SESSION['id']=$userData["u.id"];
            $_SESSION['id_permiso']=$userData["id_permiso"];
            $_SESSION['permiso']=$userData["permiso"];
            $_SESSION['nombre']=$userData["u.nombre"];
            $_SESSION['apellido']=$userData["u.apellido"];
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

}