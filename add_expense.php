<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add this code to fetch recent expenses
$recent_expenses = [];
if (isset($_SESSION['user_id'])) {
    try {
        $conn = getDBConnection();
        $sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $recent_expenses = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$success_message = ''; // Add this at the top with other variables

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = getDBConnection();
        $user_id = $_SESSION['user_id'];
        $amount = floatval($_POST['amount']);
        $category = $_POST['category'];
        $description = $_POST['description'] ?? '';
        $date = $_POST['date'];

        if ($amount <= 0 || empty($category)) {
            throw new Exception("Please fill in all required fields");
        }

        $sql = "INSERT INTO expenses (user_id, amount, category, description, date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsss", $user_id, $amount, $category, $description, $date);

        if ($stmt->execute()) {
            $success_message = "Expense added successfully!";
            // Refresh the recent expenses list
            $sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 5";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $recent_expenses = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Keep all styles in one place */
        body { 
            background-color: #f5f5f5; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .container {
            max-width: 1000px;
            padding: 20px;
            margin: 0 auto;
        }
        .page-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin: 30px auto;
        }
        .expense-form {
            max-width: 400px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        .amount-input {
            font-size: 20px;
            height: 50px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
        }
        .amount-input:focus {
            border-color: #0d6efd;
            box-shadow: none;
        }
        .quick-amounts {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 25px;
        }
        .quick-amount {
            padding: 8px 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-amount:hover {
            background: #f8f9fa;
            border-color: #0d6efd;
        }
        .categories {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .category {
            text-align: center;
            padding: 15px 10px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .category:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        .category.active {
            background: #e7f1ff;
            border-color: #0d6efd;
            color: #0d6efd;
        }
        .category i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            border-radius: 15px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .btn-submit {
            padding: 12px;
            font-size: 16px;
            border-radius: 15px;
            background: #0d6efd;
            border: none;
            color: white;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Expense Management</h3>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <div class="page-layout">
            <div class="expense-form">
                <form method="POST" class="needs-validation" novalidate>
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <input type="number" name="amount" class="form-control amount-input" placeholder="₹ Enter Amount" required>
                    
                    <div class="quick-amounts">
                        <div class="quick-amount">₹100</div>
                        <div class="quick-amount">₹500</div>
                        <div class="quick-amount">₹1000</div>
                        <div class="quick-amount">₹2000</div>
                    </div>
    
                    <input type="hidden" name="category" id="selected-category" required>
                    <div class="categories">
                        <div class="category" data-category="groceries">
                            <i class="fas fa-shopping-basket"></i>
                            <div>Groceries</div>
                        </div>
                        <div class="category" data-category="utilities">
                            <i class="fas fa-bolt"></i>
                            <div>Utilities</div>
                        </div>
                        <div class="category" data-category="transportation">
                            <i class="fas fa-car"></i>
                            <div>Transport</div>
                        </div>
                        <div class="category" data-category="entertainment">
                            <i class="fas fa-film"></i>
                            <div>Entertainment</div>
                        </div>
                        <div class="category" data-category="healthcare">
                            <i class="fas fa-heart"></i>
                            <div>Healthcare</div>
                        </div>
                        <div class="category" data-category="shopping">
                            <i class="fas fa-shopping-bag"></i>
                            <div>Shopping</div>
                        </div>
                    </div>
    
                    <div class="mb-3">
                        <textarea name="description" class="form-control" rows="2" 
                                  placeholder="Add a note (optional)"></textarea>
                    </div>
    
                    <div class="mb-3">
                        <input type="date" name="date" class="form-control" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
    
                    <button type="submit" class="btn-submit">
                        Add Expense
                    </button>
                </form>
            </div>

            <div class="expense-history">
                <!-- Keep the expense history section -->
                <style>
                    /* Add these table styles to your existing styles */
                    .expense-table {
                        width: 100%;
                        border-collapse: collapse;
                        background: white;
                        border-radius: 15px;
                        overflow: hidden;
                    }
                    .expense-table th {
                        background: #f8f9fa;
                        padding: 15px;
                        text-align: left;
                        font-weight: 600;
                        color: #495057;
                        border-bottom: 2px solid #dee2e6;
                    }
                    .expense-table td {
                        padding: 15px;
                        border-bottom: 1px solid #dee2e6;
                        vertical-align: middle;
                    }
                    .expense-table tr:last-child td {
                        border-bottom: none;
                    }
                    .category-cell {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }
                    .category-icon {
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #f8f9fa;
                        border-radius: 8px;
                        color: #0d6efd;
                    }
                </style>
                
                <!-- Replace the expense-history content with this table structure -->
                <div class="expense-history">
                    <h4 class="mb-4">Recent Expenses</h4>
                    <?php if (!empty($recent_expenses)): ?>
                        <table class="expense-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_expenses as $expense): ?>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <div class="category-icon">
                                                    <?php
                                                    $icon = '';
                                                    switch ($expense['category']) {
                                                        case 'groceries': $icon = 'fa-shopping-basket'; break;
                                                        case 'utilities': $icon = 'fa-bolt'; break;
                                                        case 'transportation': $icon = 'fa-car'; break;
                                                        case 'entertainment': $icon = 'fa-film'; break;
                                                        case 'healthcare': $icon = 'fa-heart'; break;
                                                        case 'shopping': $icon = 'fa-shopping-bag'; break;
                                                        default: $icon = 'fa-receipt';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </div>
                                                <?php echo ucfirst($expense['category']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($expense['date'])); ?></td>
                                        <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                        <td>
                                            <?php if (!empty($expense['description'])): ?>
                                                <?php echo htmlspecialchars($expense['description']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No expenses recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep only one script section
        document.addEventListener('DOMContentLoaded', function() {
            const categoryItems = document.querySelectorAll('.category');
            const selectedCategoryInput = document.getElementById('selected-category');

            categoryItems.forEach(item => {
                item.addEventListener('click', function() {
                    categoryItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    selectedCategoryInput.value = this.dataset.category;
                });
            });

            const amountInput = document.querySelector('input[name="amount"]');
            const quickAmounts = document.querySelectorAll('.quick-amount');

            quickAmounts.forEach(quick => {
                quick.addEventListener('click', function() {
                    const amount = this.textContent.replace('₹', '');
                    amountInput.value = amount;
                });
            });
        });
    </script>