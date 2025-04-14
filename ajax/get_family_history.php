<?php
include '../config/connection.php';

if(isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    $query = "SELECT fp.*, c.full_name, c.civil_status, c.educational_attainment 
              FROM family_planning fp 
              JOIN clients c ON fp.name = c.full_name 
              WHERE c.id = :patient_id 
              ORDER BY fp.date DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    $sno = 1;
    
    foreach($result as $row) {
        $html .= '<tr>';
        $html .= '<td class="p-1 align-middle text-center">' . $sno++ . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['full_name']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . date('m/d/Y', strtotime($row['date'])) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['age']) . '</td>';
        $html .= '<td class="p-1 align-middle">' . htmlspecialchars($row['address']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['civil_status']) . '</td>';
        $html .= '<td class="p-1 align-middle text-center">' . htmlspecialchars($row['educational_attainment']) . '</td>';
        $html .= '</tr>';
    }
    
    echo $html;
}
?> 