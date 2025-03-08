<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showAllTasks">
                    <label class="form-check-label" for="showAllTasks">
                        Show All Tasks
                    </label>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col">
                        <div class="input-group">
                            <input type="text" id="taskInput" class="form-control" placeholder="Project # To Do">
                            <button class="btn btn-success" id="addTaskBtn">Add</button>
                        </div>
                        <div id="errorMessage" class="text-danger"></div>
                    </div>
                </div>
                
                <div id="taskList">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            loadTasks();

            $('#addTaskBtn').click(function() {
                addTask();
            });

            $('#taskInput').keypress(function(e) {
                if (e.which === 13) {
                    addTask();
                }
            });

            $('#showAllTasks').change(function() {
                loadTasks();
            });

            function loadTasks() {
                let url = "{{url('/tasks/active')}}";
                if ($('#showAllTasks').is(':checked')) {
                    url = "{{url('/tasks/all')}}";
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#taskList').empty();
                        response.forEach(function(task) {
                            appendTaskToList(task);
                        });
                    }
                });
            }

            function addTask() {
                const title = $('#taskInput').val().trim();
                
                if (title === '') {
                    return;
                }

                $.ajax({
                    url: "{{url('/tasks')}}",
                    type: 'POST',
                    data: {
                        title: title
                    },
                    success: function(response) {
                        $('#taskInput').val('');
                        $('#errorMessage').text('');
                        appendTaskToList(response);
                    },
                    error: function(response) {
                        $('#errorMessage').text(response.responseJSON.error);
                    }
                });
            }

            function appendTaskToList(task) {
                const taskHtml = `
                    <div class="row mb-2 task-item" data-id="${task.id}">
                        <div class="col-auto">
                            <input type="checkbox" class="form-check-input task-checkbox" 
                                ${task.completed ? 'checked' : ''}>
                        </div>
                        <div class="col task-title ${task.completed ? 'text-decoration-line-through' : ''}">
                            ${task.title}
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">a few seconds ago</span>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-danger delete-task">
                                <i class="bi bi-trash"></i> 🗑️
                            </button>
                        </div>
                    </div>
                `;
                $('#taskList').append(taskHtml);
            }

            $(document).on('change', '.task-checkbox', function() {
                const taskId = $(this).closest('.task-item').data('id');
                const isCompleted = $(this).is(':checked');
                
                $.ajax({
                    url: `{{ url('/tasks') }}/${taskId}`,
                    type: 'PUT',
                    data: {
                        completed: isCompleted ? 1 : 0,
                    },
                    success: function() {
                        const taskItem = $(`.task-item[data-id="${taskId}"]`);
                        
                        if (isCompleted) {
                            taskItem.find('.task-title').addClass('text-decoration-line-through');
                            if (!$('#showAllTasks').is(':checked')) {
                                taskItem.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }
                        } else {
                            taskItem.find('.task-title').removeClass('text-decoration-line-through');
                        }
                    }
                });
            });

            $(document).on('click', '.delete-task', function() {
                const taskId = $(this).closest('.task-item').data('id');
                const taskTitle = $(this).closest('.task-item').find('.task-title').text().trim();
                
                if (confirm(`Are you sure to delete this task? "${taskTitle}"`)) {
                    $.ajax({
                        url: `{{ url('/tasks') }}/${taskId}`,
                        type: 'DELETE',
                        success: function() {
                            $(`.task-item[data-id="${taskId}"]`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>