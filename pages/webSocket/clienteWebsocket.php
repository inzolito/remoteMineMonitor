<!-- clienteWebsocket.php -->
<h1>Actualización en Tiempo Real del CSV</h1>
<pre id="csvContent">Esperando datos...</pre>
<script>
    var ws = new WebSocket("ws://10.40.90.99:15001");

    ws.onopen = function() {
        console.log("Conectado al servidor WebSocket");
    };

    ws.onmessage = function(event) {
        console.log("Mensaje recibido:", event.data);
        if (event.data.indexOf("csv:") === 0) {
            var contenido = event.data.substring(4);
            document.getElementById("csvContent").textContent = contenido;
        }
    };

    ws.onerror = function(error) {
        console.error("Error en WebSocket:", error);
    };

    ws.onclose = function() {
        console.log("Conexión WebSocket cerrada");
    };
</script>
