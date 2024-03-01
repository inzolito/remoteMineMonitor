<?php
require_once("../controller/controller-faena.php");
require_once("../controller/controller-functions.php");

$system = new systemClass();
$faenaCl = new faena();
if (isset($_POST["idf"])) {

    $idFaena = $_POST["idf"];
}
$accion = $_POST["accion"];


if ($accion == "crearCheck") {

    $faenaCheckData = $faenaCl->datosCheck($idFaena);

    if (is_object($faenaCheckData)) {

        if ($faenaCheckData->estado == "finalizado") {

            $faenaCl->insertCheck($idFaena);
            echo 0;
        } else {
            echo $faenaCheckData->id;
        }
    } else {

        $faenaCl->insertCheck($idFaena);
        echo 0;
    }
}




if ($accion == "validarAlias") {

    $aliasUrl=$_POST["alias"];
    echo $aliasUrl;
    $faenaCheckData = $faenaCl->datos(0,$aliasUrl);

    if (is_object($faenaCheckData)) {

    
       echo $faenaCheckData->id;
        
    } else {

        echo 0;
    }
}



if ($accion == "guardarCheck") {

    $faenaCheckData = $faenaCl->datosCheck($idFaena);
    $arrayCheck = $_POST;
    if (is_object($faenaCheckData)) {


        $faenaCl->updateCheck($idFaena, $arrayCheck);
    } else {

        echo 0;
    }
}

if ($accion == "finalizarCheck") {

    $idcheckFaena = $_POST["idCheckFaena"];

    if ($idcheckFaena > 0) {
            $validacion=$faenaCl->validCheck($idcheckFaena);
        
        if($validacion==1)
        {
            // retorna 1 indicando que está activo y no puede ser terminado el check
            echo 1;
        }else{
            echo 0;
            $validacion=$faenaCl->finalizarCheck($idcheckFaena);
        }
    } else {

        echo 0;
    }
}


if ($accion == "borrarImagen") {
    $nombre = $_POST["foto"];
    $id_foto = $_POST["id"];
    $faenaCl->deleteFotoCheckFaena($id_foto, $nombre);
}

if ($accion == "subirImgSub") {
    $idSubprocesoCheckSupport = $_POST["idschf"];
    //  echo "recibo el id".$idSubprocesoCheckSupport ;
    //$faenaCheckData = $faenaCl->datosCheck($idFaena);
    //$arrayCheck=$_POST;

    // print_r($_FILES);
    $x = 0;
    if (!empty($_FILES['documentfiles'])) {

        // File upload configuration
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');


        $images_arr = array();
        foreach ($_FILES['documentfiles']['name'] as $key => $val) {
            $image_name = $_FILES['documentfiles']['name'][$key];
            $tmp_name   = $_FILES['documentfiles']['tmp_name'][$key];
            $size       = $_FILES['documentfiles']['size'][$key];
            $type       = $_FILES['documentfiles']['type'][$key];
            $error      = $_FILES['documentfiles']['error'][$key];

            // File upload path
            $fileName = basename($_FILES['documentfiles']['name'][$key]);

            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $targetDir = "../../dist/img/checksupport/";

            $fileName = $x . "_" . date("YmdHis") . "." . $ext;
            $x++;


            $targetFilePath = $targetDir . $fileName;

            // Check whether file type is valid
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

            if (in_array($fileType, $allowTypes)) {
                // Store images on the server

                if (move_uploaded_file($_FILES['documentfiles']['tmp_name'][$key], $targetFilePath)) {
                    $images_arr[] = $targetFilePath;
                    $mysqli = $system->conectaDB();
                    $mysqli->query("insert into fotos_subprocesos_check_faena set
                                    id_subproceso_check_faena='" . $idSubprocesoCheckSupport . "',
                                    foto='" . $fileName . "',
                                    fecha='" . date("Y-m-d H:i:s") . "'

                    ");
                }
            }
        }

        // Generate gallery view of the images
        if (!empty($images_arr)) { ?>
            <ul>
                <?php foreach ($images_arr as $image_src) { ?>
                    <li><img src="<?php echo $image_src; ?>" alt=""></li>
                <?php } ?>
            </ul>
        <?php 
        }

    }
}

if($accion=="guardarComentario"){

    $idcheckFaena = $_POST["idCheckFaena"];
    $ComentarioCheckFaena=addslashes($_POST["ComentarioCheckFaena"]);
    $estadoCheckFaena=$_POST["estado"];


    if ($idcheckFaena > 0) {

        $faenaCl->insertComentarioCheckFaena($idcheckFaena,$ComentarioCheckFaena,$estadoCheckFaena);
        return 1;
    } else {

        echo 0;
    }        

}

if($accion=="ValidarCheckGuardar"){

    $idcheckFaena = $_POST["idCheckFaena"];

    if ($idcheckFaena > 0) {

        return $faenaCl->validCheck($idcheckFaena);
        
    }   

}