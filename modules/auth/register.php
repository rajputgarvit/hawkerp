<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$db = Database::getInstance();

$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $companyName = trim($_POST['company_name']);
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validation
        if (empty($companyName) || empty($fullName) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }

        // Check if email exists
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            throw new Exception("Email already registered");
        }

        // Create username from email
        $username = explode('@', $email)[0] . '_' . time();

        // Register user
        $result = $auth->register([
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'password' => $password,
            'company_name' => $companyName,
            'is_active' => 1
        ]);

        if ($result['success']) {
            // Send verification email
            $verification = $auth->sendVerificationEmail($result['user_id'], $email);
            
            // Store user ID in session for plan selection
            $_SESSION['pending_user_id'] = $result['user_id'];
            $_SESSION['pending_user_email'] = $email;
            
            // Redirect to plan selection
            header('Location: ../subscription/select-plan.php');
            exit;
        } else {
            $error = $result['message'];
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Start your 14-day free trial</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Company Name <span style="color: var(--error-color)">*</span></label>
                    <input type="text" name="company_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" placeholder="Your Company Name">
                </div>

                <div class="form-group">
                    <label class="form-label">Your Full Name <span style="color: var(--error-color)">*</span></label>
                    <input type="text" name="full_name" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span style="color: var(--error-color)">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="name@company.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+91 98765 43210">
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span style="color: var(--error-color)">*</span></label>
                    <input type="password" name="password" class="form-control" required 
                           id="password" minlength="8" placeholder="••••••••">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password <span style="color: var(--error-color)">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           id="confirmPassword" minlength="8" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary">
                    Create Account & Choose Plan
                </button>
            </form>

            <div class="divider">
                <span>OR</span>
            </div>

            <div class="auth-footer">
                Already have an account? <a href="login.php" class="auth-link">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        // Password strength checker
        const password = document.getElementById('password');
        const strengthDiv = document.getElementById('passwordStrength');

        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            if (value.length >= 8) strength++;
            if (value.match(/[a-z]/) && value.match(/[A-Z]/)) strength++;
            if (value.match(/[0-9]/)) strength++;
            if (value.match(/[^a-zA-Z0-9]/)) strength++;

            if (strength === 0) {
                strengthDiv.textContent = '';
            } else if (strength <= 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength === 3) {
                strengthDiv.textContent = 'Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters!');
                return false;
            }
        });
    </script>
</body>
</html>
