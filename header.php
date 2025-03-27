<?php
$current_page = basename($_SERVER['PHP_SELF']);

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
} elseif (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Manager' && $_SESSION['role'] !== 'Admin')) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
} else {
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDMP - Header</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: #0a2035; /* Primary color */
            color: #ffffff;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .brand-name {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .nav-item {
            width: 100%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-link {
            padding: 15px 25px;
            color: #ffffff;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #4e73df;
        }

        .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Submenu Styles */
        .submenu {
            padding-left: 25px;
            display: none;
        }

        .submenu.active {
            display: block;
        }

        .nav-item.has-submenu > .nav-link::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 25px;
            transition: transform 0.3s;
        }

        .nav-item.has-submenu.open > .nav-link::after {
            transform: rotate(180deg);
        }

        .submenu .nav-link {
            padding: 10px 25px;
            font-size: 0.95rem;
        }

        /* Profile Dropdown */
        .profile-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background: #0a2035; /* Primary color */
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            display: none;
            min-width: 200px;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: #ffffff;
            text-decoration: none;
            transition: background 0.3s;
        }

        .profile-dropdown-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .profile-dropdown-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            transition: margin 0.3s ease;
        }

        /* Top Navigation */
        .top-nav {
            background: #0a2035 !important;
            padding: 15px 25px;
            display: flex;
            justify-content: flex-end; /* Changed to flex-end to align profile to right */
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .top-nav .profile-toggle {
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            position: relative;
        }

        .top-nav .profile-toggle i {
            font-size: 1.2rem;
            transition: transform 0.3s;
        }

        .top-nav .profile-toggle.show i {
            transform: rotate(180deg);
        }

        /* Animations */
        .nav-link, .profile-dropdown-item {
            transition: all 0.3s ease;
        }

        .sidebar, .profile-dropdown {
            transition: all 0.3s ease;
        }

        /* Responsive Adjustments */
        @media (max-width: 1199.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .top-nav {
                justify-content: space-between;
            }
        }

        /* Specific adjustments for tablets */
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .sidebar {
                width: 250px;
            }

            .sidebar-header {
                padding: 20px;
            }

            .brand-name {
                font-size: 1.5rem;
            }

            .nav-link {
                padding: 12px 20px;
            }
        }

        /* Specific adjustments for portrait tablets */
        @media (min-width: 768px) and (max-width: 1199.98px) and (orientation: portrait) {
            .sidebar {
                width: 220px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h2 class="brand-name">EDMP</h2>
            <button id="sidebar-toggle-close" class="btn d-lg-none" style="color:white; position: absolute; right: 15px; top: 15px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <?php if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Main Page</span>
                        </a>
                    </li>
                <?php } ?>

                <?php if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item">
                        <a href="manage-users.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                <?php } ?>

                <li class="nav-item">
                    <a href="customer-info.php" class="nav-link">
                        <i class="fas fa-user-friends"></i>
                        <span>Customer Information</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="ciu-inventory.php" class="nav-link">
                        <i class="fa-solid fa-keyboard"></i>
                        <span>CIU Inventory</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="online-tokens.php" class="nav-link">
                        <i class="fa-solid fa-money-check-dollar"></i>
                        <span>Online Token</span>
                    </a>
                </li>

                <?php if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item">
                        <a href="summary.php" class="nav-link">
                            <i class="fa-solid fa-chart-simple"></i>
                            <span>Summary</span>
                        </a>
                    </li>
                <?php } ?>

                <?php if ($_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-file-pen"></i>
                            <span>Logs</span>
                        </a>
                        <ul class="submenu nav flex-column">
                            <li class="nav-item">
                                <a href="audit-log.php" class="nav-link">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                    <span>Audit Log</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="user-activity-log.php" class="nav-link">
                                    <i class="fa-solid fa-users-viewfinder"></i>
                                    <span>User Log Activities</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <?php if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item">
                        <a href="new-list.php" class="nav-link">
                            <i class="fa-solid fa-upload"></i>
                            <span>Update Customer Info & Meter Status</span>
                        </a>
                    </li>
                <?php } ?>

                <?php if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') { ?>
                    <li class="nav-item">
                        <a href="edmp-db-backup.php" class="nav-link">
                            <i class="fa-solid fa-server"></i>
                            <span>Backup All Database</span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <button id="sidebar-toggle" class="btn d-lg-none" style="color:white; margin-right: auto;">
                <i class="fas fa-bars"></i>
            </button>
            <div class="profile-toggle" id="profileToggle">
                <span><?php echo $_SESSION['name']; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="profile.php" class="profile-dropdown-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="logout.php" class="profile-dropdown-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="container-fluid content">
<?php } ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile/tablet
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarToggleClose = document.getElementById('sidebar-toggle-close');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            sidebar.classList.toggle('active');
        });
    }

    if (sidebarToggleClose) {
        sidebarToggleClose.addEventListener('click', function(event) {
            event.stopPropagation();
            sidebar.classList.remove('active');
        });
    }

    // Close sidebar when clicking outside on mobile/tablet
    document.addEventListener('click', function(event) {
        const isLargeScreen = window.innerWidth >= 1200;
        if (!isLargeScreen && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });

    // Toggle profile dropdown
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            profileToggle.classList.toggle('show');
            profileDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!profileToggle.contains(event.target)) {
                profileToggle.classList.remove('show');
                profileDropdown.classList.remove('show');
            }
        });
    }

    // Toggle submenu visibility
    const submenuItems = document.querySelectorAll('.nav-item.has-submenu');

    submenuItems.forEach(item => {
        item.querySelector('.nav-link').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            item.classList.toggle('open');
            const submenu = item.querySelector('.submenu');
            submenu.classList.toggle('active');
        });
    });

    // Auto-close sidebar on large screens if it was open on small screens
    function handleResize() {
        if (window.innerWidth >= 1200) {
            sidebar.classList.remove('active');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Run once on load
});
</script>
