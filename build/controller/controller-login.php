<?php
require_once("controller-functions.php");

class login
{
    
    function logearse($user,$pass)
    {
        $system= new systemClass();
        $mysqli=$system->conectaDB();

        $result=$mysqli->query("select * from usuarios where usuario='$user' and password='$pass' ");
        if($result->num_rows>0)
        {
            $userData=$result->fetch_object();
            session_start();
            $_SESSION['id']=$userData->id;
            $_SESSION['user']=$userData->usuario;
            $_SESSION['mail']=$userData->mail;
            
            return true;

        }else{

            return false;
        }
        $result->close();
        unset($obj);
        unset($userData);
    }

}