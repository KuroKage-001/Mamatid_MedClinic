<?php
include './config/connection.php';

// Initialize the message variable for user feedback
$message = '';
if(isset($_POST['save_medicine'])) {
  $message = '';
  // Retrieve and sanitize the medicine name input
  $medicineName = trim($_POST['medicine_name']);
  $medicineName = ucwords(strtolower($medicineName)); // Capitalizes each word
  if($medicineName != '') {
    $query = "INSERT INTO `medicines`(`medicine_name`)
   VALUES('$medicineName');";
   
   try {
    // Begin a transaction to ensure data consistency
    $con->beginTransaction();

    $stmtMedicine = $con->prepare($query);
    $stmtMedicine->execute();

    // Commit the transaction if execution is successful
    $con->commit();

    $message = 'Medicine added successfully.';
  } catch(PDOException $ex) {
    // Roll back the transaction on error
    $con->rollback();

    echo $ex->getMessage();
    echo $ex->getTraceAsString();
    exit;
  }
} else {
  $message = 'Empty form can not be submitted.';
}
// Redirect to the congratulation page with a feedback message via URL parameter
header("Location:congratulation.php?goto_page=medicines.php&message=$message");
exit;
}

try {
  // Retrieve all medicines ordered by name (ascending)
  $query = "select `id`, `medicine_name` from `medicines` 
  order by `medicine_name` asc;";
  $stmt = $con->prepare($query);
  $stmt->execute();
} catch(PDOException $ex) {
  echo $ex->getMessage();
  echo $e->getTraceAsString();
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php';?>
    <?php include './config/data_tables_css.php';?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Medicines - Mamatid Health Center System</title>
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

        .btn-primary:disabled {
            background: #e4e6ef;
            transform: none;
            box-shadow: none;
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
                            <h1>Medicines</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Add Medicine Card -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Medicine</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Medicine Name</label>
                                            <input type="text" id="medicine_name" name="medicine_name" required="required"
                                                   class="form-control" placeholder="Enter medicine name"/>
                                        </div>
                                    </div>
                                    <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2">
                                        <div class="form-group">
                                            <label class="form-label d-none d-md-block">&nbsp;</label>
                                            <button type="submit" id="save_medicine" name="save_medicine" 
                                                    class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Save
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Medicine List Card -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Medicine List</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="all_medicines" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="10%">S.No</th>
                                            <th width="80%">Medicine Name</th>
                                            <th class="text-center" width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $serial = 0;
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $serial++;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $serial;?></td>
                                            <td><?php echo $row['medicine_name'];?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="update_medicine.php?id=<?php echo $row['id'];?>" 
                                                       class="btn btn-primary" title="Edit">
                                                        <i class="fa fa-edit"></i>
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
            // Initialize DataTable
            $("#all_medicines").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
                language: {
                    search: "",
                    searchPlaceholder: "Search medicines..."
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }).buttons().container().appendTo('#all_medicines_wrapper .col-md-6:eq(0)');

            // Initialize Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Medicine name duplicate check
            $("#medicine_name").blur(function() {
                var medicineName = $(this).val().trim();
                $(this).val(medicineName);

                if(medicineName !== '') {
                    $.ajax({
                        url: "ajax/check_medicine_name.php",
                        type: 'GET',
                        data: {
                            'medicine_name': medicineName
                        },
                        cache: false,
                        async: false,
                        success: function (count, status, xhr) {
                            if(count > 0) {
                                Toast.fire({
                                    icon: 'warning',
                                    title: 'This medicine name already exists. Please choose another name.'
                                });
                                $("#save_medicine").attr("disabled", "disabled");
                            } else {
                                $("#save_medicine").removeAttr("disabled");
                            }
                        },
                        error: function (jqXhr, textStatus, errorMessage) {
                            Toast.fire({
                                icon: 'error',
                                title: errorMessage
                            });
                        }
                    });
                }
            });

            // Show message if exists
            var message = '<?php echo $message;?>';
            if(message !== '') {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }
        });

        // Highlight current menu
        showMenuSelected("#mnu_medicines", "#mi_medicines");
    </script>
</body>
</html>
