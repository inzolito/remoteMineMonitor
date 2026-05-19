<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-metricas.php");


$system = new systemClass();
$faenaCl = new faena();
$metricas = new Metricas();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_GET["id"];
$id_faena = 6;
//$alias = $_GET["al"];

//Falta validar cuando no existe la faena
$faenaDatos = $faenaCl->datos($id_faena);
$estadoCheckFaena = $faenaCl->estado($id_faena);
$checkDatos = $faenaCl->datosCheck($id_faena);
date_default_timezone_set('America/Santiago');

$carpeta = $faenaDatos->alias . "/";
$alias = $carpeta;
//$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;
$ruta = $system->rutaDataSet() . $carpeta;
$servidoresFaenas = $faenaCl->servidoresFaena($id_faena, '', 'fms');

$servidorActivoArray;
$servidorSecundario;

if ($servidoresFaenas[0]['tipo_servidor'] == "Active") {
  $servidorActivoArray = $servidoresFaenas[0];
  $servidorSecundarioArray = $servidoresFaenas[1];
} else {
  $servidorActivoArray = $servidoresFaenas[1];
  $servidorSecundarioArray = $servidoresFaenas[0];
}

/*
echo "<br>";
echo " -> Serv Activo ID: " . $servidorActivoArray['id'];
echo "<br> -> Serv Sec ID: " . $servidorSecundarioArray['id'];
*/

$metricasServidorActivo = $metricas->obtenerUltimosValoresServidor($servidorActivoArray["id"]);
$metricasServidorSecundario = $metricas->obtenerUltimosValoresServidor($servidorSecundarioArray["id"]);
//var_dump($metricasServidorActivo);
//echo "<pre>";
//print_r($metricasServidorSecundario);
//echo "</pre>";

?>

<h1>
  <center>
    <a href="#" onclick="carga_modulo_container2('monitoreo_fms/monitoreo_fms.php','Monitoreo FMS')"
      style="color: black; text-decoration: none;">
      <i class='fas fa-sync-alt'></i></button>
      <?php echo "Monitoreo FMS " . $faenaDatos->faena ?>
    </a>
  </center>
</h1>

<div class="row">
  <div class="col-md-12">
    <div class="row">


      <div class="col-md-3">
        <div class="info-box shadow-none">
          <span class="info-box-icon bg-info"><i class="fa-solid fa-server"></i></span>

          <div class="info-box-content">
            <span class="info-box-text">Version JAMS</span>
            <span class="info-box-number" id="spanVersionJAMS"></span>
          </div>
        </div>
      </div>


      <div class="col-md-3">
        <div class="info-box shadow-none">
          <span class="info-box-icon bg-info"><i class="fa-brands fa-connectdevelop"></i></span>

          <div class="info-box-content">
            <span class="info-box-text">Equipos Conectados</span>
            <span class="info-box-number" id="spanEquiposConectados"></span>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="info-box shadow-none">
          <span class="info-box-icon bg-info"><i class="fa-solid fa-circle-nodes"></i></span>

          <div class="info-box-content">
            <span class="info-box-text">JAMSCluster</span>
            <span class="info-box-number">YY</span>
          </div>
        </div>
      </div>


      <div class="col-md-3">
        <div class="info-box shadow-none">
          <span class="info-box-icon bg-info"><i class="fa-solid fa-tower-cell"></i></span>

          <div class="info-box-content">
            <span class="info-box-text">Estación Base</span>
            <span class="info-box-number">YY</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>


