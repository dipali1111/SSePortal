<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_name = $_SESSION['user_name'] ?? 'Head Master';
$user_dept = $_SESSION['user_dept'] ?? 'School Infrastructure';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'HM Dashboard | Samruddh Shala'; ?></title>
    
    <!-- Fonts & Chart.js -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f0f4f9; color: #1e293b; display: flex; min-height: 100vh; }

        .main-wrapper { flex: 1; display: flex; flex-direction: column; min-height: 100vh; overflow-x: hidden; }

        /* Top Header Navigation Bar */
        .top-header { background: #ffffff; height: 70px; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05); }
        .top-header h1 { font-size: 1.3rem; font-weight: 700; color: #0f172a; }

        .header-actions { display: flex; align-items: center; gap: 20px; }
        .notification-btn { position: relative; background: #eff6ff; border: none; padding: 10px; border-radius: 50%; cursor: pointer; }
        .notification-badge { position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; font-size: 0.65rem; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .user-profile { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 6px 14px; border-radius: 30px; border: 1px solid #e2e8f0; }
        .avatar { background: #2563eb; color: white; font-weight: 700; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
        .user-info { font-size: 0.8rem; }
        .user-info strong { display: block; color: #0f172a; }
        .user-info span { color: #64748b; font-size: 0.72rem; }

        .page-content { padding: 30px; flex: 1; }
    </style>
</head>
<body>

    <!-- Include your sidebar component -->
    <?php include 'hm_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-header">
            <h1><?php echo $page_heading ?? 'HM Dashboard'; ?></h1>
            
            <div class="header-actions">
                <button class="notification-btn">
                    🔔 <span class="notification-badge">2</span>
                </button>

                <div class="user-profile">
                    <div class="avatar">HM</div>
                    <div class="user-info">
                        <strong><?php echo htmlspecialchars($user_name); ?></strong>
                        <span><?php echo htmlspecialchars($user_dept); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body Starts Here -->
        <main class="page-content" style="padding-top: 30px;">