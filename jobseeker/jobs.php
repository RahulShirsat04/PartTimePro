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
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the SQL query
$jobs_sql = "SELECT j.*, ep.company_name,
            (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = j.id) as application_count
            FROM jobs j
            INNER JOIN employer_profiles ep ON j.employer_id = ep.user_id
            WHERE j.status = 'open'";

$params = [];
$types = "";

if (!empty($search)) {
    $jobs_sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($location)) {
    $jobs_sql .= " AND j.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

if (!empty($job_type)) {
    $jobs_sql .= " AND j.job_type = ?";
    $params[] = $job_type;
    $types .= "s";
}

// Add sorting
switch ($sort) {
    case 'salary_high':
        $jobs_sql .= " ORDER BY j.salary_range DESC";
        break;
    case 'salary_low':
        $jobs_sql .= " ORDER BY j.salary_range ASC";
        break;
    case 'oldest':
        $jobs_sql .= " ORDER BY j.created_at ASC";
        break;
    default: // newest
        $jobs_sql .= " ORDER BY j.created_at DESC";
}

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $jobs_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt);

// Get the total number of jobs
$total_jobs = mysqli_num_rows($jobs);

// Check if user has already applied to jobs
$applied_jobs_sql = "SELECT job_id FROM job_applications WHERE jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $applied_jobs_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$applied_result = mysqli_stmt_get_result($stmt);
$applied_jobs = [];
while ($row = mysqli_fetch_assoc($applied_result)) {
    $applied_jobs[] = $row['job_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs - PartTimePro</title>
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
        .job-card {
            transition: transform 0.2s;
        }
        .job-card:hover {
            transform: translateY(-5px);
        }
        .filters {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Find Jobs</li>
                    </ol>
                </nav>
                <h2>Available Jobs</h2>
                <p class="text-muted">Found <?php echo $total_jobs; ?> jobs matching your criteria</p>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col">
                <div class="filters">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" name="location" 
                                       placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="job_type">
                                <option value="">Job Type</option>
                                <option value="part-time" <?php echo $job_type === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="temporary" <?php echo $job_type === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                                <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="salary_high" <?php echo $sort === 'salary_high' ? 'selected' : ''; ?>>Highest Salary</option>
                                <option value="salary_low" <?php echo $sort === 'salary_low' ? 'selected' : ''; ?>>Lowest Salary</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Job Listings -->
        <div class="row">
            <?php if (mysqli_num_rows($jobs) > 0): ?>
                <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card job-card h-100">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="default-logo">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?php echo htmlspecialchars($job['company_name']); ?>
                                        </h6>
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
                                        <p class="card-text text-muted small mb-3">
                                            <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 150) . '...')); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?> •
                                                <?php echo $job['application_count']; ?> applicant(s)
                                            </small>
                                            <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                <button class="btn btn-success btn-sm" disabled>
                                                    <i class="fas fa-check me-1"></i> Applied
                                                </button>
                                            <?php else: ?>
                                                <a href="view-job.php?id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                            <?php endif; ?>
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
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No Jobs Found</h5>
                        <p class="text-muted">Try adjusting your search criteria</p>
                        <a href="jobs.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 