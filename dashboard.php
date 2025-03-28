<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch user data
$stmt = $conn->prepare("SELECT email, monthly_income, account_type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Set the account type in session
$_SESSION['account_type'] = $user_data['account_type'] ?? 'personal';

// Initialize variables with default values
$monthly_income = $user_data['monthly_income'] ?? 0;
$total_expenses = 0;

// Only fetch expenses if the table exists
try {
    $stmt = $conn->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_assoc();
    $total_expenses = $expenses['total_expenses'] ?? 0;
} catch (Exception $e) {
    // Table doesn't exist or other database error
    error_log($e->getMessage());
}

$remaining_budget = $monthly_income - $total_expenses;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .profile-header {
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .profile-avatar img {
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-container {
            padding-top: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .quick-action {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            background: #e9ecef;
            transform: scale(1.02);
        }
        .progress-circle {
            position: relative;
            height: 120px;
            width: 120px;
            margin: auto;
        }
        .ai-insights {
            background: #f0f7ff;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">BudgetPal</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Add Profile Header -->
    <!-- Replace the existing profile header section -->
    <div class="profile-header bg-white border-bottom py-4">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="profile-avatar me-3">
                    <img src="assets/avatars/personal.svg" alt="Profile" class="rounded-circle" width="50">
                </div>
                <div class="profile-info">
                    <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                    <small class="text-muted">Personal Account</small>
                    <div class="mt-1">
                        <span class="badge bg-success">Active</span>
                        <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container dashboard-container">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <h6>Monthly Income</h6>
                            <h3>₹<?php echo number_format($monthly_income, 2); ?></h3>
                            <div class="progress mt-2">
                                <div class="progress-bar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <h6>Expenses</h6>
                            <h3>₹<?php echo number_format($total_expenses, 2); ?></h3>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-warning" style="width: <?php echo ($total_expenses/$monthly_income*100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <h6>Remaining</h6>
                            <h3>₹<?php echo number_format($remaining_budget, 2); ?></h3>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-success" style="width: <?php echo ($remaining_budget/$monthly_income*100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Expense Trends</h5>
                                <canvas id="expenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ai-insights mt-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-robot me-2"></i>
                        <h5 class="mb-0">AI Insights</h5>
                    </div>
                    <div id="aiSuggestions">
                        <!-- AI suggestions will be dynamically inserted here -->
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="quick-action" onclick="location.href='add_expense.php'">
                            <i class="fas fa-plus-circle me-2"></i> Add New Expense
                        </div>
                        <div class="quick-action" onclick="location.href='budget_setup.php'">
                            <i class="fas fa-cog me-2"></i> Budget Setup
                        </div>
                        <div class="quick-action" onclick="location.href='reports.php'">
                            <i class="fas fa-chart-bar me-2"></i> View Reports
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Savings Goal</h5>
                        <div class="progress-circle">
                            <canvas id="savingsChart"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <h6>Current Progress: 65%</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts and AI suggestions
        document.addEventListener('DOMContentLoaded', function() {
            // Expense Chart
            const ctx = document.getElementById('expenseChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Expenses',
                        data: [30000, 35000, 25000, 32000, 28000, <?php echo $total_expenses; ?>],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                }
            });

            // Savings Chart
            const savingsCtx = document.getElementById('savingsChart').getContext('2d');
            new Chart(savingsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Saved', 'Remaining'],
                    datasets: [{
                        data: [65, 35],
                        backgroundColor: ['#28a745', '#e9ecef']
                    }]
                },
                options: {
                    cutout: '70%'
                }
            });

            // AI Suggestions
            const suggestions = [
                "Based on your spending pattern, you could save ₹5,000 more by reducing entertainment expenses.",
                "Your utility bills are 15% higher than last month. Consider energy-saving measures.",
                "You're on track to meet your savings goal this month!"
            ];

            const aiContainer = document.getElementById('aiSuggestions');
            suggestions.forEach(suggestion => {
                const div = document.createElement('div');
                div.className = 'alert alert-info mb-2';
                div.textContent = suggestion;
                aiContainer.appendChild(div);
            });
        });
    </script>
    
    <!-- Add these scripts before closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>

</body>
</html>


<!-- Edit Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile & Budget Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm" action="update_profile.php" method="POST">
                    <!-- Personal Information -->
                    <h6 class="mb-3">Personal Information</h6>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <!-- Income Settings -->
                    <h6 class="mb-3 mt-4">Income Settings</h6>
                    <div class="mb-3">
                        <label class="form-label">Monthly Income</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="monthly_income" value="<?php echo $monthly_income; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Income Sources</label>
                        <input type="number" class="form-control" name="additional_income" placeholder="Other monthly income">
                    </div>

                    <!-- Budget Goals -->
                    <h6 class="mb-3 mt-4">Budget Goals</h6>
                    <div class="mb-3">
                        <label class="form-label">Monthly Savings Target</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="savings_goal">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Emergency Fund Goal</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="emergency_fund">
                        </div>
                    </div>

                    <!-- Expense Categories -->
                    <h6 class="mb-3 mt-4">Primary Expense Categories</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="expense_categories[]" value="housing">
                            <label class="form-check-label">Housing/Rent</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="expense_categories[]" value="utilities">
                            <label class="form-check-label">Utilities</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="expense_categories[]" value="groceries">
                            <label class="form-check-label">Groceries</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="expense_categories[]" value="transportation">
                            <label class="form-check-label">Transportation</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="expense_categories[]" value="healthcare">
                            <label class="form-check-label">Healthcare</label>
                        </div>
                    </div>

                    <!-- Preferences -->
                    <h6 class="mb-3 mt-4">Preferences</h6>
                    <div class="mb-3">
                        <label class="form-label">Budget Alert Threshold</label>
                        <select class="form-control" name="alert_threshold">
                            <option value="75">Alert at 75% of budget</option>
                            <option value="80">Alert at 80% of budget</option>
                            <option value="90">Alert at 90% of budget</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-control" name="currency">
                            <option value="INR">₹ (INR)</option>
                            <option value="USD">$ (USD)</option>
                            <option value="EUR">€ (EUR)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-4">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Add these styles to your existing CSS -->
    <style>
        .profile-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-info {
            flex: 1;
        }
        .profile-card {
            max-width: 600px;
        }
    </style>