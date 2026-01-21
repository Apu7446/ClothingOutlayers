<?php 
/**
 * ========================================
 * RESET PASSWORD PAGE (DEPRECATED)
 * ========================================
 * Token-based reset has been removed.
 * Using security question method instead.
 * This page now redirects to forgot_password.
 */

// Redirect to security question based password reset
header('Location: index.php?page=forgot_password');
exit;
?>
