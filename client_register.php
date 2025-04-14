<?php
include './config/connection.php';

$message = '';

// Process registration form submission
if (isset($_POST['register'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $phoneNumber = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $dateOfBirth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];

    // Validate passwords match
    if ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $checkQuery = "SELECT COUNT(*) as count FROM clients WHERE email = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $message = 'Email already exists. Please use a different email.';
        } else {
            // All validations passed, proceed with registration
            $encryptedPassword = md5($password);

            try {
                $con->beginTransaction();

                $query = "INSERT INTO `clients` 
                         (`full_name`, `email`, `password`, `phone_number`, 
                          `address`, `date_of_birth`, `gender`)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = $con->prepare($query);
                $stmt->execute([
                    $fullName, $email, $encryptedPassword, $phoneNumber,
                    $address, $dateOfBirth, $gender
                ]);

                $con->commit();
                
                // Redirect to login page with success message
                header("location:client_login.php?message=Registration successful! Please login.");
                exit;
            } catch (PDOException $ex) {
                $con->rollback();
                $message = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Registration - Mamatid Health Center</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Additional Custom CSS -->
    <link rel="stylesheet" href="dist/css/adminlte01.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        /* Background image styling for the registration page */
        body.register-page.light-mode {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                        url('dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Registration box styling */
        .register-box {
            max-width: 600px;
            width: 95%;
            margin: 2rem auto;
        }

        /* Rounded corners for the outer card */
        .card.card-outline.card-primary {
            border-radius: 10px !important;
        }

        /* Rounded corners for the inner card body */
        .card-body.register-card-body {
            border-radius: 10px !important;
        }

        .text-stroked {
            color: white;
            text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }

        .cursor {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }

        .form-control {
            border-radius: 4px;
        }

        .input-group-text {
            border-radius: 4px;
        }
    </style>
</head>

<body class="hold-transition register-page light-mode">
    <div class="register-box">
        <!-- Logo / Branding -->
        <div class="login-logo mb-4">
            <img src="dist/img/mamatid-transparent01.png"
                 class="img-thumbnail p-0 border rounded-circle" id="system-logo" alt="System Logo">
            <div class="text-center h3 mb-0 text-stroked">
                <strong>Client Registration</strong>
            </div>
        </div>

        <!-- Registration Card -->
        <div class="card card-outline card-primary shadow">
            <div class="card-body register-card-body">
                <!-- Animated typewriter text -->
                <p class="login-box-msg">
                    <span id="typewriter-text"></span><span class="cursor">|</span>
                </p>

                <?php 
                if($message != '') {
                    echo '<div id="alertMessage" class="alert alert-danger text-center" role="alert" style="opacity: 0.9;">
                        <i class="fas fa-exclamation-circle"></i> '.$message.'
                    </div>';
                }
                ?>

                <!-- Registration Form -->
                <form method="post" class="p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg"
                                           name="full_name" required
                                           value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>"
                                           placeholder="Enter your full name">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control form-control-lg"
                                           name="email" required
                                           value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>"
                                           placeholder="Enter your email">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control form-control-lg"
                                           name="password" required
                                           placeholder="Enter your password">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control form-control-lg"
                                           name="confirm_password" required
                                           placeholder="Confirm your password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg"
                                           name="phone_number" required
                                           value="<?php echo isset($_POST['phone_number']) ? $_POST['phone_number'] : ''; ?>"
                                           placeholder="Enter your phone number">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-venus-mars"></i>
                                    </span>
                                    <select class="form-control form-control-lg" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control form-control-lg"
                                           name="date_of_birth" required
                                           value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <textarea class="form-control form-control-lg"
                                              name="address" required
                                              placeholder="Enter your address"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" name="register"
                                    class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                Register
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">
                        <a href="client_login.php">I already have an account</a>
                    </p>
                    <p class="mb-0 mt-2">
                        <a href="index.php">Admin Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include './config/site_js_links.php'; ?>

    <!-- Typewriting animation script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const text = "Create your client account";
            let index = 0;
            const speed = 50;  // Typing speed
            const pause = 2000; // Pause before repeating
            const typewriter = document.getElementById("typewriter-text");

            function typeEffect() {
                if (index < text.length) {
                    typewriter.innerHTML += text.charAt(index);
                    index++;
                    setTimeout(typeEffect, speed);
                } else {
                    // After finishing, wait, then clear and restart
                    setTimeout(() => {
                        typewriter.innerHTML = "";
                        index = 0;
                        typeEffect();
                    }, pause);
                }
            }

            typeEffect();
        });

        // Auto-hide alert after 5 seconds
        if (document.getElementById("alertMessage")) {
            setTimeout(function() {
                var alertBox = document.getElementById("alertMessage");
                alertBox.style.transition = "opacity 0.5s ease";
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html> 