<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'devawakening1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

date_default_timezone_set('UTC');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

define('SITE_NAME', 'DevAwakening');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@devawakening.com');

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com fonts.googleapis.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com fonts.gstatic.com; form-action 'self';");

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function format_date($timestamp) {
    return date('M j, Y g:i a', strtotime($timestamp));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function handle_db_error($e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

function safe_display($data, $default = '') {
    if (is_array($data)) return $default;
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

function get_csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['csrf'] = 'Invalid security token. Please try again.';
    } else {
        if (isset($_POST['submit_question'])) {
            $name = sanitize_input($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $question = sanitize_input($_POST['question'] ?? '');

            if (empty($name)) $errors['name'] = 'Please enter your name.';
            if (empty($email)) $errors['email'] = 'Please enter your email.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
            if (empty($question)) $errors['question'] = 'Please enter your question.';

            if (empty($errors)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO questions (name, email, question) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $question]);
                    $success = 'Your question has been submitted successfully!';
                    $_POST = [];
                } catch (PDOException $e) {
                    $errors['database'] = 'Error submitting your question: ' . $e->getMessage();
                    error_log("Question submission error: " . $e->getMessage());
                }
            }
        } elseif (isset($_POST['submit_answer'])) {
            $answer_name = sanitize_input($_POST['answer_name'] ?? '');
            $answer_text = sanitize_input($_POST['answer_text'] ?? '');
            $question_id = intval($_POST['question_id'] ?? 0);

            if (empty($answer_name)) $errors['answer_name'] = 'Please enter your name.';
            if (empty($answer_text)) $errors['answer_text'] = 'Please enter your answer.';
            if ($question_id <= 0) $errors['question_id'] = 'Invalid question reference.';

            if (empty($errors)) {
                try {
                    $stmt = $conn->prepare("SELECT id FROM questions WHERE id = ?");
                    $stmt->execute([$question_id]);
                    if ($stmt->rowCount() > 0) {
                        $stmt = $conn->prepare("INSERT INTO answers (question_id, name, answer) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $answer_name, $answer_text]);
                        $success = 'Your answer has been submitted successfully!';
                        $_POST = [];
                    } else {
                        $errors['database'] = 'The question you\'re trying to answer doesn\'t exist.';
                    }
                } catch (PDOException $e) {
                    $errors['database'] = 'Error submitting your answer: ' . $e->getMessage();
                    error_log("Answer submission error: " . $e->getMessage());
                }
            }
        }
    }
}

