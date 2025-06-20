<?php
include './config/connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only admins and health workers can access this page
requireRole(['admin', 'health_worker']);

$message = '';
$error = '';

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Handle doctor schedule approval
if (isset($_POST['approve_schedule'])) {
    $scheduleId = $_POST['schedule_id'];
    $isApproved = isset($_POST['is_approved']) ? 1 : 0;
    $approvalNotes = $_POST['approval_notes'];
    
    try {
        $query = "UPDATE doctor_schedules SET is_approved = ?, approval_notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$isApproved, $approvalNotes, $scheduleId]);
        $message = "Doctor schedule " . ($isApproved ? "approved" : "rejected") . " successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating schedule: " . $ex->getMessage();
    }
}

// Fetch all doctor schedules
$scheduleQuery = "SELECT ds.*, u.display_name as doctor_name 
                 FROM doctor_schedules ds
                 JOIN users u ON ds.doctor_id = u.id
                 ORDER BY ds.schedule_date ASC, ds.start_time ASC";
$scheduleStmt = $con->prepare($scheduleQuery);
$scheduleStmt->execute();
$doctorSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Doctor Schedule Approval - Mamatid Health Center System</title>
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
            text-transform: capitalize;
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

        /* Badge Styling */
        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .badge-success {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background-color: rgba(255, 168, 0, 0.1);
            color: var(--warning-color);
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

        .alert-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: var(--danger-color);
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
                            <h1 class="m-0">Doctor Schedule Approval</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Doctor Schedule Approval</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <?php if (!empty($message)) { ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php } ?>
                    
                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Doctor Schedules</h3>
                                </div>
                                <div class="card-body">
                                    <table id="doctor_schedules_table" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Doctor</th>
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
                                            <?php foreach ($doctorSchedules as $schedule) { ?>
                                                <tr>
                                                    <td><?= $schedule['doctor_name'] ?></td>
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
                                                    <td><?= $schedule['notes'] ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary approve-schedule-btn" 
                                                                data-toggle="modal" 
                                                                data-target="#approveScheduleModal" 
                                                                data-id="<?= $schedule['id'] ?>"
                                                                data-doctor="<?= $schedule['doctor_name'] ?>"
                                                                data-date="<?= date('M d, Y', strtotime($schedule['schedule_date'])) ?>"
                                                                data-time="<?= date('h:i A', strtotime($schedule['start_time'])) ?> - <?= date('h:i A', strtotime($schedule['end_time'])) ?>"
                                                                data-approved="<?= $schedule['is_approved'] ?>"
                                                                data-notes="<?= $schedule['approval_notes'] ?>">
                                                            <i class="fas fa-check-circle"></i> Review
                                                        </button>
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
    
    <!-- Approve Schedule Modal -->
    <div class="modal fade" id="approveScheduleModal" tabindex="-1" role="dialog" aria-labelledby="approveScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveScheduleModalLabel">Review Doctor Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="schedule_id" id="schedule_id">
                        
                        <div class="form-group">
                            <label>Doctor:</label>
                            <p id="modal_doctor" class="font-weight-bold"></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Date:</label>
                            <p id="modal_date" class="font-weight-bold"></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Time:</label>
                            <p id="modal_time" class="font-weight-bold"></p>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_approved" name="is_approved">
                                <label class="custom-control-label" for="is_approved">Approve this schedule</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="approval_notes">Notes (Optional)</label>
                            <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Any notes for the doctor"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="approve_schedule" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(function() {
            // Initialize DataTable
            $("#doctor_schedules_table").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "order": [[1, "asc"], [2, "asc"]]
            });
            
            // Handle approve schedule modal
            $('.approve-schedule-btn').click(function() {
                var id = $(this).data('id');
                var doctor = $(this).data('doctor');
                var date = $(this).data('date');
                var time = $(this).data('time');
                var approved = $(this).data('approved');
                var notes = $(this).data('notes');
                
                $('#schedule_id').val(id);
                $('#modal_doctor').text(doctor);
                $('#modal_date').text(date);
                $('#modal_time').text(time);
                $('#is_approved').prop('checked', approved == 1);
                $('#approval_notes').val(notes);
            });
        });
    </script>
</body>
</html>
