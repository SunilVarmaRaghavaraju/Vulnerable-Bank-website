<?php
include_once 'config/db_connect.php';
include_once 'config/security_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$alert_message = '';
$alert_type = '';
$search_message = '';

if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    if (isLowSecurity()) {
        $query = "DELETE FROM users WHERE id = $user_id";
        if(mysqli_query($conn, $query)) {
            $alert_message = "User successfully deleted";
            $alert_type = "success";
        } else {
            $alert_message = "Error deleting user";
            $alert_type = "error";
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if($stmt->execute()) {
            $alert_message = "User successfully deleted";
            $alert_type = "success";
        } else {
            $alert_message = "Error deleting user";
            $alert_type = "error";
        }
    }
}

if (isset($_POST['update_role'])) {
    if (isLowSecurity()) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        $query = "UPDATE users SET role = '$new_role' WHERE id = $user_id";
        if(mysqli_query($conn, $query)) {
            $alert_message = "Role successfully updated";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating role";
            $alert_type = "error";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $_POST['new_role'], $_POST['user_id']);
        if($stmt->execute()) {
            $alert_message = "Role successfully updated";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating role";
            $alert_type = "error";
        }
    }
}

if (isset($_POST['update_balance'])) {
    if (isLowSecurity()) {
        $user_id = $_POST['user_id'];
        $new_balance = $_POST['new_balance'];
        $query = "UPDATE users SET balance = $new_balance WHERE id = $user_id";
        if(mysqli_query($conn, $query)) {
            $alert_message = "Balance successfully updated";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating balance";
            $alert_type = "error";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param("di", $_POST['new_balance'], $_POST['user_id']);
        if($stmt->execute()) {
            $alert_message = "Balance successfully updated";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating balance";
            $alert_type = "error";
        }
    }
}

// Search and data retrieval
if (isLowSecurity()) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if($search != '') {
        $search_message = "Search results for: " . $search;
        $query = "SELECT * FROM users WHERE username LIKE '%$search%' 
                 OR email LIKE '%$search%' 
                 OR id LIKE '%$search%'";
    } else {
        $query = "SELECT * FROM users ORDER BY id";
    }
    $users = mysqli_query($conn, $query);

    $transactions = mysqli_query($conn, "
        SELECT t.*,
               u1.username as sender_username,
               u2.username as receiver_username
        FROM transactions t
        JOIN users u1 ON t.from_user = u1.id
        JOIN users u2 ON t.to_user = u2.id
        ORDER BY transaction_date DESC
    ");
} else {
    if(isset($_GET['search']) && $_GET['search'] !== '') {
        $search_message = "Search results for: " . htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8');
    }
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->get_result();
    
    $stmt = $conn->prepare("
        SELECT t.*,
            u1.username as sender_username,
            u2.username as receiver_username
        FROM transactions t
        JOIN users u1 ON t.from_user = u1.id
        JOIN users u2 ON t.to_user = u2.id
        ORDER BY transaction_date DESC
    ");
    $stmt->execute();
    $transactions = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        .container { 
            padding: 20px;
        }
        .section { 
            margin-bottom: 30px;
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 5px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            padding: 10px; 
            border: 1px solid #ddd; 
            text-align: left; 
        }
        th { 
            background: #f5f5f5; 
        }
        .action-form { 
            display: inline-block; 
            margin-right: 10px; 
        }
        .logout-btn { 
            float: right; 
            padding: 5px 10px; 
            background-color: #e74c3c; 
            color: white; 
            text-decoration: none; 
            border-radius: 3px; }
        .search-section { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f5f5f5; 
            border-radius: 5px; 
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .search-result-message {
            margin-top: 10px;
            padding: 10px;
            background-color: #e8f4fd;
            border-radius: 4px;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'security_level.php'; ?>
        
        <a href="logout.php" class="logout-btn">Logout</a>
        <h1>Admin Dashboard</h1>

        <?php if($alert_message): ?>
            <div class="alert alert-<?php echo $alert_type; ?>">
                <?php echo isLowSecurity() ? $alert_message : htmlspecialchars($alert_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="search-section">
            <form method="GET">
                <input type="text" name="search" value="<?php echo isset($_GET['search']) ? (isLowSecurity() ? $_GET['search'] : htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8')) : ''; ?>">
                <input type="submit" value="Search">
            </form>
            <?php if($search_message): ?>
                <div class="search-result-message">
                    <?php echo $search_message; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>User Management</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><?php echo isLowSecurity() ? $user['id'] : htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo isLowSecurity() ? $user['username'] : htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo isLowSecurity() ? $user['email'] : htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <form class="action-form" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="new_role">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <input type="submit" name="update_role" value="Update Role">
                        </form>
                    </td>
                    <td>
                        <form class="action-form" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="number" name="new_balance" value="<?php echo $user['balance']; ?>" step="0.01">
                            <input type="submit" name="update_balance" value="Update Balance">
                        </form>
                    </td>
                    <td>
                        <form class="action-form" method="GET">
                            <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                            <input type="submit" value="Delete" onclick="return confirm('Are you sure?')">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="section">
            <h2>Transaction History</h2>
            <table>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Amount</th>
                </tr>
                <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                <tr>
                    <td><?php echo isLowSecurity() ? $tx['transaction_date'] : htmlspecialchars($tx['transaction_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo isLowSecurity() ? $tx['sender_username'] : htmlspecialchars($tx['sender_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo isLowSecurity() ? $tx['receiver_username'] : htmlspecialchars($tx['receiver_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>$<?php echo number_format($tx['amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