// Fetch questions and answers
try {
    $questions = $conn->query("
        SELECT q.id, q.name, q.email, q.question, q.created_at, 
               COUNT(a.id) AS answer_count
        FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id
        GROUP BY q.id, q.name, q.email, q.question, q.created_at
        ORDER BY q.created_at DESC
    ")->fetchAll();

    foreach ($questions as &$question) {
        $stmt = $conn->prepare("SELECT id, name, answer, created_at FROM answers WHERE question_id = ? ORDER BY created_at ASC");
        $stmt->execute([$question['id']]);
        $question['answers'] = $stmt->fetchAll();
    }
    unset($question);
} catch (PDOException $e) {
    $errors['database'] = 'Error loading questions: ' . $e->getMessage();
    error_log("Database query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaborate - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --dark-blue: #1a1a2e;
        }
        body {
            font-family: 'Poppins', sans-serif;
            color: white;
            background: linear-gradient(-45deg, #16213e, #1a1a2e, #0f3460, #16213e);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .dark-box {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .question-card {
            background: rgba(26, 26, 46, 0.6);
            border-radius: 10px;
            transition: all 0.3s ease;
            border-left: 3px solid var(--primary);
        }
        .answer-card {
            background: rgba(15, 52, 96, 0.4);
            border-radius: 8px;
            position: relative;
            margin-left: 20px;
        }
        .answer-card::before {
            content: '';
            position: absolute;
            top: 12px;
            left: -10px;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 10px solid rgba(15, 52, 96, 0.4);
        }
        .fade-in {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }
        .form-input {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 10px 14px;
            color: white;
            transition: all 0.2s ease;
            width: 100%;
            font-size: 0.9rem;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.12);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-primary {
            background: rgba(245, 158, 11, 0.15);
            color: var(--primary);
        }
        .toggle-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .toggle-content.active {
            max-height: 2000px;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="flex flex-col items-center">
    <!-- Navigation Bar -->
    <nav class="w-full bg-[#1a1a2e] p-4 shadow-md flex justify-between items-center fixed top-0 left-0 right-0 z-50">
        <h1 class="text-xl font-bold flex items-center">
            <i class="fas fa-code mr-2 text-yellow-400"></i>
            CODEBOOK-
            <?php echo SITE_NAME; ?>
        </h1>
        <ul class="hidden md:flex space-x-4 text-sm">
            <li><a href="../index.html" class="hover:text-yellow-400 transition">Home</a></li>
            <li><a href="courses/courses.html" class="hover:text-yellow-400 transition">Courses</a></li>
            <li><a href="logout.php" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">Logout</a></li>
        </ul>
        <button class="md:hidden text-xl">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <!-- Main Content -->
    <main class="w-full max-w-3xl mt-20 px-4 pb-8">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="bg-green-600/90 text-white p-3 rounded-lg mb-4 fade-in text-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo safe_display($success); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="bg-red-600/90 text-white p-3 rounded-lg mb-4 fade-in text-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo safe_display($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Header -->
        <div class="dark-box p-6 mb-6 fade-in">
            <h2 class="text-2xl font-bold mb-2">Developer Q&A</h2>
            <p class="text-sm text-gray-300">Ask questions and help others in the CODEBOOK-<?php echo SITE_NAME; ?> community</p>
        </div>

        <!-- Question Form -->
        <div class="dark-box p-5 mb-6 fade-in">
            <button id="toggleQuestionForm" class="btn-primary w-full mb-3" onclick="toggleQuestionForm()">
                <i class="fas fa-plus mr-1"></i> Ask a Question
            </button>
            <form method="POST" id="questionForm" class="toggle-content" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <div class="mb-3">
                    <input type="text" name="name" placeholder="Your Name*" class="form-input mb-1" 
                           value="<?php echo safe_display($_POST['name'] ?? ''); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <p class="text-red-400 text-xs mt-1"><?php echo safe_display($errors['name']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" placeholder="Your Email*" class="form-input mb-1" 
                           value="<?php echo safe_display($_POST['email'] ?? ''); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <p class="text-red-400 text-xs mt-1"><?php echo safe_display($errors['email']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <textarea name="question" rows="3" placeholder="Your Question*" class="form-input mb-1" required><?php echo safe_display($_POST['question'] ?? ''); ?></textarea>
                    <?php if (isset($errors['question'])): ?>
                        <p class="text-red-400 text-xs mt-1"><?php echo safe_display($errors['question']); ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" name="submit_question" class="btn-primary w-full">
                    <i class="fas fa-paper-plane mr-1"></i> Submit
                </button>
            </form>
        </div>

        <!-- Questions List -->
        <div class="space-y-4 fade-in" style="animation-delay: 0.1s">
            <h3 class="text-lg font-semibold mb-2 px-2">Recent Questions</h3>
            <?php if (empty($questions)): ?>
                <div class="dark-box p-5 text-center text-sm">
                    <p class="text-gray-400">No questions yet. Be the first to ask!</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-card p-4 fade-in">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium text-sm line-clamp-2"><?php echo safe_display($question['question']); ?></h4>
                                <div class="flex items-center mt-2 text-xs text-gray-400">
                                    <span><?php echo safe_display($question['name']); ?></span>
                                    <span class="mx-2">•</span>
                                    <span><?php echo format_date($question['created_at']); ?></span>
                                </div>
                            </div>
                            <span class="badge badge-primary ml-2 text-xs">
                                <?php echo $question['answer_count']; ?> answer<?php echo $question['answer_count'] != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                        <!-- Answers Section -->
                        <?php if ($question['answer_count'] > 0): ?>
                            <button class="toggle-answers-btn text-xs text-yellow-400 mt-2 flex items-center" 
                                    onclick="toggleAnswers(<?php echo $question['id']; ?>)">
                                <i class="fas fa-chevron-down mr-1"></i> View answers
                            </button>
                            <div id="answers-<?php echo $question['id']; ?>" class="toggle-content mt-3">
                                <div class="space-y-3">
                                    <?php foreach ($question['answers'] as $answer): ?>
                                        <div class="answer-card p-3 text-sm">
                                            <p class="mb-2"><?php echo safe_display($answer['answer']); ?></p>
                                            <div class="text-xs text-gray-400">
                                                — <?php echo safe_display($answer['name']); ?> (<?php echo format_date($answer['created_at']); ?>)
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!-- Answer Form Section (Always Visible) -->
                        <button class="toggle-answer-form-btn text-xs text-yellow-400 mt-2 flex items-center"
                                onclick="toggleAnswerForm(<?php echo $question['id']; ?>)">
                            <i class="fas fa-reply mr-1"></i> Answer this Question
                        </button>
                        <form method="POST" id="answer-form-<?php echo $question['id']; ?>" class="toggle-content mt-2" 
                              action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                            <div class="mb-2">
                                <input type="text" name="answer_name" placeholder="Your Name*" 
                                       class="form-input text-xs p-2" 
                                       value="<?php echo safe_display($_POST['answer_name'] ?? ''); ?>" required>
                                <?php if (isset($errors['answer_name']) && isset($_POST['question_id']) && $_POST['question_id'] == $question['id']): ?>
                                    <p class="text-red-400 text-xs mt-1"><?php echo safe_display($errors['answer_name']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2">
                                <textarea name="answer_text" rows="2" placeholder="Your Answer*" 
                                          class="form-input text-xs p-2" required><?php echo safe_display($_POST['answer_text'] ?? ''); ?></textarea>
                                <?php if (isset($errors['answer_text']) && isset($_POST['question_id']) && $_POST['question_id'] == $question['id']): ?>
                                    <p class="text-red-400 text-xs mt-1"><?php echo safe_display($errors['answer_text']); ?></p>
                                <?php endif; ?>
                            </div>
                            <button type="submit" name="submit_answer" class="btn-primary text-xs p-2 w-full">
                                Post Answer
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleQuestionForm() {
            const form = document.getElementById('questionForm');
            const btn = document.getElementById('toggleQuestionForm');
            if (form && btn) {
                form.classList.toggle('active');
                btn.innerHTML = form.classList.contains('active') 
                    ? '<i class="fas fa-minus mr-1"></i> Cancel'
                    : '<i class="fas fa-plus mr-1"></i> Ask a Question';
            } else {
                console.error('Question form or button not found');
            }
        }

        function toggleAnswers(questionId) {
            const answersDiv = document.getElementById(`answers-${questionId}`);
            const btn = document.querySelector(`button[onclick="toggleAnswers(${questionId})"]`);
            if (answersDiv && btn) {
                answersDiv.classList.toggle('active');
                btn.innerHTML = answersDiv.classList.contains('active')
                    ? '<i class="fas fa-chevron-up mr-1"></i> Hide answers'
                    : '<i class="fas fa-chevron-down mr-1"></i> View answers';
            } else {
                console.error(`Answers div or button not found for question ID: ${questionId}`);
            }
        }

        function toggleAnswerForm(questionId) {
            const form = document.getElementById(`answer-form-${questionId}`);
            const btn = document.querySelector(`button[onclick="toggleAnswerForm(${questionId})"]`);
            if (form && btn) {
                form.classList.toggle('active');
                btn.innerHTML = form.classList.contains('active')
                    ? '<i class="fas fa-times mr-1"></i> Cancel'
                    : '<i class="fas fa-reply mr-1"></i> Answer this Question';
            } else {
                console.error(`Answer form or button not found for question ID: ${questionId}`);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.05}s`;
            });

            <?php if (!empty($errors['name']) || !empty($errors['email']) || !empty($errors['question'])): ?>
                toggleQuestionForm();
            <?php endif; ?>

            <?php if (!empty($errors['answer_name']) || !empty($errors['answer_text']) || !empty($errors['question_id'])): ?>
                const questionId = <?php echo json_encode($_POST['question_id'] ?? 0); ?>;
                if (questionId > 0) {
                    toggleAnswerForm(questionId);
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>