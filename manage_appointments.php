<?php
/**
 * Manage Appointments
 * 
 * This file handles appointment management.
 * Doctor schedule approval functionality has been moved to appointment_plotter.php.
 */

include './config/connection.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';
$error = '';

// Get message/error from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Handle appointment notes updates
if (isset($_POST['update_notes'])) {
    $appointmentId = $_POST['appointment_id'];
    $notes = $_POST['notes'];

    try {
        $query = "UPDATE appointments SET notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$notes, $appointmentId]);
        $message = "Appointment notes updated successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating appointment notes: " . $ex->getMessage();
    }
}

// Handle appointment archive/unarchive
if (isset($_POST['toggle_archive'])) {
    $appointmentId = $_POST['appointment_id'];
    $isArchived = $_POST['is_archived'] ? 1 : 0;

    try {
        $query = "UPDATE appointments SET is_archived = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$isArchived, $appointmentId]);
        $message = $isArchived ? "Appointment archived successfully!" : "Appointment unarchived successfully!";
    } catch (PDOException $ex) {
        $error = "Error updating archive status: " . $ex->getMessage();
    }
}

// Handle archive filter
$showArchived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;

// Fetch all appointments
$query = "SELECT * FROM appointments WHERE is_archived = ? ORDER BY appointment_date DESC, appointment_time DESC";
$stmt = $con->prepare($query);
$stmt->execute([$showArchived]);

// Count total archived and active appointments for the filter
$countQuery = "SELECT 
                SUM(CASE WHEN is_archived = 0 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived_count
               FROM appointments";
$countStmt = $con->prepare($countQuery);
$countStmt->execute();
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$activeCount = $countResult['active_count'] ?? 0;
$archivedCount = $countResult['archived_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Manage Appointments - Mamatid Health Center System</title>
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

        .badge-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: var(--danger-color);
        }

        .badge-info {
            background-color: rgba(137, 80, 252, 0.1);
            color: var(--info-color);
        }

        .badge-secondary {
            background-color: rgba(128, 128, 128, 0.1);
            color: #808080;
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

        /* Filter buttons */
        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            margin-right: 0.5rem;
            transition: all var(--transition-speed);
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-btn:not(.active) {
            background-color: white;
            color: var(--dark-color);
            border-color: #e4e6ef;
        }

        .filter-btn .badge {
            margin-left: 5px;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .filter-btn.active .badge {
            background-color: white;
            color: var(--primary-color);
        }

        .filter-btn:not(.active) .badge {
            background-color: #e4e6ef;
            color: var(--dark-color);
        }

        /* Archived appointment styling */
        tr.archived-row {
            opacity: 0.7;
        }

        tr.archived-row:hover {
            opacity: 1;
        }

        .archived-tag {
            display: inline-block;
            padding: 2px 5px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #6c757d;
            margin-left: 5px;
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
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar and Sidebar -->
        <?php include './config/header.php'; include './config/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Manage Appointments</h1>
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

                <!-- Appointments Table Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">All Appointments</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Buttons -->
                        <div class="mb-4">
                            <a href="?archived=0" class="btn filter-btn <?php echo $showArchived ? '' : 'active'; ?>">
                                Active <span class="badge"><?php echo $activeCount; ?></span>
                            </a>
                            <a href="?archived=1" class="btn filter-btn <?php echo $showArchived ? 'active' : ''; ?>">
                                Archived <span class="badge"><?php echo $archivedCount; ?></span>
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="appointments" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient Name</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr class="<?php echo $row['is_archived'] ? 'archived-row' : ''; ?>">
                                        <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['patient_name']); ?>
                                            <?php if ($row['is_archived']): ?>
                                                <span class="archived-tag"><i class="fas fa-archive fa-xs"></i> Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $row['status'] == 'pending' ? 'warning' : 
                                                    ($row['status'] == 'approved' ? 'success' : 
                                                    ($row['status'] == 'completed' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#updateModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i> Notes
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm <?php echo $row['is_archived'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                        data-toggle="modal" 
                                                        data-target="#archiveModal<?php echo $row['id']; ?>">
                                                    <i class="fas <?php echo $row['is_archived'] ? 'fa-box-open' : 'fa-archive'; ?>"></i>
                                                    <?php echo $row['is_archived'] ? 'Unarchive' : 'Archive'; ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Update Notes Modal -->
                                    <div class="modal fade" id="updateModal<?php echo $row['id']; ?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">
                                                        <i class="fas fa-calendar-check mr-2"></i>
                                                        Update Appointment Notes
                                                    </h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Current Status</label>
                                                            <input type="text" class="form-control" value="<?php echo ucfirst($row['status']); ?>" readonly>
                                                            <small class="text-muted">Appointments are automatically approved when booked by patients</small>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3" 
                                                                      placeholder="Enter notes about the appointment"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            <i class="fas fa-times mr-2"></i>Close
                                                        </button>
                                                        <button type="submit" name="update_notes" class="btn btn-primary">
                                                            <i class="fas fa-save mr-2"></i>Update Notes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Archive Modal -->
                                    <div class="modal fade" id="archiveModal<?php echo $row['id']; ?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">
                                                        <i class="fas <?php echo $row['is_archived'] ? 'fa-box-open' : 'fa-archive'; ?> mr-2"></i>
                                                        <?php echo $row['is_archived'] ? 'Unarchive' : 'Archive'; ?> Appointment
                                                    </h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="is_archived" value="<?php echo $row['is_archived'] ? '0' : '1'; ?>">
                                                        
                                                        <div class="text-center mb-4">
                                                            <?php if ($row['is_archived']): ?>
                                                                <i class="fas fa-box-open fa-4x text-success mb-3"></i>
                                                                <p>Are you sure you want to unarchive this appointment? It will appear in the active appointments list.</p>
                                                            <?php else: ?>
                                                                <i class="fas fa-archive fa-4x text-secondary mb-3"></i>
                                                                <p>Are you sure you want to archive this appointment? Archived appointments are stored for record-keeping but won't appear in the active list.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="alert alert-info">
                                                            <strong>Patient:</strong> <?php echo htmlspecialchars($row['patient_name']); ?><br>
                                                            <strong>Date & Time:</strong> <?php echo date('M d, Y', strtotime($row['appointment_date'])) . ' at ' . date('h:i A', strtotime($row['appointment_time'])); ?><br>
                                                            <strong>Status:</strong> <?php echo ucfirst($row['status']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            <i class="fas fa-times mr-2"></i>Cancel
                                                        </button>
                                                        <button type="submit" name="toggle_archive" class="btn <?php echo $row['is_archived'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                            <i class="fas <?php echo $row['is_archived'] ? 'fa-box-open' : 'fa-archive'; ?> mr-2"></i>
                                                            <?php echo $row['is_archived'] ? 'Unarchive' : 'Archive'; ?> Appointment
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable for Appointments
            $("#appointments").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search appointments..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#appointments_wrapper .col-md-6:eq(0)');

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
        showMenuSelected("#mnu_appointments", "#mi_appointments");
    </script>
</body>
</html> 