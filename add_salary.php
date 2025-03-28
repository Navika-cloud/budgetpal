<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'user_auth';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $basic_salary = $_POST['basic_salary'];
    $hra = $_POST['hra'];
    $da = $_POST['da'];
    $pf = $_POST['pf'];
    $others = $_POST['others'];
    $salary_month = $_POST['salary_month'] . "-01"; // Add day to make complete date

    $query = "INSERT INTO salary_details (user_id, basic_salary, hra, da, pf, others, salary_month) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iddddds", $user_id, $basic_salary, $hra, $da, $pf, $others, $salary_month);
    
    if ($stmt->execute()) {
        header("Location: salary.php?success=1");
    } else {
        header("Location: salary.php?error=1");
    }
    
    $stmt->close();
}

$conn->close();
?>