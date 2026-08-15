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
   GET ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if (!$id) {
    header("Location: visits.php");
    exit;
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
   CHECK TABLE
========================================================= */

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'visits'"
    );


if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {

    exit(
        "The visits table does not exist."
    );

}


/* =========================================================
   GET COLUMNS
========================================================= */

$columns = [];

$columnResult =
    $conn->query(
        "SHOW COLUMNS FROM visits"
    );


while (
    $column =
    $columnResult->fetch_assoc()
) {

    $columns[] =
        $column["Field"];

}


/* =========================================================
   BUILD QUERY
========================================================= */

$select = "v.*";


$hasPropertyId =
    in_array(
        "property_id",
        $columns,
        true
    );


$hasUserId =
    in_array(
        "user_id",
        $columns,
        true
    );


$hasAgentId =
    in_array(
        "agent_id",
        $columns,
        true
    );


if ($hasPropertyId) {

    $select .= ",
        p.title AS property_title,
        p.location AS property_location,
        p.price AS property_price,
        p.image AS property_image
    ";

}


if ($hasUserId) {

    $select .= ",
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone
    ";

}


if ($hasAgentId) {

    $select .= ",
        a.name AS agent_name,
        a.email AS agent_email,
        a.phone AS agent_phone
    ";

}


$sql = "
    SELECT $select
    FROM visits v
";


if ($hasPropertyId) {

    $sql .= "
        LEFT JOIN properties p
        ON p.id = v.property_id
    ";

}


if ($hasUserId) {

    $sql .= "
        LEFT JOIN users u
        ON u.id = v.user_id
    ";

}


if ($hasAgentId) {

    $sql .= "
        LEFT JOIN users a
        ON a.id = v.agent_id
    ";

}


$sql .= "
    WHERE v.id = ?
    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    exit(
        "Database query error."
    );

}


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


$result =
    $stmt->get_result();


$visit =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   NOT FOUND
========================================================= */

if (!$visit) {

    $_SESSION["error"] =
        "Visit appointment not found.";

    header(
        "Location: visits.php"
    );

    exit;
}


/* =========================================================
   CUSTOMER
========================================================= */

$customerName =
    $visit["user_name"]
    ?? $visit["name"]
    ?? "Unknown Customer";


$customerEmail =
    $visit["user_email"]
    ?? $visit["email"]
    ?? "";


$customerPhone =
    $visit["user_phone"]
    ?? $visit["phone"]
    ?? "";


/* =========================================================
   PROPERTY
========================================================= */

$propertyTitle =
    $visit["property_title"]
    ?? "Property #"
    . (
        $visit["property_id"]
        ?? "-"
    );


$propertyLocation =
    $visit["property_location"]
    ?? "";


$propertyPrice =
    $visit["property_price"]
    ?? "";


$propertyImage =
    $visit["property_image"]
    ?? "";


/* =========================================================
   AGENT
========================================================= */

$agentName =
    $visit["agent_name"]
    ?? "Not Assigned";


$agentEmail =
    $visit["agent_email"]
    ?? "";


$agentPhone =
    $visit["agent_phone"]
    ?? "";


/* =========================================================
   APPOINTMENT
========================================================= */

$visitDate =
    $visit["visit_date"]
    ?? "";


$visitTime =
    $visit["visit_time"]
    ?? "";


$visitStatus =
    strtolower(
        $visit["status"]
        ?? "pending"
    );


$notes =
    $visit["notes"]
    ?? "";


/* =========================================================
   DATE FORMAT
========================================================= */

$formattedDate =
    "Not scheduled";


if ($visitDate !== "") {

    $timestamp =
        strtotime(
            $visitDate
        );


    if ($timestamp) {

        $formattedDate =
            date(
                "l, d F Y",
                $timestamp
            );

    }

}


/* =========================================================
   STATUS LABEL
========================================================= */

$statusLabels = [

    "pending" =>
        "Pending",

    "confirmed" =>
        "Confirmed",

    "completed" =>
        "Completed",

    "cancelled" =>
        "Cancelled"

];


$statusLabel =
    $statusLabels[
        $visitStatus
    ]
    ?? ucfirst(
        $visitStatus
    );


/* =========================================================
   CUSTOMER INITIAL
========================================================= */

$customerInitial =
    strtoupper(
        substr(
            $customerName,
            0,
            1
        )
    );


/* =========================================================
   AGENT INITIAL
========================================================= */

