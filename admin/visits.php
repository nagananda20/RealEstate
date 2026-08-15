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
   SEARCH / FILTERS
========================================================= */

$search =
    trim(
        $_GET["search"] ?? ""
    );

$status =
    trim(
        $_GET["status"] ?? ""
    );

$date =
    trim(
        $_GET["date"] ?? ""
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

    $visits = [];

} else {


    /* =====================================================
       GET COLUMNS
    ===================================================== */

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


    /* =====================================================
       BUILD QUERY
    ===================================================== */

    $select = "v.*";


    if ($hasPropertyId) {

        $select .= ",
            p.title AS property_title,
            p.location AS property_location
        ";

    }


    if ($hasUserId) {

        $select .= ",
            u.name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone
        ";

    }


    if ($hasAgentId) {

        $select .= ",
            a.name AS agent_name
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


    $where = [];

    $params = [];

    $types = "";


    /* =====================================================
       SEARCH
    ===================================================== */

    if ($search !== "") {

        $searchParts = [];


        if (
            in_array(
                "name",
                $columns,
                true
            )
        ) {

            $searchParts[] =
                "v.name LIKE ?";

        }


        if (
            in_array(
                "email",
                $columns,
                true
            )
        ) {

            $searchParts[] =
                "v.email LIKE ?";

        }


        if (
            in_array(
                "phone",
                $columns,
                true
            )
        ) {

            $searchParts[] =
                "v.phone LIKE ?";

        }


        if ($hasPropertyId) {

            $searchParts[] =
                "p.title LIKE ?";

        }


        if (!empty($searchParts)) {

            $where[] =
                "(" .
                implode(
                    " OR ",
                    $searchParts
                ) .
                ")";


            foreach (
                $searchParts as $part
            ) {

                $params[] =
                    "%" . $search . "%";

                $types .= "s";

            }

        }

    }


    /* =====================================================
       STATUS
    ===================================================== */

    if (
        $status !== "" &&
        in_array(
            "status",
            $columns,
            true
        )
    ) {

        $where[] =
            "v.status = ?";

        $params[] =
            $status;

        $types .= "s";

    }


    /* =====================================================
       DATE
    ===================================================== */

    if (
        $date !== "" &&
        in_array(
            "visit_date",
            $columns,
            true
        )
    ) {

        $where[] =
            "v.visit_date = ?";

        $params[] =
            $date;

        $types .= "s";

    }


    if (!empty($where)) {

        $sql .=
            " WHERE " .
            implode(
                " AND ",
                $where
            );

    }


    /* =====================================================
       SORT
    ===================================================== */

    if (
        in_array(
            "visit_date",
            $columns,
            true
        )
    ) {

        $sql .=
            " ORDER BY
              v.visit_date DESC";

        if (
            in_array(
                "visit_time",
                $columns,
                true
            )
        ) {

            $sql .=
                ", v.visit_time ASC";

        }

    } else {

        $sql .=
            " ORDER BY v.id DESC";

    }


    $sql .=
        " LIMIT 200";


    /* =====================================================
       EXECUTE
    ===================================================== */

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

        $visits = [];


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $visits[] =
                $row;

        }


        $stmt->close();

    } else {

        $visits = [];

    }

}


/* =========================================================
   STATUS COUNTS
========================================================= */

$totalVisits =
    count($visits);

$pendingVisits = 0;
$confirmedVisits = 0;
$completedVisits = 0;
$cancelledVisits = 0;


foreach (
    $visits as $visit
) {

    $visitStatus =
        strtolower(
            $visit["status"]
            ?? "pending"
        );


    if (
        $visitStatus === "pending"
    ) {

        $pendingVisits++;

    }


    if (
        $visitStatus === "confirmed"
    ) {

        $confirmedVisits++;

    }


    if (
        $visitStatus === "completed"
    ) {

        $completedVisits++;

    }


    if (
        $visitStatus === "cancelled"
    ) {

        $cancelledVisits++;

    }

}


/* =========================================================
   FLASH MESSAGES
========================================================= */

$success =
    $_SESSION["success"]
    ?? "";

$error =
    $_SESSION["error"]
    ?? "";


