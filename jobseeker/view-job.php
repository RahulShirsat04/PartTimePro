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

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch job details
$job_sql = "SELECT j.*, ep.company_name, ep.company_description,
            u.email as employer_email,
            (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = j.id) as application_count
            FROM jobs j
            INNER JOIN employer_profiles ep ON j.employer_id = ep.user_id
            INNER JOIN users u ON j.employer_id = u.id
            WHERE j.id = ? AND j.status = 'open'";
$stmt = mysqli_prepare($conn, $job_sql);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$job = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$job) {
    header("Location: jobs.php");
    exit();
}

// Check if user has already applied
$applied_sql = "SELECT * FROM job_applications WHERE job_id = ? AND jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $applied_sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$has_applied = mysqli_stmt_get_result($stmt)->num_rows > 0;

// Get jobseeker profile for application
$profile_sql = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Handle job application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied) {
    // Insert application
    $insert_sql = "INSERT INTO job_applications (job_id, jobseeker_id, status, application_date) 
                   VALUES (?, ?, 'pending', CURRENT_TIMESTAMP)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        // Send message to employer
        $message = "Hello, I have applied for the position of " . $job['title'] . ". 
                   I am interested in this opportunity and would love to discuss it further.";
        
        $message_sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at, is_read) 
                       VALUES (?, ?, ?, CURRENT_TIMESTAMP, 0)";
        $stmt = mysqli_prepare($conn, $message_sql);
        mysqli_stmt_bind_param($stmt, "iis", $_SESSION['id'], $job['employer_id'], $message);
        mysqli_stmt_execute($stmt);
        
        header("Location: view-job.php?id=" . $job_id . "&applied=1");
        exit();
    }
}

// Get application success message
$just_applied = isset($_GET['applied']) && $_GET['applied'] === '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .company-logo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }
        .default-logo {
            width: 64px;
            height: 64px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #6c757d;
        }
        .job-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .requirements-list {
            list-style-type: none;
            padding-left: 0;
        }
        .requirements-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .requirements-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="jobs.php">
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
        <!-- Success Message -->
        <?php if ($just_applied): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Your application has been submitted successfully! The employer will review it soon.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="jobs.php">Find Jobs</a></li>
                        <li class="breadcrumb-item active">Job Details</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <!-- Job Details -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="default-logo">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <h5 class="text-muted mb-2"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span>
                                    <?php if (!empty($job['salary_range'])): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($job['location'])): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($job['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?> •
                                    <?php echo $job['application_count']; ?> applicant(s)
                                </small>
                            </div>
                        </div>

                        <div class="job-details mb-4">
                            <h5>Job Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>

                            <?php if (!empty($job['requirements'])): ?>
                                <h5>Requirements</h5>
                                <ul class="requirements-list">
                                    <?php foreach (explode("\n", $job['requirements']) as $requirement): ?>
                                        <?php if (trim($requirement)): ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars(trim($requirement)); ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <?php if (!$has_applied): ?>
                            <?php if (!empty($profile['resume_path'])): ?>
                                <form method="post" class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Apply Now
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Please upload your resume in your profile before applying.
                                    <a href="profile.php" class="alert-link">Update Profile</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center">
                                <button class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-check me-2"></i>
                                    Already Applied
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Company Details -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">About the Company</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($job['company_description'])); ?></p>
                        
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo htmlspecialchars($job['employer_email']); ?>">
                                <?php echo htmlspecialchars($job['employer_email']); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Share Job -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Share This Job</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="fab fa-linkedin me-2"></i>Share on LinkedIn
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-outline-info" target="_blank">
                                <i class="fab fa-twitter me-2"></i>Share on Twitter
                            </a>
                            <button class="btn btn-outline-secondary" onclick="copyJobLink()">
                                <i class="fas fa-link me-2"></i>Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyJobLink() {
            navigator.clipboard.writeText(window.location.href);
            alert('Job link copied to clipboard!');
        }
    </script>
</body>
</html> 