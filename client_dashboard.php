<?php
// Include client authentication check (this handles session isolation automatically)
require_once './system/utilities/check_client_auth.php';

include './config/db_connection.php';

// Get client's appointments
$clientId = function_exists('getClientSessionVar') ? getClientSessionVar('client_id') : $_SESSION['client_id'];
$query = "SELECT *, 
          DATE_FORMAT(appointment_date, '%M %d, %Y') as formatted_date,
          TIME_FORMAT(appointment_time, '%h:%i %p') as formatted_time,
          COALESCE(updated_at, created_at) as last_modified
          FROM admin_clients_appointments 
          WHERE patient_name = (SELECT full_name FROM clients_user_accounts WHERE id = ?)
          ORDER BY appointment_date DESC, appointment_time DESC";

try {
    $stmt = $con->prepare($query);
    $stmt->execute([$clientId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}

// Get message from URL if any
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Dashboard - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        :root {
            --transition-speed: 0.3s;
        }

        /* Modern Statistics Cards */
        .stats-card {
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stats-card .card-body {
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .stats-card .icon {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-card p {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.95;
        }

        .stats-card small {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.8;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent);
            z-index: 1;
        }

        /* Stats card icons with floating animation */
        .stats-icon {
            position: absolute;
            top: 50%;
            right: 2rem;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.2;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50% { transform: translateY(-50%) translateX(5px); }
        }

        /* Modern Gradients for Stats Cards */
        .bg-gradient-info {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            color: white;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%);
            color: white;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #fd7e14 0%, #e85d04 100%);
            color: white;
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545 0%, #b52d3c 100%);
            color: white;
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

        /* Card and Table Styling */
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

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #F3F6F9;
            border-bottom: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem;
            color: #3F4254;
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
            border-radius: 30px;
        }

        .badge-success {
            background-color: rgba(27, 197, 189, 0.1);
            color: #1BC5BD;
        }

        .badge-warning {
            background-color: rgba(255, 168, 0, 0.1);
            color: #FFA800;
        }

        .badge-danger {
            background-color: rgba(246, 78, 96, 0.1);
            color: #F64E60;
        }

        .badge-info {
            background-color: rgba(137, 80, 252, 0.1);
            color: #8950FC;
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

        /* Button Styling */
        .btn {
            padding: 0.65rem 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #F64E60 0%, #ee2d41 100%);
            border: none;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(246, 78, 96, 0.4);
        }

        /* Responsive Stats Cards */
        @media (max-width: 768px) {
            .stats-card .card-body {
                padding: 1.5rem;
            }

            .stats-card h3 {
                font-size: 2rem;
            }

            .stats-card p {
                font-size: 1rem;
            }

            .stats-card small {
                font-size: 0.8rem;
            }

            .stats-icon {
                font-size: 2.5rem;
                right: 1.5rem;
            }
            
            #datetime {
                font-size: 1rem;
                padding: 8px 15px;
            }

            .content-wrapper {
                padding: 15px;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .stats-card .card-body {
                padding: 1.25rem;
            }

            .stats-card h3 {
                font-size: 1.8rem;
            }

            .stats-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
        }

        /* Remove any borders from content-header */
        .content-header {
            border: none !important;
            border-bottom: none !important;
            border-top: none !important;
            padding: 0 !important;
            margin-bottom: 0 !important;
        }

        /* Remove any borders from content wrapper and sections */
        .content-wrapper {
            border: none !important;
            border-top: none !important;
        }

        .content {
            border: none !important;
            border-top: none !important;
        }

        .content-wrapper::before,
        .content-wrapper::after {
            display: none !important;
        }

        /* Modern Tabs Styling (from admin_schedule_plotter.php) */
        .nav-tabs {
            border: none;
            margin-bottom: 20px;
            gap: 10px;
        }
        .nav-tabs .nav-item {
            margin: 0;
        }
        .nav-tabs .nav-link {
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            color: #7E8299;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .nav-tabs .nav-link:hover {
            color: #3699FF;
            background: rgba(54, 153, 255, 0.1);
            border-color: rgba(54, 153, 255, 0.2);
            transform: translateY(-1px);
        }
        .nav-tabs .nav-link.active {
            color: #3699FF;
            background: rgba(54, 153, 255, 0.15);
            border-color: rgba(54, 153, 255, 0.3);
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }
        .nav-tabs .nav-link i {
            font-size: 1rem;
        }
        .tab-content {
            background: transparent;
        }
        .tab-pane {
            border-radius: 12px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .tab-pane.active {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 768px) {
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-link {
                white-space: nowrap;
                padding: 10px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_ui/client_header.php'; ?>
        <?php include './config/client_ui/client_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header" style="border: none; padding: 0;">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12">
                            <!-- Client Dashboard header removed -->
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Modern Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="card bg-gradient-success text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo count($appointments); ?></h3>
                                            <p class="mb-1 font-weight-bold">Total Appointments</p>
                                            <small class="d-block">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                Appointment History
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-calendar-check fa-2x"></i>
                                        </div>
                                    </div>
                                    <i class="fas fa-calendar-check stats-icon"></i>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                        $approvedCount = 0;
                        $completedCount = 0;
                        $today = date('Y-m-d');
                        
                        foreach ($appointments as $appointment) {
                            if ($appointment['status'] == 'approved') $approvedCount++;
                            if ($appointment['status'] == 'completed') $completedCount++;
                        }
                        ?>
                        
                        <div class="col-lg-4 col-md-6">
                            <div class="card bg-gradient-info text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo $approvedCount; ?></h3>
                                            <p class="mb-1 font-weight-bold">Upcoming Appointments</p>
                                            <small class="d-block">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Approved & Scheduled
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                    <i class="fas fa-check-circle stats-icon"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 col-md-6">
                            <div class="card bg-gradient-primary text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo $completedCount; ?></h3>
                                            <p class="mb-1 font-weight-bold">Completed Appointments</p>
                                            <small class="d-block">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                Finished Visits
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    </div>
                                    <i class="fas fa-calendar-alt stats-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs for Status Updates and History -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                Appointments Overview
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs" id="clientAppointmentsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="recent-status-tab" data-toggle="tab" href="#recent-status" role="tab">
                                        <i class="fas fa-bell mr-2"></i>Recent Status Updates
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="all-history-tab" data-toggle="tab" href="#all-history" role="tab">
                                        <i class="fas fa-history mr-2"></i>All Appointments History
                                    </a>
                                </li>
                            </ul>
                            <!-- Tab Content -->
                            <div class="tab-content" id="clientAppointmentsTabsContent">
                                <div class="tab-pane fade show active" id="recent-status" role="tabpanel">
                                    <?php
                                    $recentUpdates = array_filter($appointments, function($apt) {
                                        return in_array($apt['status'], ['approved', 'completed', 'cancelled']) && 
                                               strtotime($apt['last_modified']) >= strtotime('-7 days');
                                    });
                                    if (!empty($recentUpdates)): 
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentUpdates as $update): ?>
                                                <tr>
                                                    <td><?php echo $update['formatted_date']; ?></td>
                                                    <td><?php echo $update['formatted_time']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo match($update['status']) {
                                                                'approved' => 'success',
                                                                'completed' => 'info',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($update['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $update['notes'] ?? 'No notes'; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                        <p class="text-muted">No recent status updates found.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="tab-pane fade" id="all-history" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Reason</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo $appointment['formatted_date']; ?></td>
                                                    <td><?php echo $appointment['formatted_time']; ?></td>
                                                    <td><?php echo $appointment['reason']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo match($appointment['status']) {
                                                                'approved' => 'success',
                                                                'cancelled' => 'danger',
                                                                'completed' => 'info',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $appointment['notes'] ?? 'No notes'; ?></td>
                                                    <td>
                                                        <?php if ($appointment['appointment_date'] > date('Y-m-d')): ?>
                                                        <div class="btn-group">
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm delete-appointment" 
                                                                    data-id="<?php echo $appointment['id']; ?>"
                                                                    data-date="<?php echo $appointment['formatted_date']; ?>"
                                                                    data-time="<?php echo $appointment['formatted_time']; ?>">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/client_ui/client_footer.php'; ?>
    </div>

    <?php include './config/site_css_js_links.php'; ?>
    
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

            // Show success message if redirected from successful action
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            if (message) {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Handle delete appointment with enhanced confirmation dialog
            $('.delete-appointment').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const date = $(this).data('date');
                const time = $(this).data('time');

                Swal.fire({
                    title: 'Delete Appointment?',
                    html: `Are you sure you want to delete your appointment on <br>
                          <strong class="text-danger">${date}</strong> at <strong class="text-danger">${time}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#F64E60',
                    cancelButtonColor: '#3699FF',
                    confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                    cancelButtonText: '<i class="fas fa-times"></i> No, keep it',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `actions/delete_appointment.php?id=${id}`;
                    }
                });
            });
        });
    </script>
</body>
</html> 
