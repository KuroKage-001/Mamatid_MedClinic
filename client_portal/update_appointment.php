<?php
// Include client authentication check (this handles session isolation automatically)
require_once '../system/utilities/check_client_auth.php';

include '../config/db_connection.php';

$message = '';
$appointmentId = isset($_GET['id']) ? $_GET['id'] : 0;

// Get client ID from session using safe getter
$clientId = getClientSessionVar('client_id');

// Fetch appointment details
$query = "SELECT * FROM admin_clients_appointments WHERE id = ? AND patient_name = (SELECT full_name FROM clients_user_accounts WHERE id = ?)";
$stmt = $con->prepare($query);
$stmt->execute([$appointmentId, $clientId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// If appointment not found or doesn't belong to client, redirect to dashboard
if (!$appointment) {
    header("location:client_dashboard.php");
    exit;
}

// Handle appointment update
if (isset($_POST['update_appointment'])) {
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];

    try {
        $con->beginTransaction();

        $query = "UPDATE admin_clients_appointments 
                 SET appointment_date = ?, 
                     appointment_time = ?, 
                     reason = ?
                 WHERE id = ? AND patient_name = (SELECT full_name FROM clients_user_accounts WHERE id = ?)";

        $stmt = $con->prepare($query);
        $stmt->execute([
            $appointmentDate,
            $appointmentTime,
            $reason,
            $appointmentId,
            $clientId
        ]);

        $con->commit();
        $message = 'Appointment updated successfully!';
        
        // Redirect to dashboard
        header("location:client_dashboard.php?message=" . urlencode($message));
        exit;
    } catch(PDOException $ex) {
        $con->rollback();
        $message = 'An error occurred. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update Appointment - Mamatid Health Center</title>
    <?php include '../config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">

    <style>
        .main-sidebar { background-color: #3c4b64 !important }
        .nav-sidebar .nav-item > .nav-link { color: #fff !important; }
        .card-primary.card-outline { border-top: 3px solid #3c4b64; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark navbar-light fixed-top">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <a href="#" class="navbar-brand">
                <span class="brand-text font-weight-light">Mamatid Health Center System</span>
            </a>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <div class="login-user text-light font-weight-bolder">Hello, <?php echo getClientSessionVar('client_name'); ?>!</div>
                </li>
                <li class="nav-item ml-3">
                    <a href="client_logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="client_dashboard.php" class="brand-link">
                <img src="../dist/img/logo01.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Client Portal</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="info">
                        <a href="#" class="d-block"><?php echo getClientSessionVar('client_name'); ?></a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="client_dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="client_appointment_booking.php" class="nav-link">
                                <i class="nav-icon fas fa-calendar-plus"></i>
                                <p>Book Appointment</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Update Appointment</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Edit Form Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Update Appointment Details</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-info">
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Date</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="far fa-calendar-alt"></i>
                                                    </span>
                                                </div>
                                                <input type="date" class="form-control" 
                                                       name="appointment_date" required
                                                       min="<?php echo date('Y-m-d'); ?>"
                                                       value="<?php echo $appointment['appointment_date']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Time</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="far fa-clock"></i>
                                                    </span>
                                                </div>
                                                <select class="form-control" name="appointment_time" required>
                                                    <option value="">Select Time</option>
                                                    <?php
                                                    $start = 8; // 8 AM
                                                    $end = 17; // 5 PM
                                                    for ($hour = $start; $hour <= $end; $hour++) {
                                                        $time = sprintf('%02d:00', $hour);
                                                        $selected = ($appointment['appointment_time'] == $time) ? 'selected' : '';
                                                        echo "<option value='$time' $selected>" . date('h:i A', strtotime($time)) . "</option>";
                                                        
                                                        $time = sprintf('%02d:30', $hour);
                                                        $selected = ($appointment['appointment_time'] == $time) ? 'selected' : '';
                                                        echo "<option value='$time' $selected>" . date('h:i A', strtotime($time)) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Reason for Visit</label>
                                    <textarea class="form-control" name="reason" rows="4" placeholder="Please describe the reason for your visit..."><?php echo htmlspecialchars($appointment['reason']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="update_appointment" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Appointment
                                    </button>
                                    <a href="client_dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php include '../config/site_js_links.php'; ?>
</body>
</html> 