<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
$configPath = __DIR__ . '/config/config.php';
if (!file_exists($configPath)) {
    die('Error: config.php not found at: ' . $configPath);
}
require_once $configPath;

// Make sure no output has been sent yet
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line. Cannot start session.");
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Seva Connect - Community Support Platform'; ?> | Ujiara.com</title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Join our community service platform. Offer your help as a volunteer or request assistance during times of need. Following Sikh dharma principles of Seva (selfless service).'; ?>">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/relief.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.dataTables.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --sikh-saffron: #FF8C00;
            --sikh-navy: #003366;
            --seva-gold: #FFD700;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--sikh-navy) 0%, #004080 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>') repeat;
            background-size: 50px 50px;
        }
        
        .content-section {
            background: linear-gradient(135deg, var(--sikh-navy) 0%, #004080 100%);
            min-height: 60vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 80px 0 40px 0;
        }
        
        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>') repeat;
            background-size: 50px 50px;
        }
        
        .card-seva {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .card-seva:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }
        
        .card-volunteer {
            background: linear-gradient(135deg, var(--sikh-saffron) 0%, #FF7F00 100%);
            color: white;
        }
        
        .card-relief {
            background: linear-gradient(135deg, var(--seva-gold) 0%, #FFC700 100%);
            color: var(--sikh-navy);
        }
        
        .btn-seva {
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-volunteer {
            background: white;
            color: var(--sikh-saffron);
        }
        
        .btn-volunteer:hover {
            background: var(--sikh-navy);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-relief {
            background: var(--sikh-navy);
            color: white;
        }
        
        .btn-relief:hover {
            background: var(--sikh-saffron);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-primary-seva {
            background: var(--sikh-saffron);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-seva:hover {
            background: var(--sikh-navy);
            color: white;
            transform: scale(1.05);
        }
        
        .icon-seva {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .text-saffron {
            color: var(--sikh-saffron);
        }
        
        .text-navy {
            color: var(--sikh-navy);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .feature-medical { background: rgba(255, 140, 0, 0.1); color: var(--sikh-saffron); }
        .feature-food { background: rgba(255, 215, 0, 0.1); color: var(--seva-gold); }
        .feature-crisis { background: rgba(0, 51, 102, 0.1); color: var(--sikh-navy); }
        
        .stats-section {
            background: var(--sikh-navy);
            color: white;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--seva-gold);
        }
        
        .form-seva {
            border-radius: 15px;
            border: 2px solid var(--sikh-saffron);
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .form-seva:focus {
            border-color: var(--sikh-navy);
            box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.25);
        }
        
        .form-label-seva {
            color: var(--sikh-navy);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .hero-section, .content-section {
                min-height: auto;
                padding: 4rem 0;
            }
            
            .icon-seva {
                font-size: 3rem;
            }
            
            .btn-seva {
                padding: 12px 30px;
                font-size: 0.9rem;
            }
        }
        
        /* Additional page-specific styles */
        <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
    </style>
    
    <!-- Additional head content -->
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-heart-fill me-2 text-warning"></i>
                Seva Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'volunteer-registration.php') ? 'active' : ''; ?>" href="volunteer-registration.php">Volunteer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'relief-request.php') ? 'active' : ''; ?>" href="relief-request.php">Request Help</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="https://ujiara.com" target="_blank">
                            <i class="bi bi-house-door"></i> Ujiara.com
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main>
        <?php 
        // Include the specific page content
        if (isset($contentFile) && file_exists($contentFile)) {
            include $contentFile;
        } else {
            echo '<div class="container mt-5 pt-5"><div class="alert alert-danger">Content not found!</div></div>';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="bi bi-heart-fill text-warning me-2"></i>
                        © 2025 Seva Connect - Ujiara.com | Powered by AH&V Software Pvt. Ltd.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="https://ujiara.com" class="text-warning text-decoration-none me-3">
                        <i class="bi bi-house-door me-1"></i>
                        Back to Ujiara.com
                    </a>
                    <a href="privacy.php" class="text-light text-decoration-none me-3">
                        <i class="bi bi-shield-check me-1"></i>
                        Privacy
                    </a>
                    <a href="terms.php" class="text-light text-decoration-none">
                        <i class="bi bi-info-circle me-1"></i>
                        Terms
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5.3.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- SweetAlert2 JS --> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<!-- Your custom validation script -->
    <script src="assets/js/volunteer-script.js"></script>
    <script src="assets/js/relief-script.js"></script>
    <script src="assets/js/gps.js"></script>
    <script src="assets/js/register-volunteer.js"></script>
    <script src="assets/js/relief-request.js"></script>
    <script src="assets/js/table-toggle.js"></script>
    <!-- Common JS -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation to cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.card-seva').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current) + (target >= 1000 ? '+' : '');
            }, 20);
        }

        // Animate stats when they come into view
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numbers = entry.target.querySelectorAll('.stat-number');
                    numbers.forEach(num => {
                        const target = parseInt(num.textContent.replace(/\D/g, ''));
                        animateCounter(num, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>