<?php
include './config/connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only doctors can access this page
requireRole(['doctor']);

$message = '';
$error = '';
$doctorId = $_SESSION['user_id'];
$doctorName = $_SESSION['display_name'];

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Automatically update past appointments to completed status
try {
    $con->beginTransaction();
    
    // Update past appointments to completed status
    $updateQuery = "UPDATE appointments 
                  SET status = 'completed', updated_at = NOW() 
                  WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                  AND status = 'approved'
                  AND doctor_id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->execute([$doctorId]);
    $updatedCount = $updateStmt->rowCount();
    
    if ($updatedCount > 0) {
        $message = $updatedCount . " past appointments were automatically marked as completed.";
    }
    
    $con->commit();
} catch(PDOException $ex) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    $error = "Error updating past appointments: " . $ex->getMessage();
}
try {
    $con->beginTransaction();
    
    // Update past appointments to completed status
    $updateQuery = "UPDATE appointments 
                  SET status = 'completed', updated_at = NOW() 
                  WHERE CONCAT(appointment_date, ' ', appointment_time) < NOW() 
                  AND status = 'approved'
                  AND doctor_id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->execute([$doctorId]);
    $updatedCount = $updateStmt->rowCount();
    
    if ($updatedCount > 0) {
        $message = $updatedCount . " past appointments were automatically marked as completed.";
    }
    
    $con->commit();
} catch(PDOException $ex) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    $error = "Error updating past appointments: " . $ex->getMessage();
}

// Handle schedule submission
if (isset($_POST['submit_schedule'])) {
    try {
        $con->beginTransaction();
        
        // Delete existing schedule entries for the selected dates if requested
        if (isset($_POST['replace_existing'])) {
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            $deleteQuery = "DELETE FROM doctor_schedules 
                            WHERE doctor_id = ? 
                            AND schedule_date BETWEEN ? AND ?";
            $deleteStmt = $con->prepare($deleteQuery);
            $deleteStmt->execute([$doctorId, $startDate, $endDate]);
        }
        
        // Insert new schedule entries
        $scheduleQuery = "INSERT INTO doctor_schedules 
                         (doctor_id, schedule_date, start_time, end_time, time_slot_minutes, max_patients, notes) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $scheduleStmt = $con->prepare($scheduleQuery);
        
        // Get form data
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $timeSlot = $_POST['time_slot'];
        $maxPatients = $_POST['max_patients'];
        $notes = $_POST['notes'];
        
        // Create schedule for each day in the range
        $currentDate = new DateTime($startDate);
        $lastDate = new DateTime($endDate);
        
        while ($currentDate <= $lastDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Skip if weekend is selected and the current day is a weekend
            if (isset($_POST['skip_weekends']) && 
                ($currentDate->format('N') == 6 || $currentDate->format('N') == 7)) {
                $currentDate->modify('+1 day');
                continue;
            }
            
            $scheduleStmt->execute([
                $doctorId,
                $dateStr,
                $startTime,
                $endTime,
                $timeSlot,
                $maxPatients,
                $notes
            ]);
            
            $currentDate->modify('+1 day');
        }
        
        $con->commit();
        $message = "Schedule successfully saved! Your schedule will be reviewed by an administrator.";
        
    } catch(PDOException $ex) {
        $con->rollback();
        $error = "Error: " . $ex->getMessage();
    }
}

// Fetch doctor's existing schedules
$query = "SELECT * FROM doctor_schedules 
          WHERE doctor_id = ? 
          ORDER BY schedule_date ASC";
