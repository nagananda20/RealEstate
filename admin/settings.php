<?php

session_start();

require_once "../config/database.php";

/* =========================================================
   AUTHENTICATION
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
   CSRF
========================================================= */

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION["csrf_token"];


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminName =
    $_SESSION["user_name"]
    ?? "Administrator";

$adminEmail =
    $_SESSION["user_email"]
    ?? "";

$adminInitial =
    strtoupper(
        substr(
            $adminName,
            0,
            1
        )
    );


/* =========================================================
   DEFAULT SETTINGS
========================================================= */

$settings = [

    "site_name" =>
        "RealEstate",

    "site_email" =>
        "admin@realestate.com",

    "phone" =>
        "+91 98765 43210",

    "currency" =>
        "INR",

    "timezone" =>
        "Asia/Kolkata",

    "maintenance" =>
        0,

    "email_notifications" =>
        1,

    "new_property_notifications" =>
        1,

    "new_enquiry_notifications" =>
        1,

    "new_user_notifications" =>
        1

];


/* =========================================================
   LOAD SETTINGS TABLE
========================================================= */

$tableExists = false;

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'settings'"
    );

if (
    $tableCheck &&
    $tableCheck->num_rows > 0
) {

    $tableExists = true;

    $result =
        $conn->query(
            "SELECT setting_key, setting_value
             FROM settings"
        );

    if ($result) {

        while (
            $row =
            $result->fetch_assoc()
        ) {

            if (
                isset(
                    $row["setting_key"]
                )
            ) {

                $settings[
                    $row["setting_key"]
                ] =
                    $row["setting_value"];

            }

        }

    }

}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$messageType = "";


