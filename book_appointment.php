<?php
include './config/connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("location:client_login.php");
    exit;
}

$message = '';
$error = '';

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get all approved doctor schedules
$scheduleQuery = "SELECT ds.*, u.display_name as doctor_name 
                FROM doctor_schedules ds
                JOIN users u ON ds.doctor_id = u.id
                WHERE ds.schedule_date >= CURDATE() 
                AND ds.is_approved = 1
                ORDER BY ds.schedule_date ASC, ds.start_time ASC";
$scheduleStmt = $con->prepare($scheduleQuery);
$scheduleStmt->execute();
$doctorSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle appointment booking
if (isset($_POST['book_appointment'])) {
    $clientId = $_SESSION['client_id'];
    $scheduleId = $_POST['schedule_id'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];

    // Get client details
    $query = "SELECT * FROM clients WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get schedule details
    $scheduleQuery = "SELECT * FROM doctor_schedules WHERE id = ?";
    $scheduleStmt = $con->prepare($scheduleQuery);
    $scheduleStmt->execute([$scheduleId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        $error = "Invalid schedule selected.";
    } else {
    try {
        $con->beginTransaction();

            // Check if the selected time slot is available
            $slotQuery = "SELECT COUNT(*) as slot_count FROM appointments 
                        WHERE schedule_id = ? AND appointment_time = ? AND status != 'cancelled'";
            $slotStmt = $con->prepare($slotQuery);
            $slotStmt->execute([$scheduleId, $appointmentTime]);
            $slotCount = $slotStmt->fetch(PDO::FETCH_ASSOC)['slot_count'];

            if ($slotCount >= $schedule['max_patients']) {
                $con->rollback();
                $error = "This time slot is already fully booked. Please select another time.";
            } else {
        $query = "INSERT INTO appointments (
                    patient_name, phone_number, address, date_of_birth,
                            gender, appointment_date, appointment_time, reason, status,
                            schedule_id, doctor_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)";

        $stmt = $con->prepare($query);
        $stmt->execute([
            $client['full_name'],
            $client['phone_number'],
            $client['address'],
            $client['date_of_birth'],
            $client['gender'],
                    $schedule['schedule_date'],
            $appointmentTime,
                    $reason,
                    $scheduleId,
                    $schedule['doctor_id']
        ]);

        $con->commit();
        $message = 'Appointment booked successfully!';
        
        // Redirect to dashboard
        header("location:client_dashboard.php?message=" . urlencode($message));
        exit;
            }
    } catch(PDOException $ex) {
        $con->rollback();
            $error = 'An error occurred: ' . $ex->getMessage();
        }
    }
}

// Format doctor schedules for calendar
$calendarEvents = [];
foreach ($doctorSchedules as $schedule) {
    // Generate time slots based on schedule
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    $timeSlotMinutes = $schedule['time_slot_minutes'];
    $doctorName = $schedule['doctor_name'];
    $scheduleDate = $schedule['schedule_date'];
    
    // Format for calendar
    $calendarEvents[] = [
        'id' => $schedule['id'],
        'title' => $doctorName,
        'start' => $scheduleDate . 'T' . date('H:i:s', $startTime),
        'end' => $scheduleDate . 'T' . date('H:i:s', $endTime),
        'backgroundColor' => '#3699FF',
        'borderColor' => '#3699FF',
        'extendedProps' => [
            'doctor_name' => $doctorName,
            'time_slot' => $timeSlotMinutes,
            'max_patients' => $schedule['max_patients'],
            'schedule_id' => $schedule['id']
        ]
    ];
}

