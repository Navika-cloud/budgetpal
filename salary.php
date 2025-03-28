<?php
session_start();

// Check if user is logged in
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

// Get salary details
$query = "SELECT id, basic_salary, hra, da, pf, others, salary_month, 
          (basic_salary + hra + da + others - pf) as total_salary 
          FROM salary_details 
          WHERE user_id = ? 
          ORDER BY salary_month DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .navbar {
            background-color: #343a40;
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-button {
            display: block;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            width: fit-content;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .salary-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .salary-history {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input[type="number"],
        input[type="month"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .save-button {
            background-color: #007bff;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .add-salary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">BudgetPal</div>
        <div>
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Salary Details</h2>
        
        <div class="add-salary">
            <h3>Add New Salary Details</h3>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert success">Salary details added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">Error adding salary details.</div>
            <?php endif; ?>
            <form action="add_salary.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Basic Salary</label>
                        <input type="number" name="basic_salary" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>HRA</label>
                        <input type="number" name="hra" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>DA</label>
                        <input type="number" name="da" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>PF</label>
                        <input type="number" name="pf" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Others</label>
                        <input type="number" name="others" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Month</label>
                        <input type="month" name="salary_month" required value="<?php echo date('Y-m'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn">Add Salary Details</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Basic Salary</th>
                    <th>HRA</th>
                    <th>DA</th>
                    <th>PF</th>
                    <th>Others</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('F Y', strtotime($row['salary_month'])); ?></td>
                    <td>₹<?php echo number_format($row['basic_salary'], 2); ?></td>
                    <td>₹<?php echo number_format($row['hra'], 2); ?></td>
                    <td>₹<?php echo number_format($row['da'], 2); ?></td>
                    <td>₹<?php echo number_format($row['pf'], 2); ?></td>
                    <td>₹<?php echo number_format($row['others'], 2); ?></td>
                    <td>₹<?php echo number_format($row['total_salary'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>