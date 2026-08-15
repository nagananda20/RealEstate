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
   AGENT ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {

    $_SESSION["error"] =
        "Invalid agent ID.";

    header("Location: agents.php");
    exit;
}


/* =========================================================
   GET AGENT
========================================================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM agents
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    exit("Database error.");
}

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$agent = $result->fetch_assoc();

$stmt->close();


if (!$agent) {

    $_SESSION["error"] =
        "Agent not found.";

    header("Location: agents.php");
    exit;
}


/* =========================================================
   AGENT DATA
========================================================= */

$name =
    $agent["name"]
    ?? "Unknown Agent";

$email =
    $agent["email"]
    ?? "";

$phone =
    $agent["phone"]
    ?? "";

$experience =
    $agent["experience"]
    ?? "";

$specialization =
    $agent["specialization"]
    ?? "";

$licenseNumber =
    $agent["license_number"]
    ?? "";

$bio =
    $agent["bio"]
    ?? "";

$status =
    strtolower(
        $agent["status"]
        ?? "active"
    );

$profileImage =
    $agent["profile_image"]
    ?? "";

$createdAt =
    $agent["created_at"]
    ?? "";

$updatedAt =
    $agent["updated_at"]
    ?? "";


/* =========================================================
   STATUS CLASS
========================================================= */

$statusClass =
    $status === "active"
    ? "active"
    : "inactive";


/* =========================================================
   PROFILE IMAGE
========================================================= */

$imagePath = "";

if ($profileImage !== "") {

    $imagePath =
        "../uploads/agents/"
        . basename($profileImage);

}


/* =========================================================
   ADMIN
========================================================= */

$adminName =
    $_SESSION["user_name"]
    ?? "Administrator";

$adminInitial =
    strtoupper(
        substr(
            $adminName,
            0,
            1
        )
    );


/* =========================================================
   SESSION MESSAGES
========================================================= */

$success =
    $_SESSION["success"]
    ?? "";

$error =
    $_SESSION["error"]
    ?? "";

unset($_SESSION["success"]);
unset($_SESSION["error"]);

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
    Agent Details | RealEstate
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

    --green:#24734e;
    --green-bg:#eaf6ef;

    --red:#b43843;
    --red-bg:#fdebed;

    --blue:#35699a;
    --blue-bg:#edf4fb;

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

    padding:
        0 25px;

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


.back:hover {

    background:#e1e6e3;

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

    max-width:1200px;

    margin:auto;

    padding:30px;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding:
        13px 16px;

    border-radius:7px;

    margin-bottom:18px;

    font-size:8px;

}


.alert-success {

    background:
        var(--green-bg);

    color:
        var(--green);

}


.alert-error {

    background:
        var(--red-bg);

    color:
        var(--red);

}


/* =========================================================
   HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-bottom:22px;

}


.page-title h2 {

    font-size:23px;

}


.page-title p {

    margin-top:6px;

    color:var(--muted);

    font-size:8px;

}


.actions {

    display:flex;

    gap:8px;

}


.btn {

    height:40px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    padding:
        0 15px;

    border-radius:6px;

    border:none;

    cursor:pointer;

    text-decoration:none;

    font-size:8px;

    font-weight:700;

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


.btn-light {

    background:#e9eeeb;

    color:var(--text);

}


.btn-danger {

    background:
        var(--red-bg);

    color:
        var(--red);

}


/* =========================================================
   GRID
========================================================= */

.grid {

    display:grid;

    grid-template-columns:
        1.5fr 1fr;

    gap:20px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

    margin-bottom:20px;

}


.card:last-child {

    margin-bottom:0;

}


