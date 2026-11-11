<?php
include 'db.php';
session_start();

$error = "";
$success = "";
$show_otp = false;

// 1. REGISTER STEP
if (isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $question = $_POST['reg_question'];
    $answer = strtolower(trim($_POST['reg_answer']));

    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email is already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password, $question, $answer);
        if ($stmt->execute()) {
            // Generate OTP for new user
            $_SESSION['pending_user'] = $username;
            $_SESSION['otp_code'] = rand(1000, 9999);
            $show_otp = true;
        }
    }
}

// 2. LOGIN STEP
if (isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Generate OTP for login
            $_SESSION['pending_user'] = $row['username'];
            $_SESSION['otp_code'] = rand(1000, 9999);
            $show_otp = true;
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "No account found with that email!";
    }
}

// 3. OTP VERIFICATION STEP
if (isset($_POST['verify_otp'])) {
    $user_otp = trim($_POST['otp_input']);

    if (isset($_SESSION['otp_code']) && $user_otp == $_SESSION['otp_code']) {
        $_SESSION['user'] = $_SESSION['pending_user'];
        unset($_SESSION['pending_user']);
        unset($_SESSION['otp_code']);
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid OTP verification code! Please try again.";
        $show_otp = true;
    }
}

// 4. FORGOT PASSWORD STEP
if (isset($_POST['reset_password'])) {
    $email = trim($_POST['reset_email']);
    $answer = strtolower(trim($_POST['reset_answer']));
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (strtolower($row['security_answer']) === $answer) {
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $new_password, $email);
            $update->execute();
            $success = "Password successfully reset! You can now log in.";
        } else {
            $error = "Incorrect answer to security question!";
        }
    } else {
        $error = "No user found with that email!";
    }
}

// Check if waiting on OTP verification
if (isset($_SESSION['pending_user']) && isset($_SESSION['otp_code'])) {
    $show_otp = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Access - UCC FindIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-ucc { border-top: 4px solid #0d2346; }
        .btn-ucc { background-color: #0d2346; color: #fff; font-weight: bold; }
        .btn-ucc:hover { background-color: #153463; color: #fff; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow border-0 p-4 rounded-4 card-ucc" style="width: 100%; max-width: 420px;">
        <div class="text-center mb-3">
            <img src="img/logo.png" alt="UCC Crest" height="60">
            <h4 class="fw-bold text-dark mt-2">UCC FindIt Portal</h4>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger p-2 small text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success p-2 small text-center"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($show_otp): ?>
            <!-- OTP VERIFICATION SCREEN -->
            <div class="alert alert-info text-center p-2 mb-3 small">
                <strong>📲 Security Verification Code:</strong><br>
                Use demo OTP code: <span class="badge bg-dark fs-6"><?php echo $_SESSION['otp_code']; ?></span>
            </div>

            <form action="auth.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-center w-100">Enter 4-Digit Security Code</label>
                    <input type="text" name="otp_input" class="form-control text-center fs-4 fw-bold letter-spacing" maxlength="4" placeholder="••••" required autofocus>
                </div>
                <button type="submit" name="verify_otp" class="btn btn-ucc w-100 py-2">Verify & Log In</button>
            </form>
            <div class="text-center mt-3">
                <a href="logout.php" class="text-decoration-none small text-muted">Cancel Verification</a>
            </div>

        <?php else: ?>
            <!-- REGULAR LOGIN / REGISTER / RESET TABS -->
            <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-content" type="button">Login</button></li>
                <li class="nav-item"><button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register-content" type="button">Register</button></li>
                <li class="nav-item"><button class="nav-link" id="forgot-tab" data-bs-toggle="pill" data-bs-target="#forgot-content" type="button">Reset</button></li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                
                <!-- LOGIN FORM -->
                <div class="tab-pane fade show active" id="login-content">
                    <form action="auth.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Student Email</label>
                            <input type="email" name="login_email" class="form-control" placeholder="student@ucc.edu.gh" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="login_password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-ucc w-100 fw-bold">Login</button>
                    </form>
                </div>

                <!-- REGISTER FORM -->
                <div class="tab-pane fade" id="register-content">
                    <form action="auth.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="reg_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="reg_email" class="form-control" placeholder="student@ucc.edu.gh" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="reg_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Security Question</label>
                            <select name="reg_question" class="form-select">
                                <option value="What is your favorite high school?">What is your favorite high school?</option>
                                <option value="What is your pet's name?">What is your pet's name?</option>
                                <option value="What town were you born in?">What town were you born in?</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Security Answer</label>
                            <input type="text" name="reg_answer" class="form-control" placeholder="Your secret answer" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-success w-100 fw-bold">Create Account</button>
                    </form>
                </div>

                <!-- FORGOT PASSWORD FORM -->
                <div class="tab-pane fade" id="forgot-content">
                    <form action="auth.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Registered Email</label>
                            <input type="email" name="reset_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Security Question Answer</label>
                            <input type="text" name="reset_answer" class="form-control" placeholder="Answer to security question" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-warning w-100 fw-bold text-dark">Update Password</button>
                    </form>
                </div>

            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none small text-muted">← Back to Home Feed</a>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>