<div class="row">
  <div class="col-md-4">

    <div class='card card-lightblue border-1'>
      <div class="card-header">
        <h3 class="card-title">Servidor Primario</h3>
      </div>

      <div class="card-body">

        <div class="row">
          <!-- Disco Duro -->
          <div class="col-md-3">
            <div class="text-left">Disco Duro</div>
            <input id="input_hd_serv1" type="text" value="" class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
            <div class="progress-group mt-2 px-2">
              HD <span id="span_hd_serv1" class="float-right"> </span>
              <div class="progress progress-sm">
                <div id="bar_hd_serv1" class="progress-bar bg-primary" style="width: 30%;     background-color: #3c8dbc !important;"></div>
              </div>
            </div>
            <div class="col-md-12">

              <button id="btn_particiones_serv1" class="btn btn-default form-control ">Particiones HD</button>
            </div>
          </div>

          <!-- Memoria RAM -->
          <div class="col-md-3">
            <div class="text-left">Memoria RAM</div>
            <input id="input_ram_serv1" type="text" value=" " class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">

            <div class="progress-group mt-2 px-2">
              RAM <span id="span_ram_serv1" class="float-right"> </span>
              <div class="progress progress-sm">
                <div id="bar_ram_serv2" class="progress-bar bg-primary" style="width: 3.2% ; background-color: #3c8dbc !important;"></div>
              </div>
            </div>
            <div class="col-md-12">

              <button id="btn_particiones_serv1" class="btn btn-default form-control ">Procesos RAM</button>
            </div>
          </div>

          <!-- CPU - Load Average -->
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-10">
                <span id="span_cpu_load_average"> CPU - Load Average: </span>
                <button type="button" id="btnTopC" onclick="verInfo('divTopCVista')" class="btn btn-outline-light btn-sm pt-0 pb-0 ml-1">
                  <i class='fas fa-eye fa-solid mr-1'></i>
                </button>
              </div>
            </div>
            <div id="grafico_cpu_serv1" style="height: 200px; padding: 0px; position: relative;">
              <canvas class="flot-base" width="557" height="200" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 836.531px; height: 300px;"></canvas>
              <canvas class="flot-overlay" width="557" height="200" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 836.531px; height: 300px;"></canvas>
            </div>
          </div>
        </div>

        <!-- end card body -->
      </div>


      <!-- Nombre ip etc -->
      <div class="card-footer">
        <div class="row">

          <div class="col-sm-3 border-right">
            <div class="description-block">
              <h5 class="description-header">Nom. Servidor</h5>
              <span class="description-text" id="span_nom_serv1"> </span>
            </div>

          </div>

          <div class="col-sm-3 border-right">
            <div class="description-block">
              <h5 class="description-header">IP</h5>
              <span class="description-text" id="span_ip_serv1"> </span>
            </div>
          </div>


          <div class="col-sm-3">
            <div class="description-block border-right">
              <h5 class="description-header">Status</h5>
              <span class="description-text" id="span_status_serv1"> </span>
            </div>
          </div>

          <div class="col-sm-3">
            <div class="description-block">
              <h5 class="description-header">Crontab</h5>
              <span class="description-text" id="span_crontab_serv1"> </span>
            </div>
          </div>

        </div>
      </div>







    </div>


  </div>


  <div class="col-md-4">

    <div class='card card-lightblue border-1'>
      <div class="card-header">
        <h3 class="card-title">Servidor Secundario</h3>
      </div>

      <div class="card-body">

        <div class="row">
          <!-- Disco Duro -->
          <div class="col-md-3">

            <div class="row">
              <div class="col-md-12">
                <div class="text-left">Disco Duro</div>
                <input id="input_hd_serv2" type="text" value="" class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                <div class="progress-group mt-2 px-2">
                  HD <span id="span_hd_serv2" class="float-right"></span>
                  <div class="progress progress-sm">
                    <div id="bar_hd_serv2" class="progress-bar bg-primary" style="width: 30%; background-color: #3c8dbc !important;"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-12">

              <button id="btn_particiones_serv2" class="btn btn-default form-control ">Particiones HD</button>
            </div>

          </div>



          <!-- Memoria RAM -->
          <div class="col-md-3">
            <div class="row">
              <div class="col-md-12">
                <div class="text-left">Memoria RAM</div>
                <input id="input_ram_serv2" type="text" value="" class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                <div class="progress-group mt-2 px-2">
                  RAM <span id="span_ram_serv2" class="float-right"></span>
                  <div class="progress progress-sm">
                    <div id="bar_ram_serv2" class="progress-bar bg-primary" style="width: 3.2% ; background-color: #3c8dbc !important;"></div>
                  </div>
                </div>
              </div>

              <div class="col-md-12">

                <button id="btn_particiones_serv2" class="btn btn-default form-control ">Procesos RAM</button>
              </div>
            </div>


          </div>

          <!-- CPU - Load Average -->
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-10">
                <span id="span_cpu_load_average"> CPU - Load Average: </span>
                <button type="button" id="btnTopC" onclick="verInfo('divTopCVista')" class="btn btn-outline-light btn-sm pt-0 pb-0 ml-1">
                  <i class='fas fa-eye fa-solid mr-1'></i>
                </button>
              </div>
            </div>
            <div id="grafico_cpu_serv2" style="height: 200px; padding: 0px; position: relative;">
              <canvas class="flot-base" width="557" height="200" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 836.531px; height: 300px;"></canvas>
              <canvas class="flot-overlay" width="557" height="200" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 836.531px; height: 300px;"></canvas>
            </div>
          </div>
        </div>

        <!-- end card body -->
      </div>


      <!-- Nombre ip etc -->
      <div class="card-footer">
        <div class="row">

          <div class="col-sm-3 border-right">
            <div class="description-block">
              <h5 class="description-header">Nom. Servidor</h5>
              <span class="description-text" id="span_nom_serv2"> </span>
            </div>

          </div>

          <div class="col-sm-3 border-right">
            <div class="description-block">
              <h5 class="description-header">IP</h5>
              <span class="description-text" id="span_ip_serv2"> </span>
            </div>
          </div>


          <div class="col-sm-3">
            <div class="description-block border-right">
              <h5 class="description-header">Status</h5>
              <span class="description-text" id="span_status_serv2"> </span>
            </div>
          </div>

          <div class="col-sm-3">
            <div class="description-block">
              <h5 class="description-header">Crontab</h5>
              <span class="description-text" id="span_crontab_serv2"> </span>
            </div>
          </div>

        </div>
      </div>







    </div>


  </div>


  <div class="col-md-12" id="div_info">


  </div>
