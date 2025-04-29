<?php
include './config/connection.php';

$message = '';

// Check if user_id is provided
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    try {
        // Begin transaction
        $con->beginTransaction();
        
        // First, get the user's profile picture to delete it
        $query = "SELECT profile_picture FROM users WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Delete the profile picture file if it exists
            $profilePicture = $user['profile_picture'];
            if ($profilePicture && file_exists('user_images/' . $profilePicture)) {
                unlink('user_images/' . $profilePicture);
            }
            
            // First delete related records in time_logs
            $deleteTimeLogsQuery = "DELETE FROM time_logs WHERE user_id = ?";
            $stmt = $con->prepare($deleteTimeLogsQuery);
            $stmt->execute([$userId]);
            
            // Delete related records in time_in_logs
            $deleteTimeInLogsQuery = "DELETE FROM time_in_logs WHERE user_id = ?";
            $stmt = $con->prepare($deleteTimeInLogsQuery);
            $stmt->execute([$userId]);
            
            // Then delete the user from database
            $deleteQuery = "DELETE FROM users WHERE id = ?";
            $stmt = $con->prepare($deleteQuery);
            $stmt->execute([$userId]);
            
            // Commit transaction
            $con->commit();
            $message = "User deleted successfully";
        } else {
            $message = "User not found";
        }
    } catch(PDOException $ex) {
        // Rollback transaction on error
        $con->rollback();
        $message = "Error deleting user: " . $ex->getMessage();
    }
} else {
    $message = "Invalid request";
}

// Redirect back to users page with message
header("Location: users.php?message=" . urlencode($message));
exit;
?> 