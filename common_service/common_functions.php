<?php

function getGender222() {
	//do not use this function
	exit;
	$data = '<option value="">Select Gender</option>';

	$data = $data .'<option value="Male">Male</option>';
	$data = $data .'<option value="Female">Female</option>';
	$data = $data .'<option value="Other">Other</option>';

	return $data;
}

function getGender($gender = '') {
	$data = '<option value="">Select Gender</option>';
	
	$arr = array("Male", "Female", "Other");

	$i = 0;
	$size = sizeof($arr);

	for($i = 0; $i < $size; $i++) {
		if($gender == $arr[$i]) {
			$data = $data .'<option selected="selected" value="'.$arr[$i].'">'.$arr[$i].'</option>';
		} else {
		$data = $data .'<option value="'.$arr[$i].'">'.$arr[$i].'</option>';
		}
	}

	return $data;
}


function getMedicines($con, $medicineId = 0) {

	$query = "select `id`, `medicine_name` from `medicines`
	order by `medicine_name` asc;";

	$stmt = $con->prepare($query);
	try {
		$stmt->execute();

	} catch(PDOException $ex) {
		echo $ex->getTraceAsString();
		echo $ex->getMessage();
		exit;
	}

	$data = '<option value="">Select Medicine</option>';

	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if($medicineId == $row['id']) {
			$data = $data.'<option selected="selected" value="'.$row['id'].'">'.$row['medicine_name'].'</option>';

		} else {
		$data = $data.'<option value="'.$row['id'].'">'.$row['medicine_name'].'</option>';
		}
	}

	return $data;
	
}


function getPatients($con) {
$query = "select `id`, `full_name`, `phone_number`
from `clients` order by `full_name` asc;";

	$stmt = $con->prepare($query);
	try {
		$stmt->execute();

	} catch(PDOException $ex) {
		echo $ex->getTraceAsString();
		echo $ex->getMessage();
		exit;
	}

	$data = '<option value="">Select Patient</option>';

	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$data = $data.'<option value="'.$row['id'].'">'.$row['full_name'].' ('.$row['phone_number'].')'.'</option>';
	}

	return $data;
}


function getDateTextBox($label, $dateId) {

	$d = '<div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
                <div class="form-group">
                  <label>'.$label.'</label>
                  <div class="input-group rounded-0 date"
                  id=""
                  data-target-input="nearest">
                  <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" data-toggle="datetimepicker"
data-target="#'.$dateId.'" name="'.$dateId.'" id="'.$dateId.'" required="required" autocomplete="off"/>
                  <div class="input-group-append rounded-0"
                  data-target="#'.$dateId.'"
                  data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
              </div>
            </div>
          </div>';

          return $d;
}

function getAllPatientsWithHistory($con) {
    // Get all unique patient names from all history tables
    $query = "SELECT DISTINCT name, cp_number as phone_number 
              FROM (
                  SELECT name, cp_number FROM bp_monitoring
                  UNION
                  SELECT name, '' as cp_number FROM family_members
                  UNION
                  SELECT name, '' as cp_number FROM random_blood_sugar
                  UNION
                  SELECT name, '' as cp_number FROM deworming
                  UNION
                  SELECT name, '' as cp_number FROM tetanus_toxoid
                  UNION
                  SELECT name, '' as cp_number FROM family_planning
              ) AS combined_patients 
              ORDER BY name ASC";

    $stmt = $con->prepare($query);
    try {
        $stmt->execute();
    } catch(PDOException $ex) {
        echo $ex->getTraceAsString();
        echo $ex->getMessage();
        exit;
    }

    $data = '<option value="">Select Patient</option>';
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $phoneDisplay = !empty($row['phone_number']) ? ' (' . $row['phone_number'] . ')' : '';
        $data = $data.'<option value="'.htmlspecialchars($row['name']).'">'
                     .htmlspecialchars($row['name']).$phoneDisplay.'</option>';
    }
    return $data;
}

function getPatientHistory($con, $patientName) {
    $result = array(
        'family' => array(),
        'deworming' => array(),
        'bp' => array(),
        'blood_sugar' => array(),
        'tetanus' => array()
    );
    
    if (!empty($patientName)) {
        // Get family planning records
        $query = "SELECT * FROM family_planning WHERE name = ? ORDER BY date DESC";
        $stmt = $con->prepare($query);
        $stmt->execute([$patientName]);
        $result['family'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get deworming records
        $query = "SELECT * FROM deworming WHERE name = ? ORDER BY date DESC";
        $stmt = $con->prepare($query);
        $stmt->execute([$patientName]);
        $result['deworming'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get BP monitoring records
        $query = "SELECT * FROM bp_monitoring WHERE name = ? ORDER BY date DESC";
        $stmt = $con->prepare($query);
        $stmt->execute([$patientName]);
        $result['bp'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get blood sugar records
        $query = "SELECT * FROM random_blood_sugar WHERE name = ? ORDER BY date DESC";
        $stmt = $con->prepare($query);
        $stmt->execute([$patientName]);
        $result['blood_sugar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tetanus records
        $query = "SELECT * FROM tetanus_toxoid WHERE name = ? ORDER BY date DESC";
        $stmt = $con->prepare($query);
        $stmt->execute([$patientName]);
        $result['tetanus'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $result;
}

/**
 * Check if a user is concurrently logged in as both admin/staff and client
 * 
 * @return bool True if both admin/staff and client sessions are active
 */
function isConcurrentLogin() {
    $isAdminLoggedIn = isset($_SESSION['user_id']);
    $isClientLoggedIn = isset($_SESSION['client_id']);
    
    return ($isAdminLoggedIn && $isClientLoggedIn);
}

/**
 * Get the current user type (admin/staff or client)
 * 
 * @return string 'admin', 'staff', 'client', or 'guest'
 */
function getCurrentUserType() {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['role'])) {
            return $_SESSION['role']; // 'admin', 'health_worker', or 'doctor'
        }
        return 'staff';
    } elseif (isset($_SESSION['client_id'])) {
        return 'client';
    }
    return 'guest';
}
?>
