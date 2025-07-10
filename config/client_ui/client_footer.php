<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';
?>
<footer class="main-footer fixed-bottom">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Left side: copyright -->
            <div class="footer-copyright">
                <strong>
                    <a href="<?php echo $base_path; ?>/client_dashboard.php" class="footer-brand">
                        <i class="fas fa-clinic-medical footer-icon"></i>
                        Mamatid Health Center
                    </a>
                </strong>
                <span class="copyright-text">&copy; <?php echo date('Y');?> All rights reserved.</span>
            </div>
            <!-- Right side: version info -->
            <div class="footer-version">
                <span class="version-badge">Client Portal Version 1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>

<style>
/* Footer Variables */
:root {
    --footer-bg: #1a1a2d;
    --footer-text: rgba(255, 255, 255, 0.7);
    --footer-link: #3699FF;
    --footer-hover: #187DE4;
    --footer-border: rgba(255, 255, 255, 0.05);
    --transition-speed: 0.3s;
}

/* Main Footer Styling */
.main-footer {
    background: var(--footer-bg);
    border-top: 1px solid var(--footer-border);
    color: var(--footer-text);
    padding: 0;
    height: 60px;
    display: flex;
    align-items: center;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.footer-container {
    width: 100%;
    padding: 0 1.5rem;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 100%;
}

/* Footer Copyright Section */
.footer-copyright {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-brand {
    display: flex;
    align-items: center;
    color: #ffffff;
    text-decoration: none;
    font-weight: 600;
    transition: color var(--transition-speed);
    gap: 0.5rem;
}

.footer-brand:hover {
    color: var(--footer-link);
    text-decoration: none;
}

.footer-icon {
    font-size: 1.2rem;
    color: var(--footer-link);
}

.copyright-text {
    color: var(--footer-text);
    margin-left: 0.5rem;
    font-size: 0.9rem;
}

/* Footer Version Section */
.footer-version {
    display: flex;
    align-items: center;
}

.version-badge {
    background: rgba(54, 153, 255, 0.15);
    color: var(--footer-link);
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .main-footer {
        height: auto;
        padding: 1rem 0;
    }

    .footer-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .footer-copyright {
        flex-direction: column;
        gap: 0.5rem;
    }

    .copyright-text {
        margin-left: 0;
    }
}

/* Control Sidebar */
.control-sidebar {
    background: var(--footer-bg) !important;
    border-left: 1px solid var(--footer-border);
}
</style> 