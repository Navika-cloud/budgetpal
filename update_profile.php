<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Update users table
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, monthly_income = ? WHERE id = ?");
    $stmt->bind_param("ssdi", 
        $_POST['fullname'],
        $_POST['email'],
        $_POST['monthly_income'],
        $user_id
    );
    $stmt->execute();

    // Update or insert into personal_accounts
    $stmt = $conn->prepare("INSERT INTO personal_accounts (user_id, savings_goal) 
                           VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE savings_goal = ?");
    $savings_goal = $_POST['savings_goal'];
    $stmt->bind_param("idd", $user_id, $savings_goal, $savings_goal);
    $stmt->execute();

    $_SESSION['username'] = $_POST['fullname'];
    $_SESSION['success_message'] = "Profile updated successfully!";
    
    header("Location: dashboard.php");
    exit();
}
?>