// Set a message if no schedules are available
if (empty($calendarEvents)) {
    $message = 'No approved doctor schedules are currently available. Please check back later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Appointment - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    
    <!-- FullCalendar CSS -->
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">

    <style>
        :root {
            --transition-speed: 0.3s;
        }

        /* Modern Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform var(--transition-speed);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2d;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            height: auto;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: #3699FF;
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .input-group-text {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            color: #4a5568;
            padding: 0.75rem 1rem;
        }

        .input-group > .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .input-group-prepend .input-group-text {
            border-right: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Styling */
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(54, 153, 255, 0.4);
        }

        .btn i {
            margin-right: 0.5rem;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background-color: rgba(54, 153, 255, 0.1);
            color: #3699FF;
        }

        .alert-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: #F64E60;
        }

        /* Content Header */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        /* DateTime Badge Styling */
        #datetime {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
            50% { box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
            100% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
        }

        /* Calendar Styling */
        .fc-event {
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            border: none;
            background-color: rgba(54, 153, 255, 0.2);
            color: #3699FF;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .fc-event:hover {
            background-color: rgba(54, 153, 255, 0.3);
            transform: translateY(-2px);
        }

        .fc-day-today {
            background-color: rgba(54, 153, 255, 0.05) !important;
        }

        .fc-button {
            background-color: #3699FF !important;
            border-color: #3699FF !important;
        }

        .fc-button:hover {
            background-color: #187DE4 !important;
            border-color: #187DE4 !important;
        }

        /* Time Slot Styling */
        .time-slot {
            display: block;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover {
            background-color: #edf2f7;
            border-color: #cbd5e0;
        }

        .time-slot.selected {
            background-color: rgba(54, 153, 255, 0.1);
            border-color: #3699FF;
        }

        .time-slot.booked {
            background-color: #f1f1f1;
            border-color: #d1d1d1;
            color: #999;
            cursor: not-allowed;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }

            .card-header {
                padding: 1.25rem;
            }

            .form-control, 
            .input-group-text {
                padding: 0.625rem 0.875rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            #datetime {
                font-size: 1rem;
                padding: 8px 15px;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_header.php'; ?>
        <?php include './config/client_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6">
                            <h1>Book Appointment</h1>
                        </div>
                        <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
                            <span id="datetime" class="d-inline-block"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Available Doctor Schedules
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calendar-plus mr-2"></i>
                                        Book Your Appointment
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="appointmentForm">
                                        <div class="form-group">
                                            <label>Selected Doctor</label>
                                            <input type="text" class="form-control" id="selectedDoctor" readonly placeholder="Click on a schedule in the calendar">
                                            <input type="hidden" name="schedule_id" id="scheduleId">
                                                </div>
                                        
                                        <div class="form-group">
                                            <label>Selected Date</label>
                                            <input type="text" class="form-control" id="selectedDate" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Available Time Slots</label>
                                            <div id="timeSlots" class="time-slots-container">
                                                <p class="text-muted">Please select a schedule from the calendar first</p>
                                            </div>
                                            <input type="hidden" name="appointment_time" id="appointmentTime">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Reason for Appointment</label>
                                            <textarea class="form-control" name="reason" rows="4" required
                                                      placeholder="Please describe your reason for the appointment"></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="book_appointment" class="btn btn-primary" id="bookBtn" disabled>
                                            <i class="fas fa-calendar-check"></i> Book Appointment
                                        </button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/client_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    
    <!-- FullCalendar JS -->
    <script src="plugins/fullcalendar/main.min.js"></script>
    
    <script>
        $(function() {
            // Initialize SweetAlert2 with modern styling
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Show success message if redirected from successful booking
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            if (message) {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Show error message if exists
            const error = urlParams.get('error');
            if (error) {
                Toast.fire({
                    icon: 'error',
                    title: error
                });
            }

            // Modern datetime display with animation
            function updateDateTime() {
                var now = new Date();
                var options = {
                    month: 'long',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                var formattedDateTime = now.toLocaleString('en-US', options);
                document.getElementById('datetime').innerHTML = formattedDateTime;
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: <?php echo json_encode($calendarEvents); ?>,
                eventClick: function(info) {
                    // Get event details
                    const event = info.event;
                    const props = event.extendedProps;
                    const doctorName = props.doctor_name;
                    const scheduleId = props.schedule_id;
                    const timeSlot = props.time_slot;
                    const maxPatients = props.max_patients;
                    
                    // Set form values
                    $('#selectedDoctor').val(doctorName);
                    $('#scheduleId').val(scheduleId);
                    $('#selectedDate').val(event.start.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    }));
                    
                    // Generate time slots
                    generateTimeSlots(event.start, event.end, timeSlot, scheduleId, maxPatients);
                }
            });
            calendar.render();

            // Generate time slots based on schedule
            function generateTimeSlots(start, end, slotMinutes, scheduleId, maxPatients) {
                const timeSlotContainer = $('#timeSlots');
                timeSlotContainer.empty();
                
                // Get available slots from the server
                $.ajax({
                    url: 'ajax/get_booked_slots.php',
                    type: 'POST',
                    data: {
                        schedule_id: scheduleId
                    },
                    dataType: 'json',
                    success: function(bookedSlots) {
                        // Clear previous time slots
                        timeSlotContainer.empty();
                        
                        // Generate time slots based on schedule
                        var currentTime = new Date(start);
                        var endTime = new Date(end);
                        var hasAvailableSlots = false;
                        
                        while (currentTime < endTime) {
                            var timeString = currentTime.toTimeString().substring(0, 5);
                            var formattedTime = currentTime.toLocaleTimeString('en-US', { 
                                hour: '2-digit', 
                                minute: '2-digit'
                            });
                            
                            // Check if this slot is fully booked
                            var slotCount = 0;
                            if (bookedSlots[timeString]) {
                                slotCount = parseInt(bookedSlots[timeString]);
                            }
                            
                            var remainingSlots = maxPatients - slotCount;
                            var isBooked = remainingSlots <= 0;
                            
                            if (!isBooked) {
                                hasAvailableSlots = true;
                                const slotElement = $('<div class="time-slot" data-time="' + timeString + '">' +
                                    formattedTime + ' <span class="badge badge-info float-right">' + 
                                    remainingSlots + ' of ' + maxPatients + ' available</span></div>');
                                
                                timeSlotContainer.append(slotElement);
                            } else {
                                const slotElement = $('<div class="time-slot booked">' +
                                    formattedTime + ' <span class="badge badge-secondary float-right">Fully booked</span></div>');
                                
                                timeSlotContainer.append(slotElement);
                            }
                            
                            // Add minutes to current time
                            currentTime.setMinutes(currentTime.getMinutes() + parseInt(slotMinutes));
                        }
                        
                        if (!hasAvailableSlots) {
                            timeSlotContainer.prepend('<p class="text-danger">No available time slots for this schedule.</p>');
                            $('#bookBtn').prop('disabled', true);
                        } else {
                            // Handle time slot selection
                            $('.time-slot:not(.booked)').click(function() {
                                $('.time-slot').removeClass('selected');
                                $(this).addClass('selected');
                                $('#appointmentTime').val($(this).data('time'));
                                $('#bookBtn').prop('disabled', false);
                            });
                        }
                    },
                    error: function() {
                        timeSlotContainer.html('<p class="text-danger">Error loading time slots. Please try again.</p>');
                        $('#bookBtn').prop('disabled', true);
                    }
                });
            }

            // Form validation
            $('#appointmentForm').submit(function(e) {
                if (!$('#scheduleId').val()) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'Please select a schedule from the calendar'
                    });
                    return false;
                }
                
                if (!$('#appointmentTime').val()) {
                    e.preventDefault();
                    Toast.fire({
                        icon: 'error',
                        title: 'Please select a time slot'
                    });
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html> 