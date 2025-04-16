<?php
include './config/connection.php';

$message = '';
$showSuccessAlert = false;

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
                $showSuccessAlert = true;
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

    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
    
    <!-- Include jQuery and SweetAlert2 JS -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>
    <!-- Include Animate.css for smooth animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        /* Background image styling for the registration page */
        body.register-page.light-mode {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                        url('dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Registration box styling */
        .register-box {
            max-width: 600px;
            width: 95%;
            margin: 0 auto;
        }

        /* Card styling */
        .card.card-outline.card-primary {
            border-radius: 15px;
            border: none;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .card-body.register-card-body {
            border-radius: 15px;
            padding: 2rem;
        }

        /* Logo styling */
        .login-logo img {
            width: 120px;
            height: 120px;
            transition: transform 0.3s ease;
            border-radius: 50% !important;
            background: white;
            padding: 5px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .login-logo img:hover {
            transform: scale(1.05);
        }

        .text-stroked {
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        /* Form styling */
        .form-control {
            height: 45px;
            border-radius: 10px;
            border: 1px solid #e4e6ef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        textarea.form-control {
            height: auto;
            min-height: 45px;
        }

        .form-control:focus {
            border-color: #3699FF;
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .input-group-text {
            border-radius: 10px;
            border: 1px solid #e4e6ef;
            background-color: #f5f8fa;
            color: #7E8299;
        }

        /* Button styling */
        .btn-primary {
            height: 45px;
            border-radius: 10px;
            background-color: #3699FF;
            border-color: #3699FF;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #187DE4;
            border-color: #187DE4;
            transform: translateY(-1px);
        }

        /* Form labels */
        .form-label {
            color: #3F4254;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        /* Links styling */
        a {
            color: #3699FF;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #187DE4;
            text-decoration: none;
        }

        /* Modern toast styling */
        .modern-toast {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15) !important;
            backdrop-filter: blur(4px) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 12px !important;
            padding: 16px !important;
        }

        .modern-toast .swal2-title {
            color: #1a1a1a !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .modern-toast .swal2-content {
            color: #4a5568 !important;
        }

        .modern-toast .swal2-timer-progress-bar {
            background: #dc3545 !important;
            height: 3px !important;
        }

        /* Form group spacing */
        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Select styling */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
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

                <?php if (!empty($message)): ?>
                <script>
                    $(document).ready(function() {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                                toast.addEventListener('click', Swal.close)
                            },
                            customClass: {
                                popup: 'modern-toast'
                            }
                        });

                        Toast.fire({
                            icon: 'error',
                            title: 'Registration Error',
                            text: '<?php echo addslashes($message); ?>',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown animate__faster'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp animate__faster'
                            }
                        });
                    });
                </script>
                <?php endif; ?>

                <?php if ($showSuccessAlert): ?>
                <script>
                    $(document).ready(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Registration Successful!',
                            text: 'You can now login with your credentials.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'modern-toast'
                            }
                        }).then(function() {
                            window.location.href = 'client_login.php';
                        });
                    });
                </script>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control"
                                           name="full_name" required
                                           value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>"
                                           placeholder="Enter your full name">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control"
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
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control"
                                           name="password" required
                                           placeholder="Enter your password">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control"
                                           name="confirm_password" required
                                           placeholder="Confirm your password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="text" class="form-control"
                                           name="phone_number" required
                                           value="<?php echo isset($_POST['phone_number']) ? $_POST['phone_number'] : ''; ?>"
                                           placeholder="Enter your phone number">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-venus-mars"></i>
                                    </span>
                                    <select class="form-control" name="gender" required>
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
                                <label class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control"
                                           name="date_of_birth" required
                                           value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <textarea class="form-control"
                                              name="address" required
                                              placeholder="Enter your address"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" name="register"
                                    class="btn btn-primary w-100">
                                Register
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">
                        <a href="client_login.php">I already have an account</a>
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
    </script>
</body>
</html> 