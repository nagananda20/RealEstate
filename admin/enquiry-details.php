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
   GET ENQUIRY ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: enquiries.php");
    exit;
}


/* =========================================================
   CHECK ENQUIRIES TABLE
========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'enquiries'"
);

if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {
    exit("Enquiries table not found.");
}


/* =========================================================
   GET COLUMNS
========================================================= */

$columns = [];

$columnResult =
    $conn->query(
        "SHOW COLUMNS FROM enquiries"
    );

while (
    $column = $columnResult->fetch_assoc()
) {
    $columns[] = $column["Field"];
}


/* =========================================================
   OPTIONAL COLUMNS
========================================================= */

$hasPropertyId =
    in_array(
        "property_id",
        $columns,
        true
    );

$hasAgentId =
    in_array(
        "agent_id",
        $columns,
        true
    );

$hasUserId =
    in_array(
        "user_id",
        $columns,
        true
    );


/* =========================================================
   BUILD QUERY
========================================================= */

$sql = "
    SELECT e.*
";


if ($hasPropertyId) {

    $sql .= ",
        p.title AS property_title,
        p.location AS property_location,
        p.price AS property_price,
        p.type AS property_type,
        p.status AS property_status
    ";

}


if ($hasAgentId) {

    $sql .= ",
        a.name AS agent_name,
        a.email AS agent_email,
        a.phone AS agent_phone
    ";

}


if ($hasUserId) {

    $sql .= ",
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone
    ";

}


$sql .= "
    FROM enquiries e
";


if ($hasPropertyId) {

    $sql .= "
        LEFT JOIN properties p
            ON p.id = e.property_id
    ";

}


if ($hasAgentId) {

    $sql .= "
        LEFT JOIN users a
            ON a.id = e.agent_id
    ";

}


if ($hasUserId) {

    $sql .= "
        LEFT JOIN users u
            ON u.id = e.user_id
    ";

}


$sql .= "
    WHERE e.id = ?
    LIMIT 1
";


$stmt =
    $conn->prepare($sql);

if (!$stmt) {
    exit("Database query error.");
}

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result =
    $stmt->get_result();

$enquiry =
    $result->fetch_assoc();

$stmt->close();


/* =========================================================
   ENQUIRY NOT FOUND
========================================================= */

if (!$enquiry) {

    exit(
        "Enquiry not found."
    );

}


/* =========================================================
   CUSTOMER
========================================================= */

$customerName =
    $enquiry["name"]
    ?? $enquiry["user_name"]
    ?? "Unknown Customer";


$customerEmail =
    $enquiry["email"]
    ?? $enquiry["user_email"]
    ?? "Not provided";


$customerPhone =
    $enquiry["phone"]
    ?? $enquiry["user_phone"]
    ?? "Not provided";


$customerInitial =
    strtoupper(
        substr(
            $customerName,
            0,
            1
        )
    );


/* =========================================================
   STATUS
========================================================= */

$status =
    strtolower(
        $enquiry["status"]
        ?? "new"
    );


$allowedStatuses = [
    "new",
    "contacted",
    "qualified",
    "closed"
];

if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {
    $status = "new";
}


/* =========================================================
   PRIORITY
========================================================= */

$priority =
    strtolower(
        $enquiry["priority"]
        ?? "medium"
    );


$allowedPriorities = [
    "low",
    "medium",
    "high"
];

if (
    !in_array(
        $priority,
        $allowedPriorities,
        true
    )
) {
    $priority = "medium";
}


/* =========================================================
   MESSAGE
========================================================= */

$message =
    trim(
        $enquiry["message"]
        ?? ""
    );

if ($message === "") {
    $message =
        "No message was provided.";
}


/* =========================================================
   PROPERTY
========================================================= */

$propertyTitle =
    $enquiry["property_title"]
    ?? "General Enquiry";


$propertyLocation =
    $enquiry["property_location"]
    ?? "Location unavailable";


$propertyPrice =
    $enquiry["property_price"]
    ?? "";


$propertyType =
    $enquiry["property_type"]
    ?? "Property";


$propertyStatus =
    $enquiry["property_status"]
    ?? "";


/* =========================================================
   AGENT
========================================================= */

