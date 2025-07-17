<?php
// Determine if we're in a subdirectory by checking the script path
$in_subdirectory = (strpos($_SERVER['SCRIPT_NAME'], '/system/') !== false);
$base_path = $in_subdirectory ? '../..' : '.';
?>
<footer class="main-footer fixed-bottom">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-copyright">
                <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="footer-brand">
                    <i class="fas fa-clinic-medical footer-icon"></i>
                    Mamatid Health Center
                </a>
                <span class="copyright-text">&copy; <?php echo date('Y');?> All rights reserved.</span>
            </div>
            <div class="footer-version">
                <span class="version-text">Version 1.0</span>
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
    min-height: 60px;
    display: flex;
    align-items: center;
}

.footer-container {
    width: 100%;
    padding: 0 1.5rem;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

/* Footer Copyright Section */
.footer-copyright {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-brand {
    color: #ffffff;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: color var(--transition-speed);
}

.footer-brand:hover {
    color: var(--footer-link);
}

.footer-icon {
    font-size: 1.2rem;
    color: var(--footer-link);
}

.copyright-text {
    margin-left: 0.5rem;
    font-size: 0.9rem;
}

/* Footer Version Section */
.version-text {
    color: var(--footer-text);
    font-size: 0.8rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .main-footer {
        min-height: auto;
        padding: 1rem;
    }
    .footer-content {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .footer-copyright {
        flex-direction: column;
        align-items: center;
    }
    .copyright-text {
        margin-left: 0;
        margin-top: 0.25rem;
    }
}

/* Control Sidebar */
.control-sidebar {
    background: var(--footer-bg) !important;
    border-left: 1px solid var(--footer-border);
}
</style>