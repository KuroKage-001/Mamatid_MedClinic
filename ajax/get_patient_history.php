<?php
include '../config/connection.php';

if(isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    $query = "SELECT v.*, p.name as patient_name, m.name as medicine_name 
              FROM visits v 
              JOIN patients p ON v.patient_id = p.id 
              LEFT JOIN medicines m ON v.medicine_id = m.id 
              WHERE v.patient_id = :patient_id 
              ORDER BY v.date DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    $sno = 1;
    
    foreach($result as $row) {
        $html .= '<tr>';
        $html .= '<td class="p-1 align-middle text-center">' . $sno++ . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . date('m/d/Y', strtotime($row['date'])) . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['disease']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . ($row['alcohol'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . ($row['smoke'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . ($row['obese'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['medicine_name']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['packing']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['quantity']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['dosage']) . '</td>';
        $html .= '</tr>';
    }
    
    echo $html;
}
?>
