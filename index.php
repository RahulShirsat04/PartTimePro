<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PartTimePro - Find Your Perfect Part-Time Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-briefcase me-2"></i>
                PartTimePro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center py-5">
        <div class="container">
            <h1 class="display-4 mb-4">Find Your Perfect Part-Time Opportunity</h1>
            <p class="lead mb-5">Connecting talented individuals with flexible part-time opportunities</p>
            
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card mb-4 hover-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-user-tie feature-icon mb-4"></i>
                            <h3>Looking for a Job?</h3>
                            <p class="mb-4">Find flexible part-time opportunities that match your schedule and skills</p>
                            <a href="auth/jobseeker-register.php" class="btn btn-primary btn-lg mb-2 w-100">Register as Job Seeker</a>
                            <a href="auth/login.php?type=jobseeker" class="btn btn-outline-primary w-100">Login as Job Seeker</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card mb-4 hover-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-building feature-icon mb-4"></i>
                            <h3>Want to Hire?</h3>
                            <p class="mb-4">Post jobs and find the perfect candidates for your requirements</p>
                            <a href="auth/employer-register.php" class="btn btn-primary btn-lg mb-2 w-100">Register as Employer</a>
                            <a href="auth/login.php?type=employer" class="btn btn-outline-primary w-100">Login as Employer</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="step-card">
                        <i class="fas fa-user-plus step-icon mb-3"></i>
                        <h4>Create Account</h4>
                        <p>Sign up as a job seeker or employer and create your profile</p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="step-card">
                        <i class="fas fa-search step-icon mb-3"></i>
                        <h4>Search or Post</h4>
                        <p>Search for jobs or post new opportunities</p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="step-card">
                        <i class="fas fa-handshake step-icon mb-3"></i>
                        <h4>Connect</h4>
                        <p>Apply for jobs or hire candidates</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Features</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-clock feature-icon-sm mb-3"></i>
                        <h5>Flexible Hours</h5>
                        <p>Work according to your schedule</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-map-marker-alt feature-icon-sm mb-3"></i>
                        <h5>Local Jobs</h5>
                        <p>Find opportunities near you</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-file-alt feature-icon-sm mb-3"></i>
                        <h5>Easy Apply</h5>
                        <p>Simple application process</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-comments feature-icon-sm mb-3"></i>
                        <h5>Direct Chat</h5>
                        <p>Communicate with employers</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">About PartTimePro</h5>
                    <p>Connecting talented individuals with flexible part-time opportunities. Making job searching and hiring easier for everyone.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="auth/jobseeker-register.php">Job Seeker Registration</a></li>
                        <li><a href="auth/employer-register.php">Employer Registration</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@parttimepro.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 234 567 8900</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Business Street, City, Country</li>
                    </ul>
                    <div class="social-icons mt-3">
                        <a href="#" class="me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4 mb-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2024 PartTimePro. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-light me-3">Privacy Policy</a>
                    <a href="#" class="text-light me-3">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 