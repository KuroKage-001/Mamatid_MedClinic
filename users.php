<?php
// Include the database connection
include './config/connection.php';

$message = '';

// Process the user registration form submission
if (isset($_POST['save_user'])) {
    // Get form inputs
    $displayName = $_POST['display_name'];
    $userName    = $_POST['user_name'];
    $password    = $_POST['password'];
    
    // Encrypt password using MD5 (Consider using password_hash for better security)
    $encryptedPassword = md5($password);

    // Handle file upload for profile picture
    $baseName   = basename($_FILES["profile_picture"]["name"]);
    $targetFile = time() . $baseName;
    $status     = move_uploaded_file($_FILES["profile_picture"]["tmp_name"], 'user_images/' . $targetFile);

    if ($status) {
        try {
            // Begin transaction for atomicity
            $con->beginTransaction();

            // Insert new user record
            $query = "INSERT INTO `users`(`display_name`, `user_name`, `password`, `profile_picture`)
                      VALUES('$displayName', '$userName', '$encryptedPassword', '$targetFile');";
            $stmtUser = $con->prepare($query);
            $stmtUser->execute();

            // Commit transaction
            $con->commit();
            $message = 'User registered successfully';
            
            // Redirect with success message
            header("location:users.php?message=" . urlencode($message));
            exit;
        } catch (PDOException $ex) {
            // Rollback transaction on error and output debug info (not recommended for production)
            $con->rollback();
            echo $ex->getTraceAsString();
            echo $ex->getMessage();
            exit;
        }
    } else {
        $message = 'A problem occurred in image uploading.';
        header("location:users.php?message=" . urlencode($message) . "&type=error");
        exit;
    }
}

// Query to get all users ordered by display name
$queryUsers = "SELECT `id`, `display_name`, `user_name`, `profile_picture` 
               FROM `users` 
               ORDER BY `display_name` ASC;";
try {
    $stmtUsers = $con->prepare($queryUsers);
    $stmtUsers->execute();
} catch (PDOException $ex) {
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Users - Mamatid Health Center System</title>

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
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Form Styling */
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

    .btn-success {
      background: linear-gradient(135deg, var(--success-color) 0%, #26C6DA 100%);
      border: none;
    }

    .btn-danger {
      background: linear-gradient(135deg, var(--danger-color) 0%, #FF647C 100%);
      border: none;
    }

    .btn-success:hover, .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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

    /* User Image Styling */
    .user-img {
      width: 3em;
      height: 3em;
      object-fit: cover;
      object-position: center;
      border-radius: 50%;
      border: 3px solid white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
      transition: transform var(--transition-speed);
    }

    .user-img:hover {
      transform: scale(1.1);
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

      .form-control {
        height: calc(2.2rem + 2px);
        padding: 0.5rem 0.75rem;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php include './config/header.php'; include './config/sidebar.php'; ?>
    
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row align-items-center mb-4">
            <div class="col-12 col-md-6" style="padding-left: 20px;">
              <h1>Users Management</h1>
            </div>
          </div>
        </div>
      </section>
      
      <section class="content">
        <div class="container-fluid">
          <!-- Add Users Form Card -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Add Users</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="display_name" class="form-label">Display Name</label>
                    <input type="text" id="display_name" name="display_name" required 
                           class="form-control" placeholder="Enter display name" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="user_name" class="form-label">Username</label>
                    <input type="text" id="user_name" name="user_name" required 
                           class="form-control" placeholder="Enter username" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="form-control" placeholder="Enter password" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" required 
                           class="form-control" accept="image/*" />
                  </div>
                  <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="d-none d-sm-block">&nbsp;</label>
                    <button type="submit" id="save_user" name="save_user" 
                            class="btn btn-primary w-100">
                      <i class="fas fa-save mr-2"></i>Save
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          
          <!-- All Users Table Card -->
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">All Users</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="all_users" class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th class="text-center">S.No</th>
                      <th class="text-center">Picture</th>
                      <th>Display Name</th>
                      <th>Username</th>
                      <th class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $serial = 0;
                    while ($row = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
                        $serial++;
                    ?>
                    <tr>
                      <td class="text-center"><?php echo $serial; ?></td>
                      <td class="text-center">
                        <img class="user-img" src="user_images/<?php echo $row['profile_picture']; ?>" 
                             alt="User Picture">
                      </td>
                      <td><?php echo $row['display_name']; ?></td>
                      <td><?php echo $row['user_name']; ?></td>
                      <td class="text-center">
                        <?php 
                          $buttonClass = ($_SESSION['user_id'] == $row['id']) ? 'btn-success' : 'btn-danger';
                          $isDisabled = ($_SESSION['user_id'] != $row['id']) ? 'disabled' : '';
                        ?>
                        <a href="update_user.php?user_id=<?php echo $row['id']; ?>" 
                           class="btn <?php echo $buttonClass; ?> btn-sm" 
                           <?php echo $isDisabled; ?>>
                          <i class="fa fa-edit"></i>
                        </a>
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
      // Initialize DataTable with modern styling
      $('#all_users').DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
        language: {
          search: "",
          searchPlaceholder: "Search users..."
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
      });

      // Initialize Toast
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

      // Show message if redirected with message parameter
      const urlParams = new URLSearchParams(window.location.search);
      const message = urlParams.get('message');
      const type = urlParams.get('type') || 'success';
      
      if (message) {
        Toast.fire({
          icon: type,
          title: message
        });
      }

      // Username availability check
      $("#user_name").blur(function() {
        var userName = $(this).val().trim();
        $(this).val(userName);
        if (userName !== '') {
          $.ajax({
            url: "ajax/check_user_name.php",
            type: 'GET',
            data: { 'user_name': userName },
            cache: false,
            async: false,
            success: function (count, status, xhr) {
              if (count > 0) {
                Toast.fire({
                  icon: 'error',
                  title: 'This username is already taken'
                });
                $("#save_user").attr("disabled", "disabled");
              } else {
                $("#save_user").removeAttr("disabled");
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
    });

    // Highlight current menu
    showMenuSelected("#mnu_users", "");
  </script>
</body>
</html>