</div>

<script>
  // ========================== Websocket ==================================
  var mensajesRecibidos = [];
  var servidorActivo = [];
  var servidorSecundario = [];

  function connectWebSocket() {
    var ws = new WebSocket("ws://10.169.140.99:9504");


    ws.onopen = function() {
      console.log("Conexión WebSocket establecida");
      $("#div_info").html("<p>Conectado al servidor WebSocket</p>");
    };

    ws.onerror = function(error) {
      console.error("Error WebSocket:", error);
      $("#div_info").html("<p style='color:red;'>Error en la conexión WebSocket</p>");
    };

    ws.onclose = function() {
      console.log("Conexión WebSocket cerrada, reintentando en 2 segundos...");
      $("#div_info").html("<p style='color:orange;'>Conexión cerrada. Reintentando...</p>");
      setTimeout(connectWebSocket, 2000);
    };

    ws.onmessage = function(event) {
      console.log("Mensaje recibido del servidor:", event.data);
      try {
        var data = JSON.parse(event.data);
        mensajesRecibidos.push(data);

        // Aquí el if que pediste, mostrar solo si id_faena === "6"
        var servidoresFiltrados = data.filter(item => item.id_faena === "6");

        if (servidoresFiltrados.length >= 2) {
          servidorActivo = servidoresFiltrados[0];
          servidorSecundario = servidoresFiltrados[1];


          //Actualizaciones  datos tiempo real

          //datos
          $("#span_nom_serv1").html(servidorActivo["nombreServidor"]);
          $("#span_ip_serv1").html(servidorActivo["ipServidor"]);
          $("#span_status_serv1").html(servidorActivo["statusServer"]);


          $("#span_nom_serv2").html(servidorSecundario["nombreServidor"]);
          $("#span_ip_serv2").html(servidorSecundario["ipServidor"]);
          $("#span_status_serv2").html(servidorSecundario["statusServer"]);
          $("#spanVersionJAMS").html(servidorActivo.metricas["versionJAMS"]);
          let equiposData = servidorActivo.metricas["repc"].trim();
          let cantidadEquipos = equiposData === "" ? 0 : equiposData.split(/\s+/).length;
          
          $("#spanEquiposConectados").html(cantidadEquipos);



          //cpu
          actualizarGrafico("#grafico_cpu_serv1", servidorActivo.metricas["loadAverage"]);
          actualizarGrafico("#grafico_cpu_serv2", servidorSecundario.metricas["loadAverage"]);

          //Ram
          actualizarGraficoCircular("ram", "serv1", servidorActivo.metricas["memoriaRamUtilizada"], servidorActivo.metricas["memoriaRamTotal"]);
          actualizarGraficoCircular("ram", "serv2", servidorSecundario.metricas["memoriaRamUtilizada"], servidorSecundario.metricas["memoriaRamTotal"]);

          //HD
          actualizarGraficoCircular("hd", "serv1", servidorActivo.metricas["espacioUtilizadoDiscoDuro"], servidorActivo.metricas["espacioTotalDiscoDuro"]);
          actualizarGraficoCircular("hd", "serv2", servidorSecundario.metricas["espacioUtilizadoDiscoDuro"], servidorSecundario.metricas["espacioTotalDiscoDuro"]);

          //$("#div_info").text(JSON.stringify(servidorActivo, null, 2));

          /*
 

  
  */


        } else {
          $("#div_info").html("<p>No hay datos </p>");
        }

      } catch (e) {
        //$("#div_info").text(event.data);
      }
    };
  }
