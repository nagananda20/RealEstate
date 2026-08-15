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
   FILTERS
========================================================= */

$search = trim($_GET["search"] ?? "");

$status = trim($_GET["status"] ?? "all");

$priority = trim($_GET["priority"] ?? "all");


$allowedStatus = [
    "all",
    "new",
    "contacted",
    "qualified",
    "closed"
];


$allowedPriority = [
    "all",
    "low",
    "medium",
    "high"
];


if (!in_array($status, $allowedStatus, true)) {
    $status = "all";
}


if (!in_array($priority, $allowedPriority, true)) {
    $priority = "all";
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

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Enquiries | RealEstate</title>

        <style>

        body {
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f4f6f5;
            font-family:Arial, sans-serif;
        }

        .box {
            max-width:550px;
            padding:35px;
            background:#fff;
            border:1px solid #dfe6e2;
            border-radius:12px;
            text-align:center;
        }

        h2 {
            color:#174a3a;
        }

        p {
            color:#737c78;
            font-size:14px;
            line-height:1.7;
        }

        code {
            background:#f0f2f1;
            padding:4px 8px;
            border-radius:4px;
        }

        a {
            display:inline-block;
            margin-top:15px;
            padding:12px 18px;
            background:#174a3a;
            color:white;
            text-decoration:none;
            border-radius:6px;
        }

        </style>

    </head>

    <body>

    <div class="box">

        <h2>
            Enquiries table not found
        </h2>

        <p>
            Create the <code>enquiries</code> table in your
            RealEstate database before using this page.
        </p>

        <a href="dashboard.php">
            Back to Dashboard
        </a>

    </div>

    </body>

    </html>

    <?php

    exit;
}


/* =========================================================
   CHECK TABLE COLUMNS
========================================================= */

$columns = [];

$columnResult =
    $conn->query(
        "SHOW COLUMNS FROM enquiries"
    );


if ($columnResult) {

    while (
        $column = $columnResult->fetch_assoc()
    ) {

        $columns[] =
            $column["Field"];

    }

}


/* =========================================================
   OPTIONAL COLUMNS
========================================================= */

$hasPriority =
    in_array(
        "priority",
        $columns,
        true
    );


$hasStatus =
    in_array(
        "status",
        $columns,
        true
    );


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


$hasName =
    in_array(
        "name",
        $columns,
        true
    );


$hasEmail =
    in_array(
        "email",
        $columns,
        true
    );


$hasPhone =
    in_array(
        "phone",
        $columns,
        true
    );


$hasMessage =
    in_array(
        "message",
        $columns,
        true
    );


$hasCreatedAt =
    in_array(
        "created_at",
        $columns,
        true
    );


/* =========================================================
   DYNAMIC QUERY
========================================================= */

$select = "
    e.*
";


if ($hasPropertyId) {

    $select .= ",
        p.title AS property_title,
        p.location AS property_location
    ";

}


if ($hasAgentId) {

    $select .= ",
        a.name AS agent_name
    ";

}


if ($hasUserId) {

    $select .= ",
        u.name AS user_name,
        u.email AS user_email
    ";

}


$sql = "
    SELECT
        $select
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
    WHERE 1 = 1
";


$params = [];

$types = "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $searchParts = [];

    if ($hasName) {
        $searchParts[] =
            "e.name LIKE ?";
    }

    if ($hasEmail) {
        $searchParts[] =
            "e.email LIKE ?";
    }

    if ($hasPhone) {
        $searchParts[] =
            "e.phone LIKE ?";
    }

    if ($hasMessage) {
        $searchParts[] =
            "e.message LIKE ?";
    }

    if ($hasPropertyId) {
        $searchParts[] =
            "p.title LIKE ?";
    }


    if (!empty($searchParts)) {

        $sql .= "
            AND (
                " .
                implode(
                    " OR ",
                    $searchParts
                )
                . "
            )
        ";

        $searchValue =
            "%" . $search . "%";


        foreach (
            $searchParts as $unused
        ) {

            $params[] =
                $searchValue;

            $types .= "s";

        }

    }

}


/* =========================================================
   STATUS
========================================================= */

