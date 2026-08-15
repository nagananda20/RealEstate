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
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
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
   SEARCH / FILTER
========================================================= */

$search =
    trim(
        $_GET["search"] ?? ""
    );

$filter =
    $_GET["filter"] ?? "all";


$allowedFilters = [
    "all",
    "unread",
    "read"
];


if (!in_array(
    $filter,
    $allowedFilters,
    true
)) {

    $filter = "all";

}


/* =========================================================
   CHECK TABLE
========================================================= */

$notifications = [];

$tableExists = false;


$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'notifications'"
    );


if (
    $tableCheck &&
    $tableCheck->num_rows > 0
) {

    $tableExists = true;

}


/* =========================================================
   MARK ALL READ
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["mark_all_read"])
) {

    $token =
        $_POST["csrf_token"] ?? "";


    if (
        hash_equals(
            $_SESSION["csrf_token"],
            $token
        )
    ) {

        if ($tableExists) {

            $conn->query(
                "UPDATE notifications
                 SET is_read = 1
                 WHERE is_read = 0"
            );

        }

    }

    header(
        "Location: notifications.php"
    );

    exit;
}


/* =========================================================
   GET NOTIFICATIONS
========================================================= */

if ($tableExists) {

    $sql =
        "SELECT *
         FROM notifications
         WHERE 1=1";


    $types = "";

    $params = [];


    /* =====================================================
       FILTER
    ===================================================== */

    if ($filter === "unread") {

        $sql .=
            " AND is_read = 0";

    }
    elseif ($filter === "read") {

        $sql .=
            " AND is_read = 1";

    }


    /* =====================================================
       SEARCH
    ===================================================== */

    if ($search !== "") {

        $sql .=
            " AND (
                title LIKE ?
                OR message LIKE ?
                OR type LIKE ?
            )";


        $searchValue =
            "%" . $search . "%";


        $types .= "sss";


        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

    }


    $sql .=
        " ORDER BY created_at DESC";


    $stmt =
        $conn->prepare($sql);


    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );

        }


        $stmt->execute();


        $result =
            $stmt->get_result();


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $notifications[] =
                $row;

        }


        $stmt->close();

    }

}


/* =========================================================
   STATISTICS
========================================================= */

$totalNotifications =
    count($notifications);

$unreadNotifications = 0;

$readNotifications = 0;


