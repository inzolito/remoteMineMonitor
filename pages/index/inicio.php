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






<div class="row">
    <div class="col-md-8">

        <!-- servicios y notificaciones -->
        <div class="row">

            <div class="col-md-6">

                <div class="card">
                    <div class="card-header">
                        Servicios locales
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li id="item-oasgraf" class="list-group-item d-flex justify-content-between align-items-center"
                                onclick="vistaTerminal($('#span_log_oasgraf').html(), 'Tunel OAS')">
                                <span><i class="fa-solid fa-plug me-2"></i> Tunel OAS</span>
                                <div class="d-flex align-items-center ms-auto">
                                    <span id="span_time_oasgraf" class="badge"> </span>
                                    <span id="span_status_oasgraf" class="badge me-2"> </span>

                                </div>
                                <span id="span_log_oasgraf" style="display: none;">Contenido del log OAS</span>
                            </li>

                            <li id="item-websocket" class="list-group-item d-flex justify-content-between align-items-center"
                                onclick="vistaTerminal($('#span_log_websocket').html(), 'WebSocket')">
                                <span><i class="fa-solid fa-plug me-2"></i> WebSocket</span>
                                <div class="d-flex align-items-center ms-auto">
                                    <span id="span_time_websocket" class="badge"> </span>
                                    <span id="span_status_websocket" class="badge me-2"> </span>

                                </div>

                                <span id="span_log_websocket" style="display: none;">Contenido del log WebSocket</span>
                            </li>

                            <li id="item-salesforce" class="list-group-item d-flex justify-content-between align-items-center"
                                onclick="vistaTerminal($('#span_log_salesforce').html(), 'Salesforce')">
                                <span><i class="fa-solid fa-plug me-2"></i> Salesforce</span>
                                <div class="d-flex align-items-center ms-auto">
                                    <span id="span_time_salesforce" class="badge"> </span>
                                    <span id="span_status_salesforce" class="badge me-2"> </span>

                                </div>
                                <span id="span_log_salesforce" style="display: none;">Contenido del log Salesforce</span>
                            </li>
                        </ul>

                    </div>
                    <div class="card-footer" id="div_footer">

                    </div>
                </div>

            </div>

            <div class="col-md-6">
                <div class="card  ">
                    <div class="card-header">
                        Notificaciones activas
                    </div>
                    <div class="card-body overflow-auto">
                        <ul id="alertasList" class="list-group list-group-flush">
                            <!-- Las notificaciones se cargarán aquí en tiempo real -->
                        </ul>
                    </div>
                    <div class="card-footer">
                     </div>
                </div>



            </div>


        </div>
        <!-- End servicios y notificaciones -->


        <!-- accesos rapidos -->
        <div class="card" style="height: 100%; display: flex; flex-direction: column;">
    <div class="card-header">
        Notificaciones activas
    </div>
    <div class="card-body" style="flex: 1; overflow-y: auto; min-height: 0;">

                <div class="row">
                    <div class="col-md-3">
                        <a href="#" onclick="lista_faenas()">
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
        <!-- End accesos rapidos -->



    </div>


    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                Últimos tickets
            </div>
            <div class="card-body" id="texto">

            </div>
            <div class="card-footer">

            </div>
        </div>


    </div>
</div>


<!--Scripts WebscoektInicio-->
<script>
    // Conecta al WebSocket. Ajusta la URL según tu configuración.
    var ws = new WebSocket("ws://10.169.140.99:9506");

    ws.onopen = function() {
        console.log("Conectado al servidor WebSocket.");
    };

    ws.onmessage = function(event) {
        console.log("Mensaje recibido:", event.data);

        try {
            if (!event.data) {
                console.warn("Mensaje vacío recibido.");
                return;
            }

            var data = JSON.parse(event.data);
            console.log("JSON parseado:", data);

            if (Object.keys(data).length === 0) {
                console.warn("Objeto JSON vacío recibido.");
                return;
            }

            // Mostrar datos en #texto de forma legible
            $("#texto").prepend("<pre>" + JSON.stringify(data, null, 2) + "</pre>");

            // Función para actualizar el estado del servicio
            function updateService(codBadge, serviceData) {
                if (!serviceData) return;

                var idDiv = "span_status_" + codBadge;
                var idTimeDiv = "span_time_" + codBadge;
                var badge = document.getElementById(idDiv);
                var minutosActivos = parseInt(serviceData.minutosUltimoActivo);
                var fechaUltimoActivo = serviceData.fechaUltimoActivo ? serviceData.fechaUltimoActivo.split(" ")[1] : "";
                //-----  Div para pruebas ----
                $("#div_footer").html(minutosActivos)
                // ----- end div pruebas ---
                if (badge) {
                    if (minutosActivos >= 0 && minutosActivos <= 2) {
                        badge.className = "badge bg-success";
                        $("#" + idDiv).html(" <i class='fa-regular fa-file-lines'></i> Activo");
                    } else {
                        badge.className = "badge bg-danger";
                        $("#" + idDiv).html("<i class='fa-regular fa-file-lines'></i> Inactivo");
                    }
                    $("#" + idTimeDiv).html(fechaUltimoActivo);
                }

                $("#span_log_" + codBadge).html(serviceData.tailLog || "Sin log");
            }

            // Actualizar servicios
            updateService("oasgraf", data.oasgraf);
            updateService("websocket", data.websocket);
            updateService("salesforce", data.salesforce);

            // Actualización de alertas
            if (Array.isArray(data.alertas) && data.alertas.length > 0) {
                var alertasList = document.getElementById("alertasList");
                if (alertasList) {
                    var html = data.alertas.map(alerta =>
                        `<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <div><strong>${alerta.alias}:</strong> ${alerta.alerta}</div>
                            <button type='button' class='btn btn-sm btn-outline-primary' onclick="verAlerta('${alerta.alerta_id}')">
                                <i class='fa fa-check'></i>
                            </button>
                        </li>`
                    ).join("");
                    alertasList.innerHTML = html;
                }
            }
        } catch (error) {
            console.error("Error al procesar mensaje WebSocket:", error);
        }
    };

    ws.onerror = function(error) {
        console.error("WebSocket Error:", error);
    };

    ws.onclose = function() {
        console.log("Conexión WebSocket cerrada.");
    };
</script>
