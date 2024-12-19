<?php
include 'config/db_connect.php';
include 'config/security_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (isLowSecurity()) {
        // LOW SECURITY: Vulnerable to SQL Injection
        $query = "SELECT * FROM users WHERE username='$username' AND password='$password' AND role='admin'";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: admin_dashboard.php');
        } else {
            $error = "Invalid credentials";
        }
    } else {
        // HIGH SECURITY: Protected against SQL Injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: admin_dashboard.php');
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        .error { color: red; }
    </style>
</head>
<body>
    <?php include 'security_level.php'; ?>
    
    <h2>Admin Login</h2>
    <?php if (isset($error)) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
    
    <form method="POST" action="">
        Username: <input type="text" name="username"><br>
        Password: <input type="password" name="password"><br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