foreach (
    $notifications
    as $notification
) {

    if (
        isset(
            $notification["is_read"]
        ) &&
        (int)$notification["is_read"] === 1
    ) {

        $readNotifications++;

    }
    else {

        $unreadNotifications++;

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
    Notifications | RealEstate Admin
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

    --blue:#35699a;
    --blue-bg:#edf4fb;

    --orange:#a86618;
    --orange-bg:#fff4df;

    --red:#b43843;
    --red-bg:#fdebed;

}


/* =========================================================
   BODY
========================================================= */

body {

    min-height:100vh;

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

    top:0;
    left:0;
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

    max-width:1250px;

    margin:auto;

    padding:30px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

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
   BUTTON
========================================================= */

.btn {

    height:40px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    padding:0 15px;

    border:0;

    border-radius:7px;

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
   STATS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:15px;

    margin-bottom:20px;

}


.stat {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    padding:18px;

}


.stat-top {

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.stat-icon {

    width:40px;
    height:40px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:9px;

    background:
        #edf2ef;

    font-size:18px;

}


.stat-value {

    margin-top:12px;

    font-size:23px;

    font-weight:800;

}


.stat-label {

    margin-top:4px;

    color:
        var(--muted);

    font-size:7px;

}


/* =========================================================
   TOOLBAR
========================================================= */

.toolbar {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    padding:15px;

    margin-bottom:15px;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

}


.search {

    position:relative;

    flex:1;

}


.search-icon {

    position:absolute;

    left:14px;

    top:12px;

    color:
        var(--muted);

}


.search input {

    width:100%;

    height:40px;

    padding:
        0 12px 0 38px;

    border:
        1px solid
        var(--border);

    border-radius:7px;

    outline:none;

    font-size:8px;

}


.search input:focus {

    border-color:
        var(--primary);

}


.filters {

    display:flex;

    gap:6px;

}


.filter {

    height:37px;

    display:flex;

    align-items:center;

    padding:0 12px;

    border-radius:6px;

    background:#edf1ef;

    color:var(--text);

    text-decoration:none;

    font-size:7px;

    font-weight:700;

}


.filter:hover,
.filter.active {

    background:
        var(--primary);

    color:white;

}


/* =========================================================
   NOTIFICATION LIST
========================================================= */

.notification-list {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

}


.notification {

    display:grid;

    grid-template-columns:
        50px 1fr 100px 70px;

    align-items:center;

    gap:15px;

    padding:18px 20px;

    border-bottom:
        1px solid
        #edf0ee;

    transition:.2s;

}


.notification:last-child {

    border-bottom:none;

}


.notification:hover {

    background:#f8faf9;

}


.notification.unread {

    background:
        #f3f8f5;

}


.notification-icon {

    width:42px;
    height:42px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:10px;

    font-size:17px;

}


.notification-icon.property {

    background:
        var(--blue-bg);

}


.notification-icon.user {

    background:
        var(--green-bg);

}


.notification-icon.enquiry {

    background:
        var(--orange-bg);

}


.notification-icon.system {

    background:
        #f0edf9;

}


.notification-content {

    min-width:0;

}


.notification-title {

    font-size:9px;

    font-weight:800;

}


.notification-message {

    margin-top:5px;

    color:
        var(--muted);

    font-size:7px;

    line-height:1.5;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.notification-date {

    color:
        var(--muted);

    font-size:7px;

}


.status {

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:6px 9px;

    border-radius:20px;

    font-size:6px;

    font-weight:800;

}


.status.unread {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


.status.read {

    background:
        var(--green-bg);

    color:
        var(--green);

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:70px 20px;

    text-align:center;

}


.empty-icon {

    width:65px;
    height:65px;

    display:flex;

    align-items:center;

    justify-content:center;

    margin:
        0 auto 15px;

    border-radius:50%;

    background:#edf1ef;

    font-size:28px;

}


.empty h3 {

    font-size:13px;

}


.empty p {

    margin-top:7px;

    color:
        var(--muted);

    font-size:8px;

}


/* =========================================================
   SETUP
========================================================= */

.setup {

    margin-top:20px;

    padding:20px;

    background:#fff8e8;

    border:
        1px solid
        #f0dfae;

    border-radius:9px;

    color:#735b18;

    font-size:8px;

    line-height:1.7;

}


.setup code {

    display:block;

    margin-top:10px;

    padding:12px;

    background:#fffdf6;

    border-radius:6px;

    overflow:auto;

    font-family:monospace;

    font-size:7px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px) {

    .notification {

        grid-template-columns:
            45px 1fr 75px;

    }

    .notification-date {

        display:none;

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


    .stats {

        grid-template-columns:1fr;

    }


    .toolbar {

        flex-direction:column;

        align-items:stretch;

    }


    .filters {

        overflow-x:auto;

    }

}


@media(max-width:550px) {

    .content {

        padding:20px 14px;

    }


    .page-header {

        align-items:flex-start;

        flex-direction:column;

    }


    .notification {

        grid-template-columns:
            42px 1fr;

    }


    .status {

        display:none;

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


<a
    href="notifications.php"
    class="active"
>

    <span class="icon">🔔</span>
    <span>Notifications</span>

</a>


<a href="reports.php">

    <span class="icon">📈</span>
    <span>Reports</span>

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


<div>

<h1>
    Notifications
</h1>

<p>
    Stay updated with important platform activity
</p>

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
     HEADER
========================================================= -->

<div class="page-header">


<div>

<h2>
    Notification Center
</h2>

<p>
    Monitor property, user, enquiry and system activity.
</p>

</div>


<form
    method="POST"
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


<button
    type="submit"
    name="mark_all_read"
    class="btn btn-primary"
>
    ✓ Mark All as Read
</button>


</form>


</div>


<!-- =====================================================
     STATS
========================================================= -->

<div class="stats">


<div class="stat">

<div class="stat-top">

<div class="stat-icon">
    🔔
</div>

</div>


<div class="stat-value">

<?php
echo number_format(
    $totalNotifications
);
?>

</div>


<div class="stat-label">
    Total Notifications
</div>

</div>


<div class="stat">

<div class="stat-top">

<div class="stat-icon">
    📬
</div>

</div>


<div class="stat-value">

<?php
echo number_format(
    $unreadNotifications
);
?>

</div>


<div class="stat-label">
    Unread Notifications
</div>

</div>


<div class="stat">

<div class="stat-top">

<div class="stat-icon">
    ✓
</div>

</div>


<div class="stat-value">

<?php
echo number_format(
    $readNotifications
);
?>

</div>


<div class="stat-label">
    Read Notifications
</div>

</div>


</div>


<!-- =====================================================
     TOOLBAR
========================================================= -->

<form
    method="GET"
    class="toolbar"
>


<div class="search">

<span class="search-icon">
    🔍
</span>


<input
    type="search"
    name="search"
    placeholder="Search notifications..."
    value="<?php
        echo safe($search);
    ?>"
>

</div>


<div class="filters">


<a
    href="notifications.php"
    class="filter <?php
        echo $filter === "all"
            ? "active"
            : "";
    ?>"
>
    All
</a>


<a
    href="notifications.php?filter=unread"
    class="filter <?php
        echo $filter === "unread"
            ? "active"
            : "";
    ?>"
>
    Unread
</a>


<a
    href="notifications.php?filter=read"
    class="filter <?php
        echo $filter === "read"
            ? "active"
            : "";
    ?>"
>
    Read
</a>


</div>


</form>


<!-- =====================================================
     NOTIFICATION LIST
========================================================= -->

<div class="notification-list">


<?php if (!$tableExists): ?>


<div class="empty">

<div class="empty-icon">
    🔔
</div>


<h3>
    Notifications table not found
</h3>


<p>
    Create the notifications table to activate this module.
</p>


</div>


<?php elseif (empty($notifications)): ?>


<div class="empty">

<div class="empty-icon">
    📭
</div>


<h3>
    No notifications found
</h3>


<p>

<?php

if ($search !== "") {

    echo "No notifications match your search.";

}
elseif ($filter === "unread") {

    echo "There are no unread notifications.";

}
elseif ($filter === "read") {

    echo "There are no read notifications.";

}
else {

    echo "There are currently no notifications.";

}

?>

</p>


</div>


<?php else: ?>


<?php foreach (
    $notifications
    as $notification
):


    $title =
        $notification["title"]
        ?? "Notification";


    $message =
        $notification["message"]
        ?? "";


    $type =
        strtolower(
            $notification["type"]
            ?? "system"
        );


    $createdAt =
        $notification["created_at"]
        ?? "";


    $isRead =
        isset(
            $notification["is_read"]
        ) &&
        (int)$notification["is_read"] === 1;


    switch ($type) {

        case "property":

            $icon = "🏠";

            break;


        case "user":

            $icon = "👤";

            break;


        case "enquiry":

            $icon = "📩";

            break;


        default:

            $icon = "⚙️";

            $type = "system";

            break;

    }

?>


<div
    class="notification <?php
        echo !$isRead
            ? "unread"
            : "";
    ?>"
>


<div
    class="notification-icon <?php
        echo safe($type);
    ?>"
>

<?php
echo $icon;
?>

</div>


<div class="notification-content">


<div class="notification-title">

<?php
echo safe($title);
?>

</div>


<div class="notification-message">

<?php
echo safe($message);
?>

</div>


</div>


<div class="notification-date">

<?php
echo safe($createdAt);
?>

</div>


<div>


<span
    class="status <?php
        echo $isRead
            ? "read"
            : "unread";
    ?>"
>

<?php

echo $isRead
    ? "Read"
    : "Unread";

?>

</span>


</div>


</div>


<?php endforeach; ?>


<?php endif; ?>


</div>


<?php if (!$tableExists): ?>


<div class="setup">

<strong>
    Database setup required
</strong>

<p>
    Create the <strong>notifications</strong> table
    in your RealEstate database.
</p>


<code>

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

</code>


</div>


<?php endif; ?>


</main>


</div>


</body>

</html>