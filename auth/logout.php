<?php
/**
 * Logout Handler
 */

session_start();
require_once '../includes/config.php';

// Destroy session
session_destroy();

// Redirect ke home
header('Location: ' . BASE_URL);
exit;
?>
