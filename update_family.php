<?php
include './config/db_connection.php';

// Check if ID is provided
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header("Location: general_family_planning.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);

    // Validate required fields
    if (empty($name) || empty($date) || empty($age) || empty($address)) {
        $error = "All fields are required.";
    } else {
        try {
            // Convert date from MM/DD/YYYY to YYYY-MM-DD
            $date_obj = DateTime::createFromFormat('m/d/Y', $date);
            if (!$date_obj) {
                throw new Exception("Invalid date format");
            }
            $formatted_date = $date_obj->format('Y-m-d');

            // Start transaction
            $con->beginTransaction();

            // Update record
            $query = "UPDATE family_planning SET 
                     name = :name,
                     date = :date,
                     age = :age,
                     address = :address
                     WHERE id = :id";

            $stmt = $con->prepare($query);
            $result = $stmt->execute([
                ':name' => ucwords(strtolower($name)),
                ':date' => $formatted_date,
                ':age' => $age,
                ':address' => ucwords(strtolower($address)),
                ':id' => $id
            ]);

            if ($result) {
                $con->commit();
                header("Location: general_family_planning.php?message=" . urlencode("Record updated successfully"));
                exit;
            } else {
                throw new Exception("Failed to update record");
            }
        } catch (Exception $e) {
            if ($con->inTransaction()) {
                $con->rollback();
            }
            $error = $e->getMessage();
        }
    }
}

// Fetch existing record
try {
    $query = "SELECT id, name, address, age, 
             DATE_FORMAT(date, '%m/%d/%Y') as date
             FROM family_planning 
             WHERE id = :id";
    
    $stmt = $con->prepare($query);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        header("Location: general_family_planning.php?message=" . urlencode("Record not found"));
        exit;
    }
} catch (Exception $e) {
    header("Location: general_family_planning.php?message=" . urlencode($e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">
    <title>Update Family Planning Record</title>
    <style>
        :root {
            --primary-color: #3699FF;
            --secondary-color: #6993FF;
            --success-color: #1BC5BD;
            --danger-color: #F64E60;
        }

        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .form-control {
            height: calc(2.5rem + 2px);
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            padding: 0.625rem 1rem;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .btn {
            padding: 0.65rem 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #FF647C 100%);
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .input-group-text {
            border-radius: 8px;
            background-color: #f3f6f9;
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
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/admin_header.php'; ?>
        <?php include './config/admin_sidebar.php'; ?>
        <div class="content-wrapper">
        <section class="content-header">
          <div class="container-fluid">
            <div class="row align-items-center mb-4">
              <div class="col-12 col-md-6" style="padding-left: 20px;">
                <h1>Update Family Planning Record</h1>
              </div>
            </div>
          </div>
        </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit mr-2"></i>Edit Record
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="updateForm">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="<?php echo htmlspecialchars($record['name']); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="date" class="form-label">Date</label>
                                        <div class="input-group date" id="datePicker" data-target-input="nearest">
                                            <input type="text" class="form-control datetimepicker-input" 
                                                   data-target="#datePicker" name="date"
                                                   value="<?php echo htmlspecialchars($record['date']); ?>" required>
                                            <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="age" class="form-label">Age</label>
                                        <input type="number" class="form-control" id="age" name="age"
                                               value="<?php echo htmlspecialchars($record['age']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address"
                                               value="<?php echo htmlspecialchars($record['address']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="float-right">
                                            <a href="general_family_planning.php" class="btn btn-secondary mr-2">
                                                <i class="fas fa-times mr-2"></i>Cancel
                                            </a>
                                            <button type="button" class="btn btn-danger mr-2" id="deleteBtn">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php include './config/footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize date picker
            $('#datePicker').datetimepicker({
                format: 'L',
                icons: {
                    time: 'fas fa-clock',
                    date: 'fas fa-calendar',
                    up: 'fas fa-arrow-up',
                    down: 'fas fa-arrow-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'fas fa-calendar-check',
                    clear: 'fas fa-trash',
                    close: 'fas fa-times'
                }
            });

            // Delete button handler
            $('#deleteBtn').click(function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This record will be permanently deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#F64E60',
                    cancelButtonColor: '#6e7881',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'actions/delete_family.php?id=<?php echo $id; ?>';
                    }
                });
            });

            // Form submission handler
            $('#updateForm').submit(function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Update Record',
                    text: "Are you sure you want to update this record?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3699FF',
                    cancelButtonColor: '#6e7881',
                    confirmButtonText: 'Yes, update it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });

            // Show menu
            showMenuSelected("#mnu_patients", "#mi_family_planning");
        });
    </script>
</body>
</html> 