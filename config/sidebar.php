<?php
if (!(isset($_SESSION['user_id']))) {
  header("location:index.php");
  exit;
}
?>
<aside class="main-sidebar sidebar-dark-primary bg-black elevation-4">
    <a href="dashboard.php" class="brand-link logo-switch bg-red">
      <h4 class="brand-image-xl logo-xs mb-0 text-center"><b>MHC</b></h4>
      <h4 class="brand-image-xl logo-xl mb-0 text-center">Clinic <b>MHC</b></h4>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 position-relative" style="position: relative;">
        <div class="image" style="position: absolute; left: -5%; top: 50%; transform: translateY(-75%); z-index: 1;">
          <img src="user_images/<?php echo $_SESSION['profile_picture'];?>"
               class="user-img"
               alt="User Image" />
        </div>
        <div class="info" style="margin-left: 4em; position: relative; z-index: 2;">
          <a href="#" class="d-block user-display-name"><?php echo $_SESSION['display_name'];?></a>
        </div>
      </div>
      
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" 
            data-widget="treeview" role="menu" data-accordion="false">
          
          <!-- Dashboard -->
          <li class="nav-item" id="mnu_dashboard">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p> Dashboard </p>
            </a>
          </li>

          <!-- General -->
          <li class="nav-item" id="mnu_patients">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-injured"></i>
              <p>
                General
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="patients.php" class="nav-link" id="mi_patients">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Add Patients</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="new_prescription.php" class="nav-link" id="mi_new_prescription">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Add Blood Pressure</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Patient History -->
          <li class="nav-item" id="mnu_patient_history">
            <a href="patient_history.php" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>Patient History</p>
            </a>
          </li>

          <!-- Medicines -->
          <li class="nav-item" id="mnu_medicines">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-pills"></i>
              <p>
                Medicines
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="medicines.php" class="nav-link" id="mi_medicines">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Add Medicine</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="medicine_details.php" class="nav-link" id="mi_medicine_details">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Medicine Details</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Reports -->
          <li class="nav-item" id="mnu_reports">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-edit"></i>
              <p>
                Reports
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="reports.php" class="nav-link" id="mi_reports">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Reports</p>
                </a>
              </li>
            </ul>
          </li> 

          <!-- Users & Time Tracker -->
          <li class="nav-item" id="mnu_user_management">
            <a href="#" class="nav-link">
              <i class="nav-icon fa fa-users"></i>
              <p>
                Users
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="users.php" class="nav-link" id="mi_users">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Users</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="time_tracker.php" class="nav-link" id="mi_time_tracker">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Time In|Time Out</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Logout -->
          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fa fa-sign-out-alt"></i>
              <p> Logout </p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<script>
  function showMenuSelected(topMenuId, subMenuId) {
    $('.nav-item').removeClass('menu-open');
    $('.nav-link').removeClass('active');
    if (topMenuId) {
      $(topMenuId).addClass('menu-open');
      $(topMenuId + ' > .nav-link').addClass('active');
    }
    if (subMenuId) {
      $(subMenuId).addClass('active');
    }
  }
</script>

<!-- Additional CSS -->
<style>
  /* Ensure user image is perfectly round and positioned to the left */
  .user-img {
    width: 3em !important;
    height: 3em !important;
    border-radius: 50% !important;
    object-fit: cover;
    object-position: center;
  }
  
  /* Style for display name */
  .user-display-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: #fff;
    background: rgba(0, 0, 0, 0.5);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
    transition: background 0.3s ease;
    display: inline-block;
  }
  
  .user-display-name:hover {
    background: rgba(0, 0, 0, 0.7);
  }

  /* Custom active styles for Users and Time In|Time Out sub-menu items */
  #mi_users.nav-link.active,
  #mi_time_tracker.nav-link.active {
    background-color: lightblue !important;
    color: white !important;
  }
</style>