$stmt = $con->prepare($query);
$stmt->execute([$doctorId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booked appointments for this doctor
$appointmentsQuery = "SELECT a.*, ds.time_slot_minutes 
                     FROM appointments a 
                     JOIN doctor_schedules ds ON a.schedule_id = ds.id 
                     WHERE a.doctor_id = ? AND a.status != 'cancelled'";
$appointmentsStmt = $con->prepare($appointmentsQuery);
$appointmentsStmt->execute([$doctorId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format schedules for calendar
$calendarEvents = [];
foreach ($schedules as $schedule) {
    $isPast = strtotime($schedule['schedule_date']) < strtotime(date('Y-m-d'));
    $status = $schedule['is_approved'] ? 'Approved' : 'Pending';
    $color = $isPast ? '#A0A0A0' : ($schedule['is_approved'] ? '#1BC5BD' : '#FFA800');
    
    $calendarEvents[] = [
        'id' => 'schedule_' . $schedule['id'],
        'title' => $doctorName . ' (' . $status . ')' . ($isPast ? ' [Past]' : ''),
        'start' => $schedule['schedule_date'] . 'T' . $schedule['start_time'],
        'end' => $schedule['schedule_date'] . 'T' . $schedule['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'max_patients' => $schedule['max_patients'],
            'time_slot' => $schedule['time_slot_minutes'],
            'notes' => $schedule['notes'],
            'is_approved' => $schedule['is_approved'],
            'approval_notes' => $schedule['approval_notes'],
            'type' => 'schedule',
            'is_past' => $isPast
        ]
    ];
}

// Add booked appointments to calendar
foreach ($appointments as $appointment) {
    $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
    $endTime = $appointmentTime + ($appointment['time_slot_minutes'] * 60);
    $isPast = $appointmentTime < time();
    
    // Set color based on status and whether it's past
    $color = '#F64E60'; // Default red for active appointments
    if ($isPast) {
        if ($appointment['status'] == 'completed') {
            $color = '#28a745'; // Green for completed
        } else {
            $color = '#6c757d'; // Gray for past but not completed
        }
    }
    
    $calendarEvents[] = [
        'id' => 'appointment_' . $appointment['id'],
        'title' => 'Booked: ' . $appointment['patient_name'] . ($isPast ? ' [Past]' : ''),
        'start' => date('Y-m-d\TH:i:s', $appointmentTime),
        'end' => date('Y-m-d\TH:i:s', $endTime),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'patient_name' => $appointment['patient_name'],
            'reason' => $appointment['reason'],
            'status' => $appointment['status'],
            'type' => 'appointment',
            'is_past' => $isPast
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <link href="plugins/fullcalendar/main.min.css" rel="stylesheet">
    <title>Doctor Schedule - Mamatid Health Center System</title>
    <style>
        :root {
            --transition-speed: 0.3s;
            --primary-color: #3699FF;
            --secondary-color: #6993FF;
            --success-color: #1BC5BD;
            --info-color: #8950FC;
            --warning-color: #FFA800;
            --danger-color: #F64E60;
            --light-color: #F3F6F9;
            --dark-color: #1a1a2d;
        }

        /* Card Styling */
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .card-outline {
            border-top: 3px solid var(--primary-color);
        }

        .card-header {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Controls */
        .form-control {
            height: calc(2.5rem + 2px);
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        textarea.form-control {
            height: auto;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        /* Button Styling */
        .btn {
            padding: 0.65rem 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
        }

        /* Table Styling */
        .table {
            margin-bottom: 0;
        }

        .table thead tr {
            background: var(--light-color);
        }

        .table thead th {
            border-bottom: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem;
            vertical-align: middle;
            color: var(--dark-color);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #eee;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background-color: rgba(54, 153, 255, 0.1);
            color: var(--primary-color);
        }

        /* Content Header Styling */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            margin-top: 1rem;
        }

        .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .custom-control-label::before {
            border-radius: 4px;
            border: 2px solid #e4e6ef;
        }

        /* Calendar Styling */
        .fc-event {
            border-radius: 6px;
            padding: 5px;
            cursor: pointer;
        }

        .fc-day-today {
            background-color: rgba(54, 153, 255, 0.05) !important;
        }

        .fc-button {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .fc-button:hover {
            background-color: var(--secondary-color) !important;
        }

        .badge-approved {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }

        .badge-pending {
            background-color: rgba(255, 168, 0, 0.1);
            color: var(--warning-color);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/header.php'; ?>
        <?php include './config/sidebar.php'; ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Doctor Schedule</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Doctor Schedule</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
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
                        <div class="col-lg-5">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Set Your Availability</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_date">Start Date</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_date">End Date</label>
                                                    <input type="date" class="form-control" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_time">Start Time</label>
                                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_time">End Time</label>
                                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="time_slot">Time Slot (minutes)</label>
                                                    <select class="form-control" id="time_slot" name="time_slot" required>
                                                        <option value="15">15 minutes</option>
                                                        <option value="30" selected>30 minutes</option>
                                                        <option value="45">45 minutes</option>
                                                        <option value="60">60 minutes</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="max_patients">Max Patients per Slot</label>
                                                    <input type="hidden" id="max_patients" name="max_patients" value="1">
                                                    <input type="text" class="form-control" value="1" readonly disabled>
                                                    <small class="form-text text-muted">Each time slot can only accept one appointment</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information about your availability"></textarea>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="skip_weekends" name="skip_weekends" checked>
                                            <label class="custom-control-label" for="skip_weekends">Skip Weekends</label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" class="custom-control-input" id="replace_existing" name="replace_existing">
                                            <label class="custom-control-label" for="replace_existing">Replace existing schedules in this date range</label>
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" name="submit_schedule" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i> Save Schedule
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-7">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Your Schedule Calendar</h3>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                    <div class="mt-3">
                                        <div class="d-flex flex-wrap justify-content-center">
                                            <div class="mr-4 mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #1BC5BD; width: 20px; height: 20px;"></span>
                                                <small>Approved Schedules</small>
                                            </div>
                                            <div class="mr-4 mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #FFA800; width: 20px; height: 20px;"></span>
                                                <small>Pending Schedules</small>
                                            </div>
                                            <div class="mr-4 mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #A0A0A0; width: 20px; height: 20px;"></span>
                                                <small>Past Schedules</small>
                                            </div>
                                            <div class="mr-4 mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #F64E60; width: 20px; height: 20px;"></span>
                                                <small>Active Appointments</small>
                                            </div>
                                            <div class="mr-4 mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #28a745; width: 20px; height: 20px;"></span>
                                                <small>Completed Appointments</small>
                                            </div>
                                            <div class="mb-2 d-flex align-items-center">
                                                <span class="badge mr-1" style="background-color: #6c757d; width: 20px; height: 20px;"></span>
                                                <small>Past Appointments</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="alert alert-info p-2">
                                            <small><i class="fas fa-info-circle mr-1"></i> Past appointments are automatically marked as completed. You can view all past and upcoming schedules in this calendar.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Your Schedules</h3>
                                </div>
                                <div class="card-body">
                                    <table id="schedules_table" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Slot Duration</th>
                                                <th>Max Patients</th>
                                                <th>Status</th>
                                                <th>Notes</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules as $schedule) { ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($schedule['schedule_date'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($schedule['start_time'])) ?> - <?= date('h:i A', strtotime($schedule['end_time'])) ?></td>
                                                    <td><?= $schedule['time_slot_minutes'] ?> minutes</td>
                                                    <td><?= $schedule['max_patients'] ?></td>
                                                    <td>
                                                        <?php if ($schedule['is_approved']) { ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php } else { ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?= $schedule['notes'] ?>
                                                        <?php if (!empty($schedule['approval_notes'])) { ?>
                                                            <br><small class="text-muted">Admin notes: <?= $schedule['approval_notes'] ?></small>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$schedule['is_approved']) { ?>
                                                            <a href="actions/delete_schedule.php?id=<?= $schedule['id'] ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php } else { ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include './config/footer.php'; ?>
    </div>
    
    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    <script src="plugins/fullcalendar/main.min.js"></script>
    
    <script>
        $(function() {
            // Initialize DataTable
            $("#schedules_table").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "order": [[0, "asc"]]
            });
            
            // Function to automatically update past appointments to completed status
            function updatePastAppointments() {
                $.ajax({
                    url: 'ajax/update_past_appointments.php',
                    type: 'POST',
                    data: {
                        doctor_id: <?= $doctorId ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.updated > 0) {
                            console.log('Updated ' + response.updated + ' past appointments to completed status');
                            // Reload page to refresh the calendar with updated status
                            location.reload();
                        }
                    }
                });
            }
            
            // Call the update function when the page loads
            updatePastAppointments();
            
            // Function to automatically update past appointments to completed status
            function updatePastAppointments() {
                $.ajax({
                    url: 'ajax/update_past_appointments.php',
                    type: 'POST',
                    data: {
                        doctor_id: <?= $doctorId ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.updated > 0) {
                            console.log('Updated ' + response.updated + ' past appointments to completed status');
                            // Optionally refresh the calendar if needed
                            // calendar.refetchEvents();
                        }
                    }
                });
            }
            
            // Call the update function when the page loads
            updatePastAppointments();
            
            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: <?= json_encode($calendarEvents) ?>,
                eventDidMount: function(info) {
                    // Add tooltip for past events
                    if (info.event.extendedProps.is_past) {
                        $(info.el).tooltip({
                            title: 'Past ' + info.event.extendedProps.type.charAt(0).toUpperCase() + info.event.extendedProps.type.slice(1),
                            placement: 'top',
                            trigger: 'hover',
                            container: 'body'
                        });
                    }
                },
                eventClick: function(info) {
                    var event = info.event;
                    var props = event.extendedProps;
                    var content = '';
                    var title = '';
                    var iconClass = '';
                    var toastClass = '';
                    
                    if (props.type === 'schedule') {
                        // Schedule event
                    var status = props.is_approved ? 'Approved' : 'Pending';
                    var statusClass = props.is_approved ? 'success' : 'warning';
                    
                        title = 'Schedule Information';
                        iconClass = 'fas fa-calendar-alt fa-lg';
                        toastClass = 'bg-light';
                        
                        content = '<div class="p-3">' +
                        '<h5 class="mb-3">Schedule Details</h5>' +
                        '<p><strong>Date:</strong> ' + event.start.toLocaleDateString() + '</p>' +
                        '<p><strong>Time:</strong> ' + event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ' - ' + 
                            event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</p>' +
                        '<p><strong>Time Slot:</strong> ' + props.time_slot + ' minutes</p>' +
                        '<p><strong>Max Patients per Slot:</strong> ' + props.max_patients + '</p>' +
                        '<p><strong>Status:</strong> <span class="badge badge-' + statusClass + '">' + status + '</span></p>';
                    
                    if (props.notes) {
                        content += '<p><strong>Notes:</strong> ' + props.notes + '</p>';
                    }
                    
                    if (props.approval_notes) {
                        content += '<p><strong>Admin Notes:</strong> ' + props.approval_notes + '</p>';
                        }
                    } else {
                        // Appointment event
                        title = 'Appointment Information';
                        iconClass = 'fas fa-user-clock fa-lg';
                        
                        // Set toast class based on appointment status and whether it's past
                        if (props.is_past) {
                            if (props.status == 'completed') {
                                toastClass = 'bg-success text-white';
                            } else {
                                toastClass = 'bg-secondary text-white';
                            }
                        } else {
                            toastClass = 'bg-danger text-white';
                        }
                        
                        content = '<div class="p-3">' +
                            '<h5 class="mb-3">Appointment Details</h5>' +
                            '<p><strong>Patient:</strong> ' + props.patient_name + '</p>' +
                            '<p><strong>Date & Time:</strong> ' + event.start.toLocaleDateString() + ' ' + 
                                event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</p>' +
                            '<p><strong>Status:</strong> <span class="badge badge-light">' + 
                                props.status.charAt(0).toUpperCase() + props.status.slice(1) + 
                                (props.is_past ? ' (Past)' : '') + '</span></p>';
                        
                        if (props.reason) {
                            content += '<p><strong>Reason:</strong> ' + props.reason + '</p>';
                        }
                    }
                    
                    content += '</div>';
                    
                    $(document).Toasts('create', {
                        title: title,
                        body: content,
                        autohide: true,
                        delay: 5000,
                        class: toastClass,
                        icon: iconClass,
                        close: true
                    });
                }
            });
            calendar.render();
            
            // Form validation
            $('#start_date, #end_date').on('change', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                    $('#end_date').val(startDate);
                    alert('End date cannot be before start date');
                }
            });
            
            $('#start_time, #end_time').on('change', function() {
                var startTime = $('#start_time').val();
                var endTime = $('#end_time').val();
                
                if (startTime && endTime && endTime <= startTime) {
                    $('#end_time').val('');
                    alert('End time must be after start time');
                }
            });
        });
    </script>
</body>
</html>