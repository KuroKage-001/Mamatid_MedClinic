<?php
// Include the database connection (connection.php already calls session_start())
include './config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}
require_once './common_service/role_functions.php';

// Check if user is admin
requireAdmin();

$message = '';

// Process the user registration form submission
if (isset($_POST['save_user'])) {
    // Get form inputs
    $displayName = $_POST['display_name'];
    $userName    = $_POST['user_name'];
    $password    = $_POST['password'];
    $email       = $_POST['email'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $role        = $_POST['role'];
    $status      = $_POST['status'];
    
    // Hash password using MD5 (to match existing system)
    $hashedPassword = md5($password);

    // Handle file upload for profile picture
    $targetFile = 'default_profile.jpg'; // Default profile picture
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $baseName   = basename($_FILES["profile_picture"]["name"]);
        $targetFile = time() . '_' . $baseName;
        $status_upload = move_uploaded_file($_FILES["profile_picture"]["tmp_name"], 'user_images/' . $targetFile);
        
        if (!$status_upload) {
            $targetFile = 'default_profile.jpg';
        }
    }

    try {
        // Insert new user record
        $query = "INSERT INTO `users`(`display_name`, `user_name`, `password`, `profile_picture`, `email`, `phone`, `role`, `status`)
                  VALUES(:display_name, :user_name, :password, :profile_picture, :email, :phone, :role, :status)";
        $stmtUser = $con->prepare($query);
        $stmtUser->bindParam(':display_name', $displayName);
        $stmtUser->bindParam(':user_name', $userName);
        $stmtUser->bindParam(':password', $hashedPassword);
        $stmtUser->bindParam(':profile_picture', $targetFile);
        $stmtUser->bindParam(':email', $email);
        $stmtUser->bindParam(':phone', $phone);
        $stmtUser->bindParam(':role', $role);
        $stmtUser->bindParam(':status', $status);
        
        if ($stmtUser->execute()) {
            $message = 'User registered successfully';
            header("location:users.php?message=" . urlencode($message));
            exit;
        } else {
            throw new Exception('Failed to insert user');
        }
    } catch (Exception $ex) {
        $message = 'Error: ' . $ex->getMessage();
        header("location:users.php?message=" . urlencode($message) . "&type=error");
        exit;
    }
}

// Query to get all users except the current logged-in user, ordered by display name
$queryUsers = "SELECT `id`, `display_name`, `user_name`, `profile_picture`, `role`, `status`, `email`, `phone` 
               FROM `users` 
               WHERE `id` != :current_user_id
               ORDER BY `display_name` ASC";
