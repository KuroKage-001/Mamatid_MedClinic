<?php
include './config/db_connection.php';
include './common_service/common_functions.php';

$message = '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location:general_family_members.php");
    exit;
}

$errors = array();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $date = trim($_POST['date'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($date)) {
        $errors[] = "Date is required";
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            // Convert date from MM/DD/YYYY to YYYY-MM-DD
            $date_obj = DateTime::createFromFormat('m/d/Y', $date);
            if (!$date_obj) {
                throw new Exception("Invalid date format");
            }
            $formatted_date = $date_obj->format('Y-m-d');

            // Start transaction
            $con->beginTransaction();

            // Prepare the update query with parameterized statement
            $query = "UPDATE family_members SET 
                     name = :name,
                     date = :date
                     WHERE id = :id";

            $stmt = $con->prepare($query);
            
            // Bind parameters
            $params = [
                ':name' => ucwords(strtolower($name)),
                ':date' => $formatted_date,
                ':id' => $id
            ];

            // Execute the update
            $result = $stmt->execute($params);

            if ($result) {
                $con->commit();
                header("Location: general_family_members.php?message=" . urlencode("Record updated successfully"));
                exit;
            } else {
                throw new Exception("Failed to update record");
            }
        } catch (Exception $e) {
            $con->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Fetch existing record
try {
    $query = "SELECT id, name, DATE_FORMAT(date, '%m/%d/%Y') as date
             FROM family_members 
             WHERE id = :id";
    
    $stmt = $con->prepare($query);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        header("Location: general_family_members.php?message=" . urlencode("Record not found"));
        exit;
    }
} catch (PDOException $e) {
    header("Location: general_family_members.php?message=" . urlencode($e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Update Family Member - Mamatid Health Center System</title>
  <style>
    :root {
        --primary-color: #3699FF;
        --secondary-color: #6993FF;
        --success-color: #1BC5BD;
        --info-color: #8950FC;
        --warning-color: #FFA800;
        --danger-color: #F64E60;
        --light-color: #F3F6F9;
        --dark-color: #1a1a2d;
        --transition-speed: 0.3s;
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
    }

    .card-body {
        padding: 2rem;
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

    /* Date Picker Styling */
    .input-group-text {
        border-radius: 8px;
        border: 2px solid #e4e6ef;
        background-color: #f5f8fa;
        color: var(--dark-color);
    }

    .input-group > .form-control {
        border-top-right-radius: 8px !important;
        border-bottom-right-radius: 8px !important;
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
        color: #fff;
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        border: none;
        color: #fff;
    }

    .btn-danger {
        background: linear-gradient(135deg, var(--danger-color) 0%, #ee2d41 100%);
        border: none;
        color: #fff;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .action-buttons .btn {
        min-width: 120px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Alert Styling */
    .alert {
        border-radius: 8px;
        border: none;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .alert-danger {
        background-color: #fff5f8;
        color: var(--danger-color);
    }

    /* Content Header */
    .content-header {
        padding: 20px 0;
    }

    .content-header h1 {
        font-size: 2rem;
        font-weight: 600;
        color: #1a1a1a;
        margin: 0;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php 
      include './config/admin_header.php';
      include './config/admin_sidebar.php'; 
    ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Update Family Member Record</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-edit mr-2"></i>Edit Family Member Record
            </h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" id="updateForm">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" id="name" name="name" required
                         class="form-control" value="<?php echo htmlspecialchars($record['name']); ?>"/>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Date</label>
                  <div class="input-group date" id="date" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" 
                           data-target="#date" name="date"
                           data-toggle="datetimepicker" autocomplete="off" required
                           value="<?php echo htmlspecialchars($record['date']); ?>"/>
                    <div class="input-group-append" data-target="#date" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="action-buttons">
                <a href="general_family_members.php" class="btn btn-secondary">
                  <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="button" class="btn btn-danger" id="deleteBtn">
                  <i class="fas fa-trash"></i> Delete
                </button>
                <button type="submit" id="update_family_member" name="update_family_member" class="btn btn-primary">
                  <i class="fas fa-save"></i> Update
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>

    <?php include './config/footer.php'; ?>
    
    <?php include './config/site_js_links.php'; ?>
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

    <script>
      $(document).ready(function() {
        // Initialize date picker
        $('#date').datetimepicker({
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
              window.location.href = 'actions/delete_family_member.php?id=<?php echo $id; ?>';
            }
          });
        });

        // Form validation and submission
        $('#updateForm').submit(function(e) {
          e.preventDefault();

          // Basic form validation
          let isValid = true;
          const name = $('#name').val().trim();
          const date = $('#date input').val().trim();

          // Clear previous error messages
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();

          // Validate each field
          if (!name) {
            isValid = false;
            $('#name').addClass('is-invalid')
              .after('<div class="invalid-feedback">Name is required</div>');
          }

          if (!date) {
            isValid = false;
            $('#date input').addClass('is-invalid')
              .after('<div class="invalid-feedback">Date is required</div>');
          }

          if (isValid) {
            // Show confirmation dialog
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
          } else {
            // Scroll to first error
            const firstError = $('.is-invalid').first();
            if (firstError.length) {
              $('html, body').animate({
                scrollTop: firstError.offset().top - 100
              }, 500);
            }
          }
        });

        // Show menu
        showMenuSelected("#mnu_patients", "#mi_family_members");
      });
    </script>
</body>
</html> 