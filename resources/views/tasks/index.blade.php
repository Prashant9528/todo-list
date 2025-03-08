<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .task-row {
            display: grid;
            grid-template-columns: 40px 1fr 50px 50px;
            border: 1px solid #dee2e6;
            margin-bottom: -1px;
            align-items: center;
        }
        
        .task-cell {
            padding: 8px;
            border-right: 1px solid #dee2e6;
            display: flex;
            align-items: center;
        }
        
        .task-cell:last-child {
            border-right: none;
        }
        
        .task-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .checkbox-cell {
            justify-content: center;
        }
        
        .image-cell {
            justify-content: center;
        }
        
        .action-cell {
            justify-content: center;
        }
        
        .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 10px;
        }
    </style>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                        
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: 'Task added successfully',
                            showConfirmButton: false,
                            timer: 1500,
                            toast: true
                        });
                    },
                    error: function(response) {
                        $('#errorMessage').text(response.responseJSON.error);
                    }
                });
            }

            function appendTaskToList(task) {
                const timeAgo = moment(task.created_at).fromNow();
                const defaultImage = "{{ asset('image/default.png') }}";
                const profileImage = `<img src="${task.user_image ? task.user_image : defaultImage}" class="rounded-circle" width="30" height="30">`;

                const taskHtml = `
                    <div class="task-row" data-id="${task.id}">
                        <div class="task-cell checkbox-cell">
                            <input type="checkbox" class="form-check-input task-checkbox" ${task.completed ? 'checked' : ''}>
                        </div>
                        <div class="task-cell">
                            <span class="task-title ${task.completed ? 'text-decoration-line-through' : ''}">${task.title}</span>
                            <span class="timestamp">${timeAgo}</span>
                        </div>
                        <div class="task-cell image-cell">
                            ${profileImage}
                        </div>
                        <div class="task-cell action-cell">
                            <button class="btn btn-sm btn-danger delete-task">
                                🗑️
                            </button>
                        </div>
                    </div>
                `;
                $('#taskList').append(taskHtml);
            }

            $(document).on('change', '.task-checkbox', function() {
                const taskId = $(this).closest('.task-row').data('id');
                const isCompleted = $(this).is(':checked');
                
                $.ajax({
                    url: `{{ url('/tasks') }}/${taskId}`,
                    type: 'PUT',
                    data: {
                        completed: isCompleted ? 1 : 0,
                    },
                    success: function() {
                        const taskItem = $(`.task-row[data-id="${taskId}"]`);
                        
                        if (isCompleted) {
                            taskItem.find('.task-title').addClass('text-decoration-line-through');
                            
                            Swal.fire({
                                position: 'top-end',
                                icon: 'success',
                                title: 'Task completed',
                                showConfirmButton: false,
                                timer: 1500,
                                toast: true
                            });
                            
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
                const taskRow = $(this).closest('.task-row');
                const taskId = taskRow.data('id');
                const taskTitle = taskRow.find('.task-title').text().trim();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete task: "${taskTitle}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('/tasks') }}/${taskId}`,
                            type: 'DELETE',
                            success: function() {
                                taskRow.fadeOut(300, function() {
                                    $(this).remove();
                                });
                                
                                Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Task deleted successfully',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    toast: true
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong while deleting the task!'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>