<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection (connection.php already calls session_start())
include './config/connection.php';
include './common_service/common_functions.php';
require_once './common_service/role_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check if user is admin
requireAdmin();

$message = '';
$error = '';

// Handle form submission to save a new user
if (isset($_POST['save_user'])) {
    // Retrieve and sanitize form inputs
    $display_name = trim($_POST['display_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $user_name = trim($_POST['user_name']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    
    // Validation
    $errors = [];
    if (empty($display_name)) $errors[] = "Display name is required";
    if (empty($user_name)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($role)) $errors[] = "Role is required";
    
    // Check if username already exists
    if (empty($errors)) {
        $checkQuery = "SELECT COUNT(*) as count FROM users WHERE user_name = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->execute([$user_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if email already exists (if provided)
    if (empty($errors) && !empty($email)) {
        $checkQuery = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = md5($password);
            
            // Default profile picture
            $profile_picture = 'default_profile.jpg';
            
            // Prepare INSERT query
            $query = "INSERT INTO users (display_name, email, phone, user_name, password, role, status, profile_picture) 
                     VALUES (:display_name, :email, :phone, :user_name, :password, :role, :status, :profile_picture)";
            
            // Start transaction
            $con->beginTransaction();
            
            $stmt = $con->prepare($query);
            $result = $stmt->execute([
                ':display_name' => $display_name,
                ':email' => $email,
                ':phone' => $phone,
                ':user_name' => $user_name,
                ':password' => $hashed_password,
                ':role' => $role,
                ':status' => $status,
                ':profile_picture' => $profile_picture
            ]);
            
            if ($result) {
                $con->commit();
                $message = "User added successfully!";
                
                // Clear form fields after successful save
                $_POST = array();
            } else {
                throw new Exception("Failed to save user");
            }
        } catch (Exception $e) {
            $con->rollback();
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle user deletion via AJAX (already handled in delete_user.php)

// Handle status toggle via AJAX (already handled in toggle_user_status.php)

// Retrieve all users for the listing
try {
    $query = "SELECT id, display_name, email, phone, user_name, role, status, profile_picture,
                     DATE_FORMAT(created_at, '%d %b %Y') as created_at,
                     DATE_FORMAT(updated_at, '%d %b %Y') as updated_at
              FROM users
              ORDER BY created_at DESC";
    $stmt = $con->prepare($query);
    $stmt->execute();
} catch (PDOException $ex) {
    $error = $ex->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
  <link rel="stylesheet" href="system_styles/users.css">
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Users Management - Mamatid Health Center System</title>
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
    .form-control, .form-select {
      height: calc(2.5rem + 2px);
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all var(--transition-speed);
    }

    .form-control:focus, .form-select:focus {
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

    .btn-tool {
      color: var(--dark-color);
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

    /* Status Badge */
    .badge {
      padding: 0.5rem 0.75rem;
      font-weight: 500;
      border-radius: 6px;
    }

    .badge-success {
      background-color: rgba(27, 197, 189, 0.1);
      color: var(--success-color);
    }

    .badge-danger {
      background-color: rgba(246, 78, 96, 0.1);
      color: var(--danger-color);
    }

    /* Role Badge */
    .role-badge {
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      font-weight: 500;
      font-size: 0.85rem;
    }

    .role-admin {
      background-color: rgba(137, 80, 252, 0.1);
      color: var(--info-color);
    }

    .role-doctor {
      background-color: rgba(255, 168, 0, 0.1);
      color: var(--warning-color);
    }

    .role-health_worker {
      background-color: rgba(54, 153, 255, 0.1);
      color: var(--primary-color);
    }

    /* Profile Picture */
    .user-img {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      object-fit: cover;
    }

    /* Alert Styling */
    .alert {
      border-radius: 8px;
      border: none;
      padding: 1rem 1.5rem;
    }

    .alert-success {
      background-color: rgba(27, 197, 189, 0.1);
      color: var(--success-color);
      border-left: 4px solid var(--success-color);
    }

    .alert-danger {
      background-color: rgba(246, 78, 96, 0.1);
      color: var(--danger-color);
      border-left: 4px solid var(--danger-color);
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

    /* Action Buttons Group */
    .action-buttons {
      display: flex;
      gap: 0.5rem;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 45px;
      height: 24px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: var(--success-color);
    }

    input:checked + .slider:before {
      transform: translateX(21px);
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
    }

    /* Modern Export Actions Inline CSS */
    .dt-button-collection {
      display: none !important;
    }

    .export-container {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 15px;
    }

    /* Elegant Export Options - Non-Button Design */
    .export-action-btn {
      display: inline-flex !important;
      align-items: center !important;
      gap: 10px !important;
      padding: 12px 18px !important;
      font-size: 0.875rem !important;
      font-weight: 600 !important;
      text-decoration: none !important;
      border-radius: 12px !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
      cursor: pointer !important;
      position: relative !important;
      overflow: hidden !important;
      border: 2px solid transparent !important;
      background: rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(10px) !important;
      -webkit-backdrop-filter: blur(10px) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
      text-transform: none !important;
      letter-spacing: 0.3px !important;
    }

    .export-action-btn::before {
      content: '' !important;
      position: absolute !important;
      top: 0 !important;
      left: -100% !important;
      width: 100% !important;
      height: 100% !important;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent) !important;
      transition: left 0.6s ease !important;
    }

    .export-action-btn:hover::before {
      left: 100% !important;
    }

    .export-action-btn:hover {
      transform: translateY(-3px) scale(1.02) !important;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15) !important;
      border-color: currentColor !important;
    }

    .export-action-btn:active {
      transform: translateY(-1px) scale(1.01) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
    }

    .export-action-btn i {
      font-size: 1rem !important;
      transition: all 0.3s ease !important;
      opacity: 0.9 !important;
      flex-shrink: 0 !important;
    }

    .export-action-btn:hover i {
      transform: scale(1.15) rotate(5deg) !important;
      opacity: 1 !important;
    }

    /* Sophisticated Color Schemes for Each Export Type */
    .export-copy-btn {
      color: #6366F1 !important;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05)) !important;
    }

    .export-copy-btn:hover {
      color: #4F46E5 !important;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(99, 102, 241, 0.25) !important;
    }

    .export-csv-btn {
      color: #10B981 !important;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)) !important;
    }

    .export-csv-btn:hover {
      color: #059669 !important;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25) !important;
    }

    .export-excel-btn {
      color: #22C55E !important;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05)) !important;
    }

    .export-excel-btn:hover {
      color: #16A34A !important;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(34, 197, 94, 0.25) !important;
    }

    .export-pdf-btn {
      color: #EF4444 !important;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)) !important;
    }

    .export-pdf-btn:hover {
      color: #DC2626 !important;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25) !important;
    }

    .export-print-btn {
      color: #8B5CF6 !important;
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05)) !important;
    }

    .export-print-btn:hover {
      color: #7C3AED !important;
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(139, 92, 246, 0.08)) !important;
      box-shadow: 0 8px 30px rgba(139, 92, 246, 0.25) !important;
    }

    /* Hide default DataTable buttons */
    .dt-buttons {
      display: none !important;
    }

    /* Custom layout for DataTable wrapper */
    #all_users_wrapper .row:first-child {
      margin-bottom: 15px;
    }

    #all_users_wrapper .dataTables_filter {
      float: left !important;
      text-align: left !important;
    }

    #all_users_wrapper .dataTables_filter input {
      width: 300px;
      border-radius: 8px;
      border: 2px solid #e4e6ef;
      padding: 0.625rem 1rem;
      font-size: 1rem;
      transition: all 0.3s;
    }

    #all_users_wrapper .dataTables_filter input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
    }

    /* Responsive Design for Modern Export Options */
    @media (max-width: 768px) {
      .export-container {
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
      }

      .export-action-btn {
        padding: 10px 14px !important;
        font-size: 0.8125rem !important;
        gap: 8px !important;
        border-radius: 10px !important;
      }

      .export-action-btn i {
        font-size: 0.9rem !important;
      }
    }

    @media (max-width: 576px) {
      .export-container {
        gap: 6px;
        flex-direction: column;
        align-items: stretch;
      }

      .export-action-btn {
        padding: 8px 12px !important;
        font-size: 0.75rem !important;
        gap: 6px !important;
        border-radius: 8px !important;
        justify-content: center !important;
      }

      .export-action-btn i {
        font-size: 0.85rem !important;
      }

      .export-action-btn:hover {
        transform: translateY(-2px) scale(1.01) !important;
      }

      #all_users_wrapper .dataTables_filter input {
        width: 100%;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
  <div class="wrapper">
    <?php include './config/admin_header.php'; include './config/admin_sidebar.php'; ?>
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
        <!-- Display Messages -->
        <?php if ($message): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
          </div>
        <?php endif; ?>

        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Add New User</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post" id="userForm">
              <div class="row">
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Display Name <span class="text-danger">*</span></label>
                    <input type="text" id="display_name" name="display_name" required
                           class="form-control" placeholder="Enter display name"
                           value="<?php echo isset($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''; ?>"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" id="user_name" name="user_name" required
                           class="form-control" placeholder="Enter username"
                           value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>"/>
                    <small class="text-muted">Username must be unique</small>
                  </div>
                </div>
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" id="password" name="password" required
                             class="form-control" placeholder="Enter password"/>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="form-control" placeholder="Enter email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone"
                           class="form-control" placeholder="Enter phone number"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select id="role" name="role" required class="form-control">
                      <option value="">Select Role</option>
                      <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                      <option value="doctor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                      <option value="health_worker" <?php echo (isset($_POST['role']) && $_POST['role'] == 'health_worker') ? 'selected' : ''; ?>>Health Worker</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" required class="form-control">
                      <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                      <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_user" name="save_user" 
                          class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save User
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <section class="content">
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
                    <th>Photo</th>
                    <th>Display Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                  <tr>
                    <td>
                      <img src="system/user_images/<?php echo htmlspecialchars($row['profile_picture']); ?>" 
                           alt="User" class="user-img">
                    </td>
                    <td><?php echo htmlspecialchars($row['display_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                    <td>
                      <span class="role-badge role-<?php echo $row['role']; ?>">
                        <?php echo getRoleDisplayName($row['role']); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($row['role'] !== 'admin' || $row['id'] == $_SESSION['user_id']): ?>
                        <label class="switch">
                          <input type="checkbox" class="status-toggle" 
                                 data-user-id="<?php echo $row['id']; ?>"
                                 <?php echo $row['status'] == 'active' ? 'checked' : ''; ?>
                                 <?php echo ($row['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                          <span class="slider"></span>
                        </label>
                      <?php else: ?>
                        <span class="badge badge-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                      <div class="action-buttons">
                        <?php if ($row['role'] === 'admin'): ?>
                          <a href="#" class="btn btn-info btn-sm" title="Administrator Account" 
                             onclick="return false;" style="cursor: default;">
                            <i class="fas fa-shield-alt"></i>
                          </a>
                        <?php else: ?>
                          <a href="update_user.php?id=<?php echo $row['id']; ?>" 
                             class="btn btn-primary btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                          </a>
                        <?php endif; ?>
                        <?php if ($row['id'] != $_SESSION['user_id'] && $row['role'] !== 'admin'): ?>
                          <button type="button" class="btn btn-danger btn-sm delete-user" 
                                  data-user-id="<?php echo $row['id']; ?>"
                                  data-user-name="<?php echo htmlspecialchars($row['display_name']); ?>"
                                  title="Delete">
                            <i class="fas fa-trash"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            
            <div class="export-container mt-4" id="exportContainer">
              <a href="#" class="export-action-btn export-copy-btn" id="btnCopy">
                <i class="fas fa-copy"></i>
                <span>Copy</span>
              </a>
              <a href="#" class="export-action-btn export-csv-btn" id="btnCSV">
                <i class="fas fa-file-csv"></i>
                <span>CSV</span>
              </a>
              <a href="#" class="export-action-btn export-excel-btn" id="btnExcel">
                <i class="fas fa-file-excel"></i>
                <span>Excel</span>
              </a>
              <a href="#" class="export-action-btn export-pdf-btn" id="btnPDF">
                <i class="fas fa-file-pdf"></i>
                <span>PDF</span>
              </a>
              <a href="#" class="export-action-btn export-print-btn" id="btnPrint">
                <i class="fas fa-print"></i>
                <span>Print</span>
              </a>
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
      var table = $("#all_users").DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        pageLength: 5,
        pagingType: "simple_numbers",
        dom: 'Bfrtip',
        buttons: [
          'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        language: {
          search: "",
          searchPlaceholder: "Search users...",
          paginate: {
            previous: "<i class='fas fa-chevron-left'></i>",
            next: "<i class='fas fa-chevron-right'></i>"
          }
        }
      });

      // Hide default buttons
      $('.dt-buttons').hide();

      // Custom export button handlers
      $('#btnCopy').click(function(e) {
        e.preventDefault();
        table.button('.buttons-copy').trigger();
        
        // Show toast notification
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true
        });
        
        Toast.fire({
          icon: 'success',
          title: 'Data copied to clipboard!'
        });
      });

      $('#btnCSV').click(function(e) {
        e.preventDefault();
        table.button('.buttons-csv').trigger();
      });

      $('#btnExcel').click(function(e) {
        e.preventDefault();
        table.button('.buttons-excel').trigger();
      });

      $('#btnPDF').click(function(e) {
        e.preventDefault();
        table.button('.buttons-pdf').trigger();
      });

      $('#btnPrint').click(function(e) {
        e.preventDefault();
        table.button('.buttons-print').trigger();
      });

      // Initialize Toast
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });

      // Show message if exists (from URL parameter)
      var urlParams = new URLSearchParams(window.location.search);
      var message = urlParams.get('message');
      if(message) {
        Toast.fire({
          icon: 'success',
          title: decodeURIComponent(message)
        });
      }

      // Form validation
      $('#userForm').submit(function(e) {
        console.log('Form submission triggered');
        const password = $('#password').val();
        console.log('Password length:', password.length);
        
        if (password.length < 6) {
          e.preventDefault();
          Toast.fire({
            icon: 'error',
            title: 'Password must be at least 6 characters long'
          });
          console.log('Form submission prevented - password too short');
          return false;
        }
        
        console.log('Form validation passed - submitting form');
        // Form should submit normally if we reach here
      });

      // Check username availability
      $('#user_name').blur(function() {
        const username = $(this).val();
        if (username.length > 0) {
          $.ajax({
            url: 'ajax/check_user_name.php',
            type: 'GET',
            data: { user_name: username },
            success: function(response) {
              if (parseInt(response) > 0) {
                $('#user_name').addClass('is-invalid');
                $('#user_name').after('<div class="invalid-feedback">Username already exists</div>');
              } else {
                $('#user_name').removeClass('is-invalid');
                $('.invalid-feedback').remove();
              }
            }
          });
        }
      });

      // Handle status toggle
      $('.status-toggle').change(function() {
        const userId = $(this).data('user-id');
        const newStatus = $(this).is(':checked') ? 'active' : 'inactive';
        const toggle = $(this);

        $.ajax({
          url: 'ajax/toggle_user_status.php',
          type: 'POST',
          data: {
            user_id: userId,
            status: newStatus
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              Toast.fire({
                icon: 'success',
                title: 'User status updated successfully'
              });
            } else {
              // Revert toggle if failed
              toggle.prop('checked', !toggle.is(':checked'));
              Toast.fire({
                icon: 'error',
                title: response.message || 'Failed to update status'
              });
            }
          },
          error: function() {
            // Revert toggle if error
            toggle.prop('checked', !toggle.is(':checked'));
            Toast.fire({
              icon: 'error',
              title: 'Error updating status'
            });
          }
        });
      });

      // Handle user deletion
      $('.delete-user').click(function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');

        Swal.fire({
          title: 'Are you sure?',
          text: `You want to delete user: ${userName}`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#F64E60',
          cancelButtonColor: '#3699FF',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: 'actions/delete_user.php',
              type: 'POST',
              data: { user_id: userId },
              dataType: 'json',
              success: function(response) {
                if (response.success) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                  }).then(() => {
                    location.reload();
                  });
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message
                  });
                }
              },
              error: function() {
                Swal.fire({
                  icon: 'error',
                  title: 'Error!',
                  text: 'Something went wrong'
                });
              }
            });
          }
        });
      });

      // Auto hide alerts after 5 seconds
      setTimeout(function() {
        $('.alert').alert('close');
      }, 5000);

      // Show menu
      showMenuSelected("#mnu_user_management", "#mi_users");
      
      // Toggle password visibility
      $(document).on('click', '#togglePassword', function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
          passwordField.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          passwordField.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });
    });
  </script>
</body>
</html>
