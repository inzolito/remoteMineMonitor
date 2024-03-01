<?php
require_once("controller-functions.php");
$system = new systemClass();
$system->validarSesion();
class alertas
{

    function insertAlert($idFaena,$codigoAlerta , $mensajeAlerta)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        //$mensajeAlerta="aparece el siguiente mensaje en el sumarizador : mensajemensajemensaje " ;
        $alertaSistemaDatos=self::codigoAlerta(0,$codigoAlerta);
        if($alertaSistemaDatos==0) return 0;
        $alertaSistemaDatos=$alertaSistemaDatos->fetch_assoc();
        $idAlertaSistema=$alertaSistemaDatos["id"]; 
        
        //---
        // $alerta=mysqli_real_escape_string($mysqli, $alerta);
        //echo "insert into problemas set id_usuario= '".$_SESSION['id']."', titulo='$titulo' , descripcion = '$descripcion', id_area=$area , fecha = NOW()";
       $existeAlerta=self::alerta($idFaena,$idAlertaSistema,1);

       if($existeAlerta->num_rows==0)
       {
         
            $mysqli->query(
                "
            insert into alertas set 
            id_faena='$idFaena',
            id_alerta_sistema='$idAlertaSistema',
            created_at = NOW(),
            updated_at=NOW(),
            id_usuario= null, 
            alerta='$mensajeAlerta', 
            vista=0,
            estado=1"
            );
            
        }
        return 1;
    }

    function codigoAlerta($idAlertaSistema,$codigoAlerta=0)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        
        $whereAlerta= ($idAlertaSistema==0)?" codigo_alerta ='$codigoAlerta' ":" id=$idAlertaSistema";
        $result=$mysqli->query("select * from alertas_sistema where $whereAlerta ");
      
        if($result->num_rows>0)
        {
            
            return $result;
             
        }else{
            return 0;
        }

    }

    function alerta($idFaena,$idAlertaSistema=0,$estado="*",$vista="-1",$idAlerta=0)
    {
        
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        $whereAlerta="";
       // echo "vista ->$vista";
        
        if ($idAlertaSistema!=0) $whereAlerta=" and id_alerta_sistema='$idAlertaSistema'" ;
        if ($estado!=0) $whereAlerta.=" and estado='$estado'" ;
        if ($vista!="-1") $whereAlerta.=" and vista='$vista'" ;
        if ($idAlerta!=0) $whereAlerta.=" and a.id=$idAlerta" ;

        //echo "select * from alertas where id_faena=$idFaena  $whereAlerta ";
        if($idFaena==0)
        {
            //echo "select * from alertas where id_faena>0  $whereAlerta ";
            //echo "select a.id as alerta_id ,id_alerta_sistema ,id_usuario ,id_faena ,created_at ,updated_at ,deleted_at ,alerta_sistema ,vista ,estado ,codigo_alerta ,alerta ,gravedad from alertas a join alertas_sistema asis on (a.id_alerta_sistema=asis.id) where id_faena>0  $whereAlerta ";
            return $mysqli->query("select a.id as alerta_id ,id_alerta_sistema ,id_usuario ,id_faena ,created_at ,updated_at ,deleted_at ,alerta_sistema ,vista ,estado ,codigo_alerta ,alerta ,gravedad from alertas a join alertas_sistema asis on (a.id_alerta_sistema=asis.id) where id_faena>0  $whereAlerta  order by a.updated_at desc");

        }else{
            //echo "select * from alertas where id_faena=$idFaena  $whereAlerta ";

            return $mysqli->query("select a.id as alerta_id ,id_alerta_sistema ,id_usuario ,id_faena ,created_at ,updated_at ,deleted_at ,alerta_sistema ,vista ,estado ,codigo_alerta ,alerta ,gravedad from alertas a join alertas_sistema asis on (a.id_alerta_sistema=asis.id) where id_faena=$idFaena  $whereAlerta order by a.updated_at desc");
         }
         
    }

    function alertaVista($idAlertaSistema,$idFaena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        $whereAlerta="";
        //print_r($_SESSION) ;
        //echo          "update alertas set id_usuario=".$_SESSION['id']." , updated_at= '".$system->formatoFecha(0,0)."' ,vista=1  where id_faena=$idFaena and vista=0 ";
         $mysqli->query("update alertas set id_usuario=".$_SESSION['id']." , updated_at= '".$system->formatoFecha(0,0)."' ,vista=1  where id_faena=$idFaena and vista=0 ");
        return 1;
        
    }

    function alertaSolucionada($idAlerta)
    {
        
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        $whereAlerta="";
        //print_r($_SESSION) ;
        //echo "update alertas set id_usuario=".$_SESSION['id']." , updated_at= '".$system->formatoFecha(0,0)."' ,vista=1  where id_faena=$idFaena and vista=0 ";
        //echo "update alertas set   updated_at= '".$system->formatoFecha(0,0)."' ,vista=1, estado=0  where id=$idAlerta ";
         $mysqli->query("update alertas set   updated_at= '".$system->formatoFecha(0,0)."' ,vista=1, estado=0  where id=$idAlerta ");
         $mysqli->query("insert into alerta_solucion set   created_at= '".$system->formatoFecha(0,0)."' , updated_at= '".$system->formatoFecha(0,0)."' , id_alerta=$idAlerta , id_usuario=".$_SESSION['id']." ");
         echo "insert into alerta_solucion set   created_at= '".$system->formatoFecha(0,0)."' , updated_at= '".$system->formatoFecha(0,0)."' , id_alerta=$idAlerta , id_usuario=".$_SESSION['id']." ";
        return 1;
        
    }
}
