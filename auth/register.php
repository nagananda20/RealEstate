<?php
session_start();

require_once "../config/database.php";

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["user_role"] ?? $_SESSION["role"] ?? "user";
    header("Location: " . ($role === "admin" ? "../admin/dashboard.php" : "../page/dashboard.php"));
    exit;
}

$message = "";
$message_type = "";
$name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validation
    if (empty($name) || empty($email) || empty($password)) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        // Check existing email
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "Email address already exists.";
            $message_type = "error";

        } else {

            // Secure password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (name, email, phone, password, role, status)
                VALUES (?, ?, ?, ?, 'user', 'active')"
            );

            $stmt->execute([
                $name,
                $email,
                $phone !== "" ? $phone : null,
                $hashed_password
            ]);

            $message = "Registration successful! You can now login.";
            $message_type = "success";

            // Clear inputs after successful registration
            $name = "";
            $email = "";
            $phone = "";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register - RealEstate</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(
                135deg,
                #0f172a,
                #2563eb
            );
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo h1 {
            color: #2563eb;
            font-size: 30px;
        }

        .logo p {
            color: #64748b;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
        }

        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .register-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .register-btn:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            text-align: center;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .success {
            background: #dcfce7;
            color: #15803d;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="register-container">

    <div class="logo">

        <h1>RealEstate</h1>

        <p>Create your account</p>

    </div>

    <?php if (!empty($message)): ?>

        <div class="message <?= htmlspecialchars($message_type) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label for="name">Full Name</label>

            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($name) ?>"
                placeholder="Enter your full name"
                required
            >

        </div>

        <div class="form-group">

            <label for="email">Email</label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email) ?>"
                placeholder="Enter your email"
                required
            >

        </div>

        <div class="form-group">

            <label for="phone">Phone</label>

            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($phone) ?>"
                placeholder="Enter your phone number"
            >

        </div>

        <div class="form-group">

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimum 6 characters"
                required
            >

        </div>

        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm your password"
                required
            >

        </div>

        <button
            type="submit"
            class="register-btn"
        >
            Create Account
        </button>

    </form>

    <div class="login-link">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </div>

</div>

</body>

</html>