try {
    $stmtUsers = $con->prepare($queryUsers);
    $stmtUsers->bindParam(':current_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
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
  <link rel="stylesheet" href="system_styles/users.css">
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Users - Mamatid Health Center System</title>
</head>
<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed users-page">
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
                    <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required 
                             class="form-control password-field" placeholder="Enter password" />
                      <span class="password-toggle-btn" 
                            onclick="togglePassword('password')" 
                            title="Show password"
                            aria-label="Toggle password visibility">
                        <span id="password-eye" class="password-toggle-text">Show</span>
                      </span>
                    </div>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control" placeholder="Enter email" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           class="form-control" placeholder="Enter phone number" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" required class="form-control">
                      <option value="">Select Role</option>
                      <option value="admin">Administrator</option>
                      <option value="health_worker">Health Worker</option>
                      <option value="doctor">Doctor</option>
                    </select>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" required class="form-control">
                      <option value="active" selected>Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture (Optional)</label>
                    <input type="file" id="profile_picture" name="profile_picture" 
                           class="form-control" accept="image/*" />
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                    <label class="d-none d-sm-block">&nbsp;</label>
                    <button type="submit" id="save_user" name="save_user" 
                            class="btn btn-primary w-100">
                      <i class="fas fa-save mr-2"></i>Save User
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
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $serial = 0;
                    foreach ($users as $row) {
                        $serial++;
                        $roleClass = '';
                        $statusClass = '';
                        
                        // Role badge colors
                        switch($row['role']) {
                            case 'admin':
                                $roleClass = 'badge-danger';
                                break;
                            case 'doctor':
                                $roleClass = 'badge-primary';
                                break;
                            case 'health_worker':
                                $roleClass = 'badge-info';
                                break;
                            default:
                                // Fallback for any unmatched roles
                                $roleClass = 'badge-secondary';
                                break;
                        }
                        
                        // Status badge colors
                        $statusClass = ($row['status'] == 'active') ? 'badge-success' : 'badge-secondary';
                    ?>
                    <tr>
                      <td class="text-center"><?php echo $serial; ?></td>
                      <td class="text-center">
                        <img class="user-img" src="user_images/<?php echo $row['profile_picture']; ?>" 
                             alt="User Picture" onerror="this.src='user_images/default_profile.jpg'">
                      </td>
                      <td><?php echo $row['display_name']; ?></td>
                      <td><?php echo $row['user_name']; ?></td>
                      <td><?php echo $row['email'] ?: '-'; ?></td>
                      <td><?php echo $row['phone'] ?: '-'; ?></td>
                      <td>
                        <?php if (!empty($row['role'])): ?>
                        <span class="badge <?php echo $roleClass; ?>" style="display: inline-block !important; position: static !important;">
                          <?php echo getRoleDisplayName($row['role']); ?>
                        </span>
                        <?php else: ?>
                        <span class="badge badge-secondary" style="display: inline-block !important; position: static !important;">
                          No Role
                        </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?php echo $statusClass; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <?php if ($row['role'] != 'admin'): ?>
                        <a href="update_user.php?user_id=<?php echo $row['id']; ?>" 
                           class="btn btn-primary btn-sm" title="Edit">
                          <i class="fa fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                          <?php if ($row['role'] != 'admin'): ?>
                        <button type="button" class="btn btn-sm <?php echo ($row['status'] == 'active') ? 'btn-warning' : 'btn-success'; ?>"
                                onclick="toggleUserStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')"
                                title="<?php echo ($row['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>">
                          <i class="fa fa-<?php echo ($row['status'] == 'active') ? 'ban' : 'check'; ?>"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger"
                                onclick="deleteUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['display_name'], ENT_QUOTES); ?>')"
                                title="Delete User">
                          <i class="fa fa-trash"></i>
                        </button>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-secondary" disabled
                                    title="Administrator accounts are protected">
                              <i class="fa fa-shield-alt"></i>
                        </button>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
              
              <!-- Export Actions - Below Table -->
              <div class="row align-items-center mt-4 pt-3" style="border-top: 1px solid rgba(0, 0, 0, 0.05);">
                <div class="col-md-6">
                  <div class="export-info">
                    <span class="export-label">Export Options:</span>
                    <span class="export-description">Download user data in various formats</span>
                  </div>
                </div>
                <div class="col-md-6 text-right">
                  <div class="chart-actions">
                    <button class="export-action-btn export-copy-btn" id="usersBtnCopy" title="Copy to Clipboard">
                      <i class="fas fa-copy"></i> Copy
                    </button>
                    <button class="export-action-btn export-csv-btn" id="usersBtnCSV" title="Export as CSV">
                      <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="export-action-btn export-excel-btn" id="usersBtnExcel" title="Export as Excel">
                      <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="export-action-btn export-pdf-btn" id="usersBtnPDF" title="Export as PDF">
                      <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button class="export-action-btn export-print-btn" id="usersBtnPrint" title="Print">
                      <i class="fas fa-print"></i> Print
                    </button>
                  </div>
                </div>
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
  
  <!-- MINIMAL COMPLEMENTARY CSS - Works with external users.css -->
  <style>
    /* Toast animations only */
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }

    /* DataTables pagination styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: linear-gradient(135deg, #3699FF, #6993FF) !important;
      color: white !important;
      border-color: #3699FF !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: linear-gradient(135deg, #3699FF, #6993FF) !important;
      color: white !important;
      border-color: #3699FF !important;
    }

    /* Form validation states */
    .form-control.is-valid {
      border-color: #1BC5BD;
      box-shadow: 0 0 0 0.2rem rgba(27, 197, 189, 0.15);
    }

    .form-control.is-invalid {
      border-color: #F64E60;
      box-shadow: 0 0 0 0.2rem rgba(246, 78, 96, 0.15);
    }

    /* Loading state for buttons */
    .btn-loading {
      position: relative;
      pointer-events: none;
    }

    .btn-loading::after {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
      top: 50%;
      left: 50%;
      margin-left: -8px;
      margin-top: -8px;
      border: 2px solid transparent;
      border-top-color: currentColor;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Custom SweetAlert styling */
    .swal2-toast-custom {
      border-radius: 12px !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
    }

    /* Enhanced Export Info Section */
    .export-info {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      padding: 1rem 1.5rem;
      background: linear-gradient(135deg, rgba(54, 153, 255, 0.05), rgba(105, 147, 255, 0.02));
      border-radius: 12px;
      border: 1px solid rgba(54, 153, 255, 0.1);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    .export-label {
      font-weight: 700;
      color: #1a1a2d;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .export-label::before {
      content: '📊';
      font-size: 1.1rem;
    }

    .export-description {
      color: #64748b;
      font-size: 0.875rem;
      font-weight: 500;
      line-height: 1.4;
    }

    /* Modern Export Actions Container */
    .chart-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      flex-wrap: wrap;
      align-items: center;
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

    /* Responsive Design for Modern Export Options */
    @media (max-width: 768px) {
      .chart-actions {
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
      
      .export-info {
        text-align: center;
        margin-bottom: 1rem;
      }
    }

    @media (max-width: 576px) {
      .chart-actions {
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
      
      .export-info {
        text-align: center;
        margin-bottom: 1rem;
      }
      
      .export-label {
        font-size: 0.875rem;
      }
      
      .export-description {
        font-size: 0.8rem;
      }
    }
  </style>

  <script>
    // Toast notification functions with modern styling
    function showSuccessMessage(message) {
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #1BC5BD, #26C6DA);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 20px rgba(27, 197, 189, 0.3);
        animation: slideIn 0.3s ease-out;
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        if (document.body.contains(toast)) {
          toast.style.animation = 'slideOut 0.3s ease-in';
          setTimeout(() => {
            if (document.body.contains(toast)) {
              document.body.removeChild(toast);
            }
          }, 300);
        }
      }, 3000);
    }

    function showErrorMessage(message) {
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #F64E60, #FF647C);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 20px rgba(246, 78, 96, 0.3);
        animation: slideIn 0.3s ease-out;
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        if (document.body.contains(toast)) {
          toast.style.animation = 'slideOut 0.3s ease-in';
          setTimeout(() => {
            if (document.body.contains(toast)) {
              document.body.removeChild(toast);
            }
          }, 300);
        }
      }, 3000);
    }

    function showInfoMessage(message) {
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #8950FC, #A855F7);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 20px rgba(137, 80, 252, 0.3);
        animation: slideIn 0.3s ease-out;
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        if (document.body.contains(toast)) {
          toast.style.animation = 'slideOut 0.3s ease-in';
          setTimeout(() => {
            if (document.body.contains(toast)) {
              document.body.removeChild(toast);
            }
          }, 300);
        }
      }, 3000);
    }

    // Safe text extraction functions
    function safeExtractText(htmlContent) {
      if (typeof htmlContent === 'string') {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        return tempDiv.textContent || tempDiv.innerText || htmlContent;
      }
      return htmlContent;
    }

    function safeExtractBadgeText(htmlContent) {
      if (typeof htmlContent === 'string') {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        const badge = tempDiv.querySelector('.badge');
        return badge ? badge.textContent || badge.innerText : tempDiv.textContent || tempDiv.innerText || htmlContent;
      }
      return htmlContent;
    }

    // SweetAlert Toast utility
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      customClass: {
        popup: 'swal2-toast-custom'
      }
    });

    // Password toggle function
    window.togglePassword = function(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const toggleText = document.getElementById(fieldId + '-eye');
      const toggleBtn = toggleText.closest('.password-toggle-btn');
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleText.textContent = 'Hide';
        toggleBtn.classList.add('password-visible');
        toggleBtn.setAttribute('title', 'Hide password');
      } else {
        passwordField.type = 'password';
        toggleText.textContent = 'Show';
        toggleBtn.classList.remove('password-visible');
        toggleBtn.setAttribute('title', 'Show password');
      }
    };

      // Function to toggle user status
      window.toggleUserStatus = function(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = currentStatus === 'active' ? 'deactivate' : 'activate';
        
        Swal.fire({
          title: 'Are you sure?',
          text: `Do you want to ${action} this user?`,
          icon: 'warning',
          showCancelButton: true,
        confirmButtonColor: '#3699FF',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${action}!`,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
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
                    title: `User ${action}d successfully`
                  });
                  setTimeout(() => location.reload(), 1500);
                } else {
                  Toast.fire({
                    icon: 'error',
                    title: response.message || 'Error updating user status'
                  });
                }
              },
              error: function() {
                Toast.fire({
                  icon: 'error',
                  title: 'Error updating user status'
                });
              }
            });
          }
        });
      };

    // Function to delete user
    window.deleteUser = function(userId, displayName) {
      Swal.fire({
        title: 'Are you sure?',
        html: `Do you want to <strong>permanently delete</strong> the user "<strong>${displayName}</strong>"?<br><br><span style="color: #F64E60; font-weight: 600;">⚠️ This action cannot be undone!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F64E60',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete User!',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-danger',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Deleting User...',
            text: 'Please wait while we delete the user.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          $.ajax({
            url: 'actions/delete_user.php',
            type: 'POST',
            data: { 
              user_id: userId
            },
            dataType: 'json',
            success: function(response) {
              Swal.close();
              if (response.success) {
                Toast.fire({
                  icon: 'success',
                  title: response.message || 'User deleted successfully'
                });
                setTimeout(() => location.reload(), 2000);
              } else {
                Toast.fire({
                  icon: 'error',
                  title: response.message || 'Error deleting user'
                });
              }
            },
            error: function() {
              Swal.close();
              Toast.fire({
                icon: 'error',
                title: 'Error deleting user. Please try again.'
              });
            }
          });
        }
      });
    };

    // Initialize everything when document is ready
    $(document).ready(function() {
      // Initialize DataTable
      if (!$.fn.DataTable.isDataTable('#all_users')) {
        $('#all_users').DataTable({
          "responsive": true,
          "lengthChange": false,
          "autoWidth": false,
          "pageLength": 10,
          "order": [[2, "asc"]], // Sort by Display Name
          "columnDefs": [
            { "orderable": false, "targets": [1, 8] }, // Disable sorting for Picture and Action columns
            { "searchable": false, "targets": [1, 8] } // Disable search for Picture and Action columns
          ],
          "language": {
            "search": "Search users:",
            "lengthMenu": "Show _MENU_ users per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ users",
            "infoEmpty": "No users found",
            "infoFiltered": "(filtered from _MAX_ total users)",
            "paginate": {
              "first": "First",
              "last": "Last",
              "next": "Next",
              "previous": "Previous"
            }
          }
        });
      }

      // Export functions - keeping exact design as requested
      $('#usersBtnCopy').off('click').on('click', function() {
        const data = $('#all_users').DataTable().data().toArray();
        const headers = ['S.No', 'Display Name', 'Username', 'Email', 'Phone', 'Role', 'Status'];
        let csvContent = headers.join('\t') + '\n';
        
        data.forEach((row, index) => {
          const rowData = [
            index + 1,
            safeExtractText(row[2]), // Display Name
            safeExtractText(row[3]), // Username  
            safeExtractText(row[4]), // Email
            safeExtractText(row[5]), // Phone
            safeExtractBadgeText(row[6]), // Role
            safeExtractBadgeText(row[7])  // Status
          ];
          csvContent += rowData.join('\t') + '\n';
        });
        
        navigator.clipboard.writeText(csvContent).then(() => {
          showSuccessMessage('Data copied to clipboard!');
        }).catch(() => {
          showErrorMessage('Failed to copy data');
        });
      });

      $('#usersBtnCSV').off('click').on('click', function() {
        const data = $('#all_users').DataTable().data().toArray();
        const headers = ['S.No', 'Display Name', 'Username', 'Email', 'Phone', 'Role', 'Status'];
        let csvContent = headers.join(',') + '\n';
        
        data.forEach((row, index) => {
          const rowData = [
            index + 1,
            `"${safeExtractText(row[2])}"`, // Display Name
            `"${safeExtractText(row[3])}"`, // Username  
            `"${safeExtractText(row[4])}"`, // Email
            `"${safeExtractText(row[5])}"`, // Phone
            `"${safeExtractBadgeText(row[6])}"`, // Role
            `"${safeExtractBadgeText(row[7])}"` // Status
          ];
          csvContent += rowData.join(',') + '\n';
        });
        
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `users_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        
        showSuccessMessage('CSV file downloaded successfully!');
      });

      $('#usersBtnExcel').off('click').on('click', function() {
        showInfoMessage('Excel export feature coming soon!');
      });

      $('#usersBtnPDF').off('click').on('click', function() {
        showInfoMessage('PDF export feature coming soon!');
      });

      $('#usersBtnPrint').off('click').on('click', function() {
        const printWindow = window.open('', '_blank');
        const tableHtml = document.querySelector('#all_users').outerHTML;
        
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>Users List - Mamatid Health Center</title>
            <style>
              body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8fafc; }
              h1 { color: #1a1a2d; text-align: center; margin-bottom: 30px; font-weight: 600; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
              th, td { border: 1px solid #e2e8f0; padding: 12px 8px; text-align: left; }
              th { background: linear-gradient(135deg, #f3f6f9 0%, #ffffff 100%); font-weight: 700; color: #1a1a2d; text-transform: uppercase; font-size: 0.8rem; }
              tr:nth-child(even) { background: rgba(243, 246, 249, 0.3); }
              .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
              .badge-danger { background: linear-gradient(135deg, #F64E60, #FF647C); color: white; }
              .badge-primary { background: linear-gradient(135deg, #3699FF, #6993FF); color: white; }
              .badge-info { background: linear-gradient(135deg, #8950FC, #A855F7); color: white; }
              .badge-success { background: linear-gradient(135deg, #1BC5BD, #26C6DA); color: white; }
              .badge-secondary { background: linear-gradient(135deg, #6c757d, #868e96); color: white; }
              .user-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
              .text-center { text-align: center; }
              @media print {
                .btn { display: none; }
                .action-column { display: none; }
                body { background: white; }
              }
            </style>
          </head>
          <body>
            <h1>Users List - Mamatid Health Center System</h1>
            <p style="text-align: center; color: #64748b; margin-bottom: 20px;">Generated on: ${new Date().toLocaleDateString()}</p>
            ${tableHtml.replace(/onclick="[^"]*"/g, '').replace(/<button[^>]*>.*?<\/button>/g, '')}
          </body>
          </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
          printWindow.print();
          printWindow.close();
        }, 250);
        
        showSuccessMessage('Print dialog opened!');
      });

      // Enhanced form field animations
      $('.form-control').on('focus', function() {
        $(this).closest('.form-group, .mb-3').addClass('focused');
      }).on('blur', function() {
        $(this).closest('.form-group, .mb-3').removeClass('focused');
      });

      // Form validation enhancement
      $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.addClass('btn-loading');
        submitBtn.prop('disabled', true);
      });

    // Highlight current menu
    showMenuSelected("#mnu_user_management", "#mi_users");
    });
  </script>
</body>
</html>
