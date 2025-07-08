<?php
include './config/db_connection.php';
include './system/utilities/admin_client_common_functions_services.php';
require_once './system/utilities/admin_client_role_functions_services.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';

// Handle form submission to save a new category
if (isset($_POST['save_category'])) {
    $categoryName = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($categoryName)) {
        $message = 'Category name is required.';
    } else {
        try {
            // Check if category already exists
            $checkQuery = "SELECT COUNT(*) as count FROM medicine_categories WHERE category_name = ?";
            $stmt = $con->prepare($checkQuery);
            $stmt->execute([$categoryName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $message = 'Category already exists.';
            } else {
                // Prepare INSERT query
                $query = "INSERT INTO medicine_categories (category_name, description) VALUES (?, ?)";
                
                // Start transaction
                $con->beginTransaction();
                
                $stmt = $con->prepare($query);
                $stmt->execute([$categoryName, $description]);
                
                $con->commit();
                $message = 'Category added successfully.';
                
                // Redirect to avoid form resubmission
                header("Location: medicine_categories.php?message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Handle category deletion
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    
    try {
        // Check if category is in use
        $checkQuery = "SELECT COUNT(*) as count FROM medicines WHERE category_id = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->execute([$deleteId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $message = 'Cannot delete: Category is in use by medicines.';
        } else {
            // Start transaction
            $con->beginTransaction();
            
            // Delete the category
            $query = "DELETE FROM medicine_categories WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$deleteId]);
            
            $con->commit();
            $message = 'Category deleted successfully.';
            
            // Redirect to avoid URL parameters
            header("Location: medicine_categories.php?message=" . urlencode($message));
            exit;
        }
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

// Handle category update
if (isset($_POST['update_category'])) {
    $categoryId = $_POST['category_id'];
    $categoryName = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($categoryName)) {
        $message = 'Category name is required.';
    } else {
        try {
            // Check if another category with the same name exists
            $checkQuery = "SELECT COUNT(*) as count FROM medicine_categories WHERE category_name = ? AND id != ?";
            $stmt = $con->prepare($checkQuery);
            $stmt->execute([$categoryName, $categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $message = 'Another category with this name already exists.';
            } else {
                // Start transaction
                $con->beginTransaction();
                
                // Update the category
                $query = "UPDATE medicine_categories SET category_name = ?, description = ? WHERE id = ?";
                $stmt = $con->prepare($query);
                $stmt->execute([$categoryName, $description, $categoryId]);
                
                $con->commit();
                $message = 'Category updated successfully.';
                
                // Redirect to avoid form resubmission
                header("Location: medicine_categories.php?message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Fetch all categories for the listing
try {
    $query = "SELECT id, category_name, description, 
                     DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as created_at,
                     DATE_FORMAT(updated_at, '%d %b %Y %h:%i %p') as updated_at
              FROM medicine_categories
              ORDER BY category_name ASC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error: ' . $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css_js.php'; ?>
  <link rel="icon" type="image/png" href="dist/img/logo01.png">
  <title>Medicine Categories - Mamatid Health Center System</title>
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

    textarea.form-control {
      height: auto;
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

    .btn-danger {
      background: linear-gradient(135deg, var(--danger-color) 0%, #ff3838 100%);
      border: none;
    }

    .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(246, 78, 96, 0.4);
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
              <h1>Medicine Categories</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Add Medicine Category</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <form method="post" id="categoryForm">
              <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" id="category_name" name="category_name" required
                           class="form-control" placeholder="Enter category name"/>
                  </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="description" name="description" 
                              class="form-control" placeholder="Enter description"></textarea>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_category" name="save_category" 
                          class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Category
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
            <h3 class="card-title">Medicine Categories</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <?php if (!empty($message)): ?>
              <div class="alert alert-info">
                <?php echo $message; ?>
              </div>
            <?php endif; ?>
            <div class="table-responsive">
              <table id="categories_table" class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $index => $category): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                    <td><?php echo $category['created_at']; ?></td>
                    <td><?php echo $category['updated_at']; ?></td>
                    <td>
                      <button type="button" class="btn btn-primary btn-sm edit-category"
                              data-id="<?php echo $category['id']; ?>"
                              data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                              data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="javascript:void(0);" class="btn btn-danger btn-sm delete-category"
                         data-id="<?php echo $category['id']; ?>"
                         data-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form method="post">
            <div class="modal-body">
              <input type="hidden" id="edit_category_id" name="category_id">
              <div class="form-group">
                <label for="edit_category_name">Category Name</label>
                <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
              </div>
              <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea class="form-control" id="edit_description" name="description"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php include './config/footer.php'; ?>
  </div>

  <?php include './config/site_css_js_links.php'; ?>
  
  
  <script>
    $(document).ready(function() {
      // Initialize DataTable
      $("#categories_table").DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        language: {
          search: "",
          searchPlaceholder: "Search categories..."
        }
      });

      // Initialize Toast
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });

      // Show message if exists
      var message = <?php echo json_encode(isset($_GET['message']) ? $_GET['message'] : ''); ?>;
      if(message !== '') {
        Toast.fire({
          icon: 'success',
          title: message
        });
      }

      // Handle edit button click
      $('.edit-category').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');

        $('#edit_category_id').val(id);
        $('#edit_category_name').val(name);
        $('#edit_description').val(description);

        $('#editCategoryModal').modal('show');
      });

      // Handle delete button click
      $('.delete-category').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
          title: 'Are you sure?',
          text: `You want to delete category: ${name}`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `medicine_categories.php?delete_id=${id}`;
          }
        });
      });

      // Form validation
      $('#categoryForm').submit(function(e) {
        const categoryName = $('#category_name').val().trim();
        
        if (!categoryName) {
          e.preventDefault();
          Toast.fire({
            icon: 'error',
            title: 'Category name is required'
          });
        }
      });

      // Show menu
      showMenuSelected("#mnu_inventory", "#mi_medicine_categories");
    });
  </script>
</body>
</html> 