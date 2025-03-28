<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    
    $stmt = $conn->prepare("INSERT INTO budgets (user_id, category, amount) VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE amount = ?");
    $stmt->bind_param("isdd", $user_id, $category, $amount, $amount);
    $stmt->execute();
}

// Fetch existing budgets
$stmt = $conn->prepare("SELECT category, amount FROM budgets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$budgets = $result->fetch_all(MYSQLI_ASSOC);

// Add success message handling
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_message = "Budget successfully updated!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Setup - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .budget-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .budget-card:hover {
            transform: translateY(-5px);
        }
        .category-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        .budget-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success-alert {
            animation: fadeOut 3s forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success success-alert text-center" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="budget-card card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Budget Setup</h5>
                        <a href="dashboard.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="budget-form">
                            <form method="POST" action="" class="row g-3">
                                <div class="col-md-5">
                                    <select name="category" class="form-select form-select-lg" required>
                                        <option value="">Select Category</option>
                                        <option value="Groceries"><i class="fas fa-shopping-basket"></i> Groceries</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Transport">Transport</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Shopping">Shopping</option>
                                        <option value="Healthcare">Healthcare</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="amount" class="form-control form-control-lg" placeholder="Monthly Budget" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-save me-2"></i>Set Budget
                                    </button>
                                </div>
                            </form>
                        </div>

                        <h6 class="mt-4 mb-3"><i class="fas fa-chart-pie me-2"></i>Current Budgets</h6>
                        <?php if (empty($budgets)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No budgets set yet. Start by adding a category budget above!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($budgets as $budget): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="d-flex align-items-center">
                                                    <i class="fas fa-tag category-icon text-primary"></i>
                                                    <?php echo htmlspecialchars($budget['category']); ?>
                                                </h6>
                                                <h3 class="mb-3">₹<?php echo number_format($budget['amount'], 2); ?></h3>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>