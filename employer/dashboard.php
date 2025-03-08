<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get employer profile information
$sql = "SELECT * FROM employer_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$employer_profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Get posted jobs count
$sql = "SELECT COUNT(*) as total_jobs FROM jobs WHERE employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$jobs_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['total_jobs'];

// Get total applications received
$sql = "SELECT COUNT(*) as total_applications FROM job_applications ja 
        INNER JOIN jobs j ON ja.job_id = j.id 
        WHERE j.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$applications_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['total_applications'];

// Get recent job postings
$sql = "SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$recent_jobs = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - PartTimePro</title>
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-list me-1"></i> My Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card welcome-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($employer_profile['company_name']); ?>!</h2>
                                <p class="text-muted mb-0">Manage your job postings and applications from your dashboard</p>
                            </div>
                            <a href="post-job.php" class="btn btn-primary ms-auto">
                                <i class="fas fa-plus me-2"></i>Post New Job
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-1"><?php echo $jobs_count; ?></h3>
                                <p class="text-muted mb-0">Posted Jobs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-1"><?php echo $applications_count; ?></h3>
                                <p class="text-muted mb-0">Total Applications</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-1">Active</h3>
                                <p class="text-muted mb-0">Account Status</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Jobs -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Job Postings</h5>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($recent_jobs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Applications</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($job = mysqli_fetch_assoc($recent_jobs)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span></td>
                                                <td>
                                                    <?php
                                                    $sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
                                                    $stmt = mysqli_prepare($conn, $sql);
                                                    mysqli_stmt_bind_param($stmt, "i", $job['id']);
                                                    mysqli_stmt_execute($stmt);
                                                    $app_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['count'];
                                                    echo $app_count;
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $job['status'] === 'open' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="jobs.php" class="btn btn-outline-primary">View All Jobs</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <img src="../assets/images/no-data.svg" alt="No jobs" class="img-fluid mb-3" style="max-width: 200px;">
                                <h5>No Jobs Posted Yet</h5>
                                <p class="text-muted">Start by posting your first job opportunity</p>
                                <a href="post-job.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Post a Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="post-job.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Post New Job
                            </a>
                            <a href="applications.php" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>View Applications
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user me-2"></i>Update Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Company Profile Preview -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Company Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="company-logo mb-3">
                                <i class="fas fa-building fa-3x text-primary"></i>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($employer_profile['company_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($employer_profile['contact_person']); ?></p>
                        </div>
                        <hr>
                        <div class="company-details">
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                <?php echo htmlspecialchars($employer_profile['phone']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                <?php echo htmlspecialchars($employer_profile['address'] ?? 'Address not set'); ?>
                            </p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="profile.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 