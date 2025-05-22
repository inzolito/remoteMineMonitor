<!DOCTYPE html>
<html lang="es">


<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>To-Do List</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.css">

  <style>
    .done {
      text-decoration: line-through;
      color: gray;
    }

    .handle {
      cursor: move;
      margin-right: 5px;
      opacity: 0.5;
    }

    .handle:hover {
      opacity: 1;
    }

    .todo-list li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 5px;
      border-bottom: 1px solid #eee;
    }

    .tools {
      display: flex;
      gap: 10px;
    }

    .tools i {
      cursor: pointer;
    }

    .priority {
      background-color: #ff9900;
      border: 4px solid #e68a00;
      padding: 5px;
      font-weight: bold;
      color: #fff;
      font-size: 1.1em;
      text-transform: uppercase;

    }

    #calendar {
      margin-top: 20px;
    }

    .row-flex {
      display: flex;
      justify-content: space-between;
      gap: 20px;
    }

    .col-flex {
      flex: 1;
      min-width: 300px;
    }

    .fc-event-title {
      color: black !important;
    }

    .btn-bottom {
      margin-top: 10px;
    }
  </style>
</head>

<h1>
  <center>
    <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
    Checklist - Soporte Remoto
    <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
  </center>
</h1>

<body>
  <div class="container mt-5">
    <div class="row-flex">

      <!-- To-Do List -->
      <div class="col-flex">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="ion ion-clipboard mr-1"></i> Tareas Diarias Pendientes Para Hoy: <span id="dateToday"></span></h3>

          </div>
          <div class="card-body">
            <ul id="todoList" class="todo-list list-unstyled"></ul>
          </div>
          <div class="card-footer clearfix">
            <button id="addTask" class="btn btn-primary float-end"><i class="fas fa-plus"></i> Add item</button>
          </div>
        </div>
      </div>

      <!-- Calendario -->
      <div class="col-flex">
        <div class="card mt-4">
          <div class="card-header">
            <h3 class="card-title">Agregar Evento al Calendario</h3>
          </div>
          <div class="card-body">
            <input type="text" id="eventTitle" class="form-control mb-2" placeholder="Título del evento">
            <input type="date" id="eventDate" class="form-control mb-2">
            <select id="eventColor" class="form-control mb-2">
              <option value="#FEF376">AMSA </option>
              <option value="#A7D769">CODELCO </option>
              <option value="#4FD2ED">OTROS </option>
            </select>
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="isPriority">
              <label class="form-check-label" for="isPriority">Prioritario</label>
            </div>

            <select id="eventRecurrence" class="form-control mb-2">
              <option value="none">No repetir</option>
              <option value="daily">Diariamente (7 días)</option>
              <option value="weekly">Semanalmente (6 semanas)</option>
              <option value="laboral">En días laborales (1 semana)</option>
            </select>

            <input type="number" id="eventRecurrenceDays" class="form-control mb-2" placeholder="Días" min="1" style="display:none;">
            <button id="addEvent" class="btn btn-success w-100 btn-bottom">Agregar Evento</button>
          </div>
        </div>
        <div id="calendar"></div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.js"></script>


  <script>
    $(document).ready(function() {
      $("#todoList").sortable({
        handle: ".handle",
        axis: "y"
      });

      $("#addTask").click(function() {
        let taskText = prompt("Ingrese la nueva tarea:");
        if (taskText) {
          const taskId = new Date().getTime();
          $("#todoList").append(`
          <li data-id="${taskId}">
            <div>
              <span class="handle"><i class="fas fa-ellipsis-v"></i><i class="fas fa-ellipsis-v"></i></span>
              <input type="checkbox" class="todo-checkbox" data-id="${taskId}">
              <span class="text">${taskText}</span>
            </div>
            <div class="tools">
              <i class="fas fa-trash delete-task"></i>
            </div>
          </li>`);
        }
      });

      $(document).on("change", ".todo-checkbox", function() {
        const taskId = $(this).data("id");
        $(this).closest("li").toggleClass("done", $(this).is(":checked"));
        updateCalendarEventColor(taskId, $(this).is(":checked"));
      });

      function updateCalendarEventColor(taskId, isDone) {
        const events = calendar.getEvents();
        events.forEach(event => {
          if (event.extendedProps.taskId === taskId) {
            if (isDone) {
              event.setProp('backgroundColor', '#D3D3D3');
              event.setProp('borderColor', '#D3D3D3');
            } else {
              event.setProp('backgroundColor', event.extendedProps.originalColor);
              event.setProp('borderColor', event.extendedProps.originalColor);
            }
          }
        });
      }

      $(document).ready(function() {
        const today = new Date();
        const day = today.getDate();
        const month = today.getMonth() + 1;
        const year = today.getFullYear();
        const formattedDate = `${day < 10 ? '0' : ''}${day}-${month < 10 ? '0' : ''}${month}-${year}`;

        $("#dateToday").text(formattedDate);
      });


      $(document).on("click", ".delete-task", function() {
        const taskId = $(this).closest("li").data("id");
        removeEventFromCalendar(taskId);
        $(this).closest("li").remove();
      });

      function removeEventFromCalendar(taskId) {
        const events = calendar.getEvents();
        events.forEach(event => {
          if (event.extendedProps.taskId === taskId) {
            event.remove();
          }
        });
      }

      var calendarEl = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        events: [],
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,dayGridWeek,dayGridDay'
        },

        dateClick: function(info) {
          let title = prompt("Título del evento:");
          if (title) {
            let eventColor = $("#eventColor").val();
            let isPriority = $("#isPriority").is(":checked");
            let event = calendar.addEvent({
              title: title,
              start: info.dateStr,
              allDay: true,
              backgroundColor: eventColor,
              borderColor: eventColor,
              extendedProps: {
                taskId: new Date().getTime(),
                originalColor: eventColor
              }
            });

            let today = new Date().toISOString().split('T')[0];
            if (info.dateStr === today) {
              addEventToToDoList(title, info.dateStr, isPriority, event.extendedProps.taskId, eventColor);
            }
          }
        }
      });

      calendar.render();

      function addEventToToDoList(title, date, priority, taskId, color) {
        let today = new Date().toISOString().split('T')[0];
        if (date === today) {
          let taskText = `Evento: ${title}`;
          if (priority) {
            taskText += ` <i class="fa-solid fa-exclamation" data-bs-toggle="tooltip" title="Prioritario"></i>`;
          }
          let taskItem = `
                    <li data-id="${taskId}" class="${priority ? 'priority' : ''}">
                        <div>
                            <span class="handle"><i class="fas fa-ellipsis-v"></i><i class="fas fa-ellipsis-v"></i></span>
                            <input type="checkbox" class="todo-checkbox" data-id="${taskId}">
                            <span class="text">${taskText}</span>
                        </div>
                        <div class="tools">
                            <i class="fas fa-trash delete-task"></i>
                        </div>
                    </li>`;
          if (priority) {
            $("#todoList").prepend(taskItem);
          } else {
            $("#todoList").append(taskItem);
          }
          $('[data-bs-toggle="tooltip"]').tooltip();
        }
      }

      $("#addEvent").click(function() {
        let title = $("#eventTitle").val();
        let date = $("#eventDate").val();
        let color = $("#eventColor").val();
        let isPriority = $("#isPriority").is(":checked");
        let recurrence = $("#eventRecurrence").val();
        let recurrenceDays = $("#eventRecurrenceDays").val();

        if (title && date) {
          let startDate = new Date(date);
          if (recurrence === "none") {
            // Evento único
            let event = calendar.addEvent({
              title: title,
              start: date,
              allDay: true,
              backgroundColor: color,
              borderColor: color,
              extendedProps: {
                taskId: new Date().getTime(),
                originalColor: color
              }
            });

            let today = new Date().toISOString().split('T')[0];
            if (date === today) {
              addEventToToDoList(title, date, isPriority, event.extendedProps.taskId, color);
            }

          } else if (recurrence === "daily") {
            // Evento diario
            let startDate = new Date(date);
            for (let i = 0; i < 7; i++) {
              let newDate = new Date(startDate);
              newDate.setDate(startDate.getDate() + i);
              let event = calendar.addEvent({
                title: title,
                start: newDate.toISOString().split('T')[0],
                allDay: true,
                backgroundColor: color,
                borderColor: color,
                extendedProps: {
                  taskId: new Date().getTime() + i,
                  originalColor: color
                }
              });

              let eventDate = newDate.toISOString().split('T')[0];
              addEventToToDoList(title, eventDate, isPriority, event.extendedProps.taskId, color);
            }

          } else if (recurrence === "weekly") {
            // Evento semanal
            let startDate = new Date(date);
            for (let i = 0; i < 4; i++) {
              let newDate = new Date(startDate);
              newDate.setDate(startDate.getDate() + (i * 7));
              let event = calendar.addEvent({
                title: title,
                start: newDate.toISOString().split('T')[0],
                allDay: true,
                backgroundColor: color,
                borderColor: color,
                extendedProps: {
                  taskId: new Date().getTime() + i,
                  originalColor: color
                }
              });

              let eventDate = newDate.toISOString().split('T')[0];
              addEventToToDoList(title, eventDate, isPriority, event.extendedProps.taskId, color);
            }

          } else if (recurrence === "laboral") {
            // Evento en días laborales
            let startDate = new Date(date);
            let daysAdded = 0;

            while (daysAdded < 5) {
              let dayOfWeek = startDate.getDay();

              if (dayOfWeek >= 0 && dayOfWeek <= 4) {
                let newDate = new Date(startDate);

                let event = calendar.addEvent({
                  title: title,
                  start: newDate.toISOString().split('T')[0],
                  allDay: true,
                  backgroundColor: color,
                  borderColor: color,
                  extendedProps: {
                    taskId: new Date().getTime() + daysAdded,
                    originalColor: color
                  }
                });

                let eventDate = newDate.toISOString().split('T')[0];
                addEventToToDoList(title, eventDate, isPriority, event.extendedProps.taskId, color);

                daysAdded++;
              }
              startDate.setDate(startDate.getDate() + 1);
            }
          }
          $("#eventTitle").val("");
          $("#eventDate").val("");
          $("#isPriority").prop("checked", false);
          $("#eventRecurrence").val("none");
          $("#eventRecurrenceDays").val("").hide();
        } else {
          alert("Por favor, ingrese un título, una fecha y seleccione un color para el evento.");
        }
      });
    });
  </script>