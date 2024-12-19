<?php
include_once 'config/db_connect.php';
include_once 'config/security_config.php';

$registration_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'], $_POST['email'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
   
    if (isLowSecurity()) {
        $query = "INSERT INTO users (username, password, email, balance, role)
                  VALUES ('$username', '$password', '$email', 0, 'user')";
        if(mysqli_query($conn, $query)) {
            $_SESSION['registration_success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $registration_error = "Registration failed!";
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, balance, role) VALUES (?, ?, ?, 0, 'user')");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        if($stmt->execute()) {
            $_SESSION['registration_success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $registration_error = "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.1rem;
            color: #34495e;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            border-color: #3498db;
            outline: none;
        }

        .submit [type="submit"] {
            background: #3498db;
            color: white;
            padding: 8px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        
        }

        .submit [type="submit"]:hover {
            background: #2980b9;
        }

        .error-message {
            background: #ff7675;
            color: white;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
            color: #7f8c8d;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .security_level {
            margin-top: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

       
   
    <div class="container">
        <h2>Register</h2>
        <div class="security_level">
            <?php include 'security_level.php'; ?>
        </div>

        <?php if ($registration_error): ?>
            <div class="error-message"><?php echo htmlspecialchars($registration_error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="submit">
                <input type="submit" value="Register">
            </div>
            
        </form>
       
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
