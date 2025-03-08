<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get application ID from URL
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch application details with related information
$sql = "SELECT ja.*, 
        j.title as job_title, j.description as job_description, j.status as job_status,
        js.first_name, js.last_name, js.phone, js.address, js.education, js.experience,
        js.skills, js.resume_path,
        u.email,
        (SELECT COUNT(*) FROM job_applications WHERE jobseeker_id = ja.jobseeker_id) as total_applications,
        (SELECT COUNT(*) FROM job_applications 
         WHERE jobseeker_id = ja.jobseeker_id AND status = 'accepted') as accepted_applications
        FROM job_applications ja
        INNER JOIN jobs j ON ja.job_id = j.id
        INNER JOIN jobseeker_profiles js ON ja.jobseeker_id = js.user_id
        INNER JOIN users u ON js.user_id = u.id
        WHERE ja.id = ? AND j.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $application_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$application = mysqli_stmt_get_result($stmt)->fetch_assoc();

// If application not found or doesn't belong to current employer, redirect
if (!$application) {
    header("location: applications.php?error=not_found");
    exit;
}

// Get other applications from this jobseeker
$other_applications_sql = "SELECT ja.*, j.title as job_title, j.status as job_status
                          FROM job_applications ja
                          INNER JOIN jobs j ON ja.job_id = j.id
                          WHERE ja.jobseeker_id = ? AND ja.id != ? AND j.employer_id = ?
                          ORDER BY ja.application_date DESC";
$other_stmt = mysqli_prepare($conn, $other_applications_sql);
mysqli_stmt_bind_param($other_stmt, "iii", $application['jobseeker_id'], $application_id, $_SESSION['id']);
mysqli_stmt_execute($other_stmt);
$other_applications = mysqli_stmt_get_result($other_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-list me-1"></i> My Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="applications.php">
                            <i class="fas fa-users me-1"></i> Applications
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="applications.php">Applications</a></li>
                        <li class="breadcrumb-item active">View Application</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Application Details</h2>
                    <div class="btn-group">
                        <?php if ($application['status'] === 'pending'): ?>
                            <a href="update-application-status.php?id=<?php echo $application_id; ?>&status=accepted" 
                               class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Accept
                            </a>
                            <a href="update-application-status.php?id=<?php echo $application_id; ?>&status=rejected" 
                               class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Reject
                            </a>
                        <?php endif; ?>
                        <a href="messages.php?user=<?php echo $application['jobseeker_id']; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Message Applicant
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
            <div class="alert alert-success">
                Application status has been updated successfully.
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Main Application Details -->
            <div class="col-md-8">
                <!-- Application Status -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Application for <?php echo htmlspecialchars($application['job_title']); ?></h5>
                                <div class="text-muted">
                                    Applied on <?php echo date('F d, Y', strtotime($application['application_date'])); ?> at 
                                    <?php echo date('h:i A', strtotime($application['application_date'])); ?>
                                </div>
                            </div>
                            <span class="badge bg-<?php 
                                echo $application['status'] === 'accepted' ? 'success' : 
                                    ($application['status'] === 'rejected' ? 'danger' : 'warning'); 
                            ?> fs-6">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Applicant Details -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Applicant Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Contact Information</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-envelope me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($application['email']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-phone me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($application['phone']); ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($application['address']); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Application Statistics</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-file-alt me-2 text-primary"></i>
                                        Total Applications: <?php echo $application['total_applications']; ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle me-2 text-primary"></i>
                                        Accepted Applications: <?php echo $application['accepted_applications']; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <h6>Education</h6>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($application['education'])); ?></p>

                        <h6>Work Experience</h6>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($application['experience'])); ?></p>

                        <h6>Skills</h6>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($application['skills'])); ?></p>

                        <?php if (!empty($application['resume_path'])): ?>
                            <h6>Resume</h6>
                            <a href="../<?php echo htmlspecialchars($application['resume_path']); ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-file-pdf me-2"></i>View Resume
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cover Letter -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Cover Letter</h5>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($application['status'] === 'pending'): ?>
                                <a href="update-application-status.php?id=<?php echo $application_id; ?>&status=accepted" 
                                   class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Accept Application
                                </a>
                                <a href="update-application-status.php?id=<?php echo $application_id; ?>&status=rejected" 
                                   class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Reject Application
                                </a>
                            <?php endif; ?>
                            <a href="messages.php?user=<?php echo $application['jobseeker_id']; ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Message Applicant
                            </a>
                            <?php if (!empty($application['resume_path'])): ?>
                                <a href="../<?php echo htmlspecialchars($application['resume_path']); ?>" 
                                   class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-file-pdf me-2"></i>Download Resume
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Other Applications -->
                <?php if (mysqli_num_rows($other_applications) > 0): ?>
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Other Applications from this Candidate</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php while ($other = mysqli_fetch_assoc($other_applications)): ?>
                                <a href="view-application.php?id=<?php echo $other['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($other['job_title']); ?></h6>
                                            <small class="text-muted">
                                                Applied <?php echo date('M d, Y', strtotime($other['application_date'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo $other['status'] === 'accepted' ? 'success' : 
                                                ($other['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($other['status']); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 