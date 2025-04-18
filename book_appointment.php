<?php
include './config/connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("location:client_login.php");
    exit;
}

$message = '';

// Handle appointment booking
if (isset($_POST['book_appointment'])) {
    $clientId = $_SESSION['client_id'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];

    // Get client details
    $query = "SELECT * FROM clients WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        $con->beginTransaction();

        $query = "INSERT INTO appointments (
                    patient_name, phone_number, address, date_of_birth,
                    gender, appointment_date, appointment_time, reason, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = $con->prepare($query);
        $stmt->execute([
            $client['full_name'],
            $client['phone_number'],
            $client['address'],
            $client['date_of_birth'],
            $client['gender'],
            $appointmentDate,
            $appointmentTime,
            $reason
        ]);

        $con->commit();
        $message = 'Appointment booked successfully!';
        
        // Redirect to dashboard
        header("location:client_dashboard.php?message=" . urlencode($message));
        exit;
    } catch(PDOException $ex) {
        $con->rollback();
        $message = 'An error occurred. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Appointment - Mamatid Health Center</title>
    <?php include './config/site_css_links.php'; ?>
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

    <style>
        :root {
            --transition-speed: 0.3s;
        }

        /* Modern Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform var(--transition-speed);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2d;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            height: auto;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            border-color: #3699FF;
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.25);
        }

        .input-group-text {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            color: #4a5568;
            padding: 0.75rem 1rem;
        }

        .input-group > .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .input-group-prepend .input-group-text {
            border-right: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Styling */
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(54, 153, 255, 0.4);
        }

        .btn i {
            margin-right: 0.5rem;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-info {
            background: linear-gradient(135deg, #3699FF 0%, #6993FF 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.2);
        }

        /* Content Header */
        .content-header {
            padding: 20px 0;
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        /* DateTime Badge Styling */
        #datetime {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
            50% { box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
            100% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }

            .card-header {
                padding: 1.25rem;
            }

            .form-control, 
            .input-group-text {
                padding: 0.625rem 0.875rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            #datetime {
                font-size: 1rem;
                padding: 8px 15px;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini light-mode layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include './config/client_header.php'; ?>
        <?php include './config/client_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6">
                            <h1>Book Appointment</h1>
                        </div>
                        <div class="col-12 col-md-6 text-md-right mt-3 mt-md-0">
                            <span id="datetime" class="d-inline-block"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-plus mr-2"></i>
                                Appointment Details
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-info">
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Date</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="far fa-calendar-alt"></i>
                                                    </span>
                                                </div>
                                                <input type="date" class="form-control" 
                                                       name="appointment_date" required
                                                       min="<?php echo date('Y-m-d'); ?>"
                                                       placeholder="Select appointment date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Time</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="far fa-clock"></i>
                                                    </span>
                                                </div>
                                                <input type="time" class="form-control" 
                                                       name="appointment_time" required
                                                       placeholder="Select appointment time">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Reason for Appointment</label>
                                            <textarea class="form-control" name="reason" 
                                                      rows="4" required
                                                      placeholder="Please describe your reason for the appointment"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-right">
                                        <button type="submit" name="book_appointment" 
                                                class="btn btn-primary">
                                            <i class="fas fa-calendar-check"></i> Book Appointment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include './config/client_footer.php'; ?>
    </div>

    <?php include './config/site_js_links.php'; ?>
    
    <script>
        $(function() {
            // Initialize SweetAlert2 with modern styling
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Show success message if redirected from successful booking
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            if (message) {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            // Modern datetime display with animation
            function updateDateTime() {
                var now = new Date();
                var options = {
                    month: 'long',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                var formattedDateTime = now.toLocaleString('en-US', options);
                document.getElementById('datetime').innerHTML = formattedDateTime;
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });
    </script>
</body>
</html> 