<?php
include './config/connection.php';
include './common_service/common_functions.php';
require_once './common_service/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Check permission
requireRole(['admin', 'health_worker', 'doctor']);

$message = '';

// Handle form submission to save a new medicine
if (isset($_POST['save_medicine'])) {
    $medicineName = trim($_POST['medicine_name']);
    $genericName = trim($_POST['generic_name']);
    $categoryId = $_POST['category_id'];
    $description = trim($_POST['description']);
    $dosageForm = trim($_POST['dosage_form']);
    $dosageStrength = trim($_POST['dosage_strength']);
    $unit = trim($_POST['unit']);
    
    // Validation
    if (empty($medicineName) || empty($categoryId)) {
        $message = 'Medicine name and category are required.';
    } else {
        try {
            // Check if medicine already exists
            $checkQuery = "SELECT COUNT(*) as count FROM medicines WHERE medicine_name = ?";
            $stmt = $con->prepare($checkQuery);
            $stmt->execute([$medicineName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $message = 'Medicine already exists.';
            } else {
                // Prepare INSERT query
                $query = "INSERT INTO medicines (medicine_name, generic_name, category_id, description, 
                          dosage_form, dosage_strength, unit) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                // Start transaction
                $con->beginTransaction();
                
                $stmt = $con->prepare($query);
                $stmt->execute([
                    $medicineName, 
                    $genericName, 
                    $categoryId, 
                    $description, 
                    $dosageForm, 
                    $dosageStrength, 
                    $unit
                ]);
                
                $con->commit();
                $message = 'Medicine added successfully.';
                
                // Redirect to avoid form resubmission
                header("Location: medicines.php?message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Handle medicine deletion
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    
    try {
        // Check if medicine has stock entries
        $checkQuery = "SELECT COUNT(*) as count FROM medicine_stock WHERE medicine_id = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->execute([$deleteId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $message = 'Cannot delete: Medicine has stock entries.';
        } else {
            // Start transaction
            $con->beginTransaction();
            
            // Delete the medicine
            $query = "DELETE FROM medicines WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$deleteId]);
            
            $con->commit();
            $message = 'Medicine deleted successfully.';
            
            // Redirect to avoid URL parameters
            header("Location: medicines.php?message=" . urlencode($message));
            exit;
        }
    } catch (PDOException $ex) {
        $con->rollback();
        $message = 'Error: ' . $ex->getMessage();
    }
}

// Handle medicine update
if (isset($_POST['update_medicine'])) {
    $medicineId = $_POST['medicine_id'];
    $medicineName = trim($_POST['medicine_name']);
    $genericName = trim($_POST['generic_name']);
    $categoryId = $_POST['category_id'];
    $description = trim($_POST['description']);
    $dosageForm = trim($_POST['dosage_form']);
    $dosageStrength = trim($_POST['dosage_strength']);
    $unit = trim($_POST['unit']);
    
    // Validation
    if (empty($medicineName) || empty($categoryId)) {
        $message = 'Medicine name and category are required.';
    } else {
        try {
            // Check if another medicine with the same name exists
            $checkQuery = "SELECT COUNT(*) as count FROM medicines WHERE medicine_name = ? AND id != ?";
            $stmt = $con->prepare($checkQuery);
            $stmt->execute([$medicineName, $medicineId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $message = 'Another medicine with this name already exists.';
            } else {
                // Start transaction
                $con->beginTransaction();
                
                // Update the medicine
                $query = "UPDATE medicines SET 
                          medicine_name = ?, 
                          generic_name = ?, 
                          category_id = ?, 
                          description = ?, 
                          dosage_form = ?, 
                          dosage_strength = ?, 
                          unit = ? 
                          WHERE id = ?";
                          
                $stmt = $con->prepare($query);
                $stmt->execute([
                    $medicineName, 
                    $genericName, 
                    $categoryId, 
                    $description, 
                    $dosageForm, 
                    $dosageStrength, 
                    $unit, 
                    $medicineId
                ]);
                
                $con->commit();
                $message = 'Medicine updated successfully.';
                
                // Redirect to avoid form resubmission
                header("Location: medicines.php?message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $ex) {
            $con->rollback();
            $message = 'Error: ' . $ex->getMessage();
        }
    }
}

// Fetch all categories for dropdown
try {
    $categoryQuery = "SELECT id, category_name FROM medicine_categories ORDER BY category_name ASC";
    $categoryStmt = $con->prepare($categoryQuery);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error fetching categories: ' . $ex->getMessage();
}

// Fetch all medicines for the listing
try {
    $query = "SELECT m.id, m.medicine_name, m.generic_name, c.category_name,
                     m.description, m.dosage_form, m.dosage_strength, m.unit,
                     DATE_FORMAT(m.created_at, '%d %b %Y %h:%i %p') as created_at,
                     DATE_FORMAT(m.updated_at, '%d %b %Y %h:%i %p') as updated_at
              FROM medicines m
              JOIN medicine_categories c ON m.category_id = c.id
              ORDER BY m.medicine_name ASC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $message = 'Error fetching medicines: ' . $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <?php include './config/data_tables_css.php'; ?>
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
    <?php include './config/header.php'; include './config/sidebar.php'; ?>
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
            <form method="post" id="medicineForm">
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Medicine Name</label>
                    <input type="text" id="medicine_name" name="medicine_name" required
                           class="form-control" placeholder="Enter medicine name"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Generic Name</label>
                    <input type="text" id="generic_name" name="generic_name"
                           class="form-control" placeholder="Enter generic name"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="category_id" name="category_id" required class="form-control">
                      <option value="">Select Category</option>
                      <?php foreach ($categories as $category): ?>
                      <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Dosage Form</label>
                    <select id="dosage_form" name="dosage_form" class="form-control">
                      <option value="">Select Dosage Form</option>
                      <option value="Tablet">Tablet</option>
                      <option value="Capsule">Capsule</option>
                      <option value="Syrup">Syrup</option>
                      <option value="Injection">Injection</option>
                      <option value="Cream">Cream</option>
                      <option value="Ointment">Ointment</option>
                      <option value="Drops">Drops</option>
                      <option value="Inhaler">Inhaler</option>
                      <option value="Powder">Powder</option>
                      <option value="Solution">Solution</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Dosage Strength</label>
                    <input type="text" id="dosage_strength" name="dosage_strength"
                           class="form-control" placeholder="e.g. 500mg, 250ml"/>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select id="unit" name="unit" class="form-control">
                      <option value="">Select Unit</option>
                      <option value="Tablet">Tablet</option>
                      <option value="Capsule">Capsule</option>
                      <option value="Bottle">Bottle</option>
                      <option value="Box">Box</option>
                      <option value="Strip">Strip</option>
                      <option value="Vial">Vial</option>
                      <option value="Ampule">Ampule</option>
                      <option value="Tube">Tube</option>
                      <option value="Piece">Piece</option>
                      <option value="Sachet">Sachet</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="description" name="description" 
                              class="form-control" placeholder="Enter description"></textarea>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-right">
                  <button type="submit" id="save_medicine" name="save_medicine" 
                          class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Medicine
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
            <h3 class="card-title">Medicines List</h3>
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
              <table id="medicines_table" class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Medicine Name</th>
                    <th>Generic Name</th>
                    <th>Category</th>
                    <th>Dosage Form</th>
                    <th>Strength</th>
                    <th>Unit</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($medicines as $index => $medicine): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                    <td><?php echo htmlspecialchars($medicine['generic_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($medicine['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($medicine['dosage_form'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($medicine['dosage_strength'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($medicine['unit'] ?? 'N/A'); ?></td>
                    <td>
                      <button type="button" class="btn btn-primary btn-sm edit-medicine"
                              data-id="<?php echo $medicine['id']; ?>"
                              data-name="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"
                              data-generic="<?php echo htmlspecialchars($medicine['generic_name'] ?? ''); ?>"
                              data-category="<?php echo htmlspecialchars($medicine['category_name']); ?>"
                              data-description="<?php echo htmlspecialchars($medicine['description'] ?? ''); ?>"
                              data-form="<?php echo htmlspecialchars($medicine['dosage_form'] ?? ''); ?>"
                              data-strength="<?php echo htmlspecialchars($medicine['dosage_strength'] ?? ''); ?>"
                              data-unit="<?php echo htmlspecialchars($medicine['unit'] ?? ''); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="javascript:void(0);" class="btn btn-danger btn-sm delete-medicine"
                         data-id="<?php echo $medicine['id']; ?>"
                         data-name="<?php echo htmlspecialchars($medicine['medicine_name']); ?>">
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

    <!-- Edit Medicine Modal -->
    <div class="modal fade" id="editMedicineModal" tabindex="-1" role="dialog" aria-labelledby="editMedicineModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editMedicineModalLabel">Edit Medicine</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form method="post">
            <div class="modal-body">
              <input type="hidden" id="edit_medicine_id" name="medicine_id">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_medicine_name">Medicine Name</label>
                    <input type="text" class="form-control" id="edit_medicine_name" name="medicine_name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_generic_name">Generic Name</label>
                    <input type="text" class="form-control" id="edit_generic_name" name="generic_name">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_category_id">Category</label>
                    <select class="form-control" id="edit_category_id" name="category_id" required>
                      <option value="">Select Category</option>
                      <?php foreach ($categories as $category): ?>
                      <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_dosage_form">Dosage Form</label>
                    <select class="form-control" id="edit_dosage_form" name="dosage_form">
                      <option value="">Select Dosage Form</option>
                      <option value="Tablet">Tablet</option>
                      <option value="Capsule">Capsule</option>
                      <option value="Syrup">Syrup</option>
                      <option value="Injection">Injection</option>
                      <option value="Cream">Cream</option>
                      <option value="Ointment">Ointment</option>
                      <option value="Drops">Drops</option>
                      <option value="Inhaler">Inhaler</option>
                      <option value="Powder">Powder</option>
                      <option value="Solution">Solution</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_dosage_strength">Dosage Strength</label>
                    <input type="text" class="form-control" id="edit_dosage_strength" name="dosage_strength">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_unit">Unit</label>
                    <select class="form-control" id="edit_unit" name="unit">
                      <option value="">Select Unit</option>
                      <option value="Tablet">Tablet</option>
                      <option value="Capsule">Capsule</option>
                      <option value="Bottle">Bottle</option>
                      <option value="Box">Box</option>
                      <option value="Strip">Strip</option>
                      <option value="Vial">Vial</option>
                      <option value="Ampule">Ampule</option>
                      <option value="Tube">Tube</option>
                      <option value="Piece">Piece</option>
                      <option value="Sachet">Sachet</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea class="form-control" id="edit_description" name="description"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="update_medicine" class="btn btn-primary">Update Medicine</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php include './config/footer.php'; ?>
  </div>

  <?php include './config/site_js_links.php'; ?>
  <?php include './config/data_tables_js.php'; ?>
  
  <script>
    $(document).ready(function() {
      // Initialize DataTable
      $("#medicines_table").DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        language: {
          search: "",
          searchPlaceholder: "Search medicines..."
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
      $('.edit-medicine').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const generic = $(this).data('generic');
        const description = $(this).data('description');
        const form = $(this).data('form');
        const strength = $(this).data('strength');
        const unit = $(this).data('unit');
        const category = $(this).data('category');

        $('#edit_medicine_id').val(id);
        $('#edit_medicine_name').val(name);
        $('#edit_generic_name').val(generic);
        $('#edit_description').val(description);
        $('#edit_dosage_form').val(form);
        $('#edit_dosage_strength').val(strength);
        $('#edit_unit').val(unit);

        // Find and select the correct category
        $('#edit_category_id option').each(function() {
          if ($(this).text().trim() === category) {
            $(this).prop('selected', true);
          }
        });

        $('#editMedicineModal').modal('show');
      });

      // Handle delete button click
      $('.delete-medicine').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
          title: 'Are you sure?',
          text: `You want to delete medicine: ${name}`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `medicines.php?delete_id=${id}`;
          }
        });
      });

      // Form validation
      $('#medicineForm').submit(function(e) {
        const medicineName = $('#medicine_name').val().trim();
        const categoryId = $('#category_id').val();
        
        if (!medicineName || !categoryId) {
          e.preventDefault();
          Toast.fire({
            icon: 'error',
            title: 'Medicine name and category are required'
          });
        }
      });

      // Show menu
      showMenuSelected("#mnu_inventory", "#mi_medicines");
    });
  </script>
</body>
</html> 