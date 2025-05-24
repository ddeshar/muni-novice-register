<?php
$current_page = basename($_SERVER['PHP_SELF']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6f86d6">
    <title><?php echo $title ?? "Registration System"; ?></title>
    
    <!-- CSS (deferred loading for performance) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    </noscript>    <!-- Critical Styles -->
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3046c1;
            --secondary: #48c6ef;
            --transition: 0.2s;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        
        .card {
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 12px;
            transition: transform var(--transition);
        }
        
        .card:hover {
            transform: translateY(-2px);
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }
        
        /* Link Styles */
        .btn-link {
            color: var(--primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
        }
        
        .btn-link:hover {
            color: var(--primary-dark);
            background: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
        }

        /* Form Control Focus */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all var(--transition-speed);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #6f86d6;
            box-shadow: 0 0 0 0.2rem rgba(111, 134, 214, 0.25);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all var(--transition-speed);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 134, 214, 0.3);
        }
        
        /* Loader */
        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loader.active {
            display: flex;
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card {
                margin: 1rem 0;
                border-radius: 12px;
            }
            
            .table-responsive {
                margin: 0 -1rem;
                padding: 0 1rem;
                width: calc(100% + 2rem);
            }
            
            .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .btn-group .btn {
                width: auto;
                flex: 1;
                white-space: nowrap;
            }
        }
        
        /* Print Styles */
        @media print {
            body {
                background: none !important;
            }
            .card {
                box-shadow: none !important;
                border: none !important;
            }
            .no-print {
                display: none !important;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #6f86d6;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #48c6ef;
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
                    if (!this.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    } else {
                        showLoader();
                    }
                    this.classList.add('was-validated');
                });
            });
            
            // Add smooth scrolling to all links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
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