unset(
    $_SESSION["success"],
    $_SESSION["error"]
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
    Visit Management | RealEstate
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

    padding:
        28px 30px 60px;

    max-width:1450px;

    margin:auto;

}


/* =========================================================
   HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:flex-end;

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


.add-btn {

    height:40px;

    display:flex;

    align-items:center;

    gap:7px;

    padding:
        0 15px;

    background:
        var(--primary);

    color:white;

    text-decoration:none;

    border-radius:6px;

    font-size:8px;

    font-weight:700;

}


/* =========================================================
   FLASH
========================================================= */

.alert {

    padding:
        13px 16px;

    border-radius:7px;

    margin-bottom:15px;

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
   STAT CARDS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:14px;

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


.stat-top {

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.stat-icon {

    width:35px;

    height:35px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:7px;

    background:#f0f3f1;

}


.stat-number {

    margin-top:13px;

    font-size:21px;

    font-weight:800;

}


.stat-label {

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   FILTER
========================================================= */

.filter-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

    padding:15px;

    margin-bottom:18px;

}


.filters {

    display:grid;

    grid-template-columns:
        2fr 1fr 1fr auto auto;

    gap:8px;

}


input,
select {

    height:39px;

    width:100%;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    padding:
        0 11px;

    background:white;

    color:var(--text);

    outline:none;

    font-family:inherit;

    font-size:8px;

}


input:focus,
select:focus {

    border-color:
        var(--primary);

}


.filter-btn {

    height:39px;

    padding:
        0 15px;

    border:none;

    border-radius:6px;

    background:
        var(--primary);

    color:white;

    cursor:pointer;

    font-size:8px;

    font-weight:700;

}


.clear-btn {

    height:39px;

    padding:
        0 14px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    color:var(--text);

    text-decoration:none;

    font-size:8px;

}


/* =========================================================
   TABLE CARD
========================================================= */

.table-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

    overflow:hidden;

}


.table-header {

    padding:
        16px 18px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    border-bottom:
        1px solid
        #edf0ee;

}


.table-title {

    font-size:10px;

    font-weight:800;

}


.table-count {

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {

    width:100%;

    overflow-x:auto;

}


table {

    width:100%;

    min-width:900px;

    border-collapse:collapse;

}


thead {

    background:#fafbfa;

}


th {

    padding:
        12px 15px;

    text-align:left;

    color:var(--muted);

    font-size:7px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.5px;

    border-bottom:
        1px solid
        var(--border);

}


td {

    padding:
        14px 15px;

    font-size:8px;

    border-bottom:
        1px solid
        #edf0ee;

    vertical-align:middle;

}


tbody tr:hover {

    background:#fafcfa;

}


tbody tr:last-child td {

    border-bottom:none;

}


/* =========================================================
   CUSTOMER
========================================================= */

.customer {

    display:flex;

    align-items:center;

    gap:9px;

}


.avatar {

    width:32px;

    height:32px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        var(--primary);

    color:white;

    border-radius:50%;

    font-size:9px;

    font-weight:800;

}


.customer-name {

    font-weight:700;

}


.customer-contact {

    margin-top:3px;

    color:var(--muted);

    font-size:6px;

}


/* =========================================================
   PROPERTY
========================================================= */

.property-title {

    font-weight:700;

}


.property-location {

    margin-top:4px;

    color:var(--muted);

    font-size:6px;

}


/* =========================================================
   DATE
========================================================= */

.visit-date {

    font-weight:700;

}


.visit-time {

    margin-top:4px;

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
        6px 9px;

    border-radius:20px;

    font-size:6px;

    font-weight:700;

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
   ACTIONS
========================================================= */

.actions {

    display:flex;

    align-items:center;

    gap:5px;

}


.action {

    width:30px;

    height:30px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:5px;

    background:#f0f3f1;

    color:var(--text);

    text-decoration:none;

    font-size:9px;

}


.action:hover {

    background:
        var(--primary);

    color:white;

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:
        70px 20px;

    text-align:center;

}


.empty-icon {

    font-size:40px;

    opacity:.5;

}


.empty h3 {

    margin-top:12px;

    font-size:12px;

}


.empty p {

    margin-top:7px;

    color:var(--muted);

    font-size:8px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .filters {

        grid-template-columns:
            1fr 1fr;

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


@media(max-width:550px) {

    .stats {

        grid-template-columns:1fr;

    }


    .filters {

        grid-template-columns:1fr;

    }


    .page-header {

        align-items:flex-start;

        flex-direction:column;

        gap:12px;

    }


    .add-btn {

        width:100%;

        justify-content:center;

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


<div>

<h1>
    Property Visits
</h1>

<p>
    Manage property viewing appointments
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
    Visit Management
</h2>

<p>
    Schedule, monitor and manage property visits.
</p>

</div>


<a
    href="visit-add.php"
    class="add-btn"
>
    + Schedule Visit
</a>


</div>


<!-- =====================================================
     FLASH
========================================================= -->

<?php if (
    $success !== ""
): ?>

<div
    class="alert alert-success"
    id="successAlert"
>

✓
<?php
echo safe($success);
?>

</div>

<?php endif; ?>


<?php if (
    $error !== ""
): ?>

<div class="alert alert-error">

⚠
<?php
echo safe($error);
?>

</div>

<?php endif; ?>


<!-- =====================================================
     STATS
========================================================= -->

<section class="stats">


<div class="stat">

<div class="stat-top">

<span>
    Total Visits
</span>

<div class="stat-icon">
    📅
</div>

</div>


<div class="stat-number">
    <?php echo $totalVisits; ?>
</div>


<div class="stat-label">
    All appointments
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Pending
</span>

<div class="stat-icon">
    ⏳
</div>

</div>


<div class="stat-number">
    <?php echo $pendingVisits; ?>
</div>


<div class="stat-label">
    Awaiting confirmation
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Confirmed
</span>

<div class="stat-icon">
    ✓
</div>

</div>


<div class="stat-number">
    <?php echo $confirmedVisits; ?>
</div>


<div class="stat-label">
    Upcoming visits
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Completed
</span>

<div class="stat-icon">
    🏆
</div>

</div>


<div class="stat-number">
    <?php echo $completedVisits; ?>
</div>


<div class="stat-label">
    Finished visits
</div>

</div>


</section>


<!-- =====================================================
     FILTER
========================================================= -->

<section class="filter-card">


<form
    method="GET"
    class="filters"
>


<input
    type="text"
    name="search"
    value="<?php echo safe($search); ?>"
    placeholder="Search customer, phone or property..."
>


<select name="status">

<option value="">
    All Status
</option>

<option
    value="pending"
    <?php
    echo $status === "pending"
        ? "selected"
        : "";
    ?>
>
    Pending
</option>

<option
    value="confirmed"
    <?php
    echo $status === "confirmed"
        ? "selected"
        : "";
    ?>
>
    Confirmed
</option>

<option
    value="completed"
    <?php
    echo $status === "completed"
        ? "selected"
        : "";
    ?>
>
    Completed
</option>

<option
    value="cancelled"
    <?php
    echo $status === "cancelled"
        ? "selected"
        : "";
    ?>
>
    Cancelled
</option>

</select>


<input
    type="date"
    name="date"
    value="<?php echo safe($date); ?>"
>


<button
    type="submit"
    class="filter-btn"
>
    🔍 Search
</button>


<a
    href="visits.php"
    class="clear-btn"
>
    Clear
</a>


</form>


</section>


<!-- =====================================================
     TABLE
========================================================= -->

<section class="table-card">


<div class="table-header">


<div class="table-title">
    Property Visits
</div>


<div class="table-count">

<?php
echo count($visits);
?>
 appointments found

</div>


</div>


<?php if (
    empty($visits)
): ?>


<div class="empty">

<div class="empty-icon">
    📅
</div>


<h3>
    No visits found
</h3>


<p>
    There are no property visit appointments matching your filters.
</p>


</div>


<?php else: ?>


<div class="table-wrapper">


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
    Visit Date
</th>

<th>
    Agent
</th>

<th>
    Status
</th>

<th>
    Actions
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $visits as $visit
): ?>


<?php

$customer =
    $visit["customer_name"]
    ?? $visit["name"]
    ?? "Unknown";

$email =
    $visit["customer_email"]
    ?? $visit["email"]
    ?? "";

$phone =
    $visit["customer_phone"]
    ?? $visit["phone"]
    ?? "";

$property =
    $visit["property_title"]
    ?? "Property #"
    . (
        $visit["property_id"]
        ?? "-"
    );

$location =
    $visit["property_location"]
    ?? "";

$agent =
    $visit["agent_name"]
    ?? "Unassigned";

$visitStatus =
    strtolower(
        $visit["status"]
        ?? "pending"
    );

$visitDate =
    $visit["visit_date"]
    ?? "";

$visitTime =
    $visit["visit_time"]
    ?? "";

$initial =
    strtoupper(
        substr(
            $customer,
            0,
            1
        )
    );

?>


<tr>


<!-- CUSTOMER -->

<td>


<div class="customer">


<div class="avatar">

<?php
echo safe($initial);
?>

</div>


<div>


<div class="customer-name">

<?php
echo safe($customer);
?>

</div>


<div class="customer-contact">

<?php

if ($phone !== "") {

    echo safe($phone);

} elseif ($email !== "") {

    echo safe($email);

} else {

    echo "No contact information";

}

?>

</div>


</div>


</div>


</td>


<!-- PROPERTY -->

<td>


<div class="property-title">

<?php
echo safe($property);
?>

</div>


<?php if (
    $location !== ""
): ?>

<div class="property-location">

📍
<?php
echo safe($location);
?>

</div>

<?php endif; ?>


</td>


<!-- DATE -->

<td>


<div class="visit-date">

<?php

if ($visitDate !== "") {

    $timestamp =
        strtotime(
            $visitDate
        );

    echo safe(
        date(
            "d M Y",
            $timestamp
        )
    );

} else {

    echo "Not scheduled";

}

?>

</div>


<?php if (
    $visitTime !== ""
): ?>

<div class="visit-time">

🕐
<?php
echo safe($visitTime);
?>

</div>

<?php endif; ?>


</td>


<!-- AGENT -->

<td>

<?php
echo safe($agent);
?>

</td>


<!-- STATUS -->

<td>


<span
    class="badge <?php echo safe($visitStatus); ?>"
>

<?php

echo ucfirst(
    safe($visitStatus)
);

?>

</span>


</td>


<!-- ACTIONS -->

<td>


<div class="actions">


<a
    href="visit-details.php?id=<?php echo (int)$visit["id"]; ?>"
    class="action"
    title="View"
>
    👁
</a>


<a
    href="visit-edit.php?id=<?php echo (int)$visit["id"]; ?>"
    class="action"
    title="Edit"
>
    ✏
</a>


<a
    href="visit-delete.php?id=<?php echo (int)$visit["id"]; ?>"
    class="action delete-action"
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


</div>


<?php endif; ?>


</section>


</main>


</div>


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

document
    .querySelectorAll(
        ".delete-action"
    )
    .forEach(
        function(button) {

            button.addEventListener(
                "click",
                function(event) {

                    const confirmed =
                        confirm(
                            "Are you sure you want to delete this visit?"
                        );


                    if (!confirmed) {

                        event.preventDefault();

                    }

                }
            );

        }
    );


/* =========================================================
   AUTO HIDE SUCCESS
========================================================= */

const successAlert =
    document.getElementById(
        "successAlert"
    );


if (successAlert) {

    setTimeout(
        function() {

            successAlert.style.transition =
                "opacity .5s ease";

            successAlert.style.opacity =
                "0";

        },
        4000
    );

}


/* =========================================================
   ROW ANIMATION
========================================================= */

document
    .querySelectorAll(
        "tbody tr"
    )
    .forEach(
        function(row, index) {

            row.style.opacity =
                "0";

            row.style.transform =
                "translateY(6px)";


            setTimeout(
                function() {

                    row.style.transition =
                        "opacity .3s ease, transform .3s ease";

                    row.style.opacity =
                        "1";

                    row.style.transform =
                        "translateY(0)";

                },
                index * 40
            );

        }
    );

</script>


</body>

</html>