.card-header {

    padding:
        17px 20px;

    border-bottom:
        1px solid
        #edf0ee;

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.card-title {

    font-size:10px;

    font-weight:800;

}


.card-body {

    padding:20px;

}


/* =========================================================
   PROFILE
========================================================= */

.profile {

    display:flex;

    align-items:center;

    gap:18px;

}


.profile-image {

    width:95px;
    height:95px;

    border-radius:50%;

    overflow:hidden;

    display:flex;

    align-items:center;
    justify-content:center;

    background:
        #e7efeb;

    color:
        var(--primary);

    font-size:32px;

    font-weight:800;

    flex-shrink:0;

}


.profile-image img {

    width:100%;
    height:100%;

    object-fit:cover;

}


.profile-name {

    font-size:20px;

    font-weight:800;

}


.profile-specialization {

    margin-top:6px;

    color:var(--muted);

    font-size:9px;

}


.status {

    display:inline-flex;

    align-items:center;

    gap:6px;

    margin-top:10px;

    padding:
        6px 10px;

    border-radius:20px;

    font-size:7px;

    font-weight:700;

}


.status::before {

    content:"";

    width:6px;
    height:6px;

    border-radius:50%;

    background:currentColor;

}


.status.active {

    color:
        var(--green);

    background:
        var(--green-bg);

}


.status.inactive {

    color:
        var(--red);

    background:
        var(--red-bg);

}


/* =========================================================
   INFORMATION GRID
========================================================= */

.info-grid {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:15px;

}


.info-item {

    padding:14px;

    background:
        #f7f9f8;

    border-radius:7px;

}


.info-label {

    color:var(--muted);

    font-size:7px;

    text-transform:uppercase;

    letter-spacing:.5px;

    margin-bottom:6px;

}


.info-value {

    font-size:9px;

    font-weight:700;

    word-break:break-word;

}


.info-value a {

    color:
        var(--primary);

    text-decoration:none;

}


/* =========================================================
   BIO
========================================================= */

.bio {

    font-size:9px;

    line-height:1.8;

    color:#45514c;

    white-space:pre-wrap;

}


/* =========================================================
   STAT CARDS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:12px;

}


.stat {

    padding:17px;

    border:
        1px solid
        var(--border);

    border-radius:8px;

}


.stat-icon {

    font-size:20px;

    margin-bottom:10px;

}


.stat-value {

    font-size:20px;

    font-weight:800;

    color:
        var(--primary);

}


.stat-label {

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   TIMELINE
========================================================= */

.timeline {

    position:relative;

    padding-left:22px;

}


.timeline::before {

    content:"";

    position:absolute;

    left:5px;

    top:5px;

    bottom:5px;

    width:1px;

    background:
        var(--border);

}


.timeline-item {

    position:relative;

    margin-bottom:20px;

}


.timeline-item:last-child {

    margin-bottom:0;

}


.timeline-dot {

    position:absolute;

    left:-21px;

    top:3px;

    width:11px;
    height:11px;

    border-radius:50%;

    background:
        var(--primary);

    border:
        2px solid white;

    box-shadow:
        0 0 0 1px
        var(--primary);

}


.timeline-title {

    font-size:8px;

    font-weight:800;

}


.timeline-date {

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   CONTACT
========================================================= */

.contact-list {

    display:flex;

    flex-direction:column;

    gap:10px;

}


.contact-item {

    display:flex;

    align-items:center;

    gap:10px;

    padding:12px;

    background:#f7f9f8;

    border-radius:7px;

}


.contact-icon {

    width:32px;
    height:32px;

    display:flex;

    align-items:center;
    justify-content:center;

    background:#e7efeb;

    border-radius:6px;

}


.contact-text {

    min-width:0;

}


.contact-label {

    color:var(--muted);

    font-size:7px;

}


.contact-value {

    margin-top:3px;

    font-size:8px;

    font-weight:700;

    word-break:break-word;

}


.contact-value a {

    color:
        var(--primary);

    text-decoration:none;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px) {

    .grid {

        grid-template-columns:1fr;

    }

}


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


@media(max-width:600px) {

    .content {

        padding:
            20px 15px;

    }


    .page-header {

        flex-direction:column;

        align-items:flex-start;

    }


    .actions {

        width:100%;

    }


    .actions .btn {

        flex:1;

    }


    .info-grid {

        grid-template-columns:1fr;

    }


    .profile {

        align-items:flex-start;

    }


    .profile-image {

        width:75px;
        height:75px;

    }


    .profile-name {

        font-size:16px;

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


<a
    href="agents.php"
    class="active"
>

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
    href="agents.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Agent Details
</h1>

<p>
    Agent profile #<?php echo (int)$id; ?>
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


<main class="content">


<!-- =====================================================
     ALERTS
========================================================= -->

<?php if ($success): ?>

<div class="alert alert-success">

✓
<?php echo safe($success); ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-error">

⚠
<?php echo safe($error); ?>

</div>

<?php endif; ?>


<!-- =====================================================
     PAGE HEADER
========================================================= -->

<div class="page-header">


<div class="page-title">

<h2>
    <?php echo safe($name); ?>
</h2>

<p>
    Agent ID #<?php echo (int)$id; ?>
</p>

</div>


<div class="actions">


<a
    href="agent-edit.php?id=<?php echo (int)$id; ?>"
    class="btn btn-primary"
>
    ✏ Edit Agent
</a>


<a
    href="agent-delete.php?id=<?php echo (int)$id; ?>"
    class="btn btn-danger"
    onclick="
        return confirm(
            'Are you sure you want to delete this agent?'
        );
    "
>
    🗑 Delete
</a>


</div>


</div>


<!-- =====================================================
     GRID
========================================================= -->

<div class="grid">


<!-- =====================================================
     LEFT
========================================================= -->

<div>


<!-- PROFILE -->

<section class="card">


<div class="card-body">


<div class="profile">


<div class="profile-image">


<?php if ($imagePath): ?>

<img
    src="<?php echo safe($imagePath); ?>"
    alt="<?php echo safe($name); ?>"
>


<?php else: ?>

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

<?php endif; ?>


</div>


<div>


<div class="profile-name">

<?php
echo safe($name);
?>

</div>


<div class="profile-specialization">

<?php

echo $specialization
    ? safe($specialization)
    : "Real Estate Agent";

?>

</div>


<span
    class="status <?php echo safe($statusClass); ?>"
>

<?php

echo $status === "active"
    ? "Active Agent"
    : "Inactive Agent";

?>

</span>


</div>


</div>


</div>


</section>


<!-- PROFESSIONAL INFORMATION -->

<section class="card">


<div class="card-header">

<div class="card-title">

Professional Information

</div>

</div>


<div class="card-body">


<div class="info-grid">


<div class="info-item">

<div class="info-label">
    Experience
</div>


<div class="info-value">

<?php

if (
    $experience !== ""
    &&
    is_numeric($experience)
) {

    echo safe($experience)
        . " years";

}
else {

    echo "Not specified";

}

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Specialization
</div>


<div class="info-value">

<?php

echo $specialization
    ? safe($specialization)
    : "Not specified";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    License Number
</div>


<div class="info-value">

<?php

echo $licenseNumber
    ? safe($licenseNumber)
    : "Not specified";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Status
</div>


<div class="info-value">

<?php

echo ucfirst(
    safe($status)
);

?>

</div>

</div>


</div>


</div>


</section>


<!-- BIO -->

<section class="card">


<div class="card-header">

<div class="card-title">

Professional Biography

</div>

</div>


<div class="card-body">


<div class="bio">

<?php

echo $bio
    ? safe($bio)
    : "No biography has been added for this agent.";

?>

</div>


</div>


</section>


</div>


<!-- =====================================================
     RIGHT
========================================================= -->

<div>


<!-- CONTACT -->

<section class="card">


<div class="card-header">

<div class="card-title">

Contact Information

</div>

</div>


<div class="card-body">


<div class="contact-list">


<div class="contact-item">


<div class="contact-icon">
    📧
</div>


<div class="contact-text">

<div class="contact-label">
    Email
</div>


<div class="contact-value">

<a
    href="mailto:<?php echo safe($email); ?>"
>

<?php
echo safe($email);
?>

</a>

</div>

</div>


</div>


<div class="contact-item">


<div class="contact-icon">
    📞
</div>


<div class="contact-text">

<div class="contact-label">
    Phone
</div>


<div class="contact-value">

<?php if ($phone): ?>

<a
    href="tel:<?php echo safe($phone); ?>"
>

<?php
echo safe($phone);
?>

</a>

<?php else: ?>

Not provided

<?php endif; ?>

</div>

</div>


</div>


</div>


</div>


</section>


<!-- STATS -->

<section class="card">


<div class="card-header">

<div class="card-title">

Agent Overview

</div>

</div>


<div class="card-body">


<div class="stats">


<div class="stat">

<div class="stat-icon">
    🏠
</div>

<div class="stat-value">
    —
</div>

<div class="stat-label">
    Properties
</div>

</div>


<div class="stat">

<div class="stat-icon">
    💬
</div>

<div class="stat-value">
    —
</div>

<div class="stat-label">
    Enquiries
</div>

</div>


<div class="stat">

<div class="stat-icon">
    📅
</div>

<div class="stat-value">
    —
</div>

<div class="stat-label">
    Visits
</div>

</div>


<div class="stat">

<div class="stat-icon">
    ⭐
</div>

<div class="stat-value">
    —
</div>

<div class="stat-label">
    Rating
</div>

</div>


</div>


</div>


</section>


<!-- TIMELINE -->

<section class="card">


<div class="card-header">

<div class="card-title">

Account Timeline

</div>

</div>


<div class="card-body">


<div class="timeline">


<?php if ($createdAt): ?>

<div class="timeline-item">

<div class="timeline-dot"></div>

<div class="timeline-title">
    Agent Account Created
</div>

<div class="timeline-date">

<?php
echo safe($createdAt);
?>

</div>

</div>

<?php endif; ?>


<?php if ($updatedAt): ?>

<div class="timeline-item">

<div class="timeline-dot"></div>

<div class="timeline-title">
    Profile Updated
</div>

<div class="timeline-date">

<?php
echo safe($updatedAt);
?>

</div>

</div>

<?php endif; ?>


<div class="timeline-item">

<div class="timeline-dot"></div>

<div class="timeline-title">

Current Status:
<?php
echo ucfirst(
    safe($status)
);
?>

</div>

</div>


</div>


</div>


</section>


</div>


</div>


</main>


</div>


</body>

</html>