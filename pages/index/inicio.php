<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();

?>

<style>
    a {
        color: inherit;
        text-decoration: none !important;
    }

    .imagen-cobre {
        filter: sepia(1) hue-rotate(329deg) saturate(2.5) brightness(0.9);
    }
</style>
<center>

    <div class="lockscreen-logo">
        <a href="#"><b>Sistema de Monitoreo Remoto</b></a>
    </div>
</center>


<div class="card ">

    <div class="card-body" style="display: block;">

        <div class="row">
            <div class="col-md-3">
                <a href="#" onclick="lista_faenas()"  >
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/monitoring.jpg" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Monitoreo Remoto</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3">

                <div class="card-body box-profile">

                    <a href="https://confluence.hexagonmining.com/pages/viewpage.action?spaceKey=SUP001&title=Clientes" target="_blank">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/confluence.png" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Confluence</h3>
                    </a>
                    <!-- <p class="text-muted text-center">Software Engineer</p> -->
                </div>

            </div>
            <div class="col-md-3">

                <div class="card-body box-profile">

                    <a href="https://confluence.hexagonmining.com/pages/viewpage.action?spaceKey=GS&title=Accesing+to+Codelco+Norte" target="_blank">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle imagen-cobre" src="dist/img/system/confluence.png" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Confluence Codelco</h3>
                    </a>
                    <!-- <p class="text-muted text-center">Software Engineer</p> -->
                </div>

            </div>
            <div class="col-md-3">
                <a href="https://login.replicon.com/DefaultV2.aspx?companykey=LeicaGeosystems&msg=&code=PleaseLoginToContinue&init=" target="_blank">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/replicon.webp" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Replicon</h3>
                    </div>
                </a>

            </div>


        </div>





        <div class="row">
            <div class="col-md-3">
                <a href="https://usa1.lightning.force.com/lightning/o/Case/list?filterName=00B8W000008ueBRUAY" target="_blank">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/salesforce.png" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">salesforce</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="https://cocha.kontroltravel.com/login.aspx" target="_blank">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/metacompilance.png" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">MetaCompliance</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3">

                <div class="card-body box-profile">

                    <a href="https://cloud.metacompliance.com/Account/Login?ReturnUrl=%2FAvailable%2FViewContent%3Ftype%3Dcourse" target="_blank">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="dist/img/system/cocha.png" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Cocha</h3>
                    </a>
                    <!-- <p class="text-muted text-center">Software Engineer</p> -->
                </div>

            </div>


        </div>





    </div>
</div>