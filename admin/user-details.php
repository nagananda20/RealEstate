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
   USER ID
========================================================= */

$userId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$userId || $userId <= 0) {
    header("Location: users.php?error=invalid_id");
    exit;
}


/* =========================================================
   GET USER
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

if (!$stmt) {
    exit("Database error.");
}

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
   USER VALUES
========================================================= */

$name =
    $user["name"] ?? "Unknown User";

$email =
    $user["email"] ?? "";

$phone =
    $user["phone"] ?? "";

$role =
    $user["role"] ?? "user";

$status =
    $user["status"] ?? "inactive";

$createdAt =
    $user["created_at"] ?? "";

$initial =
    strtoupper(
        substr($name, 0, 1)
    );


/* =========================================================
   DATE
========================================================= */

$joinedDate = "N/A";

if (!empty($createdAt)) {

    $timestamp = strtotime($createdAt);

    if ($timestamp) {

        $joinedDate = date(
            "d M Y, h:i A",
            $timestamp
        );
    }
}


/* =========================================================
   PROPERTY COUNT
========================================================= */

$propertyCount = 0;

$table = $conn->query(
    "SHOW TABLES LIKE 'properties'"
);

if ($table && $table->num_rows > 0) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM properties
        WHERE agent_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $propertyCount =
            (int)($row["total"] ?? 0);

        $stmt->close();
    }
}


/* =========================================================
   ENQUIRY COUNT
========================================================= */

$enquiryCount = 0;

$table = $conn->query(
    "SHOW TABLES LIKE 'enquiries'"
);

if ($table && $table->num_rows > 0) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM enquiries
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $enquiryCount =
            (int)($row["total"] ?? 0);

        $stmt->close();
    }
}


/* =========================================================
   VISIT COUNT
========================================================= */

$visitCount = 0;

$table = $conn->query(
    "SHOW TABLES LIKE 'visits'"
);

if ($table && $table->num_rows > 0) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM visits
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $visitCount =
            (int)($row["total"] ?? 0);

        $stmt->close();
    }
}


/* =========================================================
   FAVORITES
========================================================= */

$favoriteCount = 0;

$table = $conn->query(
    "SHOW TABLES LIKE 'favorites'"
);

if ($table && $table->num_rows > 0) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM favorites
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $favoriteCount =
            (int)($row["total"] ?? 0);

        $stmt->close();
    }
}


/* =========================================================
   USER PROPERTIES
========================================================= */

$userProperties = [];

$table = $conn->query(
    "SHOW TABLES LIKE 'properties'"
);

if ($table && $table->num_rows > 0) {

    $sql = "
        SELECT
            id,
            title,
            price,
            city,
            status,
            listing_type
        FROM properties
        WHERE agent_id = ?
        ORDER BY id DESC
        LIMIT 5
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $userProperties[] =
                $row;
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
    <?php echo safe($name); ?>
    | User Details
</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

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

    top: 0;
    left: 0;
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

    margin-bottom:
        3px;

    border-radius:
        7px;

    color:
        rgba(255,255,255,.7);

    text-decoration:
        none;

    font-size:
        10px;
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

    bottom: 0;

    left: 0;
    right: 0;

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

    height:
        75px;

    background:
        white;

    border-bottom:
        1px solid
        var(--border);

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        0 30px;

    position:
        sticky;

    top: 0;

    z-index: 20;
}


.top-left {

    display:
        flex;

    align-items:
        center;

    gap: 14px;
}


.back {

    width:
        38px;

    height:
        38px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border:
        1px solid
        var(--border);

    border-radius:
        7px;

    color:
        var(--text);

    text-decoration:
        none;

    font-size:
        15px;
}


.topbar h1 {

    font-size:
        18px;
}


.topbar p {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        8px;
}


.top-actions {

    display:
        flex;

    gap:
        7px;
}


.btn {

    height:
        38px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    padding:
        0 13px;

    border-radius:
        6px;

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;
}


.btn-edit {

    background:
        var(--primary);

    color:
        white;
}


.btn-delete {

    background:
        var(--red-bg);

    color:
        var(--red);
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1400px;

    padding:
        28px 30px 60px;
}


/* =========================================================
   PROFILE
========================================================= */

.profile-card {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    padding:
        25px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    margin-bottom:
        20px;
}


