<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SECURITY_LEVEL'])) {
    $_SESSION['SECURITY_LEVEL'] = 'low';
}

if (!function_exists('isLowSecurity')) {
    function isLowSecurity() {
        return $_SESSION['SECURITY_LEVEL'] === 'low';
    }
}
?>