$agentName =
    $enquiry["agent_name"]
    ?? "Unassigned";


$agentEmail =
    $enquiry["agent_email"]
    ?? "";


$agentPhone =
    $enquiry["agent_phone"]
    ?? "";


/* =========================================================
   DATE
========================================================= */

$createdDate =
    "N/A";


$createdTime =
    "";


if (
    !empty(
        $enquiry["created_at"]
    )
) {

    $timestamp =
        strtotime(
            $enquiry["created_at"]
        );

    $createdDate =
        date(
            "d M Y",
            $timestamp
        );

    $createdTime =
        date(
            "h:i A",
            $timestamp
        );
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
    Enquiry Details | RealEstate
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


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --primary:#174a3a;
    --primary-dark:#10372b;

    --accent:#d7a94b;

    --bg:#f4f6f5;
    --white:#ffffff;

    --text:#18231f;
    --muted:#737c78;

    --border:#dfe6e2;

    --green:#17643b;
    --green-bg:#e8f6ed;

    --red:#b43843;
    --red-bg:#fdebed;

    --orange:#a76210;
    --orange-bg:#fff4df;

    --blue:#365caa;
    --blue-bg:#edf3ff;

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

    position:sticky;

    top:0;

    z-index:20;

}


.topbar-left {

    display:flex;

    align-items:center;

    gap:15px;

}


.back-btn {

    width:36px;

    height:36px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#eef1ef;

    color:var(--text);

    text-decoration:none;

    border-radius:6px;

}


.topbar h1 {
    font-size:18px;
}


.topbar p {

    margin-top:4px;

    color:var(--muted);

    font-size:8px;

}


.admin-user {

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

    background:var(--primary);

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

    padding:
        28px 30px 60px;

    max-width:1450px;

    margin:auto;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    margin-bottom:20px;

}


.page-header h2 {

    font-size:22px;

}


.page-header p {

    margin-top:6px;

    color:var(--muted);

    font-size:8px;

}


.header-actions {

    display:flex;

    gap:7px;

}


.btn {

    height:38px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    padding:0 15px;

    border-radius:6px;

    text-decoration:none;

    border:none;

    cursor:pointer;

    font-size:8px;

    font-weight:700;

}


.btn-primary {

    background:
        var(--primary);

    color:white;

}


.btn-light {

    background:
        white;

    border:
        1px solid
        var(--border);

    color:var(--text);

}


/* =========================================================
   GRID
========================================================= */

.layout {

    display:grid;

    grid-template-columns:
        minmax(0,2fr)
        minmax(280px,1fr);

    gap:18px;

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

    margin-bottom:18px;

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

    font-size:11px;

    font-weight:800;

}


.card-body {

    padding:20px;

}


/* =========================================================
   STATUS
========================================================= */

.badges {

    display:flex;

    gap:7px;

    flex-wrap:wrap;

}


.badge {

    display:inline-flex;

    align-items:center;

    padding:
        6px 10px;

    border-radius:20px;

    font-size:6px;

    font-weight:700;

}


.badge.new {

    background:var(--blue-bg);

    color:var(--blue);

}


.badge.contacted {

    background:var(--orange-bg);

    color:var(--orange);

}


.badge.qualified {

    background:var(--green-bg);

    color:var(--green);

}


.badge.closed {

    background:var(--red-bg);

    color:var(--red);

}


.badge.low {

    background:var(--green-bg);

    color:var(--green);

}


.badge.medium {

    background:var(--orange-bg);

    color:var(--orange);

}


.badge.high {

    background:var(--red-bg);

    color:var(--red);

}


/* =========================================================
   CUSTOMER
========================================================= */

.customer-box {

    display:flex;

    align-items:center;

    gap:14px;

}


.customer-avatar {

    width:65px;

    height:65px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:var(--primary);

    color:white;

    font-size:22px;

    font-weight:800;

}


.customer-name {

    font-size:16px;

    font-weight:800;

}


.customer-contact {

    margin-top:7px;

    display:flex;

    flex-wrap:wrap;

    gap:8px;

}


.contact-link {

    padding:
        6px 9px;

    background:#f2f4f3;

    color:var(--text);

    text-decoration:none;

    border-radius:5px;

    font-size:7px;

}