</script>



<script>
  const graficos = {};

  // Función para inicializar un gráfico
  function inicializarGrafico(id, valoresY) {
    const datos = valoresY.map((y, i) => [i + 1, y]);

    graficos[id] = $.plot(id, [datos], {
      series: {
        lines: {
          show: true,
          fill: true,
          fillColor: "rgba(0, 128, 0, 0.5)",
          lineWidth: 2
        },
        points: {
          show: true
        },
        color: "#008000",
        shadowSize: 5
      },
      grid: {
        borderColor: "#ddd",
        borderWidth: 1
      },
      xaxis: {
        min: 1,
        max: valoresY.length,
        tickSize: 1
      },
      yaxis: {
        min: 0,
        max: 7,
        tickSize: 1
      }
    });
  }

  // Función para actualizar los valores de un gráfico ya creado
  function actualizarGrafico(id, cadenaValores) {
    const valores = cadenaValores.split(',').map(parseFloat);
    const nuevosDatos = valores.map((y, i) => [i + 1, y]);

    if (graficos[id]) {
      graficos[id].setData([nuevosDatos]);
      graficos[id].draw();
    } else {
      console.error(`El gráfico con ID '${id}' no ha sido inicializado.`);
    }
  }
</script>






<script>
  function actualizarColoresGraficoCpu(valor) {
    let backgroundColor, borderColor;

    if (valor < 3) {
      backgroundColor = "#FF63477A";
      borderColor = "#FF0000";
    } else if (valor >= 3 && valor < 5) {
      backgroundColor = "#FFD7007A";
      borderColor = "#FFD700";
    } else {
      backgroundColor = "#70EA6E7A";
      borderColor = "#09DA06";
    }

    graficoCpu.data.datasets[0].backgroundColor = backgroundColor;
    graficoCpu.data.datasets[0].borderColor = borderColor;
    graficoCpu.update();
  }


  // ---------------- Estilos GRAFICOS ---------------
  $('.GraficoAzul').knob({
    readOnly: true,
    rotation: 'anticlockwise',
    thickness: '.3',
    width: 90,
    height: 90,
    fgColor: '#3c8dbc'
  });



  $(".GraficoVerde").knob({

    readOnly: true,
    rotation: 'anticlockwise',
    thickness: '.2',
    width: 130,
    height: 130,
    fgColor: "#00C853", // Verde moderno
    bgColor: "#E0F2E9" // Fondo claro y limpio

  });

  $('.GraficoAmarillo').knob({
    readOnly: true,
    rotation: 'anticlockwise',
    thickness: '.1',
    width: 90,
    height: 90,
    displayInput: true,
    fgColor: '#FFD700', // Amarillo
    draw: function() {
      var value = $(this.i).val();
      $(this.i).val(value + '%');
    }
  });

  $('.GraficoRojo').knob({
    readOnly: true,
    rotation: 'anticlockwise',
    thickness: '.1',
    width: 90,
    height: 90,
    displayInput: true,
    fgColor: '#FF0000', // Rojo
    draw: function() {
      var value = $(this.i).val();
      $(this.i).val(value + '%');
    }
  });
</script>



<script>
  function actualizarGraficoCircular(tipo, id, valorUsado, valorTotal) {
    const porcentaje = calcularPorcentaje(valorUsado, valorTotal);
    const idBase = tipo + '_' + id;

    $('#span_' + idBase).html('<b>' + valorUsado + ' / ' + valorTotal + '</b>');

    let color;
    if (porcentaje <= 65) {
      color = '#00C853';
    } else if (porcentaje <= 85) {
      color = '#FFD700';
    } else {
      color = '#FF0000';
    }

    if ($('#input_' + idBase).data('knob')) {
      $('#input_' + idBase).knob('destroy');
    }

    $('#input_' + idBase).knob({
      readOnly: true,
      rotation: 'anticlockwise',
      thickness: '.1',
      width: 90,
      height: 90,
      displayInput: true,
      fgColor: color
    });

    $('#input_' + idBase).val(porcentaje).trigger('change');

    const input = $('#input_' + idBase);
    const value = input.val().toString().replace('%', '');
    if (!isNaN(value)) {
      input.val(value + '%');
    }

    $('#bar_' + idBase).css('width', porcentaje + '%');
  }
