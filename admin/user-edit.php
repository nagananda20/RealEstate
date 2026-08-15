<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   AUTH
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
   USER ID
========================================================= */

$userId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$userId) {
    header("Location: users.php?error=invalid_id");
    exit;
}


/* =========================================================
   FETCH USER
========================================================= */

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    header("Location: users.php?error=user_not_found");
    exit;
}


/* =========================================================
   DEFAULT VALUES
========================================================= */

$name =
    $user["name"] ?? "";

$email =
    $user["email"] ?? "";

$phone =
    $user["phone"] ?? "";

$role =
    $user["role"] ?? "user";

$status =
    $user["status"] ?? "active";

$errors = [];

$success = "";


/* =========================================================
   UPDATE USER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $role =
        trim($_POST["role"] ?? "user");

    $status =
        trim($_POST["status"] ?? "active");

    $password =
        $_POST["password"] ?? "";

    $confirmPassword =
        $_POST["confirm_password"] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($name === "") {

        $errors[] =
            "Full name is required.";

    }
    elseif (strlen($name) < 2) {

        $errors[] =
            "Name must contain at least 2 characters.";

    }


    if ($email === "") {

        $errors[] =
            "Email address is required.";

    }
    elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if (!in_array(
        $role,
        ["admin", "agent", "user"],
        true
    )) {

        $errors[] =
            "Invalid user role.";

    }


    if (!in_array(
        $status,
        ["active", "inactive"],
        true
    )) {

        $errors[] =
            "Invalid account status.";

    }


    /* =====================================================
       PASSWORD VALIDATION
    ===================================================== */

    if ($password !== "") {

        if (strlen($password) < 8) {

            $errors[] =
                "Password must contain at least 8 characters.";

        }

        if ($password !== $confirmPassword) {

            $errors[] =
                "Passwords do not match.";

        }

    }


    /* =====================================================
       CHECK DUPLICATE EMAIL
    ===================================================== */

    if (empty($errors)) {

        $sql = "
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ";

        $stmt =
            $conn->prepare($sql);

        $stmt->bind_param(
            "si",
            $email,
            $userId
        );

        $stmt->execute();

        $duplicate =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();


        if ($duplicate) {

            $errors[] =
                "This email address is already used by another account.";

        }

    }


    /* =====================================================
       PREVENT SELF ADMIN LOCKOUT
    ===================================================== */

    if (
        $userId === (int)$_SESSION["user_id"]
    ) {

        if ($role !== "admin") {

            $errors[] =
                "You cannot remove your own administrator role.";

        }

        if ($status !== "active") {

            $errors[] =
                "You cannot deactivate your own account.";

        }

    }


    /* =====================================================
       UPDATE
    ===================================================== */

    if (empty($errors)) {

        if ($password !== "") {

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $sql = "
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = ?,
                    role = ?,
                    status = ?,
                    password = ?
                WHERE id = ?
            ";

            $stmt =
                $conn->prepare($sql);

            $stmt->bind_param(
                "ssssssi",
                $name,
                $email,
                $phone,
                $role,
                $status,
                $hashedPassword,
                $userId
            );

        }
        else {

            $sql = "
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = ?,
                    role = ?,
                    status = ?
                WHERE id = ?
            ";

            $stmt =
                $conn->prepare($sql);

            $stmt->bind_param(
                "sssssi",
                $name,
                $email,
                $phone,
                $role,
                $status,
                $userId
            );

        }


        if ($stmt->execute()) {

            $success =
                "User account updated successfully.";

            $user["name"] =
                $name;

            $user["email"] =
                $email;

            $user["phone"] =
                $phone;

            $user["role"] =
                $role;

            $user["status"] =
                $status;

        }
        else {

            $errors[] =
                "Unable to update the user account.";

        }


        $stmt->close();

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

<title>
    Edit User | RealEstate
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --primary: #174a3a;
    --primary-dark: #10372b;

    --accent: #d7a94b;

    --bg: #f4f6f5;

    --white: #ffffff;

    --text: #18231f;

    --muted: #737c78;

    --border: #dfe6e2;

    --green: #17643b;
    --green-bg: #e8f6ed;

    --red: #b43843;
    --red-bg: #fdebed;

    --blue: #365caa;
    --blue-bg: #edf3ff;

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

    position: fixed;

    left: 0;
    top: 0;
    bottom: 0;

    width: 240px;

    background:
        var(--primary);

    color: white;

}


.logo {

    height: 75px;

    display: flex;

    align-items: center;

    padding: 0 25px;

    color: white;

    text-decoration: none;

    font-size: 20px;

    font-weight: 800;

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

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: 1.5px;

}


.menu {

    padding:
        0 12px;

}


.menu a {

    height: 44px;

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        0 13px;

    margin-bottom: 3px;

    border-radius: 7px;

    color:
        rgba(255,255,255,.7);

    text-decoration: none;

    font-size: 10px;

}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.1);

    color:
        white;

}


.icon {

    width: 20px;

    text-align: center;

}


.sidebar-bottom {

    position: absolute;

    left: 0;
    right: 0;
    bottom: 0;

    padding: 15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:
        240px;

    min-height:
        100vh;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 75px;

    background:
        white;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 30px;

    position: sticky;

    top: 0;

    z-index: 10;

}


.top-left {

    display: flex;

    align-items: center;

    gap: 12px;

}


.back {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        var(--border);

    border-radius: 7px;

    text-decoration: none;

    color:
        var(--text);

    font-size: 15px;

}


.topbar h1 {

    font-size: 18px;

}


.topbar p {

    margin-top: 4px;

    color:
        var(--muted);

    font-size: 8px;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1100px;

    margin:
        0 auto;

    padding:
        30px;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding:
        13px 15px;

    border-radius:
        7px;

    margin-bottom:
        15px;

    font-size:
        8px;

    line-height:
        1.6;

}


.alert-error {

    background:
        var(--red-bg);

    color:
        var(--red);

    border:
        1px solid
        #f2c6ca;

}


.alert-success {

    background:
        var(--green-bg);

    color:
        var(--green);

    border:
        1px solid
        #c5e7d0;

}


/* =========================================================
   LAYOUT
========================================================= */

.layout {

    display:
        grid;

    grid-template-columns:
        1fr 300px;

    gap:
        20px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    overflow:
        hidden;

}


.card-header {

    padding:
        18px 20px;

    border-bottom:
        1px solid
        var(--border);

}


.card-header h2 {

    font-size:
        13px;

}


.card-header p {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        8px;

}


.card-body {

    padding:
        22px;

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        18px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

    gap:
        7px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    color:
        var(--text);

    font-size:
        8px;

    font-weight:
        700;

}


.required {

    color:
        var(--red);

}


input,
select {

    width:
        100%;

    height:
        43px;

    padding:
        0 12px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    outline:
        none;

    background:
        white;

    color:
        var(--text);

    font-size:
        9px;

    transition:
        .2s;

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

    color:
        var(--muted);

    font-size:
        7px;

    line-height:
        1.5;

}


/* =========================================================
   PASSWORD
========================================================= */

.password-wrapper {

    position:
        relative;

}


.password-wrapper input {

    padding-right:
        45px;

}


.toggle-password {

    position:
        absolute;

    right:
        10px;

    top:
        50%;

    transform:
        translateY(-50%);

    border:
        none;

    background:
        transparent;

    cursor:
        pointer;

    font-size:
        14px;

}


/* =========================================================
   BUTTONS
========================================================= */

.form-actions {

    display:
        flex;

    align-items:
        center;

    justify-content:
        flex-end;

    gap:
        8px;

    margin-top:
        25px;

    padding-top:
        18px;

    border-top:
        1px solid
        var(--border);

}


.btn {

    height:
        42px;

    padding:
        0 17px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        6px;

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;

    cursor:
        pointer;

    border:
        none;

}


.btn-cancel {

    color:
        var(--text);

    background:
        #f0f3f1;

}


.btn-save {

    color:
        white;

    background:
        var(--primary);

}


.btn-save:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   PROFILE CARD
========================================================= */

.profile {

    text-align:
        center;

    padding:
        25px 20px;

}


.avatar {

    width:
        75px;

    height:
        75px;

    margin:
        0 auto 13px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        50%;

    background:
        var(--primary);

    color:
        white;

    font-size:
        25px;

    font-weight:
        800;

}


.profile h3 {

    font-size:
        15px;

}


.profile-email {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        8px;

    word-break:
        break-word;

}


.badge {

    display:
        inline-flex;

    margin-top:
        12px;

    padding:
        6px 10px;

    border-radius:
        20px;

    font-size:
        7px;

    font-weight:
        700;

}


.role-admin {

    background:
        #fff4d1;

    color:
        #704f08;

}


.role-agent {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


.role-user {

    background:
        var(--green-bg);

    color:
        var(--green);

}


/* =========================================================
   SIDE INFO
========================================================= */

.side-info {

    padding:
        18px 20px;

    border-top:
        1px solid
        var(--border);

}


.side-item {

    padding:
        11px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.side-item:last-child {

    border-bottom:
        none;

}


.side-label {

    color:
        var(--muted);

    font-size:
        7px;

    text-transform:
        uppercase;

}


.side-value {

    margin-top:
        4px;

    font-size:
        8px;

    font-weight:
        700;

}


/* =========================================================
   DANGER
========================================================= */

.danger-card {

    margin-top:
        20px;

    border-color:
        #f1d4d7;

}


.danger-header {

    background:
        var(--red-bg);

    color:
        var(--red);

}


.danger-body {

    padding:
        18px 20px;

}


.danger-body p {

    color:
        var(--muted);

    font-size:
        8px;

    line-height:
        1.6;

}


.delete-link {

    display:
        inline-flex;

    margin-top:
        12px;

    padding:
        9px 12px;

    border-radius:
        6px;

    background:
        var(--red);

    color:
        white;

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:950px) {

    .layout {

        grid-template-columns:
            1fr;

    }

}


@media(max-width:800px) {

    .sidebar {

        width:
            65px;

    }

    .logo {

        padding:
            0;

        justify-content:
            center;

        font-size:
            0;

    }

    .logo::after {

        content:
            "RE";

        font-size:
            14px;

    }

    .menu-title {

        display:
            none;

    }

    .menu a {

        justify-content:
            center;

        padding:
            0;

    }

    .menu a span:not(.icon) {

        display:
            none;

    }

    .main {

        margin-left:
            65px;

    }

}


@media(max-width:600px) {

    .content {

        padding:
            20px 15px;

    }

    .topbar {

        padding:
            0 15px;

    }

    .form-grid {

        grid-template-columns:
            1fr;

    }

    .form-group.full {

        grid-column:
            auto;

    }

    .form-actions {

        flex-direction:
            column-reverse;

        align-items:
            stretch;

    }

    .btn {

        width:
            100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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
===================================================== -->

<div class="main">


<header class="topbar">


<div class="top-left">


<a
    href="user-details.php?id=<?php echo (int)$userId; ?>"
    class="back"
>
    ←
</a>


<div>

<h1>
    Edit User
</h1>

<p>
    Update account information and permissions
</p>

</div>


</div>


</header>


<main class="content">


<?php if (!empty($errors)): ?>

<div class="alert alert-error">

<strong>
    Please fix the following:
</strong>

<br>

<?php foreach ($errors as $error): ?>

• <?php echo safe($error); ?><br>

<?php endforeach; ?>

</div>

<?php endif; ?>


<?php if ($success !== ""): ?>

<div class="alert alert-success">

✅

<?php echo safe($success); ?>

</div>

<?php endif; ?>


<div class="layout">


<!-- =====================================================
     FORM
===================================================== -->

<section class="card">


<div class="card-header">

<h2>
    Account Information
</h2>

<p>
    Modify the user's profile and account permissions.
</p>

</div>


<div class="card-body">


<form
    method="POST"
    id="userForm"
>


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
    required
>

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
    placeholder="user@example.com"
    required
>

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
>

</div>


<!-- ROLE -->

<div class="form-group">

<label for="role">

Account Role
<span class="required">*</span>

</label>


<select
    id="role"
    name="role"
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
    👤 User
</option>


<option
    value="agent"
    <?php
    echo $role === "agent"
        ? "selected"
        : "";
    ?>
>
    🧑‍💼 Agent
</option>


<option
    value="admin"
    <?php
    echo $role === "admin"
        ? "selected"
        : "";
    ?>
>
    🛡️ Administrator
</option>


</select>


<div class="help">

Administrators have complete access to the admin panel.

</div>


</div>


<!-- STATUS -->

<div class="form-group">

<label for="status">

Account Status
<span class="required">*</span>

</label>


<select
    id="status"
    name="status"
    required
>


<option
    value="active"
    <?php
    echo $status === "active"
        ? "selected"
        : "";
    ?>
>
    🟢 Active
</option>


<option
    value="inactive"
    <?php
    echo $status === "inactive"
        ? "selected"
        : "";
    ?>
>
    🔴 Inactive
</option>


</select>


<div class="help">

Inactive accounts should not be able to access protected areas.

</div>


</div>


<!-- PASSWORD -->

<div class="form-group">

<label for="password">

New Password

</label>


<div class="password-wrapper">


<input
    type="password"
    id="password"
    name="password"
    placeholder="Leave blank to keep current password"
    autocomplete="new-password"
>


<button
    type="button"
    class="toggle-password"
    data-target="password"
>
    👁️
</button>


</div>


<div class="help">

Leave this field empty if you do not want to change the password.

</div>


</div>


<!-- CONFIRM PASSWORD -->

<div class="form-group">

<label for="confirm_password">

Confirm New Password

</label>


<div class="password-wrapper">


<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    placeholder="Repeat new password"
    autocomplete="new-password"
>


<button
    type="button"
    class="toggle-password"
    data-target="confirm_password"
>
    👁️
</button>


</div>


</div>


</div>


<!-- BUTTONS -->

<div class="form-actions">


<a
    href="user-details.php?id=<?php echo (int)$userId; ?>"
    class="btn btn-cancel"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-save"
>
    💾 Save Changes
</button>


</div>


</form>


</div>


</section>


<!-- =====================================================
     SIDEBAR INFO
===================================================== -->

<aside>


<section class="card">


<div class="profile">


<div class="avatar">

<?php

echo safe(
    strtoupper(
        substr(
            $name,
            0,
            1
        )
    )
);

?>

</div>


<h3>

<?php
echo safe($name);
?>

</h3>


<div class="profile-email">

<?php
echo safe($email);
?>

</div>


<?php

$roleClass =
    "role-user";

if ($role === "admin") {

    $roleClass =
        "role-admin";

}
elseif ($role === "agent") {

    $roleClass =
        "role-agent";

}

?>


<span class="badge <?php echo $roleClass; ?>">

<?php

if ($role === "admin") {

    echo "Administrator";

}
elseif ($role === "agent") {

    echo "Agent";

}
else {

    echo "User";

}

?>

</span>


</div>


<div class="side-info">


<div class="side-item">

<div class="side-label">
    User ID
</div>

<div class="side-value">

#<?php
echo (int)$userId;
?>

</div>

</div>


<div class="side-item">

<div class="side-label">
    Email
</div>

<div class="side-value">

<?php
echo safe($email);
?>

</div>

</div>


<div class="side-item">

<div class="side-label">
    Phone
</div>

<div class="side-value">

<?php

echo safe(
    $phone ?: "Not provided"
);

?>

</div>

</div>


<div class="side-item">

<div class="side-label">
    Status
</div>

<div class="side-value">

<?php

echo $status === "active"
    ? "🟢 Active"
    : "🔴 Inactive";

?>

</div>

</div>


<div class="side-item">

<div class="side-label">
    Account Created
</div>

<div class="side-value">

<?php

if (!empty($user["created_at"])) {

    echo safe(
        date(
            "d M Y",
            strtotime(
                $user["created_at"]
            )
        )
    );

}
else {

    echo "N/A";

}

?>

</div>

</div>


</div>


</section>


<!-- DANGER -->

<section class="card danger-card">


<div class="card-header danger-header">

<h2>
    Danger Zone
</h2>

</div>


<div class="danger-body">

<p>

Deleting this account is permanent. All account-related
data may become unavailable.

</p>


<?php if (
    (int)$userId !==
    (int)$_SESSION["user_id"]
): ?>

<a
    href="user-delete.php?id=<?php echo (int)$userId; ?>"
    class="delete-link"
    id="deleteAccount"
>
    🗑️ Delete Account
</a>

<?php else: ?>

<p style="margin-top:12px;">

You cannot delete your own administrator account.

</p>

<?php endif; ?>


</div>


</section>


</aside>


</div>


</main>


</div>


<script>

/* =========================================================
   PASSWORD SHOW / HIDE
========================================================= */

document
    .querySelectorAll(".toggle-password")
    .forEach(function(button) {

        button.addEventListener(
            "click",
            function() {

                const targetId =
                    button.dataset.target;

                const input =
                    document.getElementById(
                        targetId
                    );

                if (!input) {
                    return;
                }

                if (
                    input.type ===
                    "password"
                ) {

                    input.type =
                        "text";

                    button.textContent =
                        "🙈";

                }
                else {

                    input.type =
                        "password";

                    button.textContent =
                        "👁️";

                }

            }
        );

    });


/* =========================================================
   PASSWORD MATCH
========================================================= */

const password =
    document.getElementById(
        "password"
    );

const confirmPassword =
    document.getElementById(
        "confirm_password"
    );


function checkPasswordMatch()
{

    if (
        confirmPassword.value === ""
    ) {

        confirmPassword.style.borderColor =
            "";

        return;

    }

    if (
        password.value ===
        confirmPassword.value
    ) {

        confirmPassword.style.borderColor =
            "#17643b";

    }
    else {

        confirmPassword.style.borderColor =
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
   FORM SUBMIT
========================================================= */

document
    .getElementById("userForm")
    .addEventListener(
        "submit",
        function(event) {

            const pass =
                password.value;

            const confirm =
                confirmPassword.value;


            if (pass !== "") {

                if (pass.length < 8) {

                    alert(
                        "Password must contain at least 8 characters."
                    );

                    event.preventDefault();

                    return;

                }


                if (pass !== confirm) {

                    alert(
                        "Passwords do not match."
                    );

                    event.preventDefault();

                    return;

                }

            }

        }
    );


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteAccount =
    document.getElementById(
        "deleteAccount"
    );


if (deleteAccount) {

    deleteAccount.addEventListener(
        "click",
        function(event) {

            const confirmed =
                confirm(
                    "Delete this user account?\n\nThis action cannot be undone."
                );

            if (!confirmed) {

                event.preventDefault();

            }

        }
    );

}

</script>


</body>

</html>