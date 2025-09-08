<?php
// Load configuration first
require_once __DIR__ . '/config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

// Set page variables
$pageTitle = "Admin Dashboard - " . APP_NAME;
$pageDescription = "Dashboard for managing volunteers and relief requests.";
$contentFile = "content/admin-dashboard-content.php";

// Get admin username from session
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Include layout
include __DIR__ . '/layout.php';
?>