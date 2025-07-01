<?php
session_start(); 
include '../includes/db_connect.php'; 
include '../includes/csrf_token.php'; 

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { 
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; 
        $confirm_password = $_POST['confirm_password'];

        if (empty($full_name)) { $errors[] = 'Full Name is required.'; }
        if (empty($email)) { $errors[] = 'Email is required.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email format.'; }
        if (empty($password)) { $errors[] = 'Password is required.'; }
        if (strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters long.'; }
        if (!preg_match('/[A-Z]/', $password)) { $errors[] = 'Password must include at least one uppercase letter.'; }
        if (!preg_match('/[a-z]/', $password)) { $errors[] = 'Password must include at least one lowercase letter.'; }
        if (!preg_match('/[0-9]/', $password)) { $errors[] = 'Password must include at least one number.'; }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) { $errors[] = 'Password must include at least one special character.'; }
        if ($password !== $confirm_password) { $errors[] = 'Passwords do not match.'; }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Email already registered. Please login or use a different email.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $default_role = 'user'; // Assign default role

                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$full_name, $email, $password_hash, $default_role]);
                    $success_message = 'Registration successful! You can now log in.';
                    
                }
            } catch (PDOException $e) {
                
                error_log("Registration error: " . $e->getMessage());
                $errors[] = 'An error occurred during registration. Please try again.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<?php include '../includes/header.php';  ?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center">Register for SEA Catering</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <a href="login.php" class="alert-link">Click here to login.</a>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="form-text text-muted">Min. 8 characters, with uppercase, lowercase, number, and special character.</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                    <p class="mt-3 text-center">
                        Already have an account? <a href="login.php">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php';  ?>