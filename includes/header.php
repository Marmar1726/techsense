<?php
// ============================================================
// TechSense — Shared Header Partial
// Variables expected: $pageTitle (string), $bodyClass (string, optional)
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle  = $pageTitle  ?? 'TechSense';
$bodyClass  = $bodyClass  ?? '';
$isLoggedIn = isLoggedIn();
$isAdminU   = isAdmin();
$curUser    = $isLoggedIn ? currentUser() : [];
$unreadN    = $isLoggedIn ? getUnreadCount($pdo, $curUser['id']) : 0;
$flash      = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="TechSense — Intelligent IT Problem Analysis and Troubleshooting System" />
<title><?= e($pageTitle) ?> — TechSense</title>
<!-- Bootstrap 5.3 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<!-- TechSense Custom CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body class="<?= e($bodyClass) ?>">

<!-- ============================================================
     TOP NAVIGATION BAR
============================================================ -->
<nav class="navbar navbar-expand-lg navbar-dark ts-navbar" id="mainNavbar">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
      <span class="ts-logo-icon"><i class="bi bi-cpu-fill"></i></span>
      <span class="fw-bold">Tech<span class="text-ts-accent">Sense</span></span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">

      <?php if ($isLoggedIn && !$isAdminU): ?>
      <!-- USER NAV -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/troubleshoot.php"><i class="bi bi-search me-1"></i>Troubleshoot</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/knowledge-base.php"><i class="bi bi-book me-1"></i>Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/history.php"><i class="bi bi-clock-history me-1"></i>History</a></li>
      </ul>

      <?php elseif ($isLoggedIn && $isAdminU): ?>
      <!-- ADMIN NAV -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-grid me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/problems.php"><i class="bi bi-journals me-1"></i>Problems</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/users.php"><i class="bi bi-people me-1"></i>Users</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/reports.php"><i class="bi bi-bar-chart me-1"></i>Reports</a></li>
      </ul>

      <?php else: ?>
      <!-- PUBLIC NAV -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#how-it-works">How It Works</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/about.php">About</a></li>
      </ul>
      <?php endif; ?>

      <!-- RIGHT SIDE -->
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <?php if ($isLoggedIn): ?>

          <!-- Notifications -->
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>/<?= $isAdminU ? 'admin' : 'user' ?>/notifications.php" title="Notifications">
              <i class="bi bi-bell fs-5"></i>
              <?php if ($unreadN > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;"><?= $unreadN ?></span>
              <?php endif; ?>
            </a>
          </li>

          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
              <span class="ts-avatar"><?= strtoupper(substr($curUser['full_name'], 0, 1)) ?></span>
              <span class="d-none d-md-inline"><?= e($curUser['full_name']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end ts-dropdown shadow">
              <li><span class="dropdown-item-text text-muted small"><?= e($curUser['email']) ?></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/<?= $isAdminU ? 'admin' : 'user' ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <?php if ($isAdminU): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>

        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-ts-primary btn-sm px-3" href="<?= BASE_URL ?>/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div><!-- /collapse -->
  </div>
</nav>

<!-- Flash Message -->
<?php if ($flash): ?>
<div class="container-fluid px-4 pt-2">
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>
