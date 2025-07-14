<?php
// Temporary error reporting - REMOVE IN PRODUCTION
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db_connection.php';

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
    
    // Profile picture handling
    $profileImage = 'default_client.png'; // Default image
    
    // Check if a file was uploaded
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if(in_array($_FILES['profile_picture']['type'], $allowedTypes) && $_FILES['profile_picture']['size'] <= $maxSize) {
            // Generate a unique filename
            $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadDir = '../system/client_images/';
            $uploadPath = $uploadDir . $newFileName;
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Move the uploaded file
            if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                $profileImage = $newFileName;
            } else {
                $message = 'Failed to upload profile picture. Please try again.';
            }
        } else {
            $message = 'Invalid file. Please upload a JPG, PNG or GIF image under 2MB.';
        }
    }

    // Validate passwords match
    if ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $checkQuery = "SELECT COUNT(*) as count FROM clients_user_accounts WHERE email = ?";
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

                $query = "INSERT INTO `clients_user_accounts` 
                         (`full_name`, `email`, `password`, `phone_number`, 
                          `address`, `date_of_birth`, `gender`, `profile_picture`)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $con->prepare($query);
                $stmt->execute([
                    $fullName, $email, $encryptedPassword, $phoneNumber,
                    $address, $dateOfBirth, $gender, $profileImage
                ]);

                $con->commit();
                $showSuccessAlert = true;
            } catch (PDOException $ex) {
                $con->rollback();
                // Temporary debug output - REMOVE IN PRODUCTION
                $message = 'Database error: ' . $ex->getMessage();
                
                // Log detailed information
                error_log("Registration error: " . $ex->getMessage());
                error_log("SQL Query: " . $query);
                error_log("Params: " . json_encode([
                    $fullName, $email, $encryptedPassword, $phoneNumber,
                    $address, $dateOfBirth, $gender, $profileImage
                ]));
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
    
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" href="../dist/img/logo01.png">
    
    <!-- Include jQuery and SweetAlert2 JS -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        :root {
            --primary-color: #00A9FF;
            --secondary-color: #89CFF3;
            --accent-color: #A0E9FF;
            --text-primary: #2B2A4C;
            --text-secondary: #4A4A4A;
            --bg-light: #F6F8FC;
            --border-color: #E1E6EF;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, rgba(0, 169, 255, 0.05) 0%, rgba(160, 233, 255, 0.05) 100%),
                        url('../dist/img/bg-001.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 2rem 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(3px);
        }

        .register-container {
            position: relative;
            z-index: 1;
            display: flex;
            width: 1200px;
            max-width: 95%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .register-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../dist/img/bg-001.jpg') center/cover;
            opacity: 0.15;
            mix-blend-mode: overlay;
        }

        .register-left-content {
            position: relative;
            z-index: 1;
        }

        .register-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .register-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .register-benefits {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .register-benefits li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .register-benefits li i {
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.2);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .register-right {
            flex: 1.2;
            padding: 3rem;
            display: flex;
            flex-direction: column;
        }

        .register-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 169, 255, 0.2);
            border: 2px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .register-logo img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 169, 255, 0.3);
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            flex: 1;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.9);
        }

        .input-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 169, 255, 0.1);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .input-group textarea ~ i {
            top: 1.5rem;
        }

        .input-group input:focus + i,
        .input-group select:focus + i,
        .input-group textarea:focus + i {
            color: var(--primary-color);
        }

        .register-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .register-btn:hover {
            background: #0095e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 169, 255, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #0095e0;
        }

        /* Profile picture upload styles */
        .profile-upload {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile-picture-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-picture-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
        }

        .profile-picture-overlay:hover {
            background: #0095e0;
        }

        .profile-picture-input {
            display: none;
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }

            .register-left {
                padding: 2rem;
                text-align: center;
            }

            .register-benefits li {
                justify-content: center;
            }

            .register-right {
                padding: 2rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        .modern-toast {
            background: white !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border-radius: 16px !important;
            padding: 1rem !important;
        }

        .modern-toast.success {
            border-left: 4px solid #10B981 !important;
        }

        .modern-toast.error {
            border-left: 4px solid #DC2626 !important;
        }

        .modern-toast .swal2-title {
            color: var(--text-primary) !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
        }

        .modern-toast .swal2-html-container {
            color: var(--text-secondary) !important;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <div class="register-left-content">
                <h1>Join Our Healthcare Community</h1>
                <p>Create your account to access personalized healthcare services and manage your medical needs efficiently.</p>
                
                <ul class="register-benefits">
                    <li><i class="fas fa-check"></i> Easy appointment scheduling</li>
                    <li><i class="fas fa-check"></i> Secure medical records access</li>
                    <li><i class="fas fa-check"></i> Direct communication with healthcare providers</li>
                    <li><i class="fas fa-check"></i> Health reminders and notifications</li>
                    <li><i class="fas fa-check"></i> View and manage prescriptions</li>
                </ul>
            </div>
        </div>
        
        <div class="register-right">
            <div class="register-logo">
                <img src="../dist/img/mamatid-transparent01.png" alt="System Logo">
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <!-- Profile Picture Upload -->
                <div class="profile-upload">
                    <div class="profile-picture-container">
                        <img src="../system/client_images/default_client.png" alt="Profile Picture" id="profilePreview" class="profile-picture">
                        <label for="profile_picture" class="profile-picture-overlay">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" class="profile-picture-input" accept="image/*">
                    </div>
                    <p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Upload your profile picture</p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <div class="input-group">
                            <input 
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="Enter your full name"
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                required
                            >
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <input 
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter your email"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                required
                            >
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm your password"
                                required
                            >
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <div class="input-group">
                            <input 
                                type="text"
                                id="phone_number"
                                name="phone_number"
                                placeholder="Enter your phone number"
                                value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                required
                            >
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <div class="input-group">
                            <input 
                                type="date"
                                id="date_of_birth"
                                name="date_of_birth"
                                value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>"
                                required
                            >
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <div class="input-group">
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                            <i class="fas fa-venus-mars"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <div class="input-group">
                            <textarea 
                                id="address"
                                name="address"
                                placeholder="Enter your address"
                                required
                            ><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="register-btn">
                    Create Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="client_login.php">Sign In</a></p>
            </div>
        </div>
    </div>

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
                },
                customClass: {
                    popup: 'modern-toast error'
                }
            });

            Toast.fire({
                icon: 'error',
                title: 'Registration Error',
                text: '<?php echo addslashes($message); ?>'
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
                    popup: 'modern-toast success'
                }
            }).then(function() {
                window.location.href = 'client_login.php';
            });
        });
    </script>
    <?php endif; ?>

    <!-- JavaScript for profile picture preview -->
    <script>
        $(document).ready(function() {
            // Profile picture preview functionality
            $('#profile_picture').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profilePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Click on image to trigger file input
            $('#profilePreview').click(function() {
                $('#profile_picture').click();
            });
        });
    </script>
</body>
</html> 