if (
    $hasStatus &&
    $status !== "all"
) {

    $sql .= "
        AND e.status = ?
    ";

    $params[] =
        $status;

    $types .= "s";

}


/* =========================================================
   PRIORITY
========================================================= */

if (
    $hasPriority &&
    $priority !== "all"
) {

    $sql .= "
        AND e.priority = ?
    ";

    $params[] =
        $priority;

    $types .= "s";

}


/* =========================================================
   ORDER
========================================================= */

if ($hasCreatedAt) {

    $sql .= "
        ORDER BY e.created_at DESC
    ";

}
else {

    $sql .= "
        ORDER BY e.id DESC
    ";

}


/* =========================================================
   EXECUTE
========================================================= */

$stmt =
    $conn->prepare($sql);


if (!$stmt) {
    exit(
        "Unable to load enquiries."
    );
}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();


$result =
    $stmt->get_result();


$enquiries = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $enquiries[] =
        $row;

}


$stmt->close();


/* =========================================================
   STATISTICS
========================================================= */

$totalEnquiries = 0;

$newEnquiries = 0;

$contactedEnquiries = 0;

$qualifiedEnquiries = 0;

$closedEnquiries = 0;

$highPriority = 0;


foreach (
    $enquiries as $enquiry
) {

    $totalEnquiries++;


    $currentStatus =
        strtolower(
            $enquiry["status"]
            ?? "new"
        );


    switch ($currentStatus) {

        case "new":
            $newEnquiries++;
            break;

        case "contacted":
            $contactedEnquiries++;
            break;

        case "qualified":
            $qualifiedEnquiries++;
            break;

        case "closed":
            $closedEnquiries++;
            break;

    }


    if (
        strtolower(
            $enquiry["priority"]
            ?? ""
        ) === "high"
    ) {

        $highPriority++;

    }

}


/* =========================================================
   CURRENT ADMIN
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
    Enquiries | RealEstate Admin
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

    padding:
        28px 30px 60px;

    max-width:1500px;

}


/* =========================================================
   STATS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:12px;

    margin-bottom:20px;

}


.stat {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

    padding:17px;

}


.stat-icon {

    width:34px;

    height:34px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:7px;

    background:
        var(--green-bg);

}


.stat-number {

    margin-top:12px;

    font-size:21px;

    font-weight:800;

}


.stat-label {

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   TOOLBAR
========================================================= */

.toolbar {

    display:flex;

    align-items:center;

    gap:8px;

    padding:15px;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

    margin-bottom:15px;

}


.search {

    position:relative;

    flex:1;

}


.search input {

    width:100%;

    height:40px;

    padding:
        0 12px 0 37px;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    outline:none;

    font-size:8px;

}


.search span {

    position:absolute;

    left:12px;

    top:50%;

    transform:
        translateY(-50%);

}


select {

    height:40px;

    padding:0 12px;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    background:white;

    outline:none;

    font-size:8px;

}


.filter {

    height:40px;

    padding:0 16px;

    border:none;

    border-radius:6px;

    background:
        var(--primary);

    color:white;

    font-size:8px;

    font-weight:700;

    cursor:pointer;

}


.clear {

    height:40px;

    display:flex;

    align-items:center;

    padding:0 13px;

    background:#eef1ef;

    border-radius:6px;

    color:var(--text);

    text-decoration:none;

    font-size:8px;

}


/* =========================================================
   TABLE
========================================================= */

.table-wrap {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:auto;

}


table {

    width:100%;

    min-width:1050px;

    border-collapse:collapse;

}


thead {

    background:#fafbfa;

}


th {

    padding:
        13px 15px;

    text-align:left;

    color:var(--muted);

    font-size:7px;

    text-transform:uppercase;

    letter-spacing:.5px;

    border-bottom:
        1px solid
        var(--border);

}


td {

    padding:
        14px 15px;

    border-bottom:
        1px solid
        #edf0ee;

    font-size:8px;

    vertical-align:middle;

}


tr:hover td {

    background:#fcfdfc;

}


.customer {

    display:flex;

    align-items:center;

    gap:9px;

}


.customer-avatar {

    width:34px;

    height:34px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-weight:800;

    font-size:10px;

}


.customer-name {

    font-weight:800;

}


