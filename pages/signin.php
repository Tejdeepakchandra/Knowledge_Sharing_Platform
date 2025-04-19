<?php
session_start();
require_once '../includes/config.php';

$error = ''; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: ../index.html"); // Redirect to homepage or dashboard
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DevAwakening</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: white;
            background: linear-gradient(-45deg, #16213e, #1a1a2e, #0f3460, #16213e);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
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
        .login-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transform-style: preserve-3d;
            animation: fadeInUp 0.8s ease-out forwards, float 6s ease-in-out infinite;
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
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
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
        .error-message {
            color: #ef4444; /* Red color for error */
            font-size: 0.875rem; /* Tailwind text-sm */
            margin-bottom: 1rem; /* Tailwind mb-4 */
            text-align: center;
            padding: 0.5rem;
            border-radius: 0.375rem; /* Tailwind rounded-md */
            background-color: rgba(239, 68, 68, 0.1);
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <!-- Floating background icons -->
    <div class="floating-icons">
        <i class="floating-icon fas fa-code text-6xl" style="top: 10%; left: 5%; animation-duration: 25s;"></i>
        <i class="floating-icon fas fa-cogs text-8xl" style="top: 30%; left: 80%; animation-duration: 30s;"></i>
        <i class="floating-icon fas fa-laptop-code text-5xl" style="top: 70%; left: 15%; animation-duration: 40s;"></i>
        <i class="floating-icon fas fa-bug text-7xl" style="top: 20%; left: 60%; animation-duration: 35s;"></i>
        <i class="floating-icon fas fa-server text-6xl" style="top: 80%; left: 70%; animation-duration: 45s;"></i>
    </div>

    <div class="login-container w-full max-w-md">
        <div class="login-box p-8">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="CodeBook Logo" class="w-24 mx-auto mb-4 hover:scale-110 transition-transform duration-300">
                <h1 class="text-3xl font-bold mb-2 bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 to-orange-500">
                    Welcome Back
                </h1>
                <p class="text-gray-300">Sign in to continue your coding journey</p>
            </div>
            
            <!-- Display error message if it exists -->
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="signin.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" class="input-field w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none" 
                               placeholder="you@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium">Password</label>
                        <a href="#" class="text-xs text-yellow-400 hover:underline">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" id="password" class="input-field w-full pl-10 pr-12 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none" 
                               placeholder="••••••••" required>
                        <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-yellow-400" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="rounded bg-gray-700 border-gray-600 text-yellow-500 focus:ring-yellow-500">
                    <label for="remember" class="ml-2 text-sm text-gray-300">Remember me</label>
                </div>
                
                <button type="submit" class="btn-primary w-full py-3 px-4 rounded-lg font-semibold text-white">
                    Sign In <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-400">
                Don't have an account? 
                <a href="signup.php" class="font-medium text-yellow-400 hover:text-yellow-300 hover:underline transition">
                    Sign up
                </a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
        });

        // Add floating animation to login box
        const loginBox = document.querySelector('.login-box');
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