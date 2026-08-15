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
        bin2hex(
            random_bytes(32)
        );
}


/* =========================================================
   ADMIN INFORMATION
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
   SEARCH & FILTER
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


if (
    !in_array(
        $filter,
        $allowedFilters,
        true
    )
) {

    $filter = "all";

}


/* =========================================================
   MESSAGE DATA
========================================================= */

$messages = [];

$tableExists = false;


/*
 * First check whether the messages table exists.
 */

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'messages'"
    );


if (
    $tableCheck
    &&
    $tableCheck->num_rows > 0
) {

    $tableExists = true;

}


/* =========================================================
   GET MESSAGES
========================================================= */

if ($tableExists) {

    $sql =
        "SELECT *
         FROM messages
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
                sender_name LIKE ?
                OR sender_email LIKE ?
                OR subject LIKE ?
                OR message LIKE ?
            )";

        $searchValue =
            "%" . $search . "%";

        $types .= "ssss";

        $params[] =
            $searchValue;

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

            $messages[] =
                $row;

        }


        $stmt->close();

    }

}


/* =========================================================
   STATISTICS
========================================================= */

$totalMessages = count($messages);

$unreadMessages = 0;

$readMessages = 0;


foreach ($messages as $message) {

    if (
        isset(
            $message["is_read"]
        )
        &&
        (int)$message["is_read"] === 1
    ) {

        $readMessages++;

    }
    else {

        $unreadMessages++;

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
    Messages | RealEstate Admin
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
   STATISTICS
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

    width:38px;
    height:38px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:8px;

    background:
        #eef3f0;

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

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    padding:15px;

    margin-bottom:15px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

}


.search {

    flex:1;

    position:relative;

}


.search input {

    width:100%;

    height:42px;

    padding:
        0 14px 0 40px;

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


.search-icon {

    position:absolute;

    left:14px;

    top:13px;

    color:
        var(--muted);

}


.filters {

    display:flex;

    gap:6px;

}


.filter {

    height:38px;

    display:flex;

    align-items:center;

    padding:
        0 12px;

    border-radius:6px;

    background:
        #eef1ef;

    color:
        var(--text);

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
   MESSAGE LIST
========================================================= */

.message-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

}


.message-row {

    display:grid;

    grid-template-columns:
        48px 1.3fr 2fr 110px 80px;

    align-items:center;

    gap:15px;

    padding:
        16px 20px;

    border-bottom:
        1px solid
        #edf0ee;

    transition:.2s;

}


.message-row:last-child {

    border-bottom:none;

}


.message-row:hover {

    background:
        #f8faf9;

}


.message-row.unread {

    background:
        #f2f7f4;

}


.avatar {

    width:42px;
    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        #e3ece7;

    color:
        var(--primary);

    font-size:11px;

    font-weight:800;

}


.sender {

    min-width:0;

}


.sender-name {

    font-size:9px;

    font-weight:800;

}


.sender-email {

    margin-top:4px;

    color:
        var(--muted);

    font-size:7px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.subject {

    min-width:0;

}


.subject-title {

    font-size:9px;

    font-weight:700;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.subject-preview {

    margin-top:5px;

    color:
        var(--muted);

    font-size:7px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.date {

    color:
        var(--muted);

    font-size:7px;

}


.badge {

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:
        6px 9px;

    border-radius:20px;

    font-size:6px;

    font-weight:800;

}


.badge.unread {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


.badge.read {

    background:
        var(--green-bg);

    color:
        var(--green);

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty {

    padding:
        70px 20px;

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

    background:
        #edf1ef;

    font-size:27px;

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
   TABLE NOT FOUND
========================================================= */

.setup {

    margin-top:20px;

    padding:20px;

    background:
        #fff8e8;

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

    background:
        #fffdf6;

    border-radius:6px;

    overflow:auto;

    font-family:
        monospace;

    font-size:7px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1000px) {

    .message-row {

        grid-template-columns:
            48px 1fr 100px;

    }


    .subject {

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

        padding:
            20px 14px;

    }


    .page-header {

        align-items:flex-start;

    }


    .message-row {

        grid-template-columns:
            42px 1fr;

        gap:10px;

    }


    .date,
    .status-column {

        display:none;

    }


    .avatar {

        width:38px;
        height:38px;

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
    href="messages.php"
    class="active"
>

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
    Messages
</h1>

<p>
    Manage conversations and customer messages
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
     PAGE HEADER
========================================================= -->

<div class="page-header">


<div>

<h2>
    Message Center
</h2>

<p>
    Review and manage messages received through the platform.
</p>

</div>


</div>


<!-- =====================================================
     STATISTICS
========================================================= -->

<div class="stats">


<div class="stat">


<div class="stat-top">

<div class="stat-icon">
    💬
</div>

</div>


<div class="stat-value">

<?php
echo number_format(
    $totalMessages
);
?>

</div>


<div class="stat-label">
    Total Messages
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
    $unreadMessages
);
?>

</div>


<div class="stat-label">
    Unread Messages
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
    $readMessages
);
?>

</div>


<div class="stat-label">
    Read Messages
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
    placeholder="Search sender, email, subject or message..."
    value="<?php
        echo safe($search);
    ?>"
>

</div>


<div class="filters">


<a
    href="messages.php"
    class="filter <?php
        echo $filter === "all"
            ? "active"
            : "";
    ?>"
>
    All
</a>


<a
    href="messages.php?filter=unread"
    class="filter <?php
        echo $filter === "unread"
            ? "active"
            : "";
    ?>"
>
    Unread
</a>


<a
    href="messages.php?filter=read"
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
     MESSAGES
========================================================= -->

<div class="message-card">


<?php if (!$tableExists): ?>


<div class="empty">

<div class="empty-icon">
    💬
</div>


<h3>
    Messages table not found
</h3>


<p>
    Create the messages table to enable the Message Center.
</p>


</div>


<?php elseif (empty($messages)): ?>


<div class="empty">

<div class="empty-icon">
    📭
</div>


<h3>
    No messages found
</h3>


<p>

<?php

if ($search !== "") {

    echo "No messages match your search.";

}
elseif ($filter === "unread") {

    echo "There are no unread messages.";

}
elseif ($filter === "read") {

    echo "There are no read messages.";

}
else {

    echo "Your message inbox is currently empty.";

}

?>

</p>


</div>


<?php else: ?>


<?php foreach (
    $messages
    as $message
):


    $senderName =
        $message["sender_name"]
        ?? "Unknown User";


    $senderEmail =
        $message["sender_email"]
        ?? "";


    $subject =
        $message["subject"]
        ?? "No Subject";


    $messageText =
        $message["message"]
        ?? "";


    $createdAt =
        $message["created_at"]
        ?? "";


    $isRead =
        isset(
            $message["is_read"]
        )
        &&
        (int)$message["is_read"] === 1;


    $initial =
        strtoupper(
            substr(
                $senderName,
                0,
                1
            )
        );

?>


<div
    class="message-row <?php
        echo !$isRead
            ? "unread"
            : "";
    ?>"
>


<div class="avatar">

<?php
echo safe($initial);
?>

</div>


<div class="sender">


<div class="sender-name">

<?php
echo safe($senderName);
?>

</div>


<div class="sender-email">

<?php
echo safe($senderEmail);
?>

</div>


</div>


<div class="subject">


<div class="subject-title">

<?php
echo safe($subject);
?>

</div>


<div class="subject-preview">

<?php

echo safe(
    mb_substr(
        $messageText,
        0,
        100
    )
);

?>

</div>


</div>


<div class="date">

<?php
echo safe($createdAt);
?>

</div>


<div class="status-column">


<span
    class="badge <?php
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
    Your current database does not contain a
    <strong>messages</strong> table.
    Create one using this SQL:
</p>


<code>

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
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