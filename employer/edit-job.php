<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify that the job exists and belongs to the current employer
$sql = "SELECT * FROM jobs WHERE id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$job = mysqli_stmt_get_result($stmt)->fetch_assoc();

// If job not found or doesn't belong to current employer, redirect
if (!$job) {
    header("location: jobs.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $salary_range = trim($_POST['salary_range']);
    $status = $_POST['status'];
    
    $errors = [];
    
    // Validate required fields
    if (empty($title)) $errors[] = "Job title is required";
    if (empty($description)) $errors[] = "Job description is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($salary_range)) $errors[] = "Salary range is required";
    
    if (empty($errors)) {
        // Update job in database
        $sql = "UPDATE jobs SET 
                title = ?, 
                description = ?, 
                requirements = ?, 
                location = ?, 
                job_type = ?, 
                salary_range = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND employer_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssii", 
            $title, $description, $requirements, $location, 
            $job_type, $salary_range, $status, $job_id, $_SESSION['id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            header("location: view-job.php?id=" . $job_id . "&msg=updated");
            exit;
        } else {
            $errors[] = "Failed to update job. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - PartTimePro</title>
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
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="jobs.php">My Jobs</a></li>
                        <li class="breadcrumb-item"><a href="view-job.php?id=<?php echo $job_id; ?>">View Job</a></li>
                        <li class="breadcrumb-item active">Edit Job</li>
                    </ol>
                </nav>
                <h2>Edit Job Posting</h2>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($job['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                                <div class="form-text">Describe the role, responsibilities, and what makes this position unique.</div>
                            </div>

                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="requirements" name="requirements" 
                                          rows="4"><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                                <div class="form-text">List qualifications, skills, and experience required for this position.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($job['location']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="job_type" class="form-label">Job Type *</label>
                                    <select class="form-select" id="job_type" name="job_type" required>
                                        <option value="full-time" <?php echo $job['job_type'] === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part-time" <?php echo $job['job_type'] === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo $job['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo $job['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="salary_range" class="form-label">Salary Range *</label>
                                    <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                           value="<?php echo htmlspecialchars($job['salary_range']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Job Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Tips for a Great Job Posting</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Be specific about job responsibilities
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                List required skills and qualifications clearly
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Include salary range to attract right candidates
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Mention work schedule and location details
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Highlight unique benefits and perks
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 