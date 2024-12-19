<?php
include 'config/security_config.php';

//checks and starts session if no session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default security level if not set
if (!isset($_SESSION['SECURITY_LEVEL'])) {
    $_SESSION['SECURITY_LEVEL'] = 'low';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['SECURITY_LEVEL'] = $_POST['security_level'];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Level</title>
</head>
<body>
    <h2>Current Security Level: <?php echo strtoupper($_SESSION['SECURITY_LEVEL']); ?></h2>
    <form method="POST">
        <select name="security_level">
            <option value="low" <?php echo ($_SESSION['SECURITY_LEVEL'] === 'low') ? 'selected' : ''; ?>>Low</option>
            <option value="high" <?php echo ($_SESSION['SECURITY_LEVEL'] === 'high') ? 'selected' : ''; ?>>High</option>
        </select>
        <input type="submit" value="Change Security Level">
    </form>
</body>
</html>

