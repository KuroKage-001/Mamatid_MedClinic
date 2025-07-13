<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mamatid Health Center - Portal Selection</title>

  <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="icon" type="image/png" href="dist/img/logo01.png">

  <style>
    :root {
            --primary-color: #3699FF;
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
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.02) 0%, rgba(99, 102, 241, 0.02) 100%),
                  url('dist/img/bg-001.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

        body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.50);
            backdrop-filter: blur(3px);
    }

        .portal-container {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
      overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
      padding: 3rem;
            max-width: 800px;
            width: 95%;
    }

        .logo-container {
      text-align: center;
      margin-bottom: 2rem;
    }

        .logo-container img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      padding: 8px;
      background: white;
      box-shadow: 0 4px 15px rgba(0, 169, 255, 0.2);
      border: 2px solid var(--primary-color);
      transition: all 0.3s ease;
    }

        .logo-container img:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0, 169, 255, 0.3);
    }

        .portal-title {
            color: var(--text-primary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
      text-align: center;
    }

        .portal-subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
            margin-bottom: 3rem;
            text-align: center;
    }

        .portal-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            width: 100%;
            max-width: 700px;
    }

        .portal-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
      transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid transparent;
    }

        .portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-color);
        }

        .portal-card.admin {
            background: linear-gradient(135deg, rgba(54, 153, 255, 0.05), rgba(105, 147, 255, 0.05));
    }

        .portal-card.client {
            background: linear-gradient(135deg, rgba(27, 197, 189, 0.05), rgba(32, 201, 151, 0.05));
        }

        .portal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
    }

        .portal-card.admin .portal-icon {
      color: var(--primary-color);
    }

        .portal-card.client .portal-icon {
            color: #1BC5BD;
        }

        .portal-card h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
    }

        .portal-card p {
      color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
    }

        .portal-btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
      border: none;
            cursor: pointer;
      font-size: 1rem;
        }

        .portal-btn.admin {
            background: linear-gradient(135deg, var(--primary-color), #6993FF);
            color: white;
    }

        .portal-btn.admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(54, 153, 255, 0.4);
            color: white;
            text-decoration: none;
      }

        .portal-btn.client {
            background: linear-gradient(135deg, #1BC5BD, #20C997);
            color: white;
        }

        .portal-btn.client:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 197, 189, 0.4);
            color: white;
            text-decoration: none;
      }

        @media (max-width: 768px) {
            .portal-container {
        padding: 2rem;
      }
            
            .portal-title {
                font-size: 2rem;
    }

            .portal-cards {
                grid-template-columns: 1fr;
            }
    }
  </style>
</head>

<body>
    <div class="portal-container">
        <div class="logo-container">
            <img src="dist/img/mamatid-transparent01.png" alt="Mamatid Health Center Logo">
    </div>
    
        <h1 class="portal-title">Mamatid Health Center</h1>
        <p class="portal-subtitle">Choose your portal to continue</p>
        
        <div class="portal-cards">
            <div class="portal-card admin">
                <div class="portal-icon">
                    <i class="fas fa-user-shield"></i>
      </div>
                <h3>Admin Portal</h3>
                <p>Access administrative features, manage users, appointments, and system settings.</p>
                <a href="admin_login.php" class="portal-btn admin">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Admin Login
                </a>
      </div>
      
            <div class="portal-card client">
                <div class="portal-icon">
                    <i class="fas fa-user-injured"></i>
          </div>
                <h3>Client Portal</h3>
                <p>Book appointments, view your medical history, and manage your health records.</p>
                <a href="client_portal/client_login.php" class="portal-btn client">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Client Login
                </a>
          </div>
    </div>
  </div>
</body>
</html>