.profile-left {

    display:
        flex;

    align-items:
        center;

    gap:
        18px;
}


.avatar {

    width:
        80px;

    height:
        80px;

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
        28px;

    font-weight:
        800;

    box-shadow:
        0 8px 20px
        rgba(23,74,58,.15);
}


.profile-name {

    font-size:
        22px;

    font-weight:
        800;
}


.profile-email {

    margin-top:
        6px;

    color:
        var(--muted);

    font-size:
        9px;
}


.profile-tags {

    margin-top:
        10px;

    display:
        flex;

    gap:
        6px;

    flex-wrap:
        wrap;
}


.badge {

    display:
        inline-flex;

    padding:
        6px 9px;

    border-radius:
        20px;

    font-size:
        7px;

    font-weight:
        700;
}


.role-admin {

    color:
        #704f08;

    background:
        #fff4d1;
}


.role-agent {

    color:
        var(--blue);

    background:
        var(--blue-bg);
}


.role-user {

    color:
        var(--green);

    background:
        var(--green-bg);
}


.status-active {

    color:
        var(--green);

    background:
        var(--green-bg);
}


.status-inactive {

    color:
        var(--red);

    background:
        var(--red-bg);
}


.joined {

    text-align:
        right;

    color:
        var(--muted);

    font-size:
        8px;
}


.joined strong {

    display:
        block;

    color:
        var(--text);

    font-size:
        10px;

    margin-top:
        5px;
}


/* =========================================================
   GRID
========================================================= */

.grid {

    display:
        grid;

    grid-template-columns:
        1.5fr .8fr;

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
        17px 20px;

    border-bottom:
        1px solid
        var(--border);

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;
}


.card-header h2 {

    font-size:
        13px;
}


.card-body {

    padding:
        20px;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        10px;

    margin-bottom:
        20px;
}


.stat {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        8px;

    padding:
        16px;
}


.stat-icon {

    font-size:
        17px;
}


.stat-number {

    margin-top:
        8px;

    font-size:
        20px;

    font-weight:
        800;
}


.stat-label {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;
}


/* =========================================================
   INFORMATION
========================================================= */

.info-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        0 25px;
}


.info-item {

    padding:
        14px 0;

    border-bottom:
        1px solid
        #edf0ee;
}


.info-label {

    color:
        var(--muted);

    font-size:
        7px;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;
}


.info-value {

    margin-top:
        5px;

    font-size:
        9px;

    font-weight:
        700;
}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {

    display:
        grid;

    gap:
        8px;
}


.quick {

    min-height:
        42px;

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    padding:
        0 12px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    color:
        var(--text);

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;
}


.quick:hover {

    background:
        #f7f9f8;
}


.quick.danger {

    color:
        var(--red);
}


/* =========================================================
   PROPERTIES
========================================================= */

.property {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        14px 0;

    border-bottom:
        1px solid
        #edf0ee;
}


.property:last-child {

    border-bottom:
        none;
}


.property-title {

    font-size:
        9px;

    font-weight:
        800;
}


.property-meta {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;
}


.property-price {

    font-size:
        9px;

    font-weight:
        800;

    color:
        var(--primary);
}


.property-status {

    margin-top:
        4px;

    font-size:
        7px;

    color:
        var(--green);
}


/* =========================================================
   ACTIVITY
========================================================= */

.activity {

    position:
        relative;
}


.activity-item {

    position:
        relative;

    padding:
        0 0 20px 25px;
}


.activity-item::before {

    content:
        "";

    position:
        absolute;

    left:
        4px;

    top:
        4px;

    width:
        10px;

    height:
        10px;

    border-radius:
        50%;

    background:
        var(--primary);
}


.activity-item:not(:last-child)::after {

    content:
        "";

    position:
        absolute;

    left:
        8px;

    top:
        14px;

    width:
        1px;

    height:
        calc(100% - 8px);

    background:
        var(--border);
}


.activity-title {

    font-size:
        8px;

    font-weight:
        800;
}


