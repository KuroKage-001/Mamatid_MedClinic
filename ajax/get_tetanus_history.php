<?php
include '../config/connection.php';

if(isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    $query = "SELECT t.*, p.name as patient_name 
              FROM tetanus_toxoid t 
              JOIN patients p ON t.patient_id = p.id 
              WHERE t.patient_id = :patient_id 
              ORDER BY t.date DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    $sno = 1;
    
    foreach($result as $row) {
        $html .= '<tr>';
        $html .= '<td class="p-1 align-middle text-center">' . $sno++ . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['patient_name']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . date('m/d/Y', strtotime($row['date'])) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['age']) . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['address']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['dose']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . date('m/d/Y', strtotime($row['next_visit'])) . '</td>';
        $html .= '</tr>';
    }
    
    echo $html;
}
?> 