/* =========================================================
   INFO GRID
========================================================= */

.info-grid {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:12px;

}


.info-item {

    padding:13px;

    background:#fafbfa;

    border:
        1px solid
        #edf0ee;

    border-radius:7px;

}


.info-label {

    color:var(--muted);

    font-size:7px;

    margin-bottom:5px;

}


.info-value {

    font-size:9px;

    font-weight:700;

    word-break:break-word;

}


/* =========================================================
   MESSAGE
========================================================= */

.message-box {

    padding:16px;

    background:#fafbfa;

    border-left:
        3px solid
        var(--primary);

    border-radius:5px;

    color:#4d5752;

    font-size:9px;

    line-height:1.8;

    white-space:pre-wrap;

}


/* =========================================================
   PROPERTY
========================================================= */

.property-box {

    display:flex;

    gap:15px;

}


.property-image {

    width:125px;

    height:95px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:7px;

    background:
        linear-gradient(
            135deg,
            #174a3a,
            #3b7560
        );

    color:white;

    font-size:30px;

}


.property-title {

    font-size:12px;

    font-weight:800;

}


.property-location {

    margin-top:7px;

    color:var(--muted);

    font-size:8px;

}


.property-price {

    margin-top:9px;

    color:var(--primary);

    font-size:12px;

    font-weight:800;

}


/* =========================================================
   AGENT
========================================================= */

.agent-box {

    display:flex;

    align-items:center;

    gap:12px;

}


.agent-avatar {

    width:45px;

    height:45px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-size:14px;

    font-weight:800;

}


.agent-name {

    font-size:10px;

    font-weight:800;

}


.agent-contact {

    margin-top:5px;

    color:var(--muted);

    font-size:7px;

    line-height:1.7;

}


/* =========================================================
   TIMELINE
========================================================= */

.timeline {

    position:relative;

}


.timeline::before {

    content:"";

    position:absolute;

    left:10px;

    top:7px;

    bottom:7px;

    width:1px;

    background:
        var(--border);

}


.timeline-item {

    position:relative;

    display:flex;

    gap:15px;

    margin-bottom:22px;

}


.timeline-item:last-child {

    margin-bottom:0;

}


.timeline-dot {

    width:21px;

    height:21px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-size:8px;

    z-index:1;

}


.timeline-content {

    padding-top:1px;

}


.timeline-title {

    font-size:8px;

    font-weight:800;

}


.timeline-date {

    margin-top:4px;

    color:var(--muted);

    font-size:6px;

}


.timeline-description {

    margin-top:5px;

    color:#555f5a;

    font-size:7px;

    line-height:1.5;

}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:7px;

}


.quick-action {

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    border-radius:6px;

    background:#f4f6f5;

    color:var(--text);

    text-decoration:none;

    font-size:7px;

    font-weight:700;

}


.quick-action:hover {

    background:
        var(--primary);

    color:white;

}


/* =========================================================
   DELETE
========================================================= */

