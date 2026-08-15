<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   AUTH CHECK
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================================================
   HELPER
========================================================= */

function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   VARIABLES
========================================================= */

$name = "";
$email = "";
$phone = "";
$role = "user";

$errors = [];

$success = "";


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));

}


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $confirmPassword =
        $_POST["confirm_password"] ?? "";

    $role =
        trim($_POST["role"] ?? "user");

    $csrfToken =
        $_POST["csrf_token"] ?? "";


    /* =====================================================
       CSRF
    ===================================================== */

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $csrfToken
        )
    ) {

        $errors[] =
            "Security verification failed.";

    }


    /* =====================================================
       NAME
    ===================================================== */

    if ($name === "") {

        $errors[] =
            "Full name is required.";

    }
    elseif (strlen($name) < 2) {

        $errors[] =
            "Name must contain at least 2 characters.";

    }
    elseif (strlen($name) > 100) {

        $errors[] =
            "Name cannot exceed 100 characters.";

    }


    /* =====================================================
       EMAIL
    ===================================================== */

    if ($email === "") {

        $errors[] =
            "Email address is required.";

    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] =
            "Please enter a valid email address.";

    }


    /* =====================================================
       PHONE
    ===================================================== */

    if ($phone !== "") {

        $cleanPhone =
            preg_replace(
                "/[^0-9+]/",
                "",
                $phone
            );

        if (
            strlen(
                preg_replace(
                    "/[^0-9]/",
                    "",
                    $cleanPhone
                )
            ) < 10
        ) {

            $errors[] =
                "Please enter a valid phone number.";

        }

    }


    /* =====================================================
       PASSWORD
    ===================================================== */

    if ($password === "") {

        $errors[] =
            "Password is required.";

    }
    elseif (strlen($password) < 8) {

        $errors[] =
            "Password must contain at least 8 characters.";

    }


    if ($confirmPassword === "") {

        $errors[] =
            "Please confirm the password.";

    }
    elseif ($password !== $confirmPassword) {

        $errors[] =
            "Passwords do not match.";

    }


    /* =====================================================
       ROLE
    ===================================================== */

    $allowedRoles = [
        "user",
        "agent",
        "admin"
    ];

    if (!in_array($role, $allowedRoles, true)) {

        $errors[] =
            "Invalid user role.";

        $role = "user";

    }


    /* =====================================================
       CHECK EMAIL
    ===================================================== */

    if (empty($errors)) {

        $checkStmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($checkStmt) {

            $checkStmt->bind_param(
                "s",
                $email
            );

            $checkStmt->execute();

            $checkResult =
                $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {

                $errors[] =
                    "An account with this email already exists.";

            }

            $checkStmt->close();

        }
        else {

            $errors[] =
                "Unable to check email address.";

        }

    }


    /* =====================================================
       INSERT USER
    ===================================================== */

    if (empty($errors)) {

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        /*
         * Most RealEstate projects use:
         *
         * id
         * name
         * email
         * password
         * phone
         * role
         * created_at
         *
         */

        $insertStmt = $conn->prepare(
            "INSERT INTO users
            (
                name,
                email,
                password,
                phone,
                role,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )"
        );


        if (!$insertStmt) {

            $errors[] =
                "Database error: unable to create user.";

        }
        else {

            $insertStmt->bind_param(
                "sssss",
                $name,
                $email,
                $hashedPassword,
                $phone,
                $role
            );


            if ($insertStmt->execute()) {

                $_SESSION["success"] =
                    "User account created successfully.";

                $insertStmt->close();

                header(
                    "Location: users.php"
                );

                exit;

            }
            else {

                $errors[] =
                    "Unable to create user. Please try again.";

            }


            $insertStmt->close();

        }

    }

}


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminName =
    $_SESSION["user_name"]
    ??
    "Administrator";

$adminInitial =
    strtoupper(
        substr(
            $adminName,
            0,
            1
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Add User | RealEstate
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin:0;

    padding:0;

    box-sizing:border-box;

}


:root {

    --primary:#174a3a;

    --primary-dark:#10372b;

    --accent:#d7a94b;

    --bg:#f4f6f5;

    --white:#ffffff;

    --text:#18231f;

    --muted:#737c78;

    --border:#dfe6e2;

    --red:#b43843;

    --red-bg:#fdebed;

    --green:#24734e;

    --green-bg:#eaf6ef;

}


/* =========================================================
   BODY
========================================================= */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        var(--bg);

    color:
        var(--text);

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position:fixed;

    left:0;

    top:0;

    bottom:0;

    width:240px;

    background:
        var(--primary);

    color:white;

    z-index:100;

}


