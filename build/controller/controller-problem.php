<?php
require_once("controller-functions.php");
$system = new systemClass();
$system->validarSesion();
class problemas{

    function insertProblem($titulo,$descripcion, $area){  
        $system= new systemClass();
        $mysqli=$system->conectaDB();
        $descripcion=mysqli_real_escape_string($mysqli, $descripcion);
        $titulo=mysqli_real_escape_string($mysqli, $titulo);
        //echo "insert into problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW()";
        $mysqli->query("insert into problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW()");
      //  echo ("insert into problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW()");
       
        return 1;
    }
    function updateProblem($titulo,$descripcion, $area,$idp){  
        $system= new systemClass();
        $mysqli=$system->conectaDB();
        $descripcion=mysqli_real_escape_string($mysqli, $descripcion);
        $titulo=mysqli_real_escape_string($mysqli, $titulo);
 
        $mysqli->query("update  problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW() where id=".$idp);
      //  echo ("update  problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW() where id=".$idp);
       
        return 1;
    }
    
    function problemasDatos(){ //método para poder leer datos que deben estar en en la tabla de problemas/tips cambiar a "problema"
        $system=new systemClass();
        $mysqli=$system->conectaDB();

        $consulta="SELECT p.id,id_usuario,titulo,descripcion,fecha,updated_at,deleted_at,id_area,area
                   FROM problemas as p left JOIN  areas as a  on (p.id_area = a.id)  order by updated_at desc ";
        $resultado=$mysqli->query($consulta);
        
       return $resultado;//obtiene todas las filas de resultados y devuelve el conjunto de resultados como una matriz asociativa, una matriz numérica o ambas.

    }

    function problemaDatos($idp){ //para un problema hay muchas soluciones, esta funcion muestra el registro en tabla (problema, area) que sea identificado por el id clickeado en el botón ver
        $system=new systemClass();
        $mysqli=$system->conectaDB();

        $consulta="SELECT * FROM problemas as p left JOIN  areas as a on(p.id_area = a.id)  where p.id=$idp";//muestra solo el registro en la tabla que tenga el id clickeado por el boton en ver
        $resultado=$mysqli->query($consulta);
        
       return $resultado;
    }
    function insertsolucion($descripcion, $idp){ //no es necesario isnertar area o algo así ya que esos campos ya están en la db
        $system= new systemClass();
        $mysqli=$system->conectaDB();
        $descripcion=mysqli_real_escape_string($mysqli, $descripcion);
        $mysqli->query("insert into soluciones set id_usuario= '".$_SESSION['id']."', id_problema='$idp' , solucion = '$descripcion' , fecha = NOW(), updated_at=NOW()");
        //echo "insert into soluciones set id_usuario= '".$_SESSION['id']."', id_problema='$idp' , solucion = '$descripcion' , fecha = NOW(), updated_at=NOW()";
        return 1;
    }
    function soluciones($idp){

        $system=new systemClass();
        $mysqli=$system->conectaDB();

        $consulta="SELECT * FROM soluciones WHERE (id_problema = $idp)";
        
        $resultado=$mysqli->query($consulta);

        return $resultado;
    }

    function areas(){
        $system=new systemClass();
        $mysqli=$system->conectaDB();

        $consulta="SELECT * FROM areas ORDER BY area desc";
        return $mysqli->query($consulta);
    }

    


}