.activity-date {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:
        35px 10px;

    text-align:
        center;

    color:
        var(--muted);

    font-size:
        8px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .grid {

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

    .content {

        padding:
            20px 15px;
    }

    .profile-card {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .joined {

        text-align:
            left;
    }

}


@media(max-width:600px) {

    .topbar {

        padding:
            0 15px;
    }

    .top-actions .btn span {

        display:
            none;
    }

    .profile-left {

        align-items:
            flex-start;
    }

    .avatar {

        width:
            60px;

        height:
            60px;

        font-size:
            21px;
    }

    .profile-name {

        font-size:
            17px;
    }

    .stats {

        grid-template-columns:
            repeat(2,1fr);
    }

    .info-grid {

        grid-template-columns:
            1fr;
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
    href="users.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    User Details
</h1>

<p>
    Complete account information
</p>

</div>


</div>


<div class="top-actions">


<a
    href="user-edit.php?id=<?php echo (int)$userId; ?>"
    class="btn btn-edit"
>
    ✏️
    <span>Edit User</span>
</a>


<?php if (
    (int)$userId !==
    (int)$_SESSION["user_id"]
): ?>

<a
    href="user-delete.php?id=<?php echo (int)$userId; ?>"
    class="btn btn-delete"
    id="deleteUser"
>
    🗑️
    <span>Delete</span>
</a>

<?php endif; ?>


</div>


</header>


<main class="content">


<!-- =====================================================
     PROFILE
===================================================== -->

<section class="profile-card">


<div class="profile-left">


<div class="avatar">

<?php
echo safe($initial);
?>

</div>


<div>


<div class="profile-name">

<?php
echo safe($name);
?>

</div>


<div class="profile-email">

<?php
echo safe($email);
?>

</div>


<div class="profile-tags">


<span class="badge
<?php

if ($role === "admin") {

    echo " role-admin";

}
elseif ($role === "agent") {

    echo " role-agent";

}
else {

    echo " role-user";

}

?>
">

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


<span class="badge
<?php

echo $status === "active"
    ? " status-active"
    : " status-inactive";

?>
">

<?php

echo $status === "active"
    ? "Active Account"
    : "Inactive Account";

?>

</span>


</div>


</div>


</div>


<div class="joined">

Member since

<strong>
    <?php echo safe($joinedDate); ?>
</strong>

</div>


</section>


<!-- =====================================================
     STATISTICS
===================================================== -->

<section class="stats">


<div class="stat">

<div class="stat-icon">
    🏠
</div>

<div class="stat-number">
    <?php echo number_format($propertyCount); ?>
</div>

<div class="stat-label">
    Properties
</div>

</div>


<div class="stat">

<div class="stat-icon">
    💬
</div>

<div class="stat-number">
    <?php echo number_format($enquiryCount); ?>
</div>

<div class="stat-label">
    Enquiries
</div>

</div>


<div class="stat">

<div class="stat-icon">
    📅
</div>

<div class="stat-number">
    <?php echo number_format($visitCount); ?>
</div>

<div class="stat-label">
    Visits
</div>

</div>


<div class="stat">

<div class="stat-icon">
    ❤️
</div>

<div class="stat-number">
    <?php echo number_format($favoriteCount); ?>
</div>

<div class="stat-label">
    Favorites
</div>

</div>


</section>


<!-- =====================================================
     MAIN GRID
===================================================== -->

<div class="grid">


<!-- LEFT -->

<div>


<!-- USER INFORMATION -->

<section class="card">


<div class="card-header">

<h2>
    Account Information
</h2>

</div>


<div class="card-body">


<div class="info-grid">


<div class="info-item">

<div class="info-label">
    User ID
</div>

<div class="info-value">

#<?php
echo (int)$userId;
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Full Name
</div>

<div class="info-value">

<?php
echo safe($name);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Email Address
</div>

<div class="info-value">

<?php
echo safe($email);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Phone Number
</div>

<div class="info-value">

<?php

echo safe(
    $phone ?: "Not provided"
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Account Role
</div>

<div class="info-value">

<?php

echo ucfirst(
    safe($role)
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Account Status
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
    Registration Date
</div>

<div class="info-value">

<?php
echo safe($joinedDate);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Profile ID
</div>

<div class="info-value">

RE-<?php
echo str_pad(
    $userId,
    5,
    "0",
    STR_PAD_LEFT
);
?>

</div>

</div>


</div>


</div>


</section>


<!-- USER PROPERTIES -->

<section
    class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Associated Properties
</h2>


<a
    href="properties.php?agent_id=<?php echo (int)$userId; ?>"
    style="
        color:#174a3a;
        text-decoration:none;
        font-size:8px;
        font-weight:700;
    "
>
    View All →
</a>


</div>


<div class="card-body">


<?php if (
    !empty($userProperties)
): ?>


<?php foreach (
    $userProperties
    as $property
): ?>


<div class="property">


<div>


<div class="property-title">

<?php
echo safe(
    $property["title"]
);
?>

</div>


<div class="property-meta">

<?php

echo safe(
    $property["city"]
    ?: "Location not available"
);

?>

&nbsp; • &nbsp;

<?php

echo $property["listing_type"] === "rent"
    ? "For Rent"
    : "For Sale";

?>

</div>


<div class="property-status">

●

<?php
echo ucfirst(
    safe(
        $property["status"]
    )
);
?>

</div>


</div>


<div class="property-price">

₹<?php

echo number_format(
    (float)(
        $property["price"]
        ?? 0
    )
);

?>

</div>


</div>


<?php endforeach; ?>


<?php else: ?>


<div class="empty">

🏠

<br><br>

No properties associated with this user.

</div>


<?php endif; ?>


</div>


</section>


</div>


<!-- RIGHT -->

<div>


<!-- QUICK ACTIONS -->

<section class="card">


<div class="card-header">

<h2>
    Quick Actions
</h2>

</div>


<div class="card-body">


<div class="quick-actions">


<a
    href="user-edit.php?id=<?php echo (int)$userId; ?>"
    class="quick"
>
    ✏️
    Edit Account
</a>


<?php if ($role === "agent"): ?>

<a
    href="properties.php?agent_id=<?php echo (int)$userId; ?>"
    class="quick"
>
    🏠
    View Agent Properties
</a>

<?php endif; ?>


<a
    href="enquiries.php?user_id=<?php echo (int)$userId; ?>"
    class="quick"
>
    💬
    View Enquiries
</a>


<a
    href="visits.php?user_id=<?php echo (int)$userId; ?>"
    class="quick"
>
    📅
    View Visits
</a>


<?php if (
    (int)$userId !==
    (int)$_SESSION["user_id"]
): ?>

<a
    href="user-delete.php?id=<?php echo (int)$userId; ?>"
    class="quick danger"
    id="quickDelete"
>
    🗑️
    Delete Account
</a>

<?php endif; ?>


</div>


</div>


</section>


<!-- ACCOUNT SECURITY -->

<section
    class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Account Security
</h2>

</div>


<div class="card-body">


<div class="info-item">

<div class="info-label">
    Account Status
</div>

<div class="info-value">

<?php

if ($status === "active") {

    echo "🟢 Account is active";

}
else {

    echo "🔴 Account is inactive";

}

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    User Role
</div>

<div class="info-value">

<?php

if ($role === "admin") {

    echo "🛡️ Administrator";

}
elseif ($role === "agent") {

    echo "🧑‍💼 Real Estate Agent";

}
else {

    echo "👤 Registered User";

}

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Account Created
</div>

<div class="info-value">

<?php
echo safe($joinedDate);
?>

</div>

</div>


</div>


</section>


<!-- ACTIVITY -->

<section
    class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Account Activity
</h2>

</div>


<div class="card-body">


<div class="activity">


<div class="activity-item">


<div class="activity-title">
    Account Created
</div>


<div class="activity-date">

<?php
echo safe($joinedDate);
?>

</div>


</div>


<div class="activity-item">


<div class="activity-title">
    Account Status
</div>


<div class="activity-date">

<?php

echo $status === "active"
    ? "Currently active"
    : "Currently inactive";

?>

</div>


</div>


<div class="activity-item">


<div class="activity-title">
    Current Role
</div>


<div class="activity-date">

<?php

echo ucfirst(
    safe($role)
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


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteButtons = [

    document.getElementById(
        "deleteUser"
    ),

    document.getElementById(
        "quickDelete"
    )

];


deleteButtons.forEach(
    function(button) {

        if (!button) {
            return;
        }

        button.addEventListener(
            "click",
            function(event) {

                const confirmed =
                    confirm(
                        "Are you sure you want to delete this user?\n\nThis action cannot be undone."
                    );

                if (!confirmed) {

                    event.preventDefault();

                }

            }
        );

    }
);

</script>


</body>

</html>