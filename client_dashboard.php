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
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_ui/header.php'; ?>
        <?php include './config/client_ui/sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6">
                            <h1>Client Dashboard</h1>
                        </div>
                        <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
                            <span id="datetime" class="d-inline-block"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Stats Overview -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-12 mb-4">
                            <div class="small-box bg-success">
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
                        <div class="col-lg-3 col-md-6 col-12 mb-4">
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
                        <div class="col-lg-3 col-md-6 col-12 mb-4">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo $approvedCount; ?></h3>
                                    <p>Approved Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 mb-4">
                            <div class="small-box bg-danger">
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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-check mr-2"></i>
                                Upcoming Approved Appointments
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bell mr-2"></i>
                                Recent Status Updates
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
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
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- All Appointments History -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                All Appointments History
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <p class="text-muted">No appointments found.</p>
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
                                                    <div class="btn-group">
                                                        <a href="update_appointment.php?id=<?php echo $appointment['id']; ?>" 
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
            </section>
        </div>

        <?php include './config/client_ui/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    
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