.delete-btn {

    width:100%;

    height:40px;

    border:none;

    border-radius:6px;

    background:
        var(--red-bg);

    color:
        var(--red);

    cursor:pointer;

    font-size:8px;

    font-weight:700;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1000px) {

    .layout {

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


    .topbar {

        padding:
            0 15px;

    }


    .admin-name {

        display:none;

    }


    .content {

        padding:
            20px 15px;

    }

}


@media(max-width:550px) {

    .page-header {

        flex-direction:column;

        gap:12px;

    }


    .header-actions {

        width:100%;

    }


    .btn {

        flex:1;

    }


    .info-grid {

        grid-template-columns:1fr;

    }


    .property-box {

        flex-direction:column;

    }


    .property-image {

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


<a href="users.php">

    <span class="icon">👥</span>

    <span>Users</span>

</a>


<a href="agents.php">

    <span class="icon">🧑‍💼</span>

    <span>Agents</span>

</a>


<a
    href="enquiries.php"
    class="active"
>

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
    href="enquiries.php"
    class="back-btn"
>
    ←
</a>


<div>

<h1>
    Enquiry Details
</h1>

<p>
    Review customer lead and property enquiry
</p>

</div>


</div>


<div class="admin-user">


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
     PAGE HEADER
========================================================= -->

<div class="page-header">


<div>

<h2>
    #ENQ-<?php echo str_pad($id, 5, "0", STR_PAD_LEFT); ?>
</h2>

<p>
    Created on
    <?php echo safe($createdDate); ?>
    <?php echo safe($createdTime); ?>
</p>

</div>


<div class="header-actions">


<a
    href="enquiry-edit.php?id=<?php echo $id; ?>"
    class="btn btn-primary"
>
    ✏️ Edit Enquiry
</a>


<a
    href="enquiries.php"
    class="btn btn-light"
>
    ← Back
</a>


</div>


</div>


<!-- =====================================================
     MAIN LAYOUT
========================================================= -->

<div class="layout">


<!-- =====================================================
     LEFT COLUMN
========================================================= -->

<div>


<!-- CUSTOMER CARD -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Customer Information
</div>


<div class="badges">

<span class="badge <?php echo safe($status); ?>">

<?php
echo ucfirst(
    safe($status)
);
?>

</span>


<span class="badge <?php echo safe($priority); ?>">

<?php
echo ucfirst(
    safe($priority)
);
?>

 Priority

</span>

</div>

</div>


<div class="card-body">


<div class="customer-box">


<div class="customer-avatar">

<?php
echo safe($customerInitial);
?>

</div>


<div>

<div class="customer-name">

<?php
echo safe($customerName);
?>

</div>


<div class="customer-contact">


<?php if (
    $customerEmail !== "Not provided"
): ?>

<a
    href="mailto:<?php echo safe($customerEmail); ?>"
    class="contact-link"
>
    ✉ <?php echo safe($customerEmail); ?>
</a>

<?php endif; ?>


<?php if (
    $customerPhone !== "Not provided"
): ?>

<a
    href="tel:<?php echo safe($customerPhone); ?>"
    class="contact-link"
>
    ☎ <?php echo safe($customerPhone); ?>
</a>

<?php endif; ?>


</div>


</div>


</div>


</div>


</section>


<!-- MESSAGE -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Customer Message
</div>

</div>


<div class="card-body">


<div class="message-box">

<?php
echo safe($message);
?>

</div>


</div>


</section>


<!-- PROPERTY -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Property Interest
</div>

</div>


<div class="card-body">


<div class="property-box">


<div class="property-image">
    🏠
</div>


<div>


<div class="property-title">

<?php
echo safe($propertyTitle);
?>

</div>


<div class="property-location">

📍
<?php
echo safe($propertyLocation);
?>

</div>


<?php if (
    $propertyPrice !== ""
): ?>

<div class="property-price">

₹
<?php
echo number_format(
    (float)$propertyPrice
);
?>

</div>

<?php endif; ?>


<div
    style="
        margin-top:8px;
        color:#737c78;
        font-size:7px;
    "
>

<?php
echo safe($propertyType);
?>

<?php if (
    $propertyStatus !== ""
): ?>

 ·
<?php
echo safe($propertyStatus);
?>

<?php endif; ?>

</div>


</div>


</div>


</div>


</section>


<!-- TIMELINE -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Enquiry Timeline
</div>

</div>


<div class="card-body">


<div class="timeline">


<div class="timeline-item">


<div class="timeline-dot">
    1
</div>


<div class="timeline-content">


<div class="timeline-title">
    Enquiry Created
</div>


<div class="timeline-date">

<?php
echo safe($createdDate);
?>
 ·
<?php
echo safe($createdTime);
?>

</div>


<div class="timeline-description">

Customer submitted a property enquiry.

</div>


</div>


</div>


<div class="timeline-item">


<div class="timeline-dot">
    2
</div>


<div class="timeline-content">


<div class="timeline-title">

Current Status:
<?php
echo ucfirst(
    safe($status)
);
?>

</div>


<div class="timeline-date">
    Lead Management
</div>


<div class="timeline-description">

This enquiry is currently marked as
<strong>
<?php
echo safe($status);
?>
</strong>.

</div>


</div>


</div>


<?php if (
    $agentName !== "Unassigned"
): ?>


<div class="timeline-item">


<div class="timeline-dot">
    3
</div>


<div class="timeline-content">


<div class="timeline-title">
    Agent Assigned
</div>


<div class="timeline-date">
    <?php echo safe($agentName); ?>
</div>


<div class="timeline-description">

The enquiry has been assigned to a real estate agent.

</div>


</div>


</div>


<?php endif; ?>


</div>


</div>


</section>


</div>


<!-- =====================================================
     RIGHT COLUMN
========================================================= -->

<div>


<!-- QUICK ACTIONS -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Quick Actions
</div>

</div>


<div class="card-body">


<div class="quick-actions">


<?php if (
    $customerPhone !== "Not provided"
): ?>

<a
    href="tel:<?php echo safe($customerPhone); ?>"
    class="quick-action"
>
    📞 Call Customer
</a>

<?php endif; ?>


<?php if (
    $customerEmail !== "Not provided"
): ?>

<a
    href="mailto:<?php echo safe($customerEmail); ?>"
    class="quick-action"
>
    ✉ Email Customer
</a>

<?php endif; ?>


<a
    href="enquiry-edit.php?id=<?php echo $id; ?>"
    class="quick-action"
>
    ✏️ Update Status
</a>


<a
    href="properties.php"
    class="quick-action"
>
    🏠 Properties
</a>


</div>


</div>


</section>


<!-- AGENT -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Assigned Agent
</div>

</div>


<div class="card-body">


<div class="agent-box">


<div class="agent-avatar">

<?php

echo safe(
    strtoupper(
        substr(
            $agentName,
            0,
            1
        )
    )
);

?>

</div>


<div>


<div class="agent-name">

<?php
echo safe($agentName);
?>

</div>


<div class="agent-contact">

<?php if (
    $agentEmail !== ""
): ?>

✉
<?php
echo safe($agentEmail);
?>

<br>

<?php endif; ?>


<?php if (
    $agentPhone !== ""
): ?>

☎
<?php
echo safe($agentPhone);
?>

<?php endif; ?>


</div>


</div>


</div>


<?php if (
    $agentName === "Unassigned"
): ?>


<a
    href="enquiry-edit.php?id=<?php echo $id; ?>"
    class="btn btn-primary"
    style="
        width:100%;
        margin-top:15px;
    "
>
    🧑‍💼 Assign Agent
</a>


<?php endif; ?>


</div>


</section>


<!-- ENQUIRY INFO -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Enquiry Information
</div>

</div>


<div class="card-body">


<div class="info-grid">


<div class="info-item">

<div class="info-label">
    Enquiry ID
</div>

<div class="info-value">

#<?php
echo $id;
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


<div class="info-item">

<div class="info-label">
    Priority
</div>

<div class="info-value">

<?php
echo ucfirst(
    safe($priority)
);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Created
</div>

<div class="info-value">

<?php
echo safe($createdDate);
?>

</div>

</div>


</div>


</div>


</section>


<!-- DANGER ZONE -->

<section class="card">


<div class="card-header">

<div class="card-title">
    Danger Zone
</div>

</div>


<div class="card-body">


<button
    type="button"
    class="delete-btn"
    id="deleteBtn"
>
    🗑 Delete Enquiry
</button>


</div>


</section>


</div>


</div>


</main>


</div>


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteBtn =
    document.getElementById(
        "deleteBtn"
    );


if (deleteBtn) {

    deleteBtn.addEventListener(
        "click",
        function() {

            const confirmed =
                confirm(
                    "Are you sure you want to permanently delete this enquiry?"
                );


            if (confirmed) {

                window.location.href =
                    "enquiry-delete.php?id=<?php echo $id; ?>";

            }

        }
    );

}


/* =========================================================
   PAGE ANIMATION
========================================================= */

const cards =
    document.querySelectorAll(
        ".card"
    );


cards.forEach(
    function(card, index) {

        card.style.opacity =
            "0";

        card.style.transform =
            "translateY(8px)";


        setTimeout(
            function() {

                card.style.transition =
                    "opacity .3s ease, transform .3s ease";

                card.style.opacity =
                    "1";

                card.style.transform =
                    "translateY(0)";

            },
            index * 70
        );

    }
);

</script>


</body>

</html>