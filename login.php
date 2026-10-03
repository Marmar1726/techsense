<?php
// ============================================================
// TechSense — User Authentication / Login
// ============================================================
$pageTitle = 'Sign In';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

redirectIfLoggedIn();

$error = '';
$username = '';
$redirect = sanitize($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = sanitize($_POST['redirect'] ?? '');

    if (!validateCsrf($token)) {
        $error = 'Security validation failed. Please refresh and try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter your username or email and your password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'This account has been disabled. Please contact the administrator.';
                } else {
                    loginUser($user);
                    setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');

                    if ($user['role'] === 'admin') {
                        redirect(BASE_URL . '/admin/dashboard.php');
                    } else {
                        if (!empty($redirect) && strpos($redirect, 'http') === false) {
                            redirect(BASE_URL . '/' . ltrim($redirect, '/'));
                        } else {
                            redirect(BASE_URL . '/user/dashboard.php');
                        }
                    }
                }
            } else {
                $error = 'Invalid username/email or password.';
            }
        } catch (Exception $e) {
            $error = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Login — TechSense</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body class="ts-auth-page">

<div class="container d-flex justify-content-center align-items-center py-5">
  <div class="ts-auth-card">
    
    <div class="text-center mb-4">
      <a href="<?= BASE_URL ?>/" class="d-inline-flex align-items-center gap-2 mb-2 text-decoration-none">
        <span class="ts-logo-icon" style="width: 42px; height: 42px;"><i class="bi bi-cpu-fill fs-5"></i></span>
        <span class="fw-bold fs-4 text-navy">Tech<span class="text-ts-accent">Sense</span></span>
      </a>
      <h5 class="fw-bold text-navy mb-1">Welcome Back</h5>
      <p class="text-muted small">Sign in to your troubleshooting account</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/login.php" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>" />

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Username or Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" name="username" class="form-control ts-form-control border-start-0 ps-0" placeholder="Enter username or email" value="<?= e($username) ?>" required autofocus />
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label small fw-semibold text-secondary mb-0">Password</label>
        </div>
        <div class="input-group mt-1">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" id="passwordInput" name="password" class="form-control ts-form-control border-start-0 border-end-0 ps-0" placeholder="Enter password" required />
          <button class="btn btn-light border border-start-0" type="button" onclick="togglePass()">
            <i class="bi bi-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="rememberMe">
          <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
        </div>
      </div>

      <button type="submit" class="btn btn-ts-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
      </button>
    </form>

    <div class="text-center mt-4 pt-2 border-top">
      <span class="text-muted small">Don't have an account? </span>
      <a href="<?= BASE_URL ?>/register.php" class="small fw-semibold text-primary">Create Account</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
  const p = document.getElementById('passwordInput');
  const icon = document.getElementById('toggleIcon');
  if (p.type === 'password') {
    p.type = 'text';
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    p.type = 'password';
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
}
</script>
</body>
</html>
