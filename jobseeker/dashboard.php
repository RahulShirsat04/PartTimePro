<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Verify user is a jobseeker
$user_check_sql = "SELECT user_type FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_check_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user['user_type'] !== 'jobseeker') {
    header("Location: ../auth/login.php");
    exit();
}

// Get jobseeker profile data
$profile_sql = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Calculate profile completion percentage
$total_fields = 8; // first_name, last_name, phone, address, skills, education, experience, resume
$filled_fields = 0;
$profile_fields = ['first_name', 'last_name', 'phone', 'address', 'skills', 'education', 'experience', 'resume_path'];
foreach ($profile_fields as $field) {
    if (!empty($profile[$field])) {
        $filled_fields++;
    }
}
$completion_percentage = ($filled_fields / $total_fields) * 100;

// Get application statistics
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
    FROM job_applications 
    WHERE jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$stats = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Get recent applications
$recent_applications_sql = "SELECT 
    ja.*, j.title, j.location, j.salary_range, j.job_type,
    ep.company_name
    FROM job_applications ja
    INNER JOIN jobs j ON ja.job_id = j.id
    INNER JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE ja.jobseeker_id = ?
    ORDER BY ja.application_date DESC
    LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_applications_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$recent_applications = mysqli_stmt_get_result($stmt);

// Get unread messages count
try {
    $messages_sql = "SELECT COUNT(*) as unread_count 
                     FROM messages 
                     WHERE receiver_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $messages_sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $messages = mysqli_stmt_get_result($stmt)->fetch_assoc();
} catch (Exception $e) {
    // If there's an error (table doesn't exist or column missing), set unread count to 0
    $messages = ['unread_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .progress {
            height: 10px;
        }
        .company-logo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        .default-logo {
            width: 48px;
            height: 48px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-briefcase me-2"></i>
                PartTimePro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-search me-1"></i> Find Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-file-alt me-1"></i> My Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="fas fa-envelope me-1"></i> Messages
                            <?php if ($messages['unread_count'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $messages['unread_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <h2>Welcome, <?php echo htmlspecialchars($profile['first_name']); ?>!</h2>
                <p class="text-muted">Here's an overview of your job search activities</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Applications</h6>
                        <h2 class="card-title mb-0"><?php echo $stats['total_applications']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Pending Review</h6>
                        <h2 class="card-title mb-0"><?php echo $stats['pending_applications']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Accepted</h6>
                        <h2 class="card-title mb-0"><?php echo $stats['accepted_applications']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-danger h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Rejected</h6>
                        <h2 class="card-title mb-0"><?php echo $stats['rejected_applications']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Applications -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Applications</h5>
                        <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($recent_applications) > 0): ?>
                            <?php while ($application = mysqli_fetch_assoc($recent_applications)): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="default-logo">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($application['title']); ?></h6>
                                        <p class="mb-0 small text-muted">
                                            <?php echo htmlspecialchars($application['company_name']); ?> •
                                            <?php echo htmlspecialchars($application['location']); ?> •
                                            <?php echo htmlspecialchars($application['job_type']); ?>
                                        </p>
                                        <small class="text-muted">
                                            Applied <?php echo date('M d, Y', strtotime($application['application_date'])); ?> •
                                            Status: <span class="badge bg-<?php 
                                                echo $application['status'] === 'accepted' ? 'success' : 
                                                    ($application['status'] === 'rejected' ? 'danger' : 
                                                    ($application['status'] === 'reviewed' ? 'info' : 'warning')); 
                                            ?>"><?php echo ucfirst($application['status']); ?></span>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No applications yet</p>
                                <a href="jobs.php" class="btn btn-primary">Find Jobs</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Completion -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Profile Completion</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Progress</span>
                            <span><?php echo round($completion_percentage); ?>%</span>
                        </div>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $completion_percentage; ?>%"></div>
                        </div>
                        <?php if ($completion_percentage < 100): ?>
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">Complete your profile</h6>
                                <p class="mb-0">A complete profile increases your chances of getting hired!</p>
                            </div>
                            <a href="profile.php" class="btn btn-primary w-100">Update Profile</a>
                        <?php else: ?>
                            <div class="alert alert-success" role="alert">
                                <h6 class="alert-heading">Great job!</h6>
                                <p class="mb-0">Your profile is complete and ready for employers to view.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages Preview -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Messages</h5>
                        <a href="messages.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($messages['unread_count'] > 0): ?>
                            <div class="alert alert-info mb-0" role="alert">
                                <i class="fas fa-envelope me-2"></i>
                                You have <?php echo $messages['unread_count']; ?> unread message(s)
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="text-muted mb-0">You're all caught up!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 