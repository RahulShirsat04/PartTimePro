<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$sql = "SELECT ja.*, j.title as job_title, j.status as job_status,
        js.first_name, js.last_name, js.phone, u.email,
        (SELECT COUNT(*) FROM job_applications WHERE jobseeker_id = ja.jobseeker_id) as total_applications
        FROM job_applications ja
        INNER JOIN jobs j ON ja.job_id = j.id
        INNER JOIN jobseeker_profiles js ON ja.jobseeker_id = js.user_id
        INNER JOIN users u ON js.user_id = u.id
        WHERE j.employer_id = ?";

$params = [$_SESSION['id']];
$types = "i";

// Add filters
if ($status !== 'all') {
    $sql .= " AND ja.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($job_id > 0) {
    $sql .= " AND ja.job_id = ?";
    $params[] = $job_id;
    $types .= "i";
}

if (!empty($search)) {
    $search = "%$search%";
    $sql .= " AND (js.first_name LIKE ? OR js.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= "ssss";
}

// Add sorting
$sql .= " ORDER BY ja.application_date DESC";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);

// Get jobs for filter dropdown
$jobs_sql = "SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY title";
$jobs_stmt = mysqli_prepare($conn, $jobs_sql);
mysqli_stmt_bind_param($jobs_stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($jobs_stmt);
$jobs = mysqli_stmt_get_result($jobs_stmt);

// Get application statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN ja.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN ja.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN ja.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM job_applications ja
    INNER JOIN jobs j ON ja.job_id = j.id
    WHERE j.employer_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_stmt_get_result($stats_stmt)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - PartTimePro</title>
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
                        <li class="breadcrumb-item active">Applications</li>
                    </ol>
                </nav>
                <h2>Job Applications</h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Applications</h6>
                                <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Pending Review</h6>
                                <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Accepted</h6>
                                <h2 class="mb-0"><?php echo $stats['accepted']; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Rejected</h6>
                                <h2 class="mb-0"><?php echo $stats['rejected']; ?></h2>
                            </div>
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="job_id" class="form-label">Filter by Job</label>
                        <select class="form-select" id="job_id" name="job_id">
                            <option value="0">All Jobs</option>
                            <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                                <option value="<?php echo $job['id']; ?>" <?php echo $job_id == $job['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Application Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, email, or job title">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Applications Table -->
        <div class="card">
            <div class="card-body">
                <?php if (mysqli_num_rows($applications) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Job</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($application = mysqli_fetch_assoc($applications)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($application['email']); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($application['phone']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($application['job_title']); ?></div>
                                            <span class="badge bg-<?php echo $application['job_status'] === 'open' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($application['job_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                            <div class="text-muted small">
                                                <?php echo date('h:i A', strtotime($application['application_date'])); ?>
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
                                            <div class="btn-group">
                                                <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" 
                                                        data-bs-toggle="dropdown"></button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($application['status'] === 'pending'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="update-application-status.php?id=<?php echo $application['id']; ?>&status=accepted">
                                                                <i class="fas fa-check me-2"></i>Accept
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="update-application-status.php?id=<?php echo $application['id']; ?>&status=rejected">
                                                                <i class="fas fa-times me-2"></i>Reject
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item" href="messages.php?user=<?php echo $application['jobseeker_id']; ?>">
                                                            <i class="fas fa-envelope me-2"></i>Message
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="../assets/images/no-data.svg" alt="No applications" class="img-fluid mb-3" style="max-width: 200px;">
                        <h5>No Applications Found</h5>
                        <p class="text-muted">
                            <?php if (!empty($search) || $status !== 'all' || $job_id > 0): ?>
                                Try adjusting your filters to see more results.
                            <?php else: ?>
                                You haven't received any job applications yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 