.customer-email {

    margin-top:3px;

    color:var(--muted);

    font-size:7px;

}


.property {

    max-width:180px;

}


.property-title {

    font-weight:700;

}


.property-location {

    margin-top:3px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:inline-flex;

    align-items:center;

    padding:
        5px 8px;

    border-radius:20px;

    font-size:6px;

    font-weight:700;

}


.badge.new {

    color:var(--blue);

    background:
        var(--blue-bg);

}


.badge.contacted {

    color:var(--orange);

    background:
        var(--orange-bg);

}


.badge.qualified {

    color:var(--green);

    background:
        var(--green-bg);

}


.badge.closed {

    color:var(--red);

    background:
        var(--red-bg);

}


.badge.low {

    color:var(--green);

    background:
        var(--green-bg);

}


.badge.medium {

    color:var(--orange);

    background:
        var(--orange-bg);

}


.badge.high {

    color:var(--red);

    background:
        var(--red-bg);

}


/* =========================================================
   AGENT
========================================================= */

.agent {

    color:
        var(--muted);

    font-size:7px;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    max-width:230px;

    color:#555f5a;

    line-height:1.5;

    font-size:7px;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display:flex;

    gap:5px;

}


.action {

    width:31px;

    height:31px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:5px;

    text-decoration:none;

    font-size:11px;

}


.action.view {

    background:
        var(--blue-bg);

}


.action.edit {

    background:
        var(--green-bg);

}


.action.delete {

    background:
        var(--red-bg);

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:70px 20px;

    text-align:center;

}


.empty-icon {

    font-size:40px;

}


.empty h3 {

    margin-top:12px;

    font-size:13px;

}


.empty p {

    margin-top:6px;

    color:var(--muted);

    font-size:8px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(3,1fr);

    }

}


