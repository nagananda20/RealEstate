<?php
session_start();
require_once "../config/database.php";

if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["user_role"] ?? $_SESSION["role"] ?? "user";
    if ($role === "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../page/dashboard.php");
    }
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $message = "Please enter a valid email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id,name,email,password,role,status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user["password"])) {
            $message = "Invalid email or password.";
        } elseif ($user["status"] !== "active") {
            $message = "Your account is not active.";
        } else {
            session_regenerate_id(true);
            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];
            /* Backward-compatible keys used by a few pages. */
            $_SESSION["role"] = $user["role"];
            $_SESSION["name"] = $user["name"];
            header("Location: " . ($user["role"] === "admin" ? "../admin/dashboard.php" : "../page/dashboard.php"));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | RealEstateHub</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <h1>RealEstateHub</h1>
        <p>Sign in to your account</p>
        <?php if ($message): ?><div class="auth-message error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <label>Email</label>
            <input type="email" name="email" required autocomplete="email">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
        <p><a href="../index.php">← Back to home</a></p>
    </div>
</div>
</body>
</html>
