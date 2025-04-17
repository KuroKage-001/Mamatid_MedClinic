<?php
include './config/connection.php';
include './common_service/common_functions.php';

// Initialize message variable to provide feedback to the user
$message = '';

if(isset($_POST['submit'])) {
  // Retrieve form inputs
  $medicineId = $_POST['medicine'];
  $packing = $_POST['packing'];
  
  // Construct insert query for new medicine detail
  // NOTE: Consider using parameterized queries here to avoid SQL injection risks
  $query = "insert into `medicine_details` (`medicine_id`, `packing`) values($medicineId, '$packing');";
  try {
    // Begin transaction to ensure data consistency
    $con->beginTransaction();
    
    // Prepare and execute the query
    $stmtDetails = $con->prepare($query);
    $stmtDetails->execute();

    // Commit the transaction on successful execution
    $con->commit();

    $message = 'Packing saved successfully.';
  } catch(PDOException $ex) {
    // Roll back transaction if any error occurs
    $con->rollback();

    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
 }
 // Redirect to congratulation page with a feedback message in the query string
 header("location:congratulation.php?goto_page=medicine_details.php&message=$message");
 exit;
}

// Fetch list of medicines for the dropdown selection in the form
$medicines = getMedicines($con);

// Query to retrieve medicine details along with medicine name
// Note: Consider using an explicit JOIN syntax for clarity
$query = "select `m`.`medicine_name`,
`md`.`id`, `md`.`packing`,  `md`.`medicine_id`,
COALESCE(mi.quantity, 0) as current_stock,
CASE 
    WHEN COALESCE(mi.quantity, 0) <= 10 THEN 'LOW'
    WHEN COALESCE(mi.quantity, 0) <= 20 THEN 'MEDIUM'
    ELSE 'GOOD'
END as stock_status
from `medicines` as `m` 
JOIN `medicine_details` as `md` ON `m`.`id` = `md`.`medicine_id`
LEFT JOIN medicine_inventory mi ON md.id = mi.medicine_details_id
order by `m`.`id` asc, `md`.`id` asc;";

try {
    // Prepare and execute the query
    $stmtDetails = $con->prepare($query);
    $stmtDetails->execute();
} catch(PDOException $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php';?>
    <?php include './config/data_tables_css.php';?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Medicine Details - Mamatid Health Center System</title>
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

        .btn-info {
            background: linear-gradient(135deg, var(--info-color) 0%, #9F7AEA 100%);
            border: none;
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(137, 80, 252, 0.4);
            color: white;
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

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: calc(2.5rem + 2px);
            border: 2px solid #e4e6ef;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.5rem + 2px);
            padding-left: 1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.5rem + 2px);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-buttons .btn {
            padding: 0.5rem;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Toast Styling */
        .swal2-toast {
            background: white !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1) !important;
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

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                height: auto;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/header.php';
        include './config/sidebar.php';?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6" style="padding-left: 20px;">
                            <h1>Medicine Details</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Add Medicine Details Card -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Medicine Details</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Select Medicine</label>
                                            <select id="medicine" name="medicine" class="form-control select2" required="required">
                                                <?php echo $medicines;?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Packing</label>
                                            <input id="packing" name="packing" class="form-control" required="required" 
                                                   placeholder="Enter packing details"/>
                                        </div>
                                    </div>
                                    <div class="col-lg-1 col-md-2 col-sm-4 col-xs-12">
                                        <div class="form-group">
                                            <label class="form-label d-none d-md-block">&nbsp;</label>
                                            <button type="submit" id="submit" name="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Save
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Medicine Details List Card -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Medicine Details List</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="medicine_details" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center">S.No</th>
                                            <th>Medicine Name</th>
                                            <th>Packing</th>
                                            <th class="text-center">Current Stock</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $serial = 0;
                                        while($row = $stmtDetails->fetch(PDO::FETCH_ASSOC)) {
                                            $serial++;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $serial; ?></td>
                                            <td><?php echo $row['medicine_name']; ?></td>
                                            <td><?php echo $row['packing']; ?></td>
                                            <td class="text-center"><?php echo $row['current_stock']; ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-<?php 
                                                    echo $row['stock_status'] == 'LOW' ? 'danger' : 
                                                        ($row['stock_status'] == 'MEDIUM' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo $row['stock_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="update_medicine_details.php?medicine_id=<?php echo $row['medicine_id']; ?>&medicine_detail_id=<?php echo $row['id']; ?>&packing=<?php echo $row['packing']; ?>" 
                                                       class="btn btn-primary" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="medicine_inventory.php?medicine_detail_id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-info" title="View Inventory">
                                                        <i class="fa fa-boxes"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Initialize DataTable
            $("#medicine_details").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search medicine details..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#medicine_details_wrapper .col-md-6:eq(0)');

            // Initialize Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Show message if exists
            var message = '<?php echo $message; ?>';
            if(message !== '') {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }
        });

        // Highlight current menu
        showMenuSelected("#mnu_medicines", "#mi_medicine_details");
    </script>
</body>
</html>
