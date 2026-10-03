<?php
// ============================================================
// TechSense — Logout Handler
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();
setFlash('success', 'You have been safely signed out. See you next time!');
redirect(BASE_URL . '/login.php');
