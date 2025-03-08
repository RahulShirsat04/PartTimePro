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

// Get profile data
$profile_sql = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $skills = trim($_POST['skills']);
    $education = trim($_POST['education']);
    $experience = trim($_POST['experience']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    
    // Handle profile picture upload
    $profile_picture_path = $profile['profile_picture'] ?? '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Delete old profile picture if exists
            if (!empty($profile_picture_path) && file_exists("../$profile_picture_path")) {
                unlink("../$profile_picture_path");
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $profile_picture_path = "uploads/profile_pictures/" . uniqid() . "." . $file_extension;
            
            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], "../" . $profile_picture_path)) {
                $errors[] = "Failed to upload profile picture";
            }
        } else {
            $errors[] = "Invalid profile picture format. Allowed types: JPG, PNG, GIF";
        }
    }
    
    // Handle resume upload
    $resume_path = $profile['resume_path'] ?? '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['resume']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Delete old resume if exists
            if (!empty($resume_path) && file_exists("../$resume_path")) {
                unlink("../$resume_path");
            }
            
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $resume_path = "uploads/resumes/" . uniqid() . "." . $file_extension;
            
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], "../" . $resume_path)) {
                $errors[] = "Failed to upload resume";
            }
        } else {
            $errors[] = "Invalid resume format. Allowed types: PDF, DOC, DOCX";
        }
    }
    
    if (empty($errors)) {
        // Update profile
        if ($profile) {
            $update_sql = "UPDATE jobseeker_profiles SET 
                          first_name = ?, last_name = ?, phone = ?, address = ?,
                          skills = ?, education = ?, experience = ?,
                          profile_picture = ?, resume_path = ?
                          WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "sssssssssi", 
                $first_name, $last_name, $phone, $address,
                $skills, $education, $experience,
                $profile_picture_path, $resume_path,
                $_SESSION['id']
            );
        } else {
            $insert_sql = "INSERT INTO jobseeker_profiles 
                          (user_id, first_name, last_name, phone, address,
                           skills, education, experience, profile_picture, resume_path)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "isssssssss", 
                $_SESSION['id'], $first_name, $last_name, $phone, $address,
                $skills, $education, $experience, $profile_picture_path, $resume_path
            );
        }
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: profile.php?updated=1");
            exit();
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

// Get success message
$just_updated = isset($_GET['updated']) && $_GET['updated'] === '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .default-profile-picture {
            width: 150px;
            height: 150px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 4rem;
            color: #6c757d;
        }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            display: none;
            border-radius: 50%;
            object-fit: cover;
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
        <!-- Success Message -->
        <?php if ($just_updated): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Your profile has been updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Profile</li>
                    </ol>
                </nav>
                <h2>My Profile</h2>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <!-- Profile Picture -->
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if (!empty($profile['profile_picture'])): ?>
                                <img src="../<?php echo htmlspecialchars($profile['profile_picture']); ?>" 
                                     alt="Profile Picture" class="profile-picture mb-3" id="currentProfilePicture">
                            <?php else: ?>
                                <div class="default-profile-picture mb-3" id="defaultProfilePicture">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <img id="profilePreview" src="#" alt="Preview" class="preview-image mb-3">
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" 
                                       name="profile_picture" accept="image/*">
                                <div class="form-text">Max size: 2MB. Formats: JPG, PNG, GIF</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="col-md-9">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Professional Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills</label>
                                <textarea class="form-control" id="skills" name="skills" rows="3" 
                                          placeholder="List your key skills..."><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                                <div class="form-text">Separate skills with commas</div>
                            </div>
                            <div class="mb-3">
                                <label for="education" class="form-label">Education</label>
                                <textarea class="form-control" id="education" name="education" rows="3" 
                                          placeholder="Your educational background..."><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="experience" class="form-label">Work Experience</label>
                                <textarea class="form-control" id="experience" name="experience" rows="3" 
                                          placeholder="Your work experience..."><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="resume" class="form-label">Resume</label>
                                <input type="file" class="form-control" id="resume" name="resume" 
                                       accept=".pdf,.doc,.docx">
                                <div class="form-text">Max size: 5MB. Formats: PDF, DOC, DOCX</div>
                                <?php if (!empty($profile['resume_path'])): ?>
                                    <div class="mt-2">
                                        <i class="fas fa-file-alt me-1"></i>
                                        Current Resume: 
                                        <a href="../<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                                           target="_blank">View Resume</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Hide current profile picture or default icon
                    const currentPicture = document.getElementById('currentProfilePicture');
                    const defaultPicture = document.getElementById('defaultProfilePicture');
                    if (currentPicture) currentPicture.style.display = 'none';
                    if (defaultPicture) defaultPicture.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 