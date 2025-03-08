<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['job_id']) && isset($_POST['status'])) {
    $job_id = $_POST['job_id'];
    $status = $_POST['status'];
    
    // Verify that the job belongs to the current employer
    $sql = "SELECT id FROM jobs WHERE id = ? AND employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update job status
        $sql = "UPDATE jobs SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $job_id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("location: view-job.php?id=" . $job_id . "&msg=status_updated");
        } else {
            header("location: view-job.php?id=" . $job_id . "&error=update_failed");
        }
    } else {
        header("location: jobs.php?error=unauthorized");
    }
} else {
    header("location: jobs.php");
}
exit; 