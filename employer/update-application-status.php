<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Check if required parameters are set
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("location: applications.php?error=invalid_request");
    exit;
}

$application_id = (int)$_GET['id'];
$new_status = $_GET['status'];

// Validate status value
$allowed_statuses = ['pending', 'accepted', 'rejected'];
if (!in_array($new_status, $allowed_statuses)) {
    header("location: applications.php?error=invalid_status");
    exit;
}

// Verify that the application belongs to a job owned by the current employer
$sql = "SELECT ja.*, j.employer_id, j.title as job_title, 
        CONCAT(js.first_name, ' ', js.last_name) as applicant_name,
        u.email as applicant_email
        FROM job_applications ja
        INNER JOIN jobs j ON ja.job_id = j.id
        INNER JOIN jobseeker_profiles js ON ja.jobseeker_id = js.user_id
        INNER JOIN users u ON js.user_id = u.id
        WHERE ja.id = ? AND j.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $application_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$application = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$application) {
    header("location: applications.php?error=unauthorized");
    exit;
}

// Don't update if status is already set to the new status
if ($application['status'] === $new_status) {
    header("location: applications.php?msg=no_change");
    exit;
}

// Update application status
$update_sql = "UPDATE job_applications SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "si", $new_status, $application_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Send email notification to applicant
    $subject = "Application Status Update - " . $application['job_title'];
    
    $message = "Dear " . $application['applicant_name'] . ",\n\n";
    $message .= "Your application for the position of " . $application['job_title'] . " has been " . $new_status . ".\n\n";
    
    if ($new_status === 'accepted') {
        $message .= "Congratulations! The employer will contact you soon with further details.\n";
    } elseif ($new_status === 'rejected') {
        $message .= "Thank you for your interest in this position. We encourage you to apply for other opportunities that match your skills and experience.\n";
    }
    
    $message .= "\nBest regards,\nThe PartTimePro Team";
    
    // Send email (commented out for now, implement proper email sending later)
    // mail($application['applicant_email'], $subject, $message);
    
    // Create notification in the database
    $notification_sql = "INSERT INTO notifications (user_id, type, message, related_id, created_at) 
                        VALUES (?, 'application_status', ?, ?, CURRENT_TIMESTAMP)";
    $notification_message = "Your application for " . $application['job_title'] . " has been " . $new_status;
    
    $notification_stmt = mysqli_prepare($conn, $notification_sql);
    mysqli_stmt_bind_param($notification_stmt, "isi", 
        $application['jobseeker_id'], 
        $notification_message,
        $application_id
    );
    mysqli_stmt_execute($notification_stmt);
    
    header("location: applications.php?msg=status_updated");
} else {
    header("location: applications.php?error=update_failed");
}
exit; 