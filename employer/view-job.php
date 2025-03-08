<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify that the job exists and belongs to the current employer
$sql = "SELECT j.*, 
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id AND status = 'pending') as pending_count,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id AND status = 'accepted') as accepted_count
        FROM jobs j 
        WHERE j.id = ? AND j.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$job = mysqli_stmt_get_result($stmt)->fetch_assoc();

// If job not found or doesn't belong to current employer, redirect
if (!$job) {
    header("location: jobs.php");
    exit;
}

// Get recent applications for this job
$sql = "SELECT ja.*, js.first_name, js.last_name, js.phone, u.email 
        FROM job_applications ja 
        INNER JOIN jobseeker_profiles js ON ja.jobseeker_id = js.user_id 
        INNER JOIN users u ON js.user_id = u.id
        WHERE ja.job_id = ? 
        ORDER BY ja.application_date DESC 
        LIMIT 5";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$recent_applications = mysqli_stmt_get_result($stmt);
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
                        <a class="nav-link active" href="jobs.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="jobs.php">My Jobs</a></li>
                        <li class="breadcrumb-item active">View Job</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center">
                    <h2 class="mb-0 me-3"><?php echo htmlspecialchars($job['title']); ?></h2>
                    <span class="badge bg-<?php echo $job['status'] === 'open' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($job['status']); ?>
                    </span>
                </div>
                <p class="text-muted">Posted on <?php echo date('F d, Y', strtotime($job['created_at'])); ?></p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Job
                </a>
                <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $job_id; ?>)">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Job Details -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Job Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="text-muted mb-1">Job Type</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-briefcase me-2 text-primary"></i>
                                        <?php echo ucfirst($job['job_type']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="text-muted mb-1">Location</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="text-muted mb-1">Salary Range</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($job['salary_range']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Job Description</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        </div>

                        <?php if(!empty($job['requirements'])): ?>
                        <div>
                            <h6 class="fw-bold mb-3">Requirements</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Applications</h5>
                        <a href="view-applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($recent_applications) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($application = mysqli_fetch_assoc($recent_applications)): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                    </div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($application['email']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $application['status'] === 'accepted' ? 'success' : 
                                                            ($application['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($application['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <img src="../assets/images/no-data.svg" alt="No applications" class="img-fluid mb-3" style="max-width: 200px;">
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">Applications will appear here once candidates apply.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Application Stats -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Application Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-0">
                            <div class="col-4 text-center border-end">
                                <div class="h3 mb-1"><?php echo $job['application_count']; ?></div>
                                <div class="text-muted small">Total</div>
                            </div>
                            <div class="col-4 text-center border-end">
                                <div class="h3 mb-1"><?php echo $job['pending_count']; ?></div>
                                <div class="text-muted small">Pending</div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="h3 mb-1"><?php echo $job['accepted_count']; ?></div>
                                <div class="text-muted small">Accepted</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="update-job-status.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Job Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </form>
                        <div class="d-grid gap-2">
                            <a href="view-applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>View All Applications
                            </a>
                            <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Job Details
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $job_id; ?>)">
                                <i class="fas fa-trash me-2"></i>Delete Job
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Share Job -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Share Job</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="jobUrl" 
                                   value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/jobs/view.php?id=' . $job_id; ?>" 
                                   readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="copyJobUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="#" class="text-primary fs-5"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="text-info fs-5"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary fs-5"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-success fs-5"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
                    <p class="mb-0 text-danger"><strong>Note:</strong> All associated applications will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete-job.php" method="post" class="d-inline">
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                        <button type="submit" class="btn btn-danger">Delete Job</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(jobId) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function copyJobUrl() {
            var jobUrl = document.getElementById('jobUrl');
            jobUrl.select();
            document.execCommand('copy');
            alert('Job URL copied to clipboard!');
        }
    </script>
</body>
</html> 