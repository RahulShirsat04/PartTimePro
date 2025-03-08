<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// For employer pages, check if user is an employer
if(basename(dirname($_SERVER['PHP_SELF'])) === 'employer' && $_SESSION["user_type"] !== 'employer') {
    header("location: ../index.php");
    exit;
}

// For jobseeker pages, check if user is a jobseeker
if(basename(dirname($_SERVER['PHP_SELF'])) === 'jobseeker' && $_SESSION["user_type"] !== 'jobseeker') {
    header("location: ../index.php");
    exit;
}
?> 