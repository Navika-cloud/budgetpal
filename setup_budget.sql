USE budgetpal;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS spending_categories;

-- Create spending_categories table
CREATE TABLE spending_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    budget_limit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_user (user_id, category_name)
) ENGINE=InnoDB;

-- Create budgets table
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category)
) ENGINE=InnoDB;

-- Insert default categories for the current user
INSERT INTO spending_categories (user_id, category_name, budget_limit) VALUES
(1, 'Groceries', 5000.00),
(1, 'Utilities', 3000.00),
(1, 'Transport', 2000.00),
(1, 'Entertainment', 2000.00),
(1, 'Shopping', 3000.00),
(1, 'Healthcare', 2000.00);