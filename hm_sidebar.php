<?php
/**
 * Headmaster (HM) Sidebar Component
 * File: hm_sidebar.php
 */

// Determine current page for active state highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-white border-end" style="width: 280px; min-height: 100vh;">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <i class="fa-solid fa-graduation-cap fa-2x me-2 text-primary"></i>
        <span class="fs-5 fw-bold">HM Portal</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
            <a href="index.php" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : 'link-dark'; ?>">
                <i class="fa-solid fa-house me-2"></i>
                <span>Home (मुख्य पृष्ठ)</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="financial_tracking.php?role=admin" class="nav-link <?= ($currentPage === 'financial_tracking.php') ? 'active' : 'link-dark'; ?>">
                <i class="fa-solid fa-chart-line me-2"></i>
                <span>📊 Financial Tracking (वित्तीय मागोवा)</span>
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-user-circle fa-2x me-2"></i>
            <strong>Headmaster</strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
            <li><a class="dropdown-item" href="index.php">Logout</a></li>
        </ul>
    </div>
</div>