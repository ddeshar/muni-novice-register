<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Security Headers
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https://cdn.jsdelivr.net");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("X-XSS-Protection: 1; mode=block");

// Define page-specific meta descriptions
$meta_descriptions = [
    'index.php' => 'भिक्षु अध्ययन कार्यक्रम दर्ता फारम - Register for Buddhist Study Program at Muni Vihar. Complete online registration for Buddhist studies.',
    'admin.php' => 'Muni Vihar Registration System Admin Dashboard - Manage student registrations and program details',
    'login.php' => 'Admin Login - Secure access to Muni Vihar Registration System'
];

$meta_description = $meta_descriptions[$current_page] ?? "Muni Vihar Buddhist Study Program - Online Registration System";
?>
<!DOCTYPE html>
<html lang="ne-NP">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#8B4513">
    <title><?php
            switch ($current_page) {
                case 'index.php':
                    echo "मुनि विहार - भिक्षु अध्ययन कार्यक्रम दर्ता | Muni Vihar - Buddhist Study Program Registration";
                    break;
                case 'admin.php':
                    echo "Admin Dashboard - Muni Vihar Registration Management";
                    break;
                case 'login.php':
                    echo "Admin Login - Muni Vihar Registration System";
                    break;
                default:
                    echo $title ?? "Registration System";
            }
            ?></title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="मुनि विहार, भिक्षु अध्ययन, Buddhist Studies, Muni Vihar, Registration, Nepal">
    <meta name="author" content="Muni Vihar">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title ?? "Muni Vihar Registration System"); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST']); ?>/assets/img/teaching.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($title ?? "Muni Vihar Registration System"); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST']); ?>/assets/img/teaching.png">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">

    <!-- CSS (deferred loading for performance) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    </noscript> <!-- Critical Styles -->
    <style>
        :root {
            /* Buddhist-Inspired Color Palette */
            --primary: #8B4513;
            /* Saddle Brown - main brand color */
            --primary-dark: #654321;
            /* Darker brown for hover states */
            --primary-light: #CD853F;
            /* Peru - lighter brown for accents */
            --primary-gradient: linear-gradient(135deg, #8B4513, #654321);
            /* Primary gradient */
            --secondary: #DAA520;
            /* Goldenrod - sacred gold color */
            --secondary-dark: #B8860B;
            /* Dark goldenrod for hover */
            --secondary-light: #F0E68C;
            /* Khaki - light gold for backgrounds */
            --accent: #FF8C00;
            /* Dark orange - saffron robe color */
            --accent-light: #FFA500;
            /* Orange for highlights */

            /* Neutral Colors */
            --background: #FFF8F0;
            /* Warm off-white */
            --surface: #FFFFFF;
            /* Pure white for cards */
            --surface-soft: #F8F4EE;
            /* Soft warm background */
            --text-primary: #2D1810;
            /* Dark brown for primary text */
            --text-secondary: #6B4423;
            /* Medium brown for secondary text */
            --text-muted: #8B6F47;
            /* Light brown for muted text */
            --border-light: #E0CDA9;
            /* Light beige for borders */
            --border-dark: #C2B280;
            /* Dark beige for hover states */

            /* Status Colors */
            --success: #4A5D23;
            /* Dark olive green */
            --success-light: #8FBC8F;
            /* Sage green */
            --warning: #B8860B;
            /* Dark goldenrod */
            --warning-light: #F0E68C;
            /* Light gold */
            --danger: #8B0000;
            /* Dark red */
            --danger-light: #F4A0A0;
            /* Light red */

            /* Shadows and Effects */
            --shadow-soft: 0 2px 8px rgba(139, 69, 19, 0.1);
            --shadow-medium: 0 4px 16px rgba(139, 69, 19, 0.15);
            --shadow-strong: 0 8px 32px rgba(139, 69, 19, 0.2);
            --transition: 0.3s ease;
            --border-radius: 12px;
            --border-radius-small: 8px;
        }

        body {
            background: linear-gradient(135deg, var(--background) 0%, var(--surface-soft) 100%);
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .card {
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            border: none;
            border-radius: var(--border-radius);
            transition: all var(--transition);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-small);
            font-weight: 500;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-color: var(--primary-dark);
            color: var(--surface);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-soft);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            border-color: var(--secondary);
            color: var(--text-primary);
            box-shadow: var(--shadow-soft);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--secondary-dark), var(--secondary));
            border-color: var(--secondary-dark);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-outline-secondary {
            color: var(--secondary-dark);
            border: 2px solid var(--secondary);
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: var(--secondary);
            border-color: var(--secondary);
            color: var(--text-primary);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-color: var(--accent);
            color: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-outline-warning {
            color: var(--accent);
            border: 2px solid var(--accent);
            background: transparent;
        }

        .btn-outline-warning:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--surface);
        }

        /* Link Styles */
        .btn-link {
            color: var(--primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
        }

        .btn-link:hover {
            color: var(--primary-dark);
            background: rgba(139, 69, 19, 0.05);
            border-radius: var(--border-radius-small);
        }

        /* Form Control Styles */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(139, 69, 19, 0.15);
        }

        .form-control,
        .form-select {
            border-radius: var(--border-radius-small);
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all var(--transition);
            background-color: var(--surface);
            color: var(--text-primary);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.15);
            background-color: var(--surface);
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(139, 69, 19, 0.15);
        }

        /* Alert Styles */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-soft);
        }

        .alert-success {
            background: linear-gradient(135deg, var(--success-light), rgba(143, 188, 143, 0.8));
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-warning {
            background: linear-gradient(135deg, var(--warning-light), rgba(240, 230, 140, 0.8));
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }

        .alert-danger {
            background: linear-gradient(135deg, var(--danger-light), rgba(244, 160, 160, 0.8));
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Badge Styles */
        .badge {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .bg-success {
            background-color: var(--success) !important;
        }

        .bg-warning {
            background-color: var(--warning) !important;
        }

        .bg-danger {
            background-color: var(--danger) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .bg-secondary {
            background-color: var(--secondary) !important;
        }

        /* Text Colors */
        .text-primary {
            color: var(--primary) !important;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-success {
            color: var(--success) !important;
        }

        .text-warning {
            color: var(--warning) !important;
        }

        .text-danger {
            color: var(--danger) !important;
        }

        /* Loader */
        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 248, 240, 0.9);
            backdrop-filter: blur(5px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loader.active {
            display: flex;
        }

        .spinner-border {
            color: var(--primary) !important;
        }

        /* Mobile-First Responsive Design */
        @media (max-width: 576px) {
            :root {
                --border-radius: 10px;
                --border-radius-small: 6px;
            }

            body {
                font-size: 14px;
            }

            .container {
                padding: 0.75rem;
            }

            .card {
                margin: 0.75rem 0;
                border-radius: var(--border-radius);
            }

            .btn {
                padding: 0.875rem 1.25rem;
                font-size: 1rem;
                min-height: 48px;
                /* Touch-friendly minimum */
                border-radius: var(--border-radius-small);
            }

            .form-control,
            .form-select {
                padding: 0.875rem 1rem;
                font-size: 1rem;
                min-height: 48px;
                /* Prevent iOS zoom */
                border-radius: var(--border-radius-small);
            }

            .form-check-input {
                width: 1.25rem;
                height: 1.25rem;
            }

            .table-responsive {
                margin: 0 -0.75rem;
                padding: 0 0.75rem;
                width: calc(100% + 1.5rem);
            }

            .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .btn-group .btn {
                width: auto;
                flex: 1;
                white-space: nowrap;
                min-width: 120px;
            }
        }

        @media (min-width: 577px) and (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .card {
                margin: 1rem 0;
                border-radius: var(--border-radius);
            }
        }

        @media (min-width: 769px) {
            .card:hover {
                transform: translateY(-5px);
                box-shadow: var(--shadow-strong);
            }

            .btn:hover {
                transform: translateY(-2px);
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: none !important;
                color: #000 !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }

            .no-print {
                display: none !important;
            }

            .btn {
                border: 1px solid #000 !important;
                background: none !important;
                color: #000 !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-soft);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Accessibility Improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus indicators for keyboard navigation */
        .btn:focus,
        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --primary: #000000;
                --primary-dark: #000000;
                --text-primary: #000000;
                --background: #ffffff;
                --surface: #ffffff;
            }

            .card {
                border: 2px solid #000000;
            }
        }
    </style>

    <!-- Loading Indicator -->
    <div class="loader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script>
        // Utility Functions
        function showLoader() {
            document.querySelector('.loader').classList.add('active');
        }

        function hideLoader() {
            document.querySelector('.loader').classList.remove('active');
        }
        // Form Validation Enhancements
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    console.log('Header form validation triggered');

                    // Only validate if form doesn't already have validation
                    if (!form.classList.contains('needs-validation')) {
                        if (!this.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                        } else {
                            showLoader();
                        }
                        this.classList.add('was-validated');
                    }
                });
            });

            // Add smooth scrolling to all links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</head>

<body class="animate__animated animate__fadeIn">