<?php
include_once 'config/db_connect.php';
include_once 'config/security_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Updated user fetching logic
if (isLowSecurity()) {
    if (isset($_GET['user_id'])) {
        $query = "SELECT * FROM users WHERE id = {$_GET['user_id']}";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        $current_user_id = $_GET['user_id']; // Store the current viewed user ID
        if (!isset($_GET['user_id']) && isset($_SESSION['user_id'])) {
            header("Location: user_dashboard.php?user_id=" . $_SESSION['user_id']);
            exit();
        }
    } else {
        $query = "SELECT * FROM users WHERE id = {$_SESSION['user_id']}";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        $current_user_id = $_SESSION['user_id'];
    }
} else {
    // High security - only allow access to own account
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $current_user_id = $_SESSION['user_id'];
}

// Handle money transfer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $transfer_type = $_POST['transfer_type'];
    $recipient = $_POST['recipient'];

    if (isLowSecurity()) {
        if ($transfer_type === 'username') {
            $query = "SELECT id FROM users WHERE username = '$recipient'";
            $result = mysqli_query($conn, $query);
            $recipient_data = mysqli_fetch_assoc($result);
            $to_user = $recipient_data['id'];
        } else {
            $to_user = $recipient;
        }

        // Use current_user_id instead of SESSION user_id
        $update_sender = "UPDATE users SET balance = balance - $amount WHERE id = $current_user_id";
        $update_receiver = "UPDATE users SET balance = balance + $amount WHERE id = $to_user";

        if(mysqli_query($conn, $update_sender) && mysqli_query($conn, $update_receiver)) {
            $success_message = "Transfer successful!";
        }
    } else {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }

        if ($transfer_type === 'username') {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $recipient);
            $stmt->execute();
            $result = $stmt->get_result();
            $recipient_data = $result->fetch_assoc();
            $to_user = $recipient_data['id'];
        } else {
            $to_user = $recipient;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $stmt->bind_param("ddd", $amount, $_SESSION['user_id'], $amount);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->bind_param("dd", $amount, $to_user);
                $stmt->execute();
                
                $conn->commit();
                $success_message = "Transfer successful!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Transfer failed";
        }
    }
}

// Generate CSRF token for high security
if (!isLowSecurity()) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <style>
        

.welcome-section {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .account-info {
            background-color: #e8f4ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .balance {
            font-size: 24px;
            color: #2c3e50;
            font-weight: bold;
        }
        .transfer-section {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .logout-btn {
            float: right;
            padding: 5px 10px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        </style>
</head>
<body>
    <div class="container">
        <?php include 'security_level.php'; ?>
        
        <a href="logout.php" class="logout-btn">Logout</a>
        <h1>User Dashboard</h1>

        <?php if(isset($success_message)): ?>
            <div class="success-message">
                <?php echo isLowSecurity() ? $success_message : htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div class="error-message">
                <?php echo isLowSecurity() ? $error_message : htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="account-info">
            <h3>Account Information</h3>
            <p>Username: <?php echo isLowSecurity() ? $user['username'] : htmlspecialchars($user['username']); ?></p>
            <p>Email: <?php echo isLowSecurity() ? $user['email'] : htmlspecialchars($user['email']); ?></p>
            <p>Current Balance: $<?php echo number_format($user['balance'], 2); ?></p>
        </div>

        <div class="transfer-section">
            <h2>Transfer Money</h2>
            <form method="POST" action="">
                <select name="transfer_type">
                    <option value="username">Transfer by Username</option>
                    <option value="userid">Transfer by User ID</option>
                </select><br>
                Recipient: <input type="text" name="recipient" required><br>
                Amount: <input type="number" name="amount" step="0.01" required><br>
                <?php if (!isLowSecurity()): ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <?php endif; ?>
                <input type="submit" value="Transfer">
            </form>
        </div>
    </div>
</body>
</html>
