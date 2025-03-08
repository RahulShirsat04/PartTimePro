<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Fetch employer profile
$sql = "SELECT u.*, ep.* 
        FROM users u 
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
        WHERE u.id = ? AND u.user_type = 'employer'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = trim($_POST['company_name']);
    $company_description = trim($_POST['company_description']);
    $industry = trim($_POST['industry']);
    $website = trim($_POST['website']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($company_description)) $errors[] = "Company description is required";
    if (empty($industry)) $errors[] = "Industry is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) $errors[] = "Invalid website URL";
    
    // Handle logo upload
    $logo_path = $profile['logo_path'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['logo']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        } else {
            $upload_dir = "../uploads/logos/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('logo_') . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                if (!empty($logo_path) && file_exists("../" . $logo_path)) {
                    unlink("../" . $logo_path);
                }
                $logo_path = "uploads/logos/" . $file_name;
            } else {
                $errors[] = "Failed to upload logo. Please try again.";
            }
        }
    }
    
    if (empty($errors)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update user email
            $update_user_sql = "UPDATE users SET email = ? WHERE id = ?";
            $user_stmt = mysqli_prepare($conn, $update_user_sql);
            mysqli_stmt_bind_param($user_stmt, "si", $email, $_SESSION['id']);
            mysqli_stmt_execute($user_stmt);
            
            // Check if employer profile exists
            if ($profile['user_id']) {
                // Update existing profile
                $update_sql = "UPDATE employer_profiles SET 
                            company_name = ?, 
                            company_description = ?, 
                            industry = ?, 
                            website = ?, 
                            address = ?, 
                            phone = ?,
                            logo_path = ?,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($stmt, "sssssssi", 
                    $company_name, $company_description, $industry, 
                    $website, $address, $phone, $logo_path, $_SESSION['id']
                );
            } else {
                // Insert new profile
                $insert_sql = "INSERT INTO employer_profiles (
                            user_id, company_name, company_description, industry, 
                            website, address, phone, logo_path, created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "isssssss", 
                    $_SESSION['id'], $company_name, $company_description, 
                    $industry, $website, $address, $phone, $logo_path
                );
            }
            
            mysqli_stmt_execute($stmt);
            mysqli_commit($conn);
            
            // Refresh profile data
            $profile['company_name'] = $company_name;
            $profile['company_description'] = $company_description;
            $profile['industry'] = $industry;
            $profile['website'] = $website;
            $profile['address'] = $address;
            $profile['phone'] = $phone;
            $profile['email'] = $email;
            $profile['logo_path'] = $logo_path;
            
            $success = "Profile updated successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PartTimePro</title>
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
                        <a class="nav-link active" href="profile.php">
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
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </nav>
                <h2>Company Profile</h2>
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

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Profile Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="company_description" class="form-label">Company Description *</label>
                                <textarea class="form-control" id="company_description" name="company_description" 
                                          rows="4" required><?php echo htmlspecialchars($profile['company_description'] ?? ''); ?></textarea>
                                <div class="form-text">Describe your company, its mission, and what makes it unique.</div>
                            </div>

                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry *</label>
                                <input type="text" class="form-control" id="industry" name="industry" 
                                       value="<?php echo htmlspecialchars($profile['industry'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" 
                                       value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>" 
                                       placeholder="https://example.com">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" 
                                          rows="2" required><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="logo" class="form-label">Company Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="form-text">Recommended size: 200x200 pixels. Max file size: 2MB.</div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Current Logo -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Current Logo</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($profile['logo_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($profile['logo_path']); ?>" 
                                 alt="Company Logo" class="img-fluid mb-3" style="max-height: 200px;">
                        <?php else: ?>
                            <div class="text-muted py-5">
                                <i class="fas fa-building fa-4x mb-3"></i>
                                <p class="mb-0">No logo uploaded</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Tips -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Profile Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Keep your company description clear and concise
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Use a professional logo to build trust
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Provide accurate contact information
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Regularly update your profile
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