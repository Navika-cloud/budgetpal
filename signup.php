<?php
session_start();
require_once 'config.php';

// At the top of signup.php, update the form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $account_type = $_POST['account_type'] ?? 'personal';
    $monthly_income = $_POST['monthly_income'] ?? 0;

    try {
        $conn = getDBConnection();
        
        // Insert new user
        $sql = "INSERT INTO users (username, email, password, account_type, monthly_income) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssd", $username, $email, $password, $account_type, $monthly_income);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registration successful!";
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Failed to create account");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .signup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
        .password-strength {
            margin-top: 10px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
        }
        .strength-meter {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .progress-indicator {
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="signup-container">
        <h2 class="text-center mb-4">Join BudgetPal</h2>
        
        <div class="progress-indicator">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <form id="signupForm" method="POST" class="needs-validation" novalidate>
            <div class="form-step active" data-step="1">
                <h4>Basic Information</h4>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password" required>
                    <div class="password-strength">
                        <div class="strength-meter"></div>
                    </div>
                    <small class="password-feedback text-muted"></small>
                </div>
            </div>

            <div class="avatar-selection">
                <div class="avatar-option-wrapper">
                    <img src="data:image/svg+xml;base64,<?php echo base64_encode(file_get_contents('assets/avatars/business.svg')); ?>" class="avatar-option" data-avatar="1" alt="Professional">
                    <span class="avatar-label">Business</span>
                </div>
                <div class="avatar-option-wrapper">
                    <img src="data:image/svg+xml;base64,<?php echo base64_encode(file_get_contents('assets/avatars/personal.svg')); ?>" class="avatar-option" data-avatar="2" alt="Personal">
                    <span class="avatar-label">Personal</span>
                </div>
            </div>
            <div class="text-center mb-3">
                <span id="accountTypeLabel" class="text-muted"></span>
            </div>

            <!-- Add to Financial Profile step -->
            <div class="form-step" data-step="2">
                <h4>Financial Profile</h4>
                <div class="mb-3">
                    <label class="form-label">Monthly Income</label>
                    <div class="input-group">
                        <input type="number" name="monthly_income" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary voice-input">
                            <i class="fas fa-microphone"></i>
                        </button>
                    </div>
                </div>
                <div id="aiRecommendations" class="tip-card d-none">
                    <!-- AI recommendations will be inserted here -->
                </div>
            </div>

            <!-- Update AI Assistant section -->
            <div class="ai-assistant" id="aiAssistant">
                <div class="ai-assistant-header">
                    <img src="data:image/svg+xml;base64,<?php echo base64_encode(file_get_contents('assets/ai-bot.svg')); ?>" class="ai-icon" alt="AI Assistant">
                    <h5>AI Budget Assistant</h5>
                </div>
                <div id="aiMessages"></div>
            </div>

            <div class="form-step" data-step="3">
                <h4>Spending Habits</h4>
                <div class="mb-3">
                    <label class="form-label">Primary Expense Categories</label>
                    <div class="form-check">
                        <input type="checkbox" name="spending_habits[]" value="groceries" class="form-check-input">
                        <label class="form-check-label">Groceries</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="spending_habits[]" value="transportation" class="form-check-input">
                        <label class="form-check-label">Transportation</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="spending_habits[]" value="entertainment" class="form-check-input">
                        <label class="form-check-label">Entertainment</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary prev-step" style="display:none">Previous</button>
                <button type="button" class="btn btn-primary next-step">Next</button>
                <button type="submit" class="btn btn-success" style="display:none">Create Account</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const steps = document.querySelectorAll('.form-step');
            const progressBar = document.querySelector('.progress-bar');
            const prevBtn = document.querySelector('.prev-step');
            const nextBtn = document.querySelector('.next-step');
            const submitBtn = document.querySelector('button[type="submit"]');
            let currentStep = 1;

            // Update form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = document.getElementById('password').value;
                if (password.length < 6) {
                    alert('Password must be at least 6 characters long');
                    return;
                }

                const formData = new FormData(this);
                
                try {
                    const response = await fetch('signup.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        window.location.href = 'login.php';
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            // Remove the other form.onsubmit handler
            form.onsubmit = null;

            // Add avatar selection handling
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const avatarInput = document.createElement('input');
            avatarInput.type = 'hidden';
            avatarInput.name = 'account_type';
            form.appendChild(avatarInput);

            avatarOptions.forEach(avatar => {
                avatar.addEventListener('click', function() {
                    // Remove selected class from all avatars
                    avatarOptions.forEach(a => {
                        a.classList.remove('selected');
                        a.parentElement.classList.remove('selected-wrapper');
                    });
                    
                    // Add selected class to clicked avatar
                    this.classList.add('selected');
                    this.parentElement.classList.add('selected-wrapper');
                    
                    // Update hidden input value
                    avatarInput.value = this.dataset.avatar;
                    
                    // Show different text based on selection
                    const accountType = this.dataset.avatar === "1" ? "Business" : "Personal";
                    document.querySelector('#accountTypeLabel').textContent = `Selected: ${accountType} Account`;
                });
            });

            // Add avatar validation to next button click
            nextBtn.addEventListener('click', function(e) {
                if (currentStep === 1 && !avatarInput.value) {
                    e.preventDefault();
                    alert('Please select an account type');
                    return;
                }
            });

            // Password strength checker
            const passwordInput = document.getElementById('password');
            const strengthMeter = document.querySelector('.strength-meter');
            const feedback = document.querySelector('.password-feedback');

            passwordInput.addEventListener('input', function() {
                const strength = checkPasswordStrength(this.value);
                strengthMeter.style.width = (strength.score * 20) + '%';
                strengthMeter.style.backgroundColor = getStrengthColor(strength.score);
                feedback.textContent = strength.feedback;
            });

            function checkPasswordStrength(password) {
                let score = 0;
                let feedback = '';

                if (password.length >= 8) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^A-Za-z0-9]/.test(password)) score++;

                if (score < 3) {
                    feedback = 'Password should be stronger';
                }

                return { score, feedback };
            }

            function getStrengthColor(score) {
                const colors = ['#ff4444', '#ffbb33', '#00C851', '#33b5e5', '#2BBBAD'];
                return colors[score - 1] || colors[0];
            }

            // Multi-step form navigation
            function updateStep(step) {
                steps.forEach(s => s.classList.remove('active'));
                steps[step-1].classList.add('active');
                progressBar.style.width = ((step-1) / (steps.length-1) * 100) + '%';
                
                prevBtn.style.display = step === 1 ? 'none' : 'block';
                nextBtn.style.display = step === steps.length ? 'none' : 'block';
                submitBtn.style.display = step === steps.length ? 'block' : 'none';
            }

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateStep(currentStep);
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentStep < steps.length) {
                    currentStep++;
                    updateStep(currentStep);
                }
            });
        });
    </script>
</body>
</html>

<style>
    /* Add these styles */
    .avatar-selection {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin: 20px 0;
    }
    .avatar-option-wrapper {
        text-align: center;
        transition: transform 0.3s ease;
        flex: 0 0 auto;
        padding: 15px;
        border-radius: 12px;
        cursor: pointer;
    }
    .selected-wrapper {
        background: #f8f9fa;
        transform: translateY(-5px);
    }
    .avatar-label {
        font-weight: 500;
        margin-top: 8px;
        color: #495057;
    }
    .avatar-option {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 4px solid #e9ecef;
        object-fit: cover;
        background-color: #ffffff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 8px;
    }
    .avatar-option:hover {
        border-color: #007bff;
        box-shadow: 0 6px 20px rgba(0,123,255,0.15);
    }
    .avatar-option.selected {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
    }
    .ai-assistant-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .ai-assistant-header img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }
</style>