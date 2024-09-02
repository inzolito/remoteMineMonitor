<?php
require_once("controller-functions.php");
$system = new systemClass();
$system->validarSesion();
class faena
{

    function estado($id_faena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $faenaCheckSql = $mysqli->query("select *  from  check_faena  where id_faena=" . $id_faena . " and estado='pendiente'");
        // $faenaCheckSql=$mysqli ->query("select * from usuarios where usuario='$user' and password='$pass' ");

        if ($faenaCheckSql->num_rows == 1) {
            return "Pendiente";
        } else {
            return "Finalizado";
        }
    }

    function datosCheck($id_faena, $fecha_inicio = 0, $fecha_fin = 0)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $whereBetween = "";
        if ($fecha_inicio == 0 && $fecha_fin == 0) {
        } else {
            $whereBetween = " and fecha between '" . $fecha_inicio . "' and '" . $fecha_fin . "' ";
        }
        //echo "select * FROM check_faena where fecha = (select max(fecha) from check_faena where  id_faena=".$id_faena." ".$whereBetween." )";
        //echo "select * FROM check_faena where fecha = (select max(fecha) from check_faena where  id_faena=".$id_faena.")";
        $faenaCheckSql = $mysqli->query("select * FROM check_faena where fecha = (select max(fecha) from check_faena where  id_faena=" . $id_faena . " " . $whereBetween . " )");

 
        if ($faenaCheckSql->num_rows == 0) {
            return 0;
        } else {

            return $faenaCheckSql->fetch_object();
        }
    }

    function deleteFotoCheckFaena($id_foto_check_faena, $foto)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        $targetDir = "../../dist/img/checksupport/";

        $mysqli->query("delete  from fotos_subprocesos_check_faena where id='" . $id_foto_check_faena . "'");
        if (File_exists($targetDir . $foto)) {
            unlink($targetDir . $foto);
        }
        return 0;
    }

    function datoSubprocesoCheck($idCheckFaena, $idSubproceso)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        //echo "select * FROM check_faena where fecha = (select max(fecha) from check_faena where  id_faena=".$id_faena.")";
        if ($idCheckFaena > 0 && $idSubproceso > 0) {
            $faenaCheckSql = $mysqli->query("select * FROM subproceso_check_faena where id_check_faena='" . $idCheckFaena . "' and id_subproceso='" . $idSubproceso . "' ");
            return $faenaCheckSql->fetch_object();
        } else {
            return 0;
        }
    }


    function fotoSubprocesoCheck($idSubprocesoCheckFaena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        $fotosSql = $mysqli->query("select * FROM fotos_subprocesos_check_faena
                                       where id_subproceso_check_faena=" . $idSubprocesoCheckFaena);


        if ($fotosSql->num_rows == 0) {
            return 0;
        } else {

            return $fotosSql;
        }
    }


    function datos($id_faena,$aliasFaena=null)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        
        //echo "Alias Faena".$aliasFaena;
        if($aliasFaena==null){
            $faenaSql = $mysqli->query("select *  from faenas  where id=" . $id_faena . " ");
            //echo "select *  from faenas  where id=" . $id_faena . " ";
        }else{
            $faenaSql = $mysqli->query("select *  from faenas  where alias='" . $aliasFaena . "' ");
            //echo "select *  from faenas  where alias='" . $aliasFaena . "' ";
        }

        if ($faenaSql->num_rows == 1) {
            return $faenaSql->fetch_object();
        } else {
            return 0;
        }
    }

    function insertCheck($id_faena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $mysqli->query("insert into check_faena set  id_faena=" . $id_faena . ", id_usuario='" . $_SESSION['id'] . "', estado='pendiente', fecha='" . date("Y-m-d H:i:s") . "', descripcion='', aprobado=3");
        $datosCheckFaena = $mysqli->query("select * from check_faena where id_faena=" . $id_faena . " and estado='pendiente' ")->fetch_object();
        $subprocesosSql = $mysqli->query("select * from subprocesos");

        while ($SubprocesosDatos = $subprocesosSql->fetch_object()) {

            $mysqli->query("insert into 
                            subproceso_check_faena set 
                            id_check_faena=" . $datosCheckFaena->id . " , 
                            id_subproceso=" . $SubprocesosDatos->id . ", 
                            id_usuario='" . $_SESSION['id'] . "',
                            estado=3,
                            fecha='" . date("Y-m-d H:i:s") . "' ");
        }
    }

    function updateCheck($id_faena, $arrayCheck)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $datosCheckFaena = $mysqli->query("select * from check_faena where id_faena=" . $id_faena . " and estado='pendiente' ");

        //pregunto si hay faenas pendientes

        if ($datosCheckFaena->num_rows == 0) {
            return -1;
        }else{
            $datosCheckFaena=$datosCheckFaena->fetch_object();
            $subprocesosCheckFaenaSql = $mysqli->query("select * from subproceso_check_faena where id_check_faena=" . $datosCheckFaena->id);

            while ($SubprocesosCheckFaenaDatos = $subprocesosCheckFaenaSql->fetch_object()) {
                // solo hace update lo que se modifica en la vista.
                if ($SubprocesosCheckFaenaDatos->comentario != $arrayCheck["comentario_" . $SubprocesosCheckFaenaDatos->id_subproceso] || $SubprocesosCheckFaenaDatos->estado !=$arrayCheck["estado_" . $SubprocesosCheckFaenaDatos->id_subproceso]) {
                   
                   
                    $mysqli->query("update subproceso_check_faena set
                            id_usuario='" . $_SESSION['id'] . "',
                            estado='" . $arrayCheck["estado_" . $SubprocesosCheckFaenaDatos->id_subproceso] . "',
                            comentario='" . addslashes($arrayCheck["comentario_" . $SubprocesosCheckFaenaDatos->id_subproceso]) . "',
                            fecha='" . date("Y-m-d H:i:s") . "'

                            where id='" . $SubprocesosCheckFaenaDatos->id . "'
                            ");
                }
            }
        }
    }



    function finalizarCheck($idCheckFaena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $mysqli->query("update check_faena set estado='finalizado' , aprobado=2  where id='" . $idCheckFaena . "'");
    }

    function comentariosCheckFaena($idCheckFaena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $comentarioCheckFaenaSql = $mysqli->query("select *  from comentarios_check_faena  where id_check_faena=" . $idCheckFaena . " ");

        if ($comentarioCheckFaenaSql->num_rows > 0) {
            return $comentarioCheckFaenaSql;
        } else {
            return 0;
        }
    }

    function insertComentarioCheckFaena($idCheckFaena, $comentario, $estado)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $mysqli->query("insert into comentarios_check_faena set id_usuario='" . $_SESSION['id'] . "', comentario='" . $comentario . "', estado='" . $estado . "', fecha='" . date("Y-m-d H:i:s") . "', id_check_faena='" . $idCheckFaena . "' ");
    }

    function validCheck($idCheckFaena)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();

        $subprocesosCheckFaenaSql = $mysqli->query("select * from subproceso_check_faena where estado=3 and id_check_faena=" . $idCheckFaena);

        if ($subprocesosCheckFaenaSql->num_rows == 0) {
            return 0;
        } else {
            return 1;
        }
    }
}