$agentInitial =
    strtoupper(
        substr(
            $agentName,
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
    Visit Details | RealEstate
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

    --green:#17643b;
    --green-bg:#e8f6ed;

    --orange:#a76210;
    --orange-bg:#fff4df;

    --red:#b43843;
    --red-bg:#fdebed;

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

    z-index:20;

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

    max-width:1150px;

    margin:auto;

    padding:
        30px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:20px;

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

    gap:8px;

}


.btn {

    height:40px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:
        0 14px;

    border-radius:6px;

    text-decoration:none;

    font-size:8px;

    font-weight:700;

}


.btn-primary {

    background:
        var(--primary);

    color:white;

}


.btn-light {

    background:white;

    color:var(--text);

    border:
        1px solid
        var(--border);

}


/* =========================================================
   STATUS HERO
========================================================= */

.hero {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    padding:22px;

    margin-bottom:18px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.hero-left {

    display:flex;

    align-items:center;

    gap:15px;

}


.hero-icon {

    width:60px;

    height:60px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        var(--primary);

    color:white;

    border-radius:10px;

    font-size:25px;

}


.hero-title {

    font-size:17px;

    font-weight:800;

}


.hero-subtitle {

    margin-top:5px;

    color:var(--muted);

    font-size:8px;

}


.badge {

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:
        8px 14px;

    border-radius:20px;

    font-size:7px;

    font-weight:800;

}


.badge.pending {

    background:
        var(--orange-bg);

    color:
        var(--orange);

}


.badge.confirmed {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


.badge.completed {

    background:
        var(--green-bg);

    color:
        var(--green);

}


.badge.cancelled {

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
        1.6fr 1fr;

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


.card-header {

    padding:
        16px 20px;

    border-bottom:
        1px solid
        #edf0ee;

    font-size:10px;

    font-weight:800;

}


.card-body {

    padding:20px;

}


/* =========================================================
   INFO ROW
========================================================= */

.info-row {

    display:grid;

    grid-template-columns:
        140px 1fr;

    gap:15px;

    padding:
        13px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.info-row:last-child {

    border-bottom:none;

}


.info-label {

    color:var(--muted);

    font-size:7px;

    text-transform:uppercase;

    letter-spacing:.5px;

}


.info-value {

    font-size:9px;

    font-weight:700;

}


.info-value a {

    color:
        var(--primary);

    text-decoration:none;

}


.info-value a:hover {

    text-decoration:underline;

}


/* =========================================================
   CUSTOMER
========================================================= */

.profile {

    display:flex;

    align-items:center;

    gap:12px;

    margin-bottom:18px;

}


.avatar {

    width:48px;

    height:48px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-size:16px;

    font-weight:800;

}


.profile-name {

    font-size:11px;

    font-weight:800;

}


.profile-role {

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   PROPERTY
========================================================= */

.property-card {

    display:flex;

    gap:13px;

}


.property-image {

    width:105px;

    height:80px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    overflow:hidden;

    background:#eef1ef;

    border-radius:7px;

    font-size:25px;

}


.property-image img {

    width:100%;

    height:100%;

    object-fit:cover;

}


.property-title {

    font-size:10px;

    font-weight:800;

    line-height:1.4;

}


.property-location {

    margin-top:6px;

    color:var(--muted);

    font-size:7px;

    line-height:1.4;

}


.property-price {

    margin-top:7px;

    color:
        var(--primary);

    font-size:9px;

    font-weight:800;

}


/* =========================================================
   SCHEDULE
========================================================= */

.schedule {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:10px;

}


.schedule-box {

    padding:16px;

    background:#fafbfa;

    border:
        1px solid
        #edf0ee;

    border-radius:7px;

}


.schedule-icon {

    font-size:18px;

}


.schedule-label {

    margin-top:10px;

    color:var(--muted);

    font-size:7px;

}


.schedule-value {

    margin-top:5px;

    font-size:10px;

    font-weight:800;

}


/* =========================================================
   NOTES
========================================================= */

.notes {

    padding:15px;

    background:#fafbfa;

    border:
        1px solid
        #edf0ee;

    border-radius:7px;

    color:#4e5753;

    font-size:8px;

    line-height:1.7;

    white-space:pre-wrap;

}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:8px;

}


.quick-action {

    height:40px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    text-decoration:none;

    color:var(--text);

    font-size:7px;

    font-weight:700;

}


.quick-action:hover {

    background:
        #f3f6f4;

}


.quick-action.delete {

    color:
        var(--red);

}


.quick-action.delete:hover {

    background:
        var(--red-bg);

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


    .content {

        padding:
            20px 15px;

    }


    .admin-name {

        display:none;

    }

}


@media(max-width:600px) {

    .page-header {

        flex-direction:column;

        align-items:flex-start;

    }


    .header-actions {

        width:100%;

    }


    .header-actions .btn {

        flex:1;

    }


    .hero {

        align-items:flex-start;

        flex-direction:column;

    }


    .hero-status {

        width:100%;

    }


    .hero-status .badge {

        width:100%;

    }


    .info-row {

        grid-template-columns:1fr;

        gap:5px;

    }


    .schedule {

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


<a href="enquiries.php">

    <span class="icon">💬</span>
    <span>Enquiries</span>

</a>


<a
    href="visits.php"
    class="active"
>

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
    href="visits.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Visit Details
</h1>

<p>
    Appointment #<?php echo (int)$id; ?>
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
     PAGE HEADER
========================================================= -->

<div class="page-header">


<div>

<h2>
    Property Visit #<?php echo (int)$id; ?>
</h2>

<p>
    Complete appointment information and management options.
</p>

</div>


<div class="header-actions">


<a
    href="visits.php"
    class="btn btn-light"
>
    ← Back
</a>


<a
    href="visit-edit.php?id=<?php echo (int)$id; ?>"
    class="btn btn-primary"
>
    ✏ Edit Visit
</a>


</div>


</div>


<!-- =====================================================
     HERO
========================================================= -->

<section class="hero">


<div class="hero-left">


<div class="hero-icon">
    📅
</div>


<div>

<div class="hero-title">

<?php
echo safe($propertyTitle);
?>

</div>


<div class="hero-subtitle">

Appointment
#<?php echo (int)$id; ?>

</div>


</div>


</div>


<div class="hero-status">


<span
    class="badge <?php echo safe($visitStatus); ?>"
>

<?php
echo safe($statusLabel);
?>

</span>


</div>


</section>


<!-- =====================================================
     GRID
========================================================= -->

<div class="grid">


<!-- =====================================================
     LEFT
========================================================= -->

<div>


<!-- CUSTOMER -->

<section class="card">


<div class="card-header">
    Customer Information
</div>


<div class="card-body">


<div class="profile">


<div class="avatar">

<?php
echo safe($customerInitial);
?>

</div>


<div>

<div class="profile-name">

<?php
echo safe($customerName);
?>

</div>


<div class="profile-role">
    Property Buyer / Visitor
</div>

</div>


</div>


<div class="info-row">

<div class="info-label">
    Email
</div>


<div class="info-value">

<?php if (
    $customerEmail !== ""
): ?>

<a
    href="mailto:<?php echo safe($customerEmail); ?>"
>

<?php
echo safe($customerEmail);
?>

</a>

<?php else: ?>

Not provided

<?php endif; ?>

</div>

</div>


<div class="info-row">

<div class="info-label">
    Phone
</div>


<div class="info-value">

<?php if (
    $customerPhone !== ""
): ?>

<a
    href="tel:<?php echo safe($customerPhone); ?>"
>

<?php
echo safe($customerPhone);
?>

</a>

<?php else: ?>

Not provided

<?php endif; ?>

</div>

</div>


</div>


</section>


<!-- PROPERTY -->

<section class="card">


<div class="card-header">
    Property Information
</div>


<div class="card-body">


<div class="property-card">


<div class="property-image">


<?php if (
    $propertyImage !== ""
): ?>

<img
    src="<?php echo safe($propertyImage); ?>"
    alt="Property"
>


<?php else: ?>

🏠

<?php endif; ?>


</div>


<div>


<div class="property-title">

<?php
echo safe($propertyTitle);
?>

</div>


<?php if (
    $propertyLocation !== ""
): ?>

<div class="property-location">

📍
<?php
echo safe($propertyLocation);
?>

</div>

<?php endif; ?>


<?php if (
    $propertyPrice !== ""
): ?>

<div class="property-price">

₹
<?php
echo safe(
    number_format(
        (float)$propertyPrice
    )
);
?>

</div>

<?php endif; ?>


</div>


</div>


<?php if (
    $hasPropertyId &&
    !empty($visit["property_id"])
): ?>


<div
    style="
        margin-top:15px;
        padding-top:15px;
        border-top:1px solid #edf0ee;
    "
>


<a
    href="../page/property-details.php?id=<?php echo (int)$visit["property_id"]; ?>"
    class="btn btn-light"
    style="
        display:inline-flex;
        height:35px;
    "
>

🏠 View Property

</a>


</div>


<?php endif; ?>


</div>


</section>


<!-- NOTES -->

<section class="card">


<div class="card-header">
    Additional Notes
</div>


<div class="card-body">


<div class="notes">


<?php

if ($notes !== "") {

    echo safe($notes);

} else {

    echo "No additional notes were added for this appointment.";

}

?>


</div>


</div>


</section>


</div>


<!-- =====================================================
     RIGHT
========================================================= -->

<div>


<!-- SCHEDULE -->

<section class="card">


<div class="card-header">
    Appointment Schedule
</div>


<div class="card-body">


<div class="schedule">


<div class="schedule-box">


<div class="schedule-icon">
    📅
</div>


<div class="schedule-label">
    Visit Date
</div>


<div class="schedule-value">

<?php
echo safe($formattedDate);
?>

</div>


</div>


<div class="schedule-box">


<div class="schedule-icon">
    🕐
</div>


<div class="schedule-label">
    Visit Time
</div>


<div class="schedule-value">

<?php

echo $visitTime !== ""
    ? safe($visitTime)
    : "Not scheduled";

?>

</div>


</div>


</div>


</div>


</section>


<!-- AGENT -->

<section class="card">


<div class="card-header">
    Assigned Agent
</div>


<div class="card-body">


<div class="profile">


<div
    class="avatar"
    style="
        background:#365caa;
    "
>

<?php
echo safe($agentInitial);
?>

</div>


<div>

<div class="profile-name">

<?php
echo safe($agentName);
?>

</div>


<div class="profile-role">
    Property Agent
</div>

</div>


</div>


<?php if (
    $agentEmail !== ""
): ?>

<div class="info-row">

<div class="info-label">
    Email
</div>


<div class="info-value">

<a
    href="mailto:<?php echo safe($agentEmail); ?>"
>

<?php
echo safe($agentEmail);
?>

</a>

</div>

</div>

<?php endif; ?>


<?php if (
    $agentPhone !== ""
): ?>

<div class="info-row">

<div class="info-label">
    Phone
</div>


<div class="info-value">

<a
    href="tel:<?php echo safe($agentPhone); ?>"
>

<?php
echo safe($agentPhone);
?>

</a>

</div>

</div>

<?php endif; ?>


</div>


</section>


<!-- QUICK ACTIONS -->

<section class="card">


<div class="card-header">
    Quick Actions
</div>


<div class="card-body">


<div class="quick-actions">


<a
    href="visit-edit.php?id=<?php echo (int)$id; ?>"
    class="quick-action"
>
    ✏ Edit
</a>


<?php if (
    $customerPhone !== ""
): ?>

<a
    href="tel:<?php echo safe($customerPhone); ?>"
    class="quick-action"
>
    📞 Call Customer
</a>

<?php else: ?>

<a
    href="#"
    class="quick-action"
    onclick="return false;"
>
    📞 No Phone
</a>

<?php endif; ?>


<?php if (
    $customerEmail !== ""
): ?>

<a
    href="mailto:<?php echo safe($customerEmail); ?>"
    class="quick-action"
>
    ✉ Email
</a>

<?php else: ?>

<a
    href="#"
    class="quick-action"
    onclick="return false;"
>
    ✉ No Email
</a>

<?php endif; ?>


<a
    href="visit-delete.php?id=<?php echo (int)$id; ?>"
    class="quick-action delete"
    id="deleteVisit"
>
    🗑 Delete
</a>


</div>


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

const deleteVisit =
    document.getElementById(
        "deleteVisit"
    );


if (deleteVisit) {

    deleteVisit.addEventListener(
        "click",
        function(event) {

            const confirmed =
                confirm(
                    "Are you sure you want to permanently delete this property visit?"
                );


            if (!confirmed) {

                event.preventDefault();

            }

        }
    );

}


/* =========================================================
   PAGE ANIMATION
========================================================= */

const cards =
    document.querySelectorAll(
        ".card, .hero"
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
                    "opacity .35s ease, transform .35s ease";

                card.style.opacity =
                    "1";

                card.style.transform =
                    "translateY(0)";

            },
            index * 60
        );

    }
);

</script>


</body>

</html>