.logo {

    height:75px;

    display:flex;

    align-items:center;

    padding:0 25px;

    color:white;

    text-decoration:none;

    font-size:20px;

    font-weight:800;

    border-bottom:
        1px solid
        rgba(255,255,255,.1);

}


.logo strong {

    color:
        var(--accent);

}


.menu-title {

    padding:
        20px 25px 8px;

    color:
        rgba(255,255,255,.4);

    font-size:8px;

    text-transform:uppercase;

    letter-spacing:1.5px;

}


.menu {

    padding:0 12px;

}


.menu a {

    height:44px;

    display:flex;

    align-items:center;

    gap:12px;

    padding:0 13px;

    margin-bottom:3px;

    border-radius:7px;

    color:
        rgba(255,255,255,.7);

    text-decoration:none;

    font-size:10px;

    transition:.2s;

}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.1);

    color:white;

}


.icon {

    width:20px;

    text-align:center;

}


.sidebar-bottom {

    position:absolute;

    left:0;

    right:0;

    bottom:0;

    padding:15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:240px;

    min-height:100vh;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height:75px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    background:white;

    border-bottom:
        1px solid
        var(--border);

}


.topbar-left {

    display:flex;

    align-items:center;

    gap:12px;

}


.back {

    width:36px;

    height:36px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:6px;

    background:#eef1ef;

    color:var(--text);

    text-decoration:none;

}


.topbar h1 {

    font-size:18px;

}


.topbar p {

    margin-top:4px;

    color:var(--muted);

    font-size:8px;

}


.admin {

    display:flex;

    align-items:center;

    gap:9px;

}


.admin-avatar {

    width:35px;

    height:35px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-size:12px;

    font-weight:800;

}


.admin-name {

    font-size:8px;

    font-weight:700;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:1000px;

    margin:auto;

    padding:30px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    margin-bottom:22px;

}


.page-header h2 {

    font-size:23px;

}


.page-header p {

    margin-top:7px;

    color:var(--muted);

    font-size:8px;

}


/* =========================================================
   ERROR
========================================================= */

.error-box {

    background:
        var(--red-bg);

    color:
        var(--red);

    border:
        1px solid
        #f4cbd0;

    border-radius:8px;

    padding:14px 17px;

    margin-bottom:20px;

    font-size:8px;

}


.error-box strong {

    display:block;

    margin-bottom:7px;

}


.error-box ul {

    padding-left:18px;

}


.error-box li {

    margin-bottom:4px;

}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:11px;

    overflow:hidden;

}


.card-header {

    padding:
        18px 22px;

    border-bottom:
        1px solid
        #edf0ee;

}


.card-header h3 {

    font-size:11px;

}


.card-header p {

    margin-top:5px;

    color:var(--muted);

    font-size:7px;

}


.form-body {

    padding:25px;

}


/* =========================================================
   FORM GRID
========================================================= */

.form-grid {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:20px;

}


.form-group {

    display:flex;

    flex-direction:column;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    margin-bottom:7px;

    font-size:8px;

    font-weight:700;

}


.required {

    color:
        var(--red);

}


input,
select {

    width:100%;

    height:44px;

    padding:
        0 13px;

    border:
        1px solid
        var(--border);

    border-radius:7px;

    background:white;

    color:var(--text);

    outline:none;

    font-family:inherit;

    font-size:9px;

    transition:.2s;

}


input:focus,
select:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23,74,58,.08);

}


