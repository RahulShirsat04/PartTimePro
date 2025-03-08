<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $requirements = trim($_POST["requirements"]);
    $location = trim($_POST["location"]);
    $salary_range = trim($_POST["salary_range"]);
    $job_type = $_POST["job_type"];
    
    // Basic validation
    if (empty($title) || empty($description) || empty($location) || empty($salary_range)) {
        $error = "Please fill in all required fields.";
    } else {
        // Insert job posting
        $sql = "INSERT INTO jobs (employer_id, title, description, requirements, location, salary_range, job_type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'open')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", 
            $_SESSION['id'], 
            $title, 
            $description, 
            $requirements, 
            $location, 
            $salary_range, 
            $job_type
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Job posted successfully!";
            // Clear form data after successful submission
            $_POST = array();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - PartTimePro</title>
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
            <div class="col-md-12">
                <h2>Post New Job</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="jobs.php">My Jobs</a></li>
                        <li class="breadcrumb-item active">Post New Job</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Job Posting Form -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="job_type" class="form-label">Job Type *</label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="temporary" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'temporary') ? 'selected' : ''; ?>>Temporary</option>
                                    <option value="contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="salary_range" class="form-label">Salary Range *</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                       value="<?php echo isset($_POST['salary_range']) ? htmlspecialchars($_POST['salary_range']) : ''; ?>" 
                                       placeholder="e.g., $15-20/hour or $2000-2500/month"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="6" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Describe the role, responsibilities, and what a typical day looks like.</div>
                            </div>

                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="4"><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                                <div class="form-text">List qualifications, skills, experience, or any other requirements.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="jobs.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Post Job
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Tips Card -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Tips for a Great Job Post</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Be specific about job responsibilities
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Clearly state required skills and experience
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Include working hours and schedule
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Specify location and work environment
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Be transparent about compensation
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Preview Card -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                    </div>
                    <div class="card-body">
                        <div id="preview-title" class="h5 mb-2">Job Title</div>
                        <div id="preview-type" class="badge bg-primary mb-2">Part Time</div>
                        <div id="preview-location" class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i> Location
                        </div>
                        <div id="preview-salary" class="text-muted small mb-3">
                            <i class="fas fa-money-bill-wave me-1"></i> Salary Range
                        </div>
                        <div id="preview-description" class="small text-muted">
                            Job description will appear here...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const title = document.getElementById('title');
            const jobType = document.getElementById('job_type');
            const location = document.getElementById('location');
            const salaryRange = document.getElementById('salary_range');
            const description = document.getElementById('description');

            const previewTitle = document.getElementById('preview-title');
            const previewType = document.getElementById('preview-type');
            const previewLocation = document.getElementById('preview-location');
            const previewSalary = document.getElementById('preview-salary');
            const previewDescription = document.getElementById('preview-description');

            function updatePreview() {
                previewTitle.textContent = title.value || 'Job Title';
                previewType.textContent = jobType.options[jobType.selectedIndex].text;
                previewLocation.innerHTML = `<i class="fas fa-map-marker-alt me-1"></i> ${location.value || 'Location'}`;
                previewSalary.innerHTML = `<i class="fas fa-money-bill-wave me-1"></i> ${salaryRange.value || 'Salary Range'}`;
                previewDescription.textContent = description.value || 'Job description will appear here...';
            }

            title.addEventListener('input', updatePreview);
            jobType.addEventListener('change', updatePreview);
            location.addEventListener('input', updatePreview);
            salaryRange.addEventListener('input', updatePreview);
            description.addEventListener('input', updatePreview);

            // Success message fade out
            var alert = document.querySelector('.alert-success');
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