<?php
include '../config/connection.php';

$patientId = $_GET['patient_id'] ?? 0;
$data = '';

$query = "
  SELECT 
    pv.visit_date,
    pv.disease,
    pv.alcohol,
    pv.smoke,
    pv.obese,
    pmh.quantity,
    pmh.dosage,
    md.packing,
    m.medicine_name
  FROM patient_visits pv
  LEFT JOIN patient_medication_history pmh 
         ON pv.id = pmh.patient_visit_id
  LEFT JOIN medicine_details md 
         ON pmh.medicine_details_id = md.id
  LEFT JOIN medicines m 
         ON md.medicine_id = m.id
  WHERE pv.patient_id = :patientId
  ORDER BY pv.id ASC, pmh.id ASC;
";

try {
    $stmt = $con->prepare($query);
    $stmt->bindParam(':patientId', $patientId, PDO::PARAM_INT);
    $stmt->execute();

    $i = 0;
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $i++;
        $alcohol = ($r['alcohol'] == 1) ? "Yes" : "No";
        $smoke   = ($r['smoke']   == 1) ? "Yes" : "No";
        $obese   = ($r['obese']   == 1) ? "Yes" : "No";

        // Safely handle possibly null medication fields
        $medicineName = $r['medicine_name'] ?? '';
        $packing      = $r['packing']       ?? '';
        $quantity     = $r['quantity']      ?? '';
        $dosage       = $r['dosage']        ?? '';

        $data .= '<tr>';
        $data .= '<td class="px-2 py-1 align-middle text-center">'.$i.'</td>';
        $data .= '<td class="px-2 py-1 align-middle">'.date("M d, Y", strtotime($r['visit_date'])).'</td>';
        $data .= '<td class="px-2 py-1 align-middle">'.$r['disease'].'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-center">'.$alcohol.'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-center">'.$smoke.'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-center">'.$obese.'</td>';
        $data .= '<td class="px-2 py-1 align-middle">'.$medicineName.'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-right">'.$packing.'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-right">'.$quantity.'</td>';
        $data .= '<td class="px-2 py-1 align-middle text-right">'.$dosage.'</td>';
        $data .= '</tr>';
    }
} catch(PDOException $ex) {
    echo $ex->getTraceAsString();
    echo $ex->getMessage();
    exit;
}

echo $data;
