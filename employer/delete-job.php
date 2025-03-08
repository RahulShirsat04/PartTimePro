<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    
    // Verify that the job belongs to the current employer
    $sql = "SELECT id FROM jobs WHERE id = ? AND employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete related applications first
            $sql = "DELETE FROM job_applications WHERE job_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $job_id);
            mysqli_stmt_execute($stmt);
            
            // Delete the job
            $sql = "DELETE FROM jobs WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $job_id);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($conn);
            
            // Redirect with success message
            header("location: jobs.php?msg=deleted");
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("location: jobs.php?error=delete_failed");
            exit;
        }
    } else {
        header("location: jobs.php?error=unauthorized");
        exit;
    }
} else {
    header("location: jobs.php");
    exit;
} 