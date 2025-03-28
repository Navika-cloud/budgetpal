<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Remove duplicate trend queries and keep only this one
// Get budget and spending data in one query
$budget_query = "SELECT 
    b.category,
    b.amount as budget_amount,
    COALESCE(SUM(e.amount), 0) as spent_amount
FROM budgets b
LEFT JOIN expenses e ON b.user_id = e.user_id 
    AND b.category = e.category 
    AND MONTH(e.date) = MONTH(CURRENT_DATE())
    AND YEAR(e.date) = YEAR(CURRENT_DATE())
WHERE b.user_id = ?
GROUP BY b.category, b.amount";

$stmt = $conn->prepare($budget_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result();

// Simplified trends query
// Update the trends query to show correct monthly data
$trends_query = "SELECT 
    months.month,
    COALESCE(SUM(e.amount), 0) as total_spent,
    b.total_budget
FROM (
    SELECT DISTINCT LAST_DAY(date) as last_date,
           DATE_FORMAT(date, '%b %Y') as month
    FROM expenses 
    WHERE user_id = ?
    UNION
    SELECT LAST_DAY(CURRENT_DATE()) as last_date,
           DATE_FORMAT(CURRENT_DATE(), '%b %Y') as month
    ORDER BY last_date DESC
    LIMIT 6
) months
LEFT JOIN expenses e ON DATE_FORMAT(e.date, '%b %Y') = months.month 
    AND e.user_id = ?
CROSS JOIN (
    SELECT SUM(amount) as total_budget 
    FROM budgets 
    WHERE user_id = ?
) b
GROUP BY months.month, months.last_date, b.total_budget
ORDER BY months.last_date DESC";

$stmt = $conn->prepare($trends_query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$trends_result = $stmt->get_result();

// Calculate totals
$total_budget = 0;
$total_spent = 0;
$budget_data = [];
while ($row = $budget_result->fetch_assoc()) {
    $total_budget += $row['budget_amount'];
    $total_spent += $row['spent_amount'];
    $budget_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .progress { height: 10px; border-radius: 5px; }
        .total-card {
            background: linear-gradient(45deg, #2193b0, #6dd5ed);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line"></i> Financial Reports</h2>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Total Overview -->
        <div class="row">
            <div class="col-md-6">
                <div class="total-card">
                    <h5>Total Budget</h5>
                    <h3>₹<?php echo number_format($total_budget, 2); ?></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="total-card">
                    <h5>Total Spent</h5>
                    <h3>₹<?php echo number_format($total_spent, 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Budget Overview -->
        <div class="report-card">
            <h4>Budget Overview</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Spent</th>
                            <th>Remaining</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($budget_data as $row): 
                            $remaining = $row['budget_amount'] - $row['spent_amount'];
                            $percentage = ($row['spent_amount'] / $row['budget_amount']) * 100;
                            $progress_class = $percentage > 90 ? 'bg-danger' : 
                                            ($percentage > 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>₹<?php echo number_format($row['budget_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['spent_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($remaining, 2); ?></td>
                            <td width="20%">
                                <div class="progress">
                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                         style="width: <?php echo min(100, $percentage); ?>%">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monthly Spending Chart -->
        <div class="report-card">
            <h4>Monthly Spending Trends</h4>
            <canvas id="spendingChart"></canvas>
        </div>

        <!-- Add new charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="report-card">
                    <h4>Budget Distribution</h4>
                    <canvas id="budgetPieChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4>Category-wise Comparison</h4>
                    <canvas id="categoryBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Add Spending Analysis Section -->
        <div class="report-card">
            <h4><i class="fas fa-chart-pie me-2"></i>Spending Analysis</h4>
            <div class="analysis-content">
                <?php
                $total_percentage = ($total_spent / $total_budget) * 100;
                $overall_status = '';
                $overall_class = '';
                
                if ($total_percentage > 100) {
                    $overall_status = 'Critical: Overall spending exceeds budget';
                    $overall_class = 'text-danger';
                } elseif ($total_percentage > 80) {
                    $overall_status = 'Warning: Overall spending is high';
                    $overall_class = 'text-warning';
                } else {
                    $overall_status = 'Good: Overall spending is within budget';
                    $overall_class = 'text-success';
                }
                ?>
                
                <div class="alert alert-info mb-4">
                    <h5 class="<?php echo $overall_class; ?>">
                        <i class="fas fa-info-circle me-2"></i><?php echo $overall_status; ?>
                    </h5>
                    <p>You've spent <?php echo number_format($total_percentage, 1); ?>% of your total budget</p>
                </div>

                <h5 class="mb-3">Category-wise Analysis:</h5>
                <?php foreach($budget_data as $row): 
                    $cat_percentage = ($row['spent_amount'] / $row['budget_amount']) * 100;
                    $remaining = $row['budget_amount'] - $row['spent_amount'];
                    
                    if ($cat_percentage > 100): ?>
                        <div class="alert alert-danger mb-3">
                            <strong><?php echo htmlspecialchars($row['category']); ?>: Overspending Alert!</strong>
                            <p>You've exceeded your budget by ₹<?php echo number_format($remaining * -1, 2); ?></p>
                            <small>Recommendation: Immediately reduce spending in this category.</small>
                        </div>
                    <?php elseif ($cat_percentage > 80): ?>
                        <div class="alert alert-warning mb-3">
                            <strong><?php echo htmlspecialchars($row['category']); ?>: Approaching Limit</strong>
                            <p>You've used <?php echo number_format($cat_percentage, 1); ?>% of your budget</p>
                            <small>Recommendation: Consider limiting expenses in this category for the rest of the month.</small>
                        </div>
                    <?php elseif ($cat_percentage < 20 && $row['spent_amount'] > 0): ?>
                        <div class="alert alert-success mb-3">
                            <strong><?php echo htmlspecialchars($row['category']); ?>: Good Management</strong>
                            <p>You're managing this category well with only <?php echo number_format($cat_percentage, 1); ?>% spent</p>
                        </div>
                    <?php endif; 
                endforeach; ?>

                <div class="mt-4">
                    <h5>Tips for Better Budget Management:</h5>
                    <ul class="list-group">
                        <?php if ($total_percentage > 90): ?>
                            <li class="list-group-item text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Consider reviewing and adjusting your spending habits immediately
                            </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            <i class="fas fa-lightbulb me-2"></i>
                            Track your daily expenses to stay within budget
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-piggy-bank me-2"></i>
                            Look for areas where you can reduce unnecessary spending
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-chart-line me-2"></i>
                            Review your budget allocations monthly and adjust as needed
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('spendingChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $months = [];
                    $spent = [];
                    $budget = [];
                    while($row = $trends_result->fetch_assoc()) {
                        $months[] = "'" . $row['month'] . "'";
                        $spent[] = $row['total_spent'];
                        $budget[] = $row['total_budget'];
                    }
                    echo implode(',', array_reverse($months));
                ?>],
                datasets: [{
                    label: 'Monthly Budget',
                    data: [<?php echo implode(',', array_reverse($budget)); ?>],
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1,
                    fill: false
                }, {
                    label: 'Monthly Spending',
                    data: [<?php echo implode(',', array_reverse($spent)); ?>],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (₹)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });

        // Add new charts initialization
        const pieCtx = document.getElementById('budgetPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: [<?php 
                    echo implode(',', array_map(function($row) {
                        return "'" . $row['category'] . "'";
                    }, $budget_data));
                ?>],
                datasets: [{
                    data: [<?php 
                        echo implode(',', array_map(function($row) {
                            return $row['budget_amount'];
                        }, $budget_data));
                    ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        const barCtx = document.getElementById('categoryBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    echo implode(',', array_map(function($row) {
                        return "'" . $row['category'] . "'";
                    }, $budget_data));
                ?>],
                datasets: [{
                    label: 'Budget Amount',
                    data: [<?php 
                        echo implode(',', array_map(function($row) {
                            return $row['budget_amount'];
                        }, $budget_data));
                    ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Spent Amount',
                    data: [<?php 
                        echo implode(',', array_map(function($row) {
                            return $row['spent_amount'];
                        }, $budget_data));
                    ?>],
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>