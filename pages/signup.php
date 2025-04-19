<?php
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Hash the password

    try {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (:full_name, :email, :password)");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':password' => $password
        ]);

        // Redirect to login page after successful signup
        header("Location: signin.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - DevAwakening</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        body {
            font-family: 'Arial', sans-serif;
            color: white;
            background: linear-gradient(-45deg, #16213e, #1a1a2e, #0f3460, #16213e);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .login-container {
            perspective: 1000px;
        }
        .dark-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transform-style: preserve-3d;
            animation: fadeInUp 0.8s ease-out forwards, float 6s ease-in-out infinite;
            padding: 2rem;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px) rotateX(15deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .input-field {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 2.5rem;
            border-radius: 0.5rem;
            color: white;
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: white;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0)
            );
            transform: rotate(30deg);
            animation: shine 3s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) rotate(30deg); }
            100% { transform: translateX(100%) rotate(30deg); }
        }
        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: -1;
        }
        .floating-icon {
            position: absolute;
            opacity: 0.1;
            animation: floatRandom linear infinite;
        }
        @keyframes floatRandom {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
            }
            100% {
                transform: translateY(-100vh) translateX(20vw) rotate(360deg);
            }
        }
        .password-strength {
            height: 4px;
            margin-top: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-fill {
            height: 100%;
            transition: width 0.3s, background-color 0.3s;
        }
        .validation-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .validation-rule {
            opacity: 0.5;
            transition: all 0.3s;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .validation-rule.valid {
            opacity: 1;
            color: #10b981;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        .form-group {
            opacity: 0;
            transform: translateY(20px);
            animation: formFadeIn 0.5s ease-out forwards;
        }
        @keyframes formFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
        @media (max-width: 640px) {
            .dark-box {
                padding: 1.5rem;
            }
            body {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating background icons -->
    <div class="floating-icons">
        <i class="floating-icon fas fa-code text-6xl" style="top: 10%; left: 5%; animation-duration: 25s;"></i>
        <i class="floating-icon fas fa-cogs text-8xl" style="top: 30%; left: 80%; animation-duration: 30s;"></i>
        <i class="floating-icon fas fa-laptop-code text-5xl" style="top: 70%; left: 15%; animation-duration: 40s;"></i>
        <i class="floating-icon fas fa-bug text-7xl" style="top: 20%; left: 60%; animation-duration: 35s;"></i>
        <i class="floating-icon fas fa-server text-6xl" style="top: 80%; left: 70%; animation-duration: 45s;"></i>
    </div>

    <div class="login-container w-full max-w-md">
        <div class="dark-box">
            <div class="text-center mb-6">
                <img src="../assets/images/logo.png" alt="DevAwakening Logo" class="w-20 mx-auto mb-3 hover:scale-110 transition-transform duration-300">
                <h1 class="text-2xl font-bold mb-1 bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 to-orange-500">
                    Join DevAwakening
                </h1>
                <p class="text-gray-300 text-sm">Create your account to begin your journey</p>
            </div>
            
            <form action="signup.php" method="POST">
                <div class="mb-4 relative form-group">
                    <label class="block text-sm font-medium mb-1">Full Name</label>
                    <div class="relative">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="full_name" class="input-field" placeholder="Your name" required>
                    </div>
                </div>
                
                <div class="mb-4 relative form-group">
                    <label class="block text-sm font-medium mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="input-field" placeholder="you@example.com" required>
                    </div>
                </div>
                
                <div class="mb-4 relative form-group">
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye text-gray-400 hover:text-yellow-400"></i>
                        </button>
                        <div class="validation-icon text-green-400" id="validIcon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="password-strength mt-1">
                        <div id="password-strength-fill" class="password-strength-fill w-0"></div>
                    </div>
                    <div class="validation-rules mt-2 space-y-1">
                        <div class="validation-rule" id="length-rule">
                            <i class="fas fa-circle mr-1 text-xs"></i> At least 8 characters
                        </div>
                        <div class="validation-rule" id="uppercase-rule">
                            <i class="fas fa-circle mr-1 text-xs"></i> 1 uppercase letter
                        </div>
                        <div class="validation-rule" id="number-rule">
                            <i class="fas fa-circle mr-1 text-xs"></i> 1 number
                        </div>
                    </div>
                </div>
                
                <div class="mb-4 relative form-group">
                    <label class="block text-sm font-medium mb-1">Confirm Password</label>
                    <div class="relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirmPassword" class="input-field" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye text-gray-400 hover:text-yellow-400"></i>
                        </button>
                        <div class="validation-icon text-red-400 hidden" id="mismatchIcon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4 flex items-start form-group">
                    <input id="terms" name="terms" type="checkbox" class="mt-1 focus:ring-yellow-500 h-4 w-4 text-yellow-500 rounded bg-gray-700 border-gray-600" required>
                    <label for="terms" class="ml-2 text-sm text-gray-300">
                        I agree to the <a href="#" class="text-yellow-400 hover:underline">Terms</a> and <a href="#" class="text-yellow-400 hover:underline">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-primary form-group">
                    Create Account <i class="fas fa-user-plus ml-1"></i>
                </button>
            </form>
            
            <div class="mt-4 text-center text-sm text-gray-400 form-group" style="animation-delay: 0.7s;">
                Already have an account? 
                <a href="signin.php" class="text-yellow-400 hover:underline">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');

        togglePassword.addEventListener('click', function(e) {
            e.preventDefault();
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function(e) {
            e.preventDefault();
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        password.addEventListener('input', function() {
            const pass = this.value;
            const strengthFill = document.getElementById('password-strength-fill');
            const validIcon = document.getElementById('validIcon');
            
            const hasLength = pass.length >= 8;
            const hasUpper = /[A-Z]/.test(pass);
            const hasNumber = /\d/.test(pass);
            
            document.getElementById('length-rule').classList.toggle('valid', hasLength);
            document.getElementById('uppercase-rule').classList.toggle('valid', hasUpper);
            document.getElementById('number-rule').classList.toggle('valid', hasNumber);
            
            let strength = 0;
            if (hasLength) strength += 40;
            if (hasUpper) strength += 30;
            if (hasNumber) strength += 30;
            
            strengthFill.style.width = `${strength}%`;
            
            if (strength < 40) {
                strengthFill.style.backgroundColor = '#ef4444';
                validIcon.style.opacity = '0';
            } else if (strength < 70) {
                strengthFill.style.backgroundColor = '#f59e0b';
                validIcon.style.opacity = '0';
            } else {
                strengthFill.style.backgroundColor = '#10b981';
                validIcon.style.opacity = '1';
            }
            
            if (confirmPassword.value) {
                checkPasswordMatch();
            }
        });

        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const mismatchIcon = document.getElementById('mismatchIcon');
            if (password.value && confirmPassword.value) {
                if (password.value === confirmPassword.value) {
                    mismatchIcon.classList.add('hidden');
                } else {
                    mismatchIcon.classList.remove('hidden');
                }
            } else {
                mismatchIcon.classList.add('hidden');
            }
        }

        // Add continuous floating animation to the form
        const loginBox = document.querySelector('.dark-box');
        let floatDirection = 1;
        setInterval(() => {
            const currentTransform = window.getComputedStyle(loginBox).transform;
            const currentY = currentTransform === 'none' ? 0 : parseFloat(currentTransform.split(',')[5]);
            
            if (Math.abs(currentY) > 10) floatDirection *= -1;
            
            loginBox.style.transform = `translateY(${currentY + floatDirection * 0.5}px) rotateX(0)`;
        }, 50);
    </script>
</body>
</html>