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

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the SQL query
$applications_sql = "SELECT ja.*, j.title, j.description, j.location, j.salary_range, j.job_type,
                    ep.company_name, u.email as employer_email
                    FROM job_applications ja
                    INNER JOIN jobs j ON ja.job_id = j.id
                    INNER JOIN employer_profiles ep ON j.employer_id = ep.user_id
                    INNER JOIN users u ON j.employer_id = u.id
                    WHERE ja.jobseeker_id = ?";

$params = [$_SESSION['id']];
$types = "i";

if (!empty($status)) {
    $applications_sql .= " AND ja.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $applications_sql .= " ORDER BY ja.application_date ASC";
        break;
    case 'company':
        $applications_sql .= " ORDER BY ep.company_name ASC";
        break;
    default: // newest
        $applications_sql .= " ORDER BY ja.application_date DESC";
}

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $applications_sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);

// Get application statistics
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
    FROM job_applications 
    WHERE jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$stats = mysqli_stmt_get_result($stmt)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
        .application-card {
            transition: transform 0.2s;
        }
        .application-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
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
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-search me-1"></i> Find Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="applications.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Applications</li>
                    </ol>
                </nav>
                <h2>My Applications</h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md">
                                <h3 class="mb-0"><?php echo $stats['total_applications']; ?></h3>
                                <small class="text-muted">Total Applications</small>
                            </div>
                            <div class="col-md">
                                <h3 class="mb-0 text-warning"><?php echo $stats['pending_applications']; ?></h3>
                                <small class="text-muted">Pending</small>
                            </div>
                            <div class="col-md">
                                <h3 class="mb-0 text-info"><?php echo $stats['reviewed_applications']; ?></h3>
                                <small class="text-muted">Reviewed</small>
                            </div>
                            <div class="col-md">
                                <h3 class="mb-0 text-success"><?php echo $stats['accepted_applications']; ?></h3>
                                <small class="text-muted">Accepted</small>
                            </div>
                            <div class="col-md">
                                <h3 class="mb-0 text-danger"><?php echo $stats['rejected_applications']; ?></h3>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="sort">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="company" <?php echo $sort === 'company' ? 'selected' : ''; ?>>Company Name</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications List -->
        <div class="row">
            <?php if (mysqli_num_rows($applications) > 0): ?>
                <?php while ($application = mysqli_fetch_assoc($applications)): ?>
                    <div class="col-12 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Company Logo -->
                                    <div class="col-auto">
                                        <div class="default-logo">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>

                                    <!-- Job Details -->
                                    <div class="col">
                                        <h5 class="card-title mb-1">
                                            <a href="view-job.php?id=<?php echo $application['job_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($application['title']); ?>
                                            </a>
                                        </h5>
                                        <h6 class="text-muted mb-2"><?php echo htmlspecialchars($application['company_name']); ?></h6>
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?php echo ucfirst($application['job_type']); ?></span>
                                            <?php if (!empty($application['salary_range'])): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    <?php echo htmlspecialchars($application['salary_range']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($application['location'])): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($application['location']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            Applied on <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                        </small>
                                    </div>

                                    <!-- Status and Actions -->
                                    <div class="col-auto text-end">
                                        <div class="mb-3">
                                            <span class="badge status-badge bg-<?php 
                                                echo $application['status'] === 'accepted' ? 'success' : 
                                                    ($application['status'] === 'rejected' ? 'danger' : 
                                                    ($application['status'] === 'reviewed' ? 'info' : 'warning')); 
                                            ?>">
                                                <i class="fas fa-<?php 
                                                    echo $application['status'] === 'accepted' ? 'check-circle' : 
                                                        ($application['status'] === 'rejected' ? 'times-circle' : 
                                                        ($application['status'] === 'reviewed' ? 'eye' : 'clock')); 
                                                ?> me-1"></i>
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </div>
                                        <div class="btn-group">
                                            <a href="view-job.php?id=<?php echo $application['job_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i> View Job
                                            </a>
                                            <a href="messages.php?user=<?php echo $application['employer_id']; ?>" 
                                               class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-envelope me-1"></i> Message
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col">
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5>No Applications Found</h5>
                        <p class="text-muted">You haven't applied to any jobs yet</p>
                        <a href="jobs.php" class="btn btn-primary">Find Jobs</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 