<?php
// Include client authentication check (this handles session isolation automatically)
require_once '../system/utilities/check_client_auth.php';

include '../config/db_connection.php';

// Get client's appointments using safe session getter
$clientId = getClientSessionVar('client_id');
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
    <?php include '../config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">

    <style>
        :root {
            --transition-speed: 0.3s;
        }

        /* Modern Card Styles */
        .small-box {
            border-radius: 15px;
            overflow: hidden;
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .small-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .small-box .inner {
            padding: 20px;
        }

        .small-box .inner h3 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            transition: var(--transition-speed);
        }

        .small-box .inner p {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
            opacity: 0.9;
        }

        .small-box .icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            opacity: 0.3;
            transition: var(--transition-speed);
        }

        .small-box:hover .icon {
            opacity: 0.5;
            transform: translateY(-50%) scale(1.1);
        }

        /* Modern Gradients for Stat Boxes */
        .bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%)!important;
        }

        .bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%)!important;
        }

        .bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)!important;
            color: #fff!important;
        }

        .bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c81e1e 100%)!important;
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

        /* Statistics Cards */
        .stats-card {
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            opacity: 0.8;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #8950FC, #3699FF);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #1BC5BD, #0d7e66);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #3699FF, #6993FF);
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

        /* Tab Styling */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            background-color: #f8f9fa;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 0;
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            background-color: transparent;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            color: #3699FF;
            background-color: rgba(54, 153, 255, 0.05);
        }

        .nav-tabs .nav-link.active {
            border: none;
            color: #3699FF;
            background-color: white;
            border-bottom: 3px solid #3699FF;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #3699FF;
        }

        .tab-content {
            background-color: white;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }

        .tab-pane {
            min-height: 300px;
        }

        .nav-tabs .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
        }

        /* Empty state styling */
        .text-center.py-4 {
            padding: 3rem 1rem !important;
        }

        .text-center.py-4 i {
            opacity: 0.5;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .small-box .inner h3 {
                font-size: 2rem;
            }
            
            .small-box .icon {
                font-size: 3rem;
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

            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .nav-tabs .badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.4rem;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_ui/client_header.php'; ?>
        <?php include './config/client_ui/client_sidebar.php'; ?>

        <div class="content-wrapper" style="padding-top: 32px;">
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistics Overview -->
                    <div class="row mb-4 g-4 mt-3" style="margin-top: 0; padding-top: 0;">
                        <?php
                        $approvedCount = 0;
                        $completedCount = 0;
                        $today = date('Y-m-d');
                        
                        foreach ($appointments as $appointment) {
                            if ($appointment['status'] == 'approved') $approvedCount++;
                            if ($appointment['status'] == 'completed') $completedCount++;
                        }
                        ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card bg-gradient-info text-white stats-card shadow-sm" style="border-radius: 18px; box-shadow: 0 4px 24px rgba(54,153,255,0.10);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo count($appointments); ?></h3>
                                            <p class="mb-1 font-weight-bold">Total Appointments</p>
                                            <small class="d-block opacity-75">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                All Time Bookings
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-calendar-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card bg-gradient-success text-white stats-card shadow-sm" style="border-radius: 18px; box-shadow: 0 4px 24px rgba(27,197,189,0.10);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo $approvedCount; ?></h3>
                                            <p class="mb-1 font-weight-bold">Upcoming Appointments</p>
                                            <small class="d-block opacity-75">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Confirmed Bookings
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card bg-gradient-primary text-white stats-card shadow-sm" style="border-radius: 18px; box-shadow: 0 4px 24px rgba(54,153,255,0.10);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-1"><?php echo $completedCount; ?></h3>
                                            <p class="mb-1 font-weight-bold">Completed Appointments</p>
                                            <small class="d-block opacity-75">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                Finished Sessions
                                            </small>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Tabs -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                My Appointments
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-fill" id="appointmentsTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="upcoming-tab" data-toggle="tab" data-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                                        <i class="fas fa-calendar-check mr-1"></i>
                                        Upcoming
                                        <?php
                                        $upcomingCount = count(array_filter($appointments, function($apt) use ($today) {
                                            return $apt['status'] == 'approved' && $apt['appointment_date'] >= $today;
                                        }));
                                        if ($upcomingCount > 0): ?>
                                        <span class="badge badge-success ml-1"><?php echo $upcomingCount; ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="recent-tab" data-toggle="tab" data-target="#recent" type="button" role="tab" aria-controls="recent" aria-selected="false">
                                        <i class="fas fa-bell mr-1"></i>
                                        Recent Updates
                                        <?php
                                        $recentCount = count(array_filter($appointments, function($apt) {
                                            return in_array($apt['status'], ['approved', 'completed', 'cancelled']) && 
                                                   strtotime($apt['last_modified']) >= strtotime('-7 days');
                                        }));
                                        if ($recentCount > 0): ?>
                                        <span class="badge badge-warning ml-1"><?php echo $recentCount; ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="history-tab" data-toggle="tab" data-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                                        <i class="fas fa-history mr-1"></i>
                                        All History
                                        <span class="badge badge-info ml-1"><?php echo count($appointments); ?></span>
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content" id="appointmentsTabContent">
                                <!-- Upcoming Appointments Tab -->
                                <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                                    <?php
                                    $upcomingApproved = array_filter($appointments, function($apt) use ($today) {
                                        return $apt['status'] == 'approved' && $apt['appointment_date'] >= $today;
                                    });
                                    ?>
                                    <div class="p-3">
                                        <?php if (empty($upcomingApproved)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 3rem;"></i>
                                                <h5 class="text-muted">No Upcoming Appointments</h5>
                                                <p class="text-muted">You don't have any approved appointments scheduled.</p>
                                                <a href="client_appointment_booking.php" class="btn btn-primary">
                                                    <i class="fas fa-plus mr-1"></i> Book New Appointment
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Time</th>
                                                            <th>Reason</th>
                                                            <th>Notes</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($upcomingApproved as $appointment): ?>
                                                        <tr>
                                                            <td>
                                                                <strong class="text-success">
                                                                    <?php echo $appointment['formatted_date']; ?>
                                                                </strong>
                                                            </td>
                                                            <td><?php echo $appointment['formatted_time']; ?></td>
                                                            <td><?php echo $appointment['reason']; ?></td>
                                                            <td><?php echo $appointment['notes'] ?? 'No notes'; ?></td>
                                                            <td>
                                                                <button type="button" 
                                                                        class="btn btn-danger btn-sm delete-appointment" 
                                                                        data-id="<?php echo $appointment['id']; ?>"
                                                                        data-date="<?php echo $appointment['formatted_date']; ?>"
                                                                        data-time="<?php echo $appointment['formatted_time']; ?>">
                                                                    <i class="fas fa-times"></i> Cancel
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Recent Updates Tab -->
                                <div class="tab-pane fade" id="recent" role="tabpanel" aria-labelledby="recent-tab">
                                    <?php
                                    $recentUpdates = array_filter($appointments, function($apt) {
                                        return in_array($apt['status'], ['approved', 'completed', 'cancelled']) && 
                                               strtotime($apt['last_modified']) >= strtotime('-7 days');
                                    });
                                    ?>
                                    <div class="p-3">
                                        <?php if (empty($recentUpdates)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                                                <h5 class="text-muted">No Recent Updates</h5>
                                                <p class="text-muted">No appointment status changes in the last 7 days.</p>
                                            </div>
                                        <?php else: ?>
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
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- All History Tab -->
                                <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                                    <div class="p-3">
                                        <?php if (empty($appointments)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-history text-muted mb-3" style="font-size: 3rem;"></i>
                                                <h5 class="text-muted">No Appointment History</h5>
                                                <p class="text-muted">You haven't booked any appointments yet.</p>
                                                <a href="client_appointment_booking.php" class="btn btn-primary">
                                                    <i class="fas fa-plus mr-1"></i> Book Your First Appointment
                                                </a>
                                            </div>
                                        <?php else: ?>
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
                                        <?php endif; ?>
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

    <?php include '../config/site_css_js_links.php'; ?>
    
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
                        window.location.href = `../actions/delete_appointment.php?id=${id}`;
                    }
                });
            });
        });
    </script>
</body>
</html> 
