<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Handle job status updates
if(isset($_POST['update_status']) && isset($_POST['job_id']) && isset($_POST['status'])) {
    $job_id = $_POST['job_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE jobs SET status = ? WHERE id = ? AND employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $status, $job_id, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
}

// Get all jobs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total jobs count
$sql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$total_jobs = mysqli_stmt_get_result($stmt)->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $limit);

// Get jobs for current page
$sql = "SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['id'], $limit, $offset);
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - PartTimePro</title>
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
            <div class="col-md-6">
                <h2 class="mb-0">My Job Postings</h2>
                <p class="text-muted">Manage all your job listings</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="post-job.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Post New Job
                </a>
            </div>
        </div>

        <!-- Jobs List -->
        <div class="card">
            <div class="card-body">
                <?php if(mysqli_num_rows($jobs) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Salary Range</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Posted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($job = mysqli_fetch_assoc($jobs)): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($job['title']); ?></div>
                                            <div class="text-muted small">ID: #<?php echo $job['id']; ?></div>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                                        <td><?php echo htmlspecialchars($job['salary_range']); ?></td>
                                        <td>
                                            <?php
                                            $sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
                                            $stmt = mysqli_prepare($conn, $sql);
                                            mysqli_stmt_bind_param($stmt, "i", $job['id']);
                                            mysqli_stmt_execute($stmt);
                                            $app_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['count'];
                                            ?>
                                            <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                <span class="badge bg-info"><?php echo $app_count; ?> applications</span>
                                            </a>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" class="form-select form-select-sm status-select" onchange="this.form.submit()">
                                                    <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                                    <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <img src="../assets/images/no-data.svg" alt="No jobs" class="img-fluid mb-3" style="max-width: 200px;">
                        <h4>No Jobs Posted Yet</h4>
                        <p class="text-muted">Start by posting your first job opportunity</p>
                        <a href="post-job.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Post a Job
                        </a>
                    </div>
                <?php endif; ?>
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
                    Are you sure you want to delete this job posting? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="post" action="delete-job.php" class="d-inline">
                        <input type="hidden" name="job_id" id="deleteJobId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(jobId) {
            document.getElementById('deleteJobId').value = jobId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Add success message fade out
        document.addEventListener('DOMContentLoaded', function() {
            var alert = document.querySelector('.alert');
            if(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html> 