.help {

    margin-top:6px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   PASSWORD
========================================================= */

.password-wrap {

    position:relative;

}


.password-wrap input {

    padding-right:45px;

}


.password-toggle {

    position:absolute;

    right:10px;

    top:50%;

    transform:translateY(-50%);

    border:none;

    background:none;

    cursor:pointer;

    color:var(--muted);

    font-size:15px;

}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

.strength {

    margin-top:8px;

}


.strength-bar {

    height:5px;

    background:#edf0ee;

    border-radius:10px;

    overflow:hidden;

}


.strength-fill {

    height:100%;

    width:0;

    transition:.3s;

}


.strength-text {

    margin-top:5px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   ROLE
========================================================= */

.role-options {

    display:grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:10px;

}


.role-option {

    position:relative;

}


.role-option input {

    position:absolute;

    opacity:0;

}


.role-label {

    display:block;

    padding:14px;

    border:
        1px solid
        var(--border);

    border-radius:8px;

    cursor:pointer;

    transition:.2s;

}


.role-label strong {

    display:block;

    font-size:9px;

    margin-bottom:5px;

}


.role-label span {

    color:var(--muted);

    font-size:7px;

    line-height:1.5;

}


.role-option input:checked
+ .role-label {

    border-color:
        var(--primary);

    background:
        #f0f6f3;

    box-shadow:
        0 0 0 2px
        rgba(23,74,58,.08);

}


/* =========================================================
   ACTIONS
========================================================= */

.form-actions {

    display:flex;

    align-items:center;

    justify-content:flex-end;

    gap:10px;

    margin-top:28px;

    padding-top:20px;

    border-top:
        1px solid
        #edf0ee;

}


.btn {

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:
        0 18px;

    border:none;

    border-radius:7px;

    cursor:pointer;

    text-decoration:none;

    font-size:8px;

    font-weight:700;

    transition:.2s;

}


.btn-cancel {

    background:#eef1ef;

    color:var(--text);

}


.btn-cancel:hover {

    background:#e1e7e3;

}


.btn-submit {

    background:
        var(--primary);

    color:white;

}


.btn-submit:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:800px) {

    .sidebar {

        width:65px;

    }


    .logo {

        padding:0;

        justify-content:center;

        font-size:0;

    }


    .logo::after {

        content:"RE";

        font-size:14px;

    }


    .menu-title {

        display:none;

    }


    .menu a {

        justify-content:center;

        padding:0;

    }


    .menu a span:not(.icon) {

        display:none;

    }


    .main {

        margin-left:65px;

    }


    .admin-name {

        display:none;

    }

}


@media(max-width:650px) {

    .content {

        padding:
            20px 15px;

    }


    .form-grid {

        grid-template-columns:1fr;

    }


    .form-group.full {

        grid-column:auto;

    }


    .role-options {

        grid-template-columns:1fr;

    }


    .form-actions {

        flex-direction:column-reverse;

    }


    .form-actions .btn {

        width:100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


<a
    href="dashboard.php"
    class="logo"
>
    Real<strong>Estate</strong>
</a>


<div class="menu-title">
    Administration
</div>


<nav class="menu">


<a href="dashboard.php">

    <span class="icon">📊</span>
    <span>Dashboard</span>

</a>


<a href="properties.php">

    <span class="icon">🏠</span>
    <span>Properties</span>

</a>


<a
    href="users.php"
    class="active"
>

    <span class="icon">👥</span>
    <span>Users</span>

</a>


<a href="agents.php">

    <span class="icon">🧑‍💼</span>
    <span>Agents</span>

</a>


<a href="enquiries.php">

    <span class="icon">💬</span>
    <span>Enquiries</span>

</a>


<a href="visits.php">

    <span class="icon">📅</span>
    <span>Visits</span>

</a>


<a href="settings.php">

    <span class="icon">⚙️</span>
    <span>Settings</span>

</a>


</nav>


<div class="sidebar-bottom">

<a
    href="../auth/logout.php"
    style="
        color:#ffb8bf;
        text-decoration:none;
        font-size:10px;
    "
>
    🚪 Logout
</a>

</div>


</aside>


<!-- =====================================================
     MAIN
========================================================= -->

<div class="main">


<header class="topbar">


<div class="topbar-left">


<a
    href="users.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Add User
</h1>

<p>
    Create a new RealEstate account
</p>

</div>


</div>


<div class="admin">


<div class="admin-avatar">

<?php
echo safe($adminInitial);
?>

</div>


<div class="admin-name">

<?php
echo safe($adminName);
?>

</div>


</div>


</header>


<!-- =====================================================
     CONTENT
========================================================= -->

<main class="content">


<div class="page-header">

<h2>
    Create New User
</h2>

<p>
    Enter the user's account information and assign an appropriate role.
</p>

</div>


<!-- =====================================================
     ERRORS
========================================================= -->

<?php if (!empty($errors)): ?>

<div class="error-box">

<strong>
    ⚠ Please fix the following:
</strong>


<ul>

<?php foreach ($errors as $error): ?>

<li>
    <?php echo safe($error); ?>
</li>

<?php endforeach; ?>

</ul>

</div>

<?php endif; ?>


<!-- =====================================================
     FORM
========================================================= -->

<section class="form-card">


<div class="card-header">

<h3>
    Account Information
</h3>

<p>
    All fields marked with * are required.
</p>

</div>


<form
    method="POST"
    id="userForm"
    autocomplete="off"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo safe(
            $_SESSION["csrf_token"]
        );
    ?>"
>


<div class="form-body">


<div class="form-grid">


<!-- NAME -->

<div class="form-group">

<label for="name">

Full Name
<span class="required">*</span>

</label>


<input
    type="text"
    id="name"
    name="name"
    value="<?php echo safe($name); ?>"
    placeholder="Enter full name"
    maxlength="100"
    required
>


<div class="help">
    Enter the user's full name.
</div>

</div>


<!-- EMAIL -->

<div class="form-group">

<label for="email">

Email Address
<span class="required">*</span>

</label>


<input
    type="email"
    id="email"
    name="email"
    value="<?php echo safe($email); ?>"
    placeholder="example@email.com"
    maxlength="150"
    required
>


<div class="help">
    This email will be used for login.
</div>

</div>


<!-- PHONE -->

<div class="form-group">

<label for="phone">
    Phone Number
</label>


<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php echo safe($phone); ?>"
    placeholder="+91 98765 43210"
    maxlength="20"
>


<div class="help">
    Optional contact number.
</div>

</div>


<!-- ROLE -->

<div class="form-group">

<label>
    Account Role
    <span class="required">*</span>
</label>


<select
    name="role"
    id="role"
    required
>

<option
    value="user"
    <?php
    echo $role === "user"
        ? "selected"
        : "";
    ?>
>
    User
</option>


<option
    value="agent"
    <?php
    echo $role === "agent"
        ? "selected"
        : "";
    ?>
>
    Agent
</option>


<option
    value="admin"
    <?php
    echo $role === "admin"
        ? "selected"
        : "";
    ?>
>
    Administrator
</option>

</select>


<div class="help">
    Controls the user's access level.
</div>

</div>


<!-- PASSWORD -->

<div class="form-group">

<label for="password">

Password
<span class="required">*</span>

</label>


<div class="password-wrap">


<input
    type="password"
    id="password"
    name="password"
    placeholder="Minimum 8 characters"
    minlength="8"
    required
>


<button
    type="button"
    class="password-toggle"
    onclick="togglePassword('password', this)"
>
    👁
</button>


</div>


<div class="strength">

<div class="strength-bar">

<div
    class="strength-fill"
    id="strengthFill"
></div>

</div>


<div
    class="strength-text"
    id="strengthText"
>
    Password strength
</div>

</div>

</div>


<!-- CONFIRM PASSWORD -->

<div class="form-group">

<label for="confirm_password">

Confirm Password
<span class="required">*</span>

</label>


<div class="password-wrap">


<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    placeholder="Repeat password"
    minlength="8"
    required
>


<button
    type="button"
    class="password-toggle"
    onclick="togglePassword('confirm_password', this)"
>
    👁
</button>


</div>


<div
    class="help"
    id="matchMessage"
>
    Enter the same password again.
</div>

</div>


<!-- ROLE INFORMATION -->

<div class="form-group full">


<label>
    Role Information
</label>


<div class="role-options">


<div class="role-option">

<input
    type="radio"
    id="roleUser"
    name="role_preview"
    checked
>

<label
    class="role-label"
    for="roleUser"
>

<strong>
    👤 User
</strong>

<span>
    Can browse properties,
    send enquiries and schedule visits.
</span>

</label>

</div>


<div class="role-option">

<input
    type="radio"
    id="roleAgent"
    name="role_preview"
>

<label
    class="role-label"
    for="roleAgent"
>

<strong>
    🧑‍💼 Agent
</strong>

<span>
    Can manage assigned property
    listings and customer enquiries.
</span>

</label>

</div>


<div class="role-option">

<input
    type="radio"
    id="roleAdmin"
    name="role_preview"
>

<label
    class="role-label"
    for="roleAdmin"
>

<strong>
    🛡 Administrator
</strong>

<span>
    Full access to the RealEstate
    administration panel.
</span>

</label>

</div>


</div>

</div>


</div>


<!-- ACTIONS -->

<div class="form-actions">


<a
    href="users.php"
    class="btn btn-cancel"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-submit"
    id="submitBtn"
>
    ✓ Create User
</button>


</div>


</div>


</form>


</section>


</main>


</div>


<script>

/* =========================================================
   PASSWORD TOGGLE
========================================================= */

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(
            inputId
        );


    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";

    }
    else {

        input.type = "password";

        button.textContent = "👁";

    }

}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

const password =
    document.getElementById(
        "password"
    );

const strengthFill =
    document.getElementById(
        "strengthFill"
    );

const strengthText =
    document.getElementById(
        "strengthText"
    );


password.addEventListener(
    "input",
    function() {

        const value =
            password.value;

        let score = 0;


        if (value.length >= 8) {
            score++;
        }

        if (/[A-Z]/.test(value)) {
            score++;
        }

        if (/[a-z]/.test(value)) {
            score++;
        }

        if (/[0-9]/.test(value)) {
            score++;
        }

        if (/[^A-Za-z0-9]/.test(value)) {
            score++;
        }


        const widths = [
            "0%",
            "20%",
            "40%",
            "60%",
            "80%",
            "100%"
        ];


        strengthFill.style.width =
            widths[score];


        if (score <= 1) {

            strengthText.textContent =
                "Very weak password";

        }
        else if (score === 2) {

            strengthText.textContent =
                "Weak password";

        }
        else if (score === 3) {

            strengthText.textContent =
                "Medium password";

        }
        else if (score === 4) {

            strengthText.textContent =
                "Strong password";

        }
        else {

            strengthText.textContent =
                "Very strong password";

        }

    }
);


/* =========================================================
   PASSWORD MATCH
========================================================= */

const confirmPassword =
    document.getElementById(
        "confirm_password"
    );

const matchMessage =
    document.getElementById(
        "matchMessage"
    );


function checkPasswordMatch() {

    if (
        confirmPassword.value === ""
    ) {

        matchMessage.textContent =
            "Enter the same password again.";

        matchMessage.style.color =
            "#737c78";

        return;

    }


    if (
        password.value ===
        confirmPassword.value
    ) {

        matchMessage.textContent =
            "✓ Passwords match.";

        matchMessage.style.color =
            "#24734e";

    }
    else {

        matchMessage.textContent =
            "✕ Passwords do not match.";

        matchMessage.style.color =
            "#b43843";

    }

}


password.addEventListener(
    "input",
    checkPasswordMatch
);

confirmPassword.addEventListener(
    "input",
    checkPasswordMatch
);


/* =========================================================
   ROLE PREVIEW
========================================================= */

const roleSelect =
    document.getElementById(
        "role"
    );

const roleUser =
    document.getElementById(
        "roleUser"
    );

const roleAgent =
    document.getElementById(
        "roleAgent"
    );

const roleAdmin =
    document.getElementById(
        "roleAdmin"
    );


function updateRolePreview() {

    roleUser.checked =
        roleSelect.value === "user";

    roleAgent.checked =
        roleSelect.value === "agent";

    roleAdmin.checked =
        roleSelect.value === "admin";

}


roleSelect.addEventListener(
    "change",
    updateRolePreview
);


/* =========================================================
   FORM VALIDATION
========================================================= */

const form =
    document.getElementById(
        "userForm"
    );


form.addEventListener(
    "submit",
    function(event) {

        if (
            password.value !==
            confirmPassword.value
        ) {

            event.preventDefault();

            alert(
                "Passwords do not match."
            );

            confirmPassword.focus();

            return;

        }


        if (
            password.value.length < 8
        ) {

            event.preventDefault();

            alert(
                "Password must contain at least 8 characters."
            );

            password.focus();

            return;

        }


        const submitBtn =
            document.getElementById(
                "submitBtn"
            );


        submitBtn.disabled =
            true;

        submitBtn.textContent =
            "Creating User...";

    }
);


/* =========================================================
   PAGE ANIMATION
========================================================= */

const card =
    document.querySelector(
        ".form-card"
    );


card.style.opacity = "0";

card.style.transform =
    "translateY(15px)";


setTimeout(
    function() {

        card.style.transition =
            "opacity .35s ease, transform .35s ease";

        card.style.opacity = "1";

        card.style.transform =
            "translateY(0)";

    },
    50
);

</script>


</body>

</html>