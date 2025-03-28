<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="navbar">
    <div class="nav-brand"><?php echo SITE_NAME; ?></div>
    <div class="nav-links">
        <a href="dashboard.php" <?php echo $current_page == 'dashboard.php' ? 'class="active"' : ''; ?>>Dashboard</a>
        <a href="salary.php" <?php echo $current_page == 'salary.php' ? 'class="active"' : ''; ?>>Salary Details</a>
        <a href="budget.php" <?php echo $current_page == 'budget.php' ? 'class="active"' : ''; ?>>Budget Setup</a>
        <a href="purchases.php" <?php echo $current_page == 'purchases.php' ? 'class="active"' : ''; ?>>Purchases</a>
        <a href="reports.php" <?php echo $current_page == 'reports.php' ? 'class="active"' : ''; ?>>Reports</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<style>
.navbar {
    background-color: #343a40;
    padding: 15px 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-brand {
    font-size: 1.5em;
    font-weight: bold;
}
.nav-links a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    padding: 5px 10px;
    border-radius: 4px;
}
.nav-links a:hover {
    background-color: rgba(255,255,255,0.1);
}
.nav-links a.active {
    background-color: rgba(255,255,255,0.2);
}
.logout-btn {
    background-color: #dc3545;
}
</style>