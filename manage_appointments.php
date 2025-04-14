<?php
include './config/connection.php';

$message = '';

// Handle appointment status updates
if (isset($_POST['update_status'])) {
    $appointmentId = $_POST['appointment_id'];
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'];

    try {
        $query = "UPDATE appointments SET status = ?, notes = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$newStatus, $notes, $appointmentId]);
        $message = "Appointment status updated successfully!";
    } catch (PDOException $ex) {
        $message = "Error updating appointment: " . $ex->getMessage();
    }
}

// Fetch all appointments
$query = "SELECT * FROM appointments ORDER BY appointment_date ASC, appointment_time ASC";
$stmt = $con->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <?php include './config/data_tables_css.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Manage Appointments - Mamatid Health Center System</title>
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
                    <div class="row mb-2">
                        <div class="col-sm-6">
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
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>

                <!-- Appointments Table Card -->
                <div class="card card-outline card-primary rounded-0 shadow">
                    <div class="card-header">
                        <h3 class="card-title">All Appointments</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="appointments" class="table table-striped dataTable table-bordered dtr-inline">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Phone</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
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
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-toggle="modal" 
                                                data-target="#updateModal<?php echo $row['id']; ?>">
                                            Update
                                        </button>
                                    </td>
                                </tr>

                                <!-- Update Modal for each appointment -->
                                <div class="modal fade" id="updateModal<?php echo $row['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Update Appointment Status</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select name="status" class="form-control" required>
                                                            <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>
                                                                Pending
                                                            </option>
                                                            <option value="approved" <?php echo $row['status'] == 'approved' ? 'selected' : ''; ?>>
                                                                Approved
                                                            </option>
                                                            <option value="completed" <?php echo $row['status'] == 'completed' ? 'selected' : ''; ?>>
                                                                Completed
                                                            </option>
                                                            <option value="cancelled" <?php echo $row['status'] == 'cancelled' ? 'selected' : ''; ?>>
                                                                Cancelled
                                                            </option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>Notes</label>
                                                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_status" class="btn btn-primary">Update</button>
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
            </section>
        </div>

        <?php include './config/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <?php include './config/data_tables_js.php'; ?>
    
    <script>
        $(function() {
            // Highlight the appointments menu item
            showMenuSelected("#mnu_appointments", "#mi_appointments");

            // Initialize DataTable
            $("#appointments").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#appointments_wrapper .col-md-6:eq(0)');

            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html> 