/* =========================================================
   SAVE SETTINGS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_settings"])
) {

    $token =
        $_POST["csrf_token"]
        ?? "";

    if (
        !hash_equals(
            $csrfToken,
            $token
        )
    ) {

        $message =
            "Invalid security token.";

        $messageType =
            "error";

    }
    else {

        $siteName =
            trim(
                $_POST["site_name"]
                ?? ""
            );

        $siteEmail =
            trim(
                $_POST["site_email"]
                ?? ""
            );

        $phone =
            trim(
                $_POST["phone"]
                ?? ""
            );

        $currency =
            trim(
                $_POST["currency"]
                ?? "INR"
            );

        $timezone =
            trim(
                $_POST["timezone"]
                ?? "Asia/Kolkata"
            );


        $maintenance =
            isset(
                $_POST["maintenance"]
            )
            ? 1
            : 0;


        $emailNotifications =
            isset(
                $_POST[
                    "email_notifications"
                ]
            )
            ? 1
            : 0;


        $newProperty =
            isset(
                $_POST[
                    "new_property_notifications"
                ]
            )
            ? 1
            : 0;


        $newEnquiry =
            isset(
                $_POST[
                    "new_enquiry_notifications"
                ]
            )
            ? 1
            : 0;


        $newUser =
            isset(
                $_POST[
                    "new_user_notifications"
                ]
            )
            ? 1
            : 0;


        $newSettings = [

            "site_name" =>
                $siteName,

            "site_email" =>
                $siteEmail,

            "phone" =>
                $phone,

            "currency" =>
                $currency,

            "timezone" =>
                $timezone,

            "maintenance" =>
                $maintenance,

            "email_notifications" =>
                $emailNotifications,

            "new_property_notifications" =>
                $newProperty,

            "new_enquiry_notifications" =>
                $newEnquiry,

            "new_user_notifications" =>
                $newUser

        ];


        /*
         * Create settings table if necessary
         */

        if (!$tableExists) {

            $createSQL = "
                CREATE TABLE settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NULL,
                    updated_at TIMESTAMP
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            if (
                $conn->query(
                    $createSQL
                )
            ) {

                $tableExists = true;

            }

        }


        /*
         * Save each setting
         */

        if ($tableExists) {

            $stmt =
                $conn->prepare(
                    "INSERT INTO settings
                    (
                        setting_key,
                        setting_value
                    )
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(
                        setting_value
                    )"
                );


            if ($stmt) {

                foreach (
                    $newSettings
                    as $key => $value
                ) {

                    $stmt->bind_param(
                        "ss",
                        $key,
                        $value
                    );

                    $stmt->execute();

                }

                $stmt->close();


                $settings =
                    array_merge(
                        $settings,
                        $newSettings
                    );


                $message =
                    "Settings saved successfully.";

                $messageType =
                    "success";

            }
            else {

                $message =
                    "Unable to save settings.";

                $messageType =
                    "error";

            }

        }

    }

}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $token =
        $_POST["csrf_token"]
        ?? "";

    if (
        !hash_equals(
            $csrfToken,
            $token
        )
    ) {

        $message =
            "Invalid security token.";

        $messageType =
            "error";

    }
    else {

        $currentPassword =
            $_POST["current_password"]
            ?? "";

        $newPassword =
            $_POST["new_password"]
            ?? "";

        $confirmPassword =
            $_POST["confirm_password"]
            ?? "";


        if (
            strlen($newPassword) < 8
        ) {

            $message =
                "New password must contain at least 8 characters.";

            $messageType =
                "error";

        }
        elseif (
            $newPassword !==
            $confirmPassword
        ) {

            $message =
                "New passwords do not match.";

            $messageType =
                "error";

        }
        elseif (
            !tableExists(
                $conn,
                "users"
            )
        ) {

            $message =
                "Users table was not found.";

            $messageType =
                "error";

        }
        else {

            $userId =
                (int)
                $_SESSION["user_id"];


            $stmt =
                $conn->prepare(
                    "SELECT password
                     FROM users
                     WHERE id = ?
                     LIMIT 1"
                );


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $userId
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $user =
                    $result->fetch_assoc();

                $stmt->close();


                if (
                    $user &&
                    password_verify(
                        $currentPassword,
                        $user["password"]
                    )
                ) {

                    $hashedPassword =
                        password_hash(
                            $newPassword,
                            PASSWORD_DEFAULT
                        );


                    $update =
                        $conn->prepare(
                            "UPDATE users
                             SET password = ?
                             WHERE id = ?"
                        );


                    if ($update) {

                        $update->bind_param(
                            "si",
                            $hashedPassword,
                            $userId
                        );

                        $update->execute();

                        $update->close();


                        $message =
                            "Password changed successfully.";

                        $messageType =
                            "success";

                    }

                }
                else {

                    $message =
                        "Current password is incorrect.";

                    $messageType =
                        "error";

                }

            }

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

<title>
    Settings | RealEstate Admin
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

    --success:#24734e;
    --success-bg:#eaf6ef;

    --danger:#b43843;
    --danger-bg:#fdebed;

}


/* =========================================================
   BODY
========================================================= */

body {

    min-height:100vh;

    background:
        var(--bg);

    color:
        var(--text);

    font-family:
        Arial,
        Helvetica,
        sans-serif;

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
    color:var(--accent);
}


.menu-title {

    padding:
        20px 25px 8px;

    color:
        rgba(255,255,255,.4);

    font-size:8px;

    letter-spacing:1.5px;

    text-transform:uppercase;

}


.menu {

    padding:
        0 12px;

}


.menu a {

    height:44px;

    display:flex;

    align-items:center;

    gap:12px;

    padding:
        0 13px;

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

    padding:
        0 30px;

    background:white;

    border-bottom:
        1px solid
        var(--border);

    position:sticky;

    top:0;

    z-index:50;

}


.topbar h1 {

    font-size:19px;

}


.topbar p {

    margin-top:4px;

    color:
        var(--muted);

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

    max-width:1100px;

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

    margin-top:6px;

    color:
        var(--muted);

    font-size:8px;

}


/* =========================================================
   MESSAGE
========================================================= */

.alert {

    padding:
        13px 16px;

    margin-bottom:18px;

    border-radius:8px;

    font-size:8px;

    font-weight:700;

}


.alert.success {

    background:
        var(--success-bg);

    color:
        var(--success);

    border:
        1px solid
        #c8e8d5;

}


.alert.error {

    background:
        var(--danger-bg);

    color:
        var(--danger);

    border:
        1px solid
        #f2c8cd;

}


/* =========================================================
   SETTINGS GRID
========================================================= */

