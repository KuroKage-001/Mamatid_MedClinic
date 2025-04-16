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

// Get client's appointments
$clientId = $_SESSION['client_id'];
$query = "SELECT *, 
          DATE_FORMAT(appointment_date, '%M %d, %Y') as formatted_date,
          TIME_FORMAT(appointment_time, '%h:%i %p') as formatted_time,
          COALESCE(updated_at, created_at) as last_modified
          FROM appointments 
          WHERE patient_name = (SELECT full_name FROM clients WHERE id = ?)
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
            --header-bg: #1a1a2d;
            --sidebar-bg: #1E1E2D;
            --primary-color: #3699FF;
            --text-primary: #ffffff;
            --text-muted: #B5B5C3;
            --transition-speed: 0.3s;
        }

        /* Sidebar Styling */
        .main-sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 1rem !important;
            background: var(--header-bg);
        }

        .brand-link .brand-image {
            border-radius: 8px;
            margin-right: 0.75rem;
        }

        .brand-text {
            color: var(--text-primary);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* User Panel */
        .user-panel {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .user-panel .info a {
            color: var(--text-primary);
            font-weight: 600;
            transition: color var(--transition-speed);
        }

        .user-panel .info a:hover {
            color: var(--primary-color);
        }

        /* Sidebar Navigation */
        .nav-sidebar .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-sidebar .nav-link {
            color: #9899AC !important;
            padding: 0.75rem 1rem;
            margin: 0 0.5rem;
            border-radius: 0.475rem;
            transition: all var(--transition-speed);
        }

        .nav-sidebar .nav-link:hover {
            color: var(--text-primary) !important;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .nav-sidebar .nav-link.active {
            color: var(--primary-color) !important;
            background-color: rgba(54, 153, 255, 0.15) !important;
        }

        .nav-sidebar .nav-link i {
            margin-right: 0.75rem;
        }

        /* Header Styling */
        .main-header {
            background: var(--header-bg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .main-header .navbar-nav .nav-link {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .main-header .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .login-user {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
        }

        /* Footer Styling */
        .main-footer {
            background: var(--header-bg) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            padding: 1rem;
        }

        .main-footer a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color var(--transition-speed);
        }

        .main-footer a:hover {
            color: #187DE4;
        }

        /* Content Wrapper Adjustments */
        .content-wrapper {
            background: #f5f8fa;
            padding-top: 60px;
        }

        .card {
            border: none;
            border-radius: 0.475rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #ebedf3;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .brand-text {
                font-size: 1.1rem;
            }
            
            .login-user {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include './config/client_header.php'; ?>
        <?php include './config/client_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Quick Stats -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo count($appointments); ?></h3>
                                    <p>Total Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                        <?php
                        $pendingCount = 0;
                        $approvedCount = 0;
                        $completedCount = 0;
                        $today = date('Y-m-d');
                        
                        foreach ($appointments as $appointment) {
                            if ($appointment['status'] == 'pending') $pendingCount++;
                            if ($appointment['status'] == 'approved') $approvedCount++;
                            if ($appointment['status'] == 'completed') $completedCount++;
                        }
                        ?>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $pendingCount; ?></h3>
                                    <p>Pending Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $approvedCount; ?></h3>
                                    <p>Approved Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo $completedCount; ?></h3>
                                    <p>Completed Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Upcoming Appointments -->
                    <?php
                    $upcomingApproved = array_filter($appointments, function($apt) use ($today) {
                        return $apt['status'] == 'approved' && $apt['appointment_date'] >= $today;
                    });
                    if (!empty($upcomingApproved)): 
                    ?>
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-check mr-2"></i>
                                Upcoming Approved Appointments
                            </h3>
                            <div class="card-tools">
                                <!-- Collapse Button -->
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Reason</th>
                                            <th>Notes</th>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Status Updates -->
                    <?php
                    $recentUpdates = array_filter($appointments, function($apt) {
                        return in_array($apt['status'], ['approved', 'completed', 'cancelled']) && 
                               strtotime($apt['last_modified']) >= strtotime('-7 days');
                    });
                    if (!empty($recentUpdates)): 
                    ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bell mr-2"></i>
                                Recent Status Updates
                            </h3>
                            <div class="card-tools">
                                <!-- Collapse Button -->
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
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
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- All Appointments History -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                All Appointments History
                            </h3>
                            <div class="card-tools">
                                <!-- Collapse Button -->
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <p>No appointments found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
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
                                                                'pending' => 'warning',
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
                                                        <?php if ($appointment['status'] == 'pending'): ?>
                                                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                               class="btn btn-primary btn-sm">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm delete-appointment" 
                                                                    data-id="<?php echo $appointment['id']; ?>"
                                                                    data-date="<?php echo $appointment['formatted_date']; ?>"
                                                                    data-time="<?php echo $appointment['formatted_time']; ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
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
            </section>
        </div>

        <?php include './config/client_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    
    <script>
        $(function() {
            // Initialize SweetAlert2
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
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

            // Handle delete appointment
            $('.delete-appointment').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const date = $(this).data('date');
                const time = $(this).data('time');

                Swal.fire({
                    title: 'Delete Appointment?',
                    html: `Are you sure you want to delete your appointment on <br><strong>${date}</strong> at <strong>${time}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, keep it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete_appointment.php?id=${id}`;
                    }
                });
            });
        });
    </script>
</body>
</html> 