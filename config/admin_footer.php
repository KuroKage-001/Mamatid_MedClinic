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
                    <a href="<?php echo $base_path; ?>/admin_dashboard.php" class="footer-brand">
                        Mamatid Health Center
                    </a>
                </strong>
                <span class="copyright-text">&copy; <?php echo date('Y');?> All rights reserved.</span>
            </div>
            <!-- Right side: version info -->
            <div class="footer-version">
                <span class="version-badge">Version 1.0</span>
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
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
    transition: color var(--transition-speed);
    position: relative;
}

.footer-brand:hover {
    color: var(--footer-link);
    text-decoration: none;
}

.footer-brand::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--footer-link);
    transition: width var(--transition-speed);
}

.footer-brand:hover::after {
    width: 100%;
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
    background: rgba(54, 153, 255, 0.1);
    color: var(--footer-link);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    border: 1px solid rgba(54, 153, 255, 0.2);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .main-footer {
        height: auto;
        padding: 0.8rem 0;
    }

    .footer-container {
        padding: 0 1rem;
    }

    .footer-content {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }

    .footer-copyright {
        flex-direction: column;
        gap: 0.3rem;
    }

    .footer-brand {
        font-size: 0.9rem;
    }

    .copyright-text {
        margin-left: 0;
        font-size: 0.8rem;
    }

    .version-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
    }
}

@media (max-width: 480px) {
    .main-footer {
        padding: 0.5rem 0;
    }

    .footer-container {
        padding: 0 0.8rem;
    }

    .footer-content {
        gap: 0.3rem;
    }

    .footer-copyright {
        gap: 0.2rem;
    }

    .footer-brand {
        font-size: 0.8rem;
    }

    .copyright-text {
        font-size: 0.7rem;
    }

    .version-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }
}

@media (max-width: 320px) {
    .main-footer {
        padding: 0.4rem 0;
    }

    .footer-container {
        padding: 0 0.5rem;
    }

    .footer-content {
        gap: 0.2rem;
    }

    .footer-brand {
        font-size: 0.75rem;
    }

    .copyright-text {
        font-size: 0.65rem;
    }

    .version-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
    }
}

/* Control Sidebar */
.control-sidebar {
    background: var(--footer-bg) !important;
    border-left: 1px solid var(--footer-border);
}
</style>
