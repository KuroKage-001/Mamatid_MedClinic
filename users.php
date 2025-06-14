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
            
            <!-- Export Actions -->
            <div class="card-body pb-2">
              <div class="row align-items-center mb-3">
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
            
            <div class="card-body pt-0">
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
                        <span class="badge <?php echo $roleClass; ?>">
                          <?php echo getRoleDisplayName($row['role']); ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge <?php echo $statusClass; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <a href="update_user.php?user_id=<?php echo $row['id']; ?>" 
                           class="btn btn-primary btn-sm" title="Edit">
                          <i class="fa fa-edit"></i>
                        </a>
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
                                    title="Cannot modify administrator accounts">
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
            </div>
          </div>
        </div>
      </section>
    </div>
    
    <?php include './config/footer.php'; ?>
  </div>

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>
  
  <!-- 
    EXPORT BUTTONS STYLING - INLINE CSS
    Added to ensure proper styling with maximum specificity
  -->
  <style>
    /* Export Actions Container */
    .chart-actions {
      display: flex !important;
      gap: 8px !important;
      justify-content: flex-end !important;
      flex-wrap: wrap !important;
    }

    /* Unique Export Button Base Class - Maximum Specificity */
    .export-action-btn {
      padding: 8px 16px !important;
      font-size: 0.875rem !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 8px !important;
      border-radius: 8px !important;
      transition: all 0.3s ease !important;
      font-weight: 500 !important;
      color: #ffffff !important;
      border: none !important;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
      text-transform: none !important;
      letter-spacing: normal !important;
      position: relative !important;
      overflow: visible !important;
      cursor: pointer !important;
      text-decoration: none !important;
      line-height: 1.5 !important;
    }

    /* Remove any pseudo-elements that might interfere */
    .export-action-btn::before {
      display: none !important;
    }

    /* Hover Effects */
    .export-action-btn:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
      text-decoration: none !important;
    }

    /* Active State */
    .export-action-btn:active {
      transform: translateY(0) !important;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }

    /* Icon Styling */
    .export-action-btn i {
      font-size: 0.875rem !important;
      transition: transform 0.2s ease !important;
      margin-right: 0 !important;
    }

    /* Icon Hover Effect */
    .export-action-btn:hover i {
      transform: scale(1.1) !important;
    }

    /* SPECIFIC BUTTON COLORS - Matching User's Image Requirements */
    
    /* Copy Button - Purple */
    .export-copy-btn {
      background: #6366F1 !important;
    }
    .export-copy-btn:hover {
      background: #4F46E5 !important;
    }

    /* CSV Button - Green */
    .export-csv-btn {
      background: #10B981 !important;
    }
    .export-csv-btn:hover {
      background: #059669 !important;
    }

    /* Excel Button - Light Green */
    .export-excel-btn {
      background: #22C55E !important;
    }
    .export-excel-btn:hover {
      background: #16A34A !important;
    }

    /* PDF Button - Red */
    .export-pdf-btn {
      background: #EF4444 !important;
    }
    .export-pdf-btn:hover {
      background: #DC2626 !important;
    }

    /* Print Button - Purple */
    .export-print-btn {
      background: #8B5CF6 !important;
    }
    .export-print-btn:hover {
      background: #7C3AED !important;
    }

    /* RESPONSIVE DESIGN - Mobile Optimization */
    @media (max-width: 768px) {
      .chart-actions {
        flex-wrap: wrap !important;
        gap: 6px !important;
        justify-content: center !important;
      }

      .export-action-btn {
        padding: 6px 12px !important;
        font-size: 0.8125rem !important;
      }
      
      .export-info {
        text-align: center !important;
        margin-bottom: 1rem !important;
      }
    }

    @media (max-width: 576px) {
      .export-action-btn {
        padding: 5px 10px !important;
        font-size: 0.75rem !important;
        gap: 6px !important;
      }
      
      .export-info {
        text-align: center !important;
        margin-bottom: 1rem !important;
      }
      
      .export-label {
        font-size: 0.875rem !important;
      }
      
      .export-description {
        font-size: 0.8rem !important;
      }
    }
  </style>

  <script>
    // Simple toast functions matching dashboard style
    function showSuccessMessage(message) {
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
        background: #dc3545;
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
        background: #17a2b8;
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

    // Add CSS animations for toast notifications
    function addToastStyles() {
      if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
          @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
          }
          @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
          }
        `;
        document.head.appendChild(style);
      }
    }

    // Safe text extraction function to avoid jQuery selector issues with special characters
    function safeExtractText(htmlContent) {
      if (typeof htmlContent === 'string') {
        // Create a temporary div to parse HTML safely
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

    // Initialize toast styles and export functions
    $(document).ready(function() {
      // Add toast styles
      addToastStyles();

      // Re-bind export functions with dashboard-style toast notifications
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

      // Re-bind CSV export with dashboard-style toast notifications
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

      // Excel export
      $('#usersBtnExcel').off('click').on('click', function() {
        showInfoMessage('Excel export feature coming soon!');
      });

      // PDF export
      $('#usersBtnPDF').off('click').on('click', function() {
        showInfoMessage('PDF export feature coming soon!');
      });

      // Print function
      $('#usersBtnPrint').off('click').on('click', function() {
        const printWindow = window.open('', '_blank');
        const tableHtml = document.querySelector('#all_users').outerHTML;
        
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>Users List - Mamatid Health Center</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              h1 { color: #333; text-align: center; margin-bottom: 30px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; font-weight: bold; }
              tr:nth-child(even) { background-color: #f9f9f9; }
              .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
              .badge-danger { background-color: #dc3545; color: white; }
              .badge-primary { background-color: #007bff; color: white; }
              .badge-info { background-color: #17a2b8; color: white; }
              .badge-success { background-color: #28a745; color: white; }
              .badge-secondary { background-color: #6c757d; color: white; }
              .user-img { width: 40px; height: 40px; border-radius: 50%; }
              .text-center { text-align: center; }
              @media print {
                .btn { display: none; }
                .action-column { display: none; }
              }
            </style>
          </head>
          <body>
            <h1>Users List - Mamatid Health Center System</h1>
            <p>Generated on: ${new Date().toLocaleDateString()}</p>
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
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${action}!`,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Initialize Toast for this function
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
          });

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
        html: `Do you want to <strong>permanently delete</strong> the user "<strong>${displayName}</strong>"?<br><br><span style="color: #dc3545; font-weight: 600;">⚠️ This action cannot be undone!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
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
          // Initialize Toast for this function
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
          });

          // Show loading state
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

    // Enhanced form field animations
    $('.form-control').on('focus', function() {
      $(this).closest('.form-group, .mb-3').addClass('focused');
    }).on('blur', function() {
      $(this).closest('.form-group, .mb-3').removeClass('focused');
    });

    // Highlight current menu
    showMenuSelected("#mnu_user_management", "#mi_users");

    // Prevent export-functions.js conflicts by overriding its initialization
    if (window.exportFunctionsInitialized) {
      console.log('Export functions already initialized, skipping duplicate initialization');
    }
    window.exportFunctionsInitialized = true;
  </script>
</body>
</html>
