<?php
/**
 * Appointment Plotter
 * 
 * This file manages doctor schedule approvals and planning for appointments.
 * Only admins and health workers can approve doctor schedules.
 * Staff schedules (admin and health worker) are auto-approved.
 */

include './config/db_connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission - only admin and health workers can access this page
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
$doctorSchedules = [];
$scheduleQuery = "SELECT ds.*, u.display_name as doctor_name 
                 FROM doctor_schedules ds
                 JOIN users u ON ds.doctor_id = u.id
                 ORDER BY ds.schedule_date ASC, ds.start_time ASC";
$scheduleStmt = $con->prepare($scheduleQuery);
$scheduleStmt->execute();
$doctorSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all staff schedules for reference (already auto-approved)
$staffSchedules = [];
$staffQuery = "SELECT ss.*, u.display_name as staff_name, u.role as staff_role
              FROM staff_schedules ss
              JOIN users u ON ss.staff_id = u.id
              WHERE u.role IN ('admin', 'health_worker')
              ORDER BY ss.schedule_date ASC, ss.start_time ASC";
$staffStmt = $con->prepare($staffQuery);
$staffStmt->execute();
$staffSchedules = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Appointment Plotter - Mamatid Health Center System</title>
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

        .btn-secondary {
            background: #e4e6ef;
            border: none;
            color: var(--dark-color);
        }

        .btn-secondary:hover {
            background: #d7dae7;
            color: var(--dark-color);
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

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(243, 246, 249, 0.5);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(54, 153, 255, 0.05);
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

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: var(--light-color);
            border-bottom: 1px solid #eee;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1rem 1.5rem;
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
            text-transform: capitalize;
        }

        /* Custom Switch */
        .custom-switch .custom-control-label::before {
            height: 1.5rem;
            width: 2.75rem;
            border-radius: 3rem;
        }

        .custom-switch .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: 50%;
        }

        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }
        }
        
        /* Staff schedule badge */
        .badge-role {
            font-size: 0.7rem;
            margin-left: 5px;
            padding: 0.2rem 0.4rem;
        }
        
        .badge-admin {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }
        
        .badge-health-worker {
            background-color: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar and Sidebar -->
        <?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Appointment Plotter</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <!-- Display Messages -->
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

                <!-- Doctor Schedules Card -->
                <div class="card card-outline card-info mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Doctor Schedules
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="doctorSchedules" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Time Slot</th>
                                        <th>Max Patients</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctorSchedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['doctor_name']); ?></td>
                                        <td><?php echo date('M d, Y (D)', strtotime($schedule['schedule_date'])); ?></td>
                                        <td>
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td><?php echo $schedule['time_slot_minutes']; ?> minutes</td>
                                        <td><?php echo $schedule['max_patients']; ?></td>
                                        <td>
                                            <?php if (isset($schedule['is_approved'])): ?>
                                                <span class="badge badge-<?php echo $schedule['is_approved'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $schedule['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['notes'] ?? ''); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#scheduleModal<?php echo $schedule['id']; ?>">
                                                <i class="fas fa-check-circle mr-1"></i> Manage
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Schedule Management Modal -->
                                    <div class="modal fade" id="scheduleModal<?php echo $schedule['id']; ?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">
                                                        <i class="fas fa-calendar-check mr-2"></i>
                                                        Manage Doctor Schedule
                                                    </h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Doctor</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($schedule['doctor_name']); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Date</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?php echo date('F d, Y (l)', strtotime($schedule['schedule_date'])); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Time</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                                                date('h:i A', strtotime($schedule['end_time'])); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Approve Schedule</label>
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input" 
                                                                       id="approveSwitch<?php echo $schedule['id']; ?>" 
                                                                       name="is_approved" 
                                                                       <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" 
                                                                       for="approveSwitch<?php echo $schedule['id']; ?>">
                                                                    <?php echo (isset($schedule['is_approved']) && $schedule['is_approved']) ? 'Approved' : 'Not Approved'; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="approval_notes" class="form-control" rows="3" 
                                                                      placeholder="Add notes about this schedule"><?php echo htmlspecialchars($schedule['approval_notes'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            <i class="fas fa-times mr-2"></i>Close
                                                        </button>
                                                        <button type="submit" name="approve_schedule" class="btn btn-primary">
                                                            <i class="fas fa-save mr-2"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Staff Schedules Card -->
                <div class="card card-outline card-success mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-md mr-2"></i>
                            Staff Schedules
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($staffSchedules)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No staff schedules are currently available. Staff members can set their availability in the "My Availability" section.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="staffSchedules" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Role</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Time Slot</th>
                                            <th>Max Patients</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffSchedules as $staffSchedule): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($staffSchedule['staff_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $staffSchedule['staff_role'] == 'admin' ? 'admin' : 'health-worker'; ?> badge-role">
                                                        <?php echo ucfirst($staffSchedule['staff_role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y (D)', strtotime($staffSchedule['schedule_date'])); ?></td>
                                                <td>
                                                    <?php echo date('h:i A', strtotime($staffSchedule['start_time'])) . ' - ' . 
                                                            date('h:i A', strtotime($staffSchedule['end_time'])); ?>
                                                </td>
                                                <td><?php echo $staffSchedule['time_slot_minutes']; ?> minutes</td>
                                                <td><?php echo $staffSchedule['max_patients']; ?></td>
                                                <td><?php echo htmlspecialchars($staffSchedule['notes'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3 alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Staff schedules are automatically approved and available for patient booking.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/admin_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable for Doctor Schedules
            $("#doctorSchedules").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search schedules..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#doctorSchedules_wrapper .col-md-6:eq(0)');

            // Initialize DataTable for Staff Schedules
            $("#staffSchedules").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search staff schedules..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#staffSchedules_wrapper .col-md-6:eq(0)');

            // Initialize Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Show message if exists
            var message = '<?php echo $message;?>';
            if(message !== '') {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Show error if exists
            var error = '<?php echo $error;?>';
            if(error !== '') {
                Toast.fire({
                    icon: 'error',
                    title: error
                });
            }

            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Toggle switch label text
            $('.custom-switch .custom-control-input').change(function() {
                if($(this).is(':checked')) {
                    $(this).next('label').text('Approved');
                } else {
                    $(this).next('label').text('Not Approved');
                }
            });
        });

        // Highlight current menu
        showMenuSelected("#mnu_appointments", "#mi_appointment_plotter");
    </script>
</body>
</html> 