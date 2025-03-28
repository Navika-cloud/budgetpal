-- Drop and create database
DROP DATABASE IF EXISTS user_auth;
CREATE DATABASE user_auth
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Select the database
USE user_auth;

-- Disable foreign key checks and delete tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS budget_categories;
DROP TABLE IF EXISTS salary_details;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Create tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Salary details table
CREATE TABLE salary_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    hra DECIMAL(10,2) NOT NULL,
    da DECIMAL(10,2) NOT NULL,
    pf DECIMAL(10,2) NOT NULL,
    others DECIMAL(10,2) NOT NULL,
    salary_month DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_month_user (user_id, salary_month)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Budget categories table
CREATE TABLE budget_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_user (user_id, category_name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Purchases table
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

COMMIT;

-- Third transaction: Insert sample data
START TRANSACTION;

-- Insert test user (if not exists)
INSERT INTO users (username, email, password) 
SELECT 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'test@example.com'
);

-- Get the user ID (whether newly inserted or existing)
SELECT id INTO @user_id FROM users WHERE email = 'test@example.com';

-- Insert salary data
INSERT INTO salary_details (user_id, basic_salary, hra, da, pf, others, salary_month) VALUES
(@user_id, 50000.00, 15000.00, 10000.00, 5000.00, 3000.00, '2024-03-01'),
(@user_id, 50000.00, 15000.00, 10000.00, 5000.00, 3000.00, '2024-02-01');

-- Insert budget categories
INSERT INTO budget_categories (user_id, category_name, amount) VALUES
(@user_id, 'Food', 15000.00),
(@user_id, 'Travel', 5000.00),
(@user_id, 'Shopping', 10000.00),
(@user_id, 'Entertainment', 5000.00);

-- Insert sample purchases
INSERT INTO purchases (user_id, item_name, category, amount, purchase_date) VALUES
(@user_id, 'Groceries', 'Food', 2500.00, CURRENT_DATE()),
(@user_id, 'Bus Ticket', 'Travel', 100.00, CURRENT_DATE()),
(@user_id, 'Clothes', 'Shopping', 3000.00, CURRENT_DATE()),
(@user_id, 'Movie', 'Entertainment', 500.00, CURRENT_DATE());

COMMIT;

-- Drop existing view if exists
DROP VIEW IF EXISTS dashboard_summary;

-- Create a simpler view for dashboard calculations
CREATE VIEW dashboard_summary AS
SELECT 
    u.id as user_id,
    bc.category_name,
    bc.amount as budget_amount,
    (SELECT COALESCE(SUM(amount), 0) 
     FROM purchases 
     WHERE user_id = u.id 
     AND category = bc.category_name 
     AND MONTH(purchase_date) = MONTH(CURRENT_DATE())
     AND YEAR(purchase_date) = YEAR(CURRENT_DATE())
    ) as spent_amount
FROM users u
LEFT JOIN budget_categories bc ON u.id = bc.user_id
GROUP BY u.id, bc.category_name, bc.amount;

-- Add a view for total summary
CREATE OR REPLACE VIEW total_summary AS
SELECT 
    user_id,
    SUM(budget_amount) as total_budget,
    SUM(spent_amount) as total_spent,
    SUM(budget_amount - spent_amount) as total_remaining
FROM dashboard_summary
GROUP BY user_id;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    monthly_income DECIMAL(10,2),
    account_type ENUM('business', 'personal') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE DATABASE IF NOT EXISTS budgetpal;
USE budgetpal;

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category)
);
USE user_auth;
ALTER TABLE users
ADD COLUMN fullname VARCHAR(100),
ADD COLUMN monthly_income DECIMAL(10,2),
ADD COLUMN currency_preference VARCHAR(10) DEFAULT 'INR';
CREATE DATABASE IF NOT EXISTS user_auth;
USE user_auth;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    monthly_income DECIMAL(10,2),
    currency_preference VARCHAR(10) DEFAULT 'INR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
USE budgetpal;

-- Create expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add default expense categories if not exists
INSERT INTO spending_categories (user_id, category_name, budget_limit) 
SELECT 1, 'Groceries', 5000.00 WHERE NOT EXISTS (SELECT 1 FROM spending_categories WHERE category_name = 'Groceries');

INSERT INTO spending_categories (user_id, category_name, budget_limit) 
SELECT 1, 'Utilities', 3000.00 WHERE NOT EXISTS (SELECT 1 FROM spending_categories WHERE category_name = 'Utilities');

INSERT INTO spending_categories (user_id, category_name, budget_limit) 
SELECT 1, 'Transport', 2000.00 WHERE NOT EXISTS (SELECT 1 FROM spending_categories WHERE category_name = 'Transport');