</script>


<!------------------------------------- End funciones graficos ------------------------------->


<script>
  var metricasServidorActivo = <?php echo json_encode($metricasServidorActivo, JSON_UNESCAPED_UNICODE); ?>;
  var metricasServidorSecundario = <?php echo json_encode($metricasServidorSecundario, JSON_UNESCAPED_UNICODE); ?>;

  function calcularPorcentaje(uso, total) {
    const convertirAGB = v => {
      v = String(v).trim(); // Asegura que siempre es string y limpia espacios
      const match = v.match(/^([\d.]+)([TG])$/); // Captura número y unidad (T o G)
      if (!match) return NaN; // Retorna NaN si el formato es incorrecto
      const [, num, unidad] = match;
      return parseFloat(num) * (unidad === "T" ? 1024 : 1);
    };

    const usadoGB = convertirAGB(uso);
    const totalGB = convertirAGB(total);

    return !isNaN(usadoGB) && !isNaN(totalGB) && totalGB > 0 ?
      ((usadoGB / totalGB) * 100).toFixed(1) :
      0;
  }


  function updateFlotChart(dataString) {
    var newDataArray = dataString.split(",").map(Number);
    var newData = newDataArray.map((value, index) => [index + 1, value]);

    plotInstance.setData([newData]);
    plotInstance.draw();
  }
</script>



<script>
  $(function() {
    inicializarGrafico("#grafico_cpu_serv1", [1.5, 2.3, 3.7, 4.1, 2.9, 3.2, 2.0]);
    inicializarGrafico("#grafico_cpu_serv2", [2.1, 1.8, 3.0, 4.4, 3.5, 2.6, 1.7]);

    // Servidor 1 

    $("#span_nom_serv1").html(metricasServidorActivo["nombreServidor"]);
    $("#span_ip_serv1").html(metricasServidorActivo["ipServidor"]);
    $("#span_status_serv1").html(metricasServidorActivo["statusServer"]);

    actualizarGraficoCircular("hd", "serv1", metricasServidorActivo["espacioUtilizadoDiscoDuro"], metricasServidorActivo["espacioTotalDiscoDuro"]);
    actualizarGraficoCircular("ram", "serv1", metricasServidorActivo["memoriaRamUtilizada"], metricasServidorActivo["memoriaRamTotal"]);

    actualizarGrafico("#grafico_cpu_serv1", metricasServidorActivo["loadAverage"]);
    actualizarGraficoCircular("hd", "serv1", metricasServidorActivo["espacioUtilizadoDiscoDuro"], metricasServidorActivo["espacioTotalDiscoDuro"]);
    actualizarGraficoCircular("ram", "serv1", metricasServidorActivo["memoriaRamUtilizada"], metricasServidorActivo["memoriaRamTotal"]);


    // Servidor 2
    $("#span_nom_serv2").html(metricasServidorSecundario["nombreServidor"]);
    $("#span_ip_serv2").html(metricasServidorSecundario["ipServidor"]);
    $("#span_status_serv2").html(metricasServidorSecundario["statusServer"]);

    actualizarGraficoCircular("hd", "serv2", metricasServidorSecundario["espacioUtilizadoDiscoDuro"], metricasServidorSecundario["espacioTotalDiscoDuro"]);
    actualizarGraficoCircular("ram", "serv2", metricasServidorSecundario["memoriaRamUtilizada"], metricasServidorSecundario["memoriaRamTotal"]);

    actualizarGrafico("#grafico_cpu_serv2", metricasServidorSecundario["loadAverage"]);
    actualizarGraficoCircular("hd", "serv2", metricasServidorSecundario["espacioUtilizadoDiscoDuro"], metricasServidorSecundario["espacioTotalDiscoDuro"]);
    actualizarGraficoCircular("ram", "serv2", metricasServidorSecundario["memoriaRamUtilizada"], metricasServidorSecundario["memoriaRamTotal"]);

    actualizarGrafico("#grafico_cpu_serv2", metricasServidorSecundario["loadAverage"]);
    connectWebSocket();
  });
</script>