@media(max-width:850px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .toolbar {

        flex-wrap:wrap;

    }


    .search {

        flex-basis:100%;

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

}


@media(max-width:600px) {

    .content {

        padding:
            20px 15px;

    }


    .stats {

        grid-template-columns:
            1fr 1fr;

    }


    .toolbar {

        align-items:stretch;

        flex-direction:column;

    }


    .search,
    select,
    .filter,
    .clear {

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

    <span class="icon">
        📊
    </span>

    <span>
        Dashboard
    </span>

</a>


<a href="properties.php">

    <span class="icon">
        🏠
    </span>

    <span>
        Properties
    </span>

</a>


<a href="users.php">

    <span class="icon">
        👥
    </span>

    <span>
        Users
    </span>

</a>


<a href="agents.php">

    <span class="icon">
        🧑‍💼
    </span>

    <span>
        Agents
    </span>

</a>


<a
    href="enquiries.php"
    class="active"
>

    <span class="icon">
        💬
    </span>

    <span>
        Enquiries
    </span>

</a>


<a href="visits.php">

    <span class="icon">
        📅
    </span>

    <span>
        Visits
    </span>

</a>


<a href="settings.php">

    <span class="icon">
        ⚙️
    </span>

    <span>
        Settings
    </span>

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
    Enquiry Management
</h1>

<p>
    Manage buyer leads and property enquiries
</p>

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
     STATISTICS
========================================================= -->

<section class="stats">


<div class="stat">

<div class="stat-icon">
    💬
</div>

<div class="stat-number">
    <?php
    echo number_format(
        $totalEnquiries
    );
    ?>
</div>

<div class="stat-label">
    Total enquiries
</div>

</div>


<div class="stat">

<div class="stat-icon">
    🆕
</div>

<div class="stat-number">
    <?php
    echo number_format(
        $newEnquiries
    );
    ?>
</div>

<div class="stat-label">
    New enquiries
</div>

</div>


<div class="stat">

<div class="stat-icon">
    📞
</div>

<div class="stat-number">
    <?php
    echo number_format(
        $contactedEnquiries
    );
    ?>
</div>

<div class="stat-label">
    Contacted
</div>

</div>


<div class="stat">

<div class="stat-icon">
    ⭐
</div>

<div class="stat-number">
    <?php
    echo number_format(
        $qualifiedEnquiries
    );
    ?>
</div>

<div class="stat-label">
    Qualified leads
</div>

</div>


<div class="stat">

<div class="stat-icon">
    🔥
</div>

<div class="stat-number">
    <?php
    echo number_format(
        $highPriority
    );
    ?>
</div>

<div class="stat-label">
    High priority
</div>

</div>


</section>


<!-- =====================================================
     FILTER BAR
========================================================= -->

<form
    method="GET"
    class="toolbar"
>


<div class="search">

<span>
    🔍
</span>


<input
    type="search"
    name="search"
    value="<?php echo safe($search); ?>"
    placeholder="Search customer, email, phone, property..."
>

</div>


<select name="status">

<option
    value="all"
    <?php
    echo $status === "all"
        ? "selected"
        : "";
    ?>
>
    All Status
</option>


<option
    value="new"
    <?php
    echo $status === "new"
        ? "selected"
        : "";
    ?>
>
    New
</option>


<option
    value="contacted"
    <?php
    echo $status === "contacted"
        ? "selected"
        : "";
    ?>
>
    Contacted
</option>


<option
    value="qualified"
    <?php
    echo $status === "qualified"
        ? "selected"
        : "";
    ?>
>
    Qualified
</option>


<option
    value="closed"
    <?php
    echo $status === "closed"
        ? "selected"
        : "";
    ?>
>
    Closed
</option>

</select>


<select name="priority">

<option
    value="all"
    <?php
    echo $priority === "all"
        ? "selected"
        : "";
    ?>
>
    All Priority
</option>


<option
    value="low"
    <?php
    echo $priority === "low"
        ? "selected"
        : "";
    ?>
>
    Low
</option>


<option
    value="medium"
    <?php
    echo $priority === "medium"
        ? "selected"
        : "";
    ?>
>
    Medium
</option>


<option
    value="high"
    <?php
    echo $priority === "high"
        ? "selected"
        : "";
    ?>
>
    High
</option>

</select>


<button
    type="submit"
    class="filter"
>
    Filter
</button>


<?php if (
    $search !== "" ||
    $status !== "all" ||
    $priority !== "all"
): ?>

<a
    href="enquiries.php"
    class="clear"
>
    Clear
</a>

<?php endif; ?>


</form>


<!-- =====================================================
     ENQUIRY TABLE
========================================================= -->

<div class="table-wrap">


<?php if (
    empty($enquiries)
): ?>


<div class="empty">


<div class="empty-icon">
    💬
</div>


<h3>
    No enquiries found
</h3>


<p>
    Try changing your search or filters.
</p>


</div>


<?php else: ?>


<table>


<thead>

<tr>

<th>
    Customer
</th>

<th>
    Property
</th>

<th>
    Message
</th>

<th>
    Status
</th>

<th>
    Priority
</th>

<th>
    Agent
</th>

<th>
    Date
</th>

<th>
    Actions
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $enquiries as $enquiry
): ?>


<?php

/* =====================================================
   CUSTOMER DATA
===================================================== */

$customerName =
    $enquiry["name"]
    ?? $enquiry["user_name"]
    ?? "Unknown";


$customerEmail =
    $enquiry["email"]
    ?? $enquiry["user_email"]
    ?? "No email";


$customerPhone =
    $enquiry["phone"]
    ?? "";


$initial =
    strtoupper(
        substr(
            $customerName,
            0,
            1
        )
    );


/* =====================================================
   PROPERTY
===================================================== */

$propertyTitle =
    $enquiry["property_title"]
    ?? "General enquiry";


$propertyLocation =
    $enquiry["property_location"]
    ?? "";


/* =====================================================
   STATUS
===================================================== */

$rowStatus =
    strtolower(
        $enquiry["status"]
        ?? "new"
    );


if (
    !in_array(
        $rowStatus,
        [
            "new",
            "contacted",
            "qualified",
            "closed"
        ],
        true
    )
) {

    $rowStatus =
        "new";

}


/* =====================================================
   PRIORITY
===================================================== */

$rowPriority =
    strtolower(
        $enquiry["priority"]
        ?? "medium"
    );


if (
    !in_array(
        $rowPriority,
        [
            "low",
            "medium",
            "high"
        ],
        true
    )
) {

    $rowPriority =
        "medium";

}


/* =====================================================
   MESSAGE
===================================================== */

$message =
    trim(
        $enquiry["message"]
        ?? ""
    );


if ($message === "") {

    $message =
        "No message provided.";

}


/* =====================================================
   DATE
===================================================== */

$dateText =
    "N/A";


if (
    !empty(
        $enquiry["created_at"]
    )
) {

    $dateText =
        date(
            "d M Y",
            strtotime(
                $enquiry["created_at"]
            )
        );

}


/* =====================================================
   AGENT
===================================================== */

$agentName =
    $enquiry["agent_name"]
    ?? "Unassigned";

?>


<tr>


<!-- CUSTOMER -->

<td>


<div class="customer">


<div class="customer-avatar">

<?php
echo safe($initial);
?>

</div>


<div>


<div class="customer-name">

<?php
echo safe(
    $customerName
);
?>

</div>


<div class="customer-email">

<?php
echo safe(
    $customerEmail
);
?>

</div>


<?php if (
    $customerPhone !== ""
): ?>

<div
    style="
        margin-top:3px;
        color:#737c78;
        font-size:7px;
    "
>

☎
<?php
echo safe(
    $customerPhone
);
?>

</div>

<?php endif; ?>


</div>


</div>


</td>


<!-- PROPERTY -->

<td>


<div class="property">


<div class="property-title">

<?php
echo safe(
    $propertyTitle
);
?>

</div>


<?php if (
    $propertyLocation !== ""
): ?>

<div class="property-location">

📍
<?php
echo safe(
    $propertyLocation
);
?>

</div>

<?php endif; ?>


</div>


</td>


<!-- MESSAGE -->

<td>


<div class="message">

<?php

echo safe(
    mb_strimwidth(
        $message,
        0,
        100,
        "..."
    )
);

?>

</div>


</td>


<!-- STATUS -->

<td>

<span class="badge <?php echo safe($rowStatus); ?>">

<?php

echo ucfirst(
    safe($rowStatus)
);

?>

</span>


</td>


<!-- PRIORITY -->

<td>

<span class="badge <?php echo safe($rowPriority); ?>">

<?php

echo strtoupper(
    safe($rowPriority)
);

?>

</span>


</td>


<!-- AGENT -->

<td>


<div class="agent">

<?php
echo safe(
    $agentName
);
?>

</div>


</td>


<!-- DATE -->

<td>

<?php
echo safe($dateText);
?>

</td>


<!-- ACTIONS -->

<td>


<div class="actions">


<a
    href="enquiry-details.php?id=<?php echo (int)$enquiry["id"]; ?>"
    class="action view"
    title="View"
>
    👁
</a>


<a
    href="enquiry-edit.php?id=<?php echo (int)$enquiry["id"]; ?>"
    class="action edit"
    title="Edit"
>
    ✏
</a>


<a
    href="enquiry-delete.php?id=<?php echo (int)$enquiry["id"]; ?>"
    class="action delete delete-enquiry"
    title="Delete"
>
    🗑
</a>


</div>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


<?php endif; ?>


</div>


</main>


</div>


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteButtons =
    document.querySelectorAll(
        ".delete-enquiry"
    );


deleteButtons.forEach(
    function(button) {

        button.addEventListener(
            "click",
            function(event) {

                const confirmed =
                    confirm(
                        "Are you sure you want to delete this enquiry?"
                    );


                if (!confirmed) {

                    event.preventDefault();

                }

            }
        );

    }
);


/* =========================================================
   TABLE ROW ANIMATION
========================================================= */

const rows =
    document.querySelectorAll(
        "tbody tr"
    );


rows.forEach(
    function(row, index) {

        row.style.opacity = "0";


        setTimeout(
            function() {

                row.style.transition =
                    "opacity .25s ease";

                row.style.opacity =
                    "1";

            },
            index * 40
        );

    }
);


/* =========================================================
   ESCAPE SEARCH
========================================================= */

const search =
    document.querySelector(
        'input[name="search"]'
    );


if (search) {

    search.addEventListener(
        "keydown",
        function(event) {

            if (
                event.key === "Escape"
            ) {

                search.value = "";

            }

        }
    );

}

</script>


</body>

</html>