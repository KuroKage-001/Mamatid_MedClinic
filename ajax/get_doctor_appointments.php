<?php
include '../config/db_connection.php';
header('Content-Type: application/json');

// Check if doctor_id is provided
if (!isset($_POST['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
    exit;
}

$doctorId = intval($_POST['doctor_id']);

try {
    // Get booked appointments for this doctor
    $appointmentsQuery = "SELECT a.*, ds.time_slot_minutes 
                         FROM appointments a 
                         JOIN admin_doctor_schedules ds ON a.schedule_id = ds.id 
                         WHERE a.doctor_id = ? AND a.status != 'cancelled'";
    $appointmentsStmt = $con->prepare($appointmentsQuery);
    $appointmentsStmt->execute([$doctorId]);
    $appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format appointments for calendar
    $calendarEvents = [];
    foreach ($appointments as $appointment) {
        $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
        $endTime = $appointmentTime + ($appointment['time_slot_minutes'] * 60);
        $isPast = $appointmentTime < time();
        
        // Set color based on status and whether it's past
        $color = '#F64E60'; // Default red for active appointments
        if ($isPast) {
            if ($appointment['status'] == 'completed') {
                $color = '#28a745'; // Green for completed
            } else {
                $color = '#6c757d'; // Gray for past but not completed
            }
        }
        
        $calendarEvents[] = [
            'id' => 'appointment_' . $appointment['id'],
            'title' => 'Booked: ' . $appointment['patient_name'] . ($isPast ? ' [Past]' : ''),
            'start' => date('Y-m-d\TH:i:s', $appointmentTime),
            'end' => date('Y-m-d\TH:i:s', $endTime),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'patient_name' => $appointment['patient_name'],
                'reason' => $appointment['reason'],
                'status' => $appointment['status'],
                'type' => 'appointment',
                'is_past' => $isPast,
                'appointment_id' => $appointment['id']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $calendarEvents
    ]);
    
} catch(PDOException $ex) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching appointments: ' . $ex->getMessage()
    ]);
}
?> 