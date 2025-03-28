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

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $categories = $_POST['category'];
    $amounts = $_POST['amount'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Clear existing categories for the user
        $delete_stmt = $conn->prepare("DELETE FROM budget_categories WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        
        // Insert new categories
        $insert_stmt = $conn->prepare("INSERT INTO budget_categories (user_id, category_name, amount) VALUES (?, ?, ?)");
        
        for ($i = 0; $i < count($categories); $i++) {
            if (!empty($categories[$i]) && !empty($amounts[$i])) {
                $insert_stmt->bind_param("isd", $user_id, $categories[$i], $amounts[$i]);
                $insert_stmt->execute();
            }
        }
        
        $conn->commit();
        $message = '<div class="success-message">Budget categories updated successfully!</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="error-message">Error: ' . $e->getMessage() . '</div>';
    }
}

// Calculate total budget
$total_query = "SELECT SUM(amount) as total FROM budget_categories WHERE user_id = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_budget = $total_result->fetch_assoc()['total'] ?? 0;

// Get existing budget categories
$query = "SELECT * FROM budget_categories WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Setup - BudgetPal</title>
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
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        h2 {
            color: #333;
            margin-top: 0;
            text-align: center;
        }
        .budget-form {
            margin-top: 20px;
        }
        .budget-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        input[type="text"],
        input[type="number"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex: 1;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        .btn-add {
            background-color: #6c757d;
        }
        .btn-save {
            background-color: #007bff;
            float: right;
            margin-top: 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div>BudgetPal</div>
        <a href="logout.php" style="color: white; text-decoration: none;">Logout</a>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-button">← Back to Dashboard</a>

        <div class="budget-form">
            <h2>Budget Setup</h2>
            <div class="budget-total">Total Budget: ₹<?php echo number_format($total_budget, 2); ?></div>
            
            <?php echo $message; ?>

            <form method="POST" action="" id="budgetForm">
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <div class="budget-row">
                        <input type="text" name="category[]" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                        <input type="number" name="amount[]" value="<?php echo $category['amount']; ?>" step="0.01" required>
                    </div>
                <?php endwhile; ?>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-add" onclick="addCategory()">Add Another Category</button>
                    <button type="submit" class="btn btn-save">Save Budget</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addCategory() {
            const form = document.getElementById('budgetForm');
            const newRow = document.createElement('div');
            newRow.className = 'budget-row';
            newRow.innerHTML = `
                <input type="text" name="category[]" placeholder="Category" required>
                <input type="number" name="amount[]" placeholder="Amount" step="0.01" required>
            `;
            
            const btnContainer = form.querySelector('.btn-container');
            form.insertBefore(newRow, btnContainer);
        }
    </script>
</body>
</html>