.settings-grid {

    display:grid;

    grid-template-columns:
        210px 1fr;

    gap:18px;

}


/* =========================================================
   SETTINGS NAV
========================================================= */

.settings-nav {

    height:max-content;

    padding:8px;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    position:sticky;

    top:95px;

}


.settings-nav button {

    width:100%;

    height:42px;

    display:flex;

    align-items:center;

    gap:10px;

    padding:
        0 12px;

    border:none;

    background:transparent;

    color:
        var(--muted);

    border-radius:6px;

    cursor:pointer;

    text-align:left;

    font-size:8px;

    font-weight:700;

}


.settings-nav button:hover,
.settings-nav button.active {

    background:
        #edf3ef;

    color:
        var(--primary);

}


/* =========================================================
   PANEL
========================================================= */

.panel {

    display:none;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

}


.panel.active {

    display:block;

}


.panel-header {

    padding:
        20px;

    border-bottom:
        1px solid
        var(--border);

}


.panel-header h3 {

    font-size:12px;

}


.panel-header p {

    margin-top:5px;

    color:
        var(--muted);

    font-size:7px;

}


.panel-body {

    padding:20px;

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:16px;

}


.form-group {

    display:flex;

    flex-direction:column;

    gap:6px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


.form-group label {

    font-size:7px;

    font-weight:800;

    color:
        #59645f;

}


.form-group input,
.form-group select {

    width:100%;

    height:40px;

    padding:
        0 11px;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    outline:none;

    background:white;

    color:
        var(--text);

    font-size:8px;

}


.form-group input:focus,
.form-group select:focus {

    border-color:
        var(--primary);

}


.form-help {

    color:
        var(--muted);

    font-size:6px;

    line-height:1.5;

}


/* =========================================================
   SAVE
========================================================= */

.panel-footer {

    display:flex;

    justify-content:flex-end;

    padding:
        15px 20px;

    border-top:
        1px solid
        var(--border);

    background:
        #fafbfa;

}


.btn {

    height:40px;

    padding:
        0 17px;

    border:none;

    border-radius:6px;

    cursor:pointer;

    font-size:7px;

    font-weight:800;

}


.btn-primary {

    background:
        var(--primary);

    color:white;

}


.btn-primary:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   TOGGLE
========================================================= */

.toggle-row {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    padding:
        15px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.toggle-row:last-child {

    border-bottom:none;

}


.toggle-info h4 {

    font-size:8px;

}


.toggle-info p {

    margin-top:4px;

    color:
        var(--muted);

    font-size:6px;

}


.switch {

    position:relative;

    width:42px;
    height:23px;

    flex-shrink:0;

}


.switch input {

    opacity:0;

    width:0;
    height:0;

}


.slider {

    position:absolute;

    inset:0;

    background:#d7ddda;

    border-radius:20px;

    cursor:pointer;

    transition:.2s;

}


.slider:before {

    content:"";

    position:absolute;

    width:17px;
    height:17px;

    left:3px;
    top:3px;

    background:white;

    border-radius:50%;

    transition:.2s;

}


.switch input:checked + .slider {

    background:
        var(--primary);

}


.switch input:checked + .slider:before {

    transform:
        translateX(19px);

}


/* =========================================================
   SECURITY
========================================================= */

.security-box {

    padding:16px;

    background:
        #f7f9f8;

    border:
        1px solid
        var(--border);

    border-radius:8px;

    margin-bottom:18px;

}


.security-box strong {

    font-size:9px;

}


.security-box p {

    margin-top:5px;

    color:
        var(--muted);

    font-size:7px;

    line-height:1.5;

}


/* =========================================================
   DATABASE INFO
========================================================= */

.info-list {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:10px;

}


.info-item {

    padding:13px;

    background:
        #f7f9f8;

    border-radius:7px;

}


.info-item span {

    display:block;

    color:
        var(--muted);

    font-size:6px;

}


.info-item strong {

    display:block;

    margin-top:5px;

    font-size:8px;

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


    .settings-grid {

        grid-template-columns:1fr;

    }


    .settings-nav {

        position:static;

        display:flex;

        overflow-x:auto;

    }


    .settings-nav button {

        min-width:125px;

        justify-content:center;

    }

}


@media(max-width:600px) {

    .content {

        padding:
            20px 14px;

    }


    .form-grid {

        grid-template-columns:1fr;

    }


    .form-group.full {

        grid-column:auto;

    }


    .info-list {

        grid-template-columns:1fr;

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


<a href="users.php">

    <span class="icon">👥</span>
    <span>Users</span>

</a>


<a href="agents.php">

    <span class="icon">🧑‍💼</span>
    <span>Agents</span>

</a>


<a href="messages.php">

    <span class="icon">💬</span>
    <span>Messages</span>

</a>


<a href="enquiries.php">

    <span class="icon">📩</span>
    <span>Enquiries</span>

</a>


<a href="notifications.php">

    <span class="icon">🔔</span>
    <span>Notifications</span>

</a>


<a href="reports.php">

    <span class="icon">📈</span>
    <span>Reports</span>

</a>


<a
    href="settings.php"
    class="active"
>

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


<div>

<h1>
    Settings
</h1>

<p>
    Configure your RealEstate platform
</p>

</div>


<div class="admin">


<div class="admin-avatar">

<?php
echo safe(
    $adminInitial
);
?>

</div>


<div class="admin-name">

<?php
echo safe(
    $adminName
);
?>

</div>


</div>


</header>


<main class="content">


<div class="page-header">

<h2>
    System Settings
</h2>

<p>
    Manage website configuration, notifications and security.
</p>

</div>


<?php if ($message !== ""): ?>


<div class="alert <?php
    echo $messageType === "success"
        ? "success"
        : "error";
?>">

<?php
echo safe($message);
?>

</div>


<?php endif; ?>


<div class="settings-grid">


<!-- =====================================================
     SETTINGS NAVIGATION
========================================================= -->

<div class="settings-nav">


<button
    type="button"
    class="tab-button active"
    data-tab="general"
>
    ⚙️ General
</button>


<button
    type="button"
    class="tab-button"
    data-tab="notifications"
>
    🔔 Notifications
</button>


<button
    type="button"
    class="tab-button"
    data-tab="security"
>
    🔐 Security
</button>


<button
    type="button"
    class="tab-button"
    data-tab="database"
>
    🗄️ Database
</button>


</div>


<!-- =====================================================
     GENERAL
========================================================= -->

<section
    class="panel active"
    id="general"
>


<div class="panel-header">

<h3>
    General Settings
</h3>

<p>
    Configure the basic information of your platform.
</p>

</div>


<form
    method="POST"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo safe($csrfToken);
    ?>"
>


<input
    type="hidden"
    name="save_settings"
    value="1"
>


<div class="panel-body">


<div class="form-grid">


<div class="form-group">

<label>
    WEBSITE NAME
</label>

<input
    type="text"
    name="site_name"
    value="<?php
        echo safe(
            $settings["site_name"]
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    WEBSITE EMAIL
</label>

<input
    type="email"
    name="site_email"
    value="<?php
        echo safe(
            $settings["site_email"]
        );
    ?>"
>

</div>


<div class="form-group">

<label>
    PHONE NUMBER
</label>

<input
    type="text"
    name="phone"
    value="<?php
        echo safe(
            $settings["phone"]
        );
    ?>"
>

</div>


<div class="form-group">

<label>
    CURRENCY
</label>

<select
    name="currency"
>

<option
    value="INR"
    <?php
    echo $settings["currency"] === "INR"
        ? "selected"
        : "";
    ?>
>
    INR - Indian Rupee
</option>

<option
    value="USD"
    <?php
    echo $settings["currency"] === "USD"
        ? "selected"
        : "";
    ?>
>
    USD - US Dollar
</option>

<option
    value="EUR"
    <?php
    echo $settings["currency"] === "EUR"
        ? "selected"
        : "";
    ?>
>
    EUR - Euro
</option>

<option
    value="GBP"
    <?php
    echo $settings["currency"] === "GBP"
        ? "selected"
        : "";
    ?>
>
    GBP - Pound
</option>

</select>

</div>


<div class="form-group full">

<label>
    TIMEZONE
</label>

<select
    name="timezone"
>

<option
    value="Asia/Kolkata"
    <?php
    echo $settings["timezone"] === "Asia/Kolkata"
        ? "selected"
        : "";
    ?>
>
    Asia/Kolkata
</option>

<option
    value="UTC"
    <?php
    echo $settings["timezone"] === "UTC"
        ? "selected"
        : "";
    ?>
>
    UTC
</option>

<option
    value="Asia/Dubai"
    <?php
    echo $settings["timezone"] === "Asia/Dubai"
        ? "selected"
        : "";
    ?>
>
    Asia/Dubai
</option>

<option
    value="Europe/London"
    <?php
    echo $settings["timezone"] === "Europe/London"
        ? "selected"
        : "";
    ?>
>
    Europe/London
</option>

</select>

</div>


</div>


<div
    class="toggle-row"
    style="margin-top:20px;"
>


<div class="toggle-info">

<h4>
    Maintenance Mode
</h4>

<p>
    Temporarily disable public access while performing maintenance.
</p>

</div>


<label class="switch">

<input
    type="checkbox"
    name="maintenance"
    <?php
    echo !empty(
        $settings["maintenance"]
    )
        ? "checked"
        : "";
    ?>
>

<span class="slider"></span>

</label>


</div>


</div>


<div class="panel-footer">

<button
    type="submit"
    class="btn btn-primary"
>
    Save General Settings
</button>

</div>


</form>


</section>


<!-- =====================================================
     NOTIFICATIONS
========================================================= -->

<section
    class="panel"
    id="notifications"
>


<div class="panel-header">

<h3>
    Notification Settings
</h3>

<p>
    Control which activities generate administrator notifications.
</p>

</div>


<form
    method="POST"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo safe($csrfToken);
    ?>"
>


<input
    type="hidden"
    name="save_settings"
    value="1"
>


<div class="panel-body">


<div class="toggle-row">

<div class="toggle-info">

<h4>
    Email Notifications
</h4>

<p>
    Receive important platform notifications by email.
</p>

</div>


<label class="switch">

<input
    type="checkbox"
    name="email_notifications"
    <?php
    echo !empty(
        $settings[
            "email_notifications"
        ]
    )
        ? "checked"
        : "";
    ?>
>

<span class="slider"></span>

</label>

</div>


<div class="toggle-row">

<div class="toggle-info">

<h4>
    New Property
</h4>

<p>
    Notify administrators when a new property is submitted.
</p>

</div>


<label class="switch">

<input
    type="checkbox"
    name="new_property_notifications"
    <?php
    echo !empty(
        $settings[
            "new_property_notifications"
        ]
    )
        ? "checked"
        : "";
    ?>
>

<span class="slider"></span>

</label>

</div>


<div class="toggle-row">

<div class="toggle-info">

<h4>
    New Enquiry
</h4>

<p>
    Notify administrators when a customer sends an enquiry.
</p>

</div>


<label class="switch">

<input
    type="checkbox"
    name="new_enquiry_notifications"
    <?php
    echo !empty(
        $settings[
            "new_enquiry_notifications"
        ]
    )
        ? "checked"
        : "";
    ?>
>

<span class="slider"></span>

</label>

</div>


<div class="toggle-row">

<div class="toggle-info">

<h4>
    New User
</h4>

<p>
    Notify administrators whenever a new user registers.
</p>

</div>


<label class="switch">

<input
    type="checkbox"
    name="new_user_notifications"
    <?php
    echo !empty(
        $settings[
            "new_user_notifications"
        ]
    )
        ? "checked"
        : "";
    ?>
>

<span class="slider"></span>

</label>

</div>


</div>


<div class="panel-footer">

<button
    type="submit"
    class="btn btn-primary"
>
    Save Notification Settings
</button>

</div>


</form>


</section>


<!-- =====================================================
     SECURITY
========================================================= -->

<section
    class="panel"
    id="security"
>


<div class="panel-header">

<h3>
    Security Settings
</h3>

<p>
    Protect your administrator account.
</p>

</div>


<form
    method="POST"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo safe($csrfToken);
    ?>"
>


<input
    type="hidden"
    name="change_password"
    value="1"
>


<div class="panel-body">


<div class="security-box">

<strong>
    🔐 Change Administrator Password
</strong>

<p>
    Use a strong password containing at least 8 characters.
</p>

</div>


<div class="form-grid">


<div class="form-group full">

<label>
    CURRENT PASSWORD
</label>

<input
    type="password"
    name="current_password"
    required
>

</div>


<div class="form-group">

<label>
    NEW PASSWORD
</label>

<input
    type="password"
    name="new_password"
    minlength="8"
    required
>

</div>


<div class="form-group">

<label>
    CONFIRM NEW PASSWORD
</label>

<input
    type="password"
    name="confirm_password"
    minlength="8"
    required
>

</div>


</div>


</div>


<div class="panel-footer">

<button
    type="submit"
    class="btn btn-primary"
>
    Change Password
</button>

</div>


</form>


</section>


<!-- =====================================================
     DATABASE
========================================================= -->

<section
    class="panel"
    id="database"
>


<div class="panel-header">

<h3>
    Database Information
</h3>

<p>
    Current RealEstate system environment.
</p>

</div>


<div class="panel-body">


<div class="info-list">


<div class="info-item">

<span>
    DATABASE
</span>

<strong>

<?php

echo safe(
    $conn->get_server_info()
);

?>

</strong>

</div>


<div class="info-item">

<span>
    DATABASE NAME
</span>

<strong>

<?php

echo safe(
    $conn->query(
        "SELECT DATABASE()"
    )->fetch_row()[0]
    ?? "Unknown"
);

?>

</strong>

</div>


<div class="info-item">

<span>
    PHP VERSION
</span>

<strong>

<?php
echo safe(
    PHP_VERSION
);
?>

</strong>

</div>


<div class="info-item">

<span>
    SERVER
</span>

<strong>

<?php
echo safe(
    $_SERVER["SERVER_SOFTWARE"]
    ?? "Unknown"
);
?>

</strong>

</div>


<div class="info-item">

<span>
    ENVIRONMENT
</span>

<strong>
    XAMPP / Localhost
</strong>

</div>


<div class="info-item">

<span>
    PLATFORM
</span>

<strong>
    RealEstate Admin
</strong>

</div>


</div>


</div>


</section>


</div>


</main>


</div>


<script>

/* =========================================================
   SETTINGS TABS
========================================================= */

const buttons =
    document.querySelectorAll(
        ".tab-button"
    );


const panels =
    document.querySelectorAll(
        ".panel"
    );


buttons.forEach(
    button => {

        button.addEventListener(
            "click",
            () => {

                const tab =
                    button.dataset.tab;


                buttons.forEach(
                    item => {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                panels.forEach(
                    panel => {

                        panel.classList.remove(
                            "active"
                        );

                    }
                );


                button.classList.add(
                    "active"
                );


                const target =
                    document.getElementById(
                        tab
                    );


                if (target) {

                    target.classList.add(
                        "active"
                    );

                }

            }
        );

    }
);


/* =========================================================
   PASSWORD CONFIRMATION
========================================================= */

const passwordForm =
    document.querySelector(
        'input[name="change_password"]'
    )?.closest("form");


if (passwordForm) {

    passwordForm.addEventListener(
        "submit",
        function(event) {

            const newPassword =
                document.querySelector(
                    'input[name="new_password"]'
                );

            const confirmPassword =
                document.querySelector(
                    'input[name="confirm_password"]'
                );


            if (
                newPassword.value !==
                confirmPassword.value
            ) {

                event.preventDefault();

                alert(
                    "New passwords do not match."
                );

            }

        }
    );

}


/* =========================================================
   MAINTENANCE CONFIRMATION
========================================================= */

const maintenance =
    document.querySelector(
        'input[name="maintenance"]'
    );


if (maintenance) {

    maintenance.addEventListener(
        "change",
        function() {

            if (
                this.checked
            ) {

                const confirmMode =
                    confirm(
                        "Enable maintenance mode? Public users may not be able to access the website."
                    );


                if (!confirmMode) {

                    this.checked =
                        false;

                }

            }

        }
    );

}

</script>


</body>

</html>