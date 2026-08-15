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
   DATE FILTER
========================================================= */

$fromDate =
    $_GET["from"]
    ?? date("Y-m-01");

$toDate =
    $_GET["to"]
    ?? date("Y-m-d");


/* =========================================================
   DEFAULT STATISTICS
========================================================= */

$totalProperties = 0;
$totalUsers = 0;
$totalAgents = 0;
$totalEnquiries = 0;

$totalRevenue = 0;

$propertyStats = [];
$enquiryStats = [];
$userStats = [];


/* =========================================================
   SAFE TABLE CHECK
========================================================= */

function tableExists($conn, $table)
{
    $result =
        $conn->query(
            "SHOW TABLES LIKE '" .
            $conn->real_escape_string($table) .
            "'"
        );

    return (
        $result &&
        $result->num_rows > 0
    );
}


/* =========================================================
   PROPERTY STATISTICS
========================================================= */

if (tableExists($conn, "properties")) {

    $result =
        $conn->query(
            "SELECT COUNT(*) AS total
             FROM properties"
        );

    if ($result) {

        $row =
            $result->fetch_assoc();

        $totalProperties =
            (int)($row["total"] ?? 0);

    }


    /*
     * Property status distribution
     */

    $result =
        $conn->query(
            "SELECT
                status,
                COUNT(*) AS total
             FROM properties
             GROUP BY status"
        );


    if ($result) {

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $propertyStats[] =
                $row;

        }

    }

}


/* =========================================================
   USER STATISTICS
========================================================= */

if (tableExists($conn, "users")) {

    $result =
        $conn->query(
            "SELECT COUNT(*) AS total
             FROM users"
        );

    if ($result) {

        $row =
            $result->fetch_assoc();

        $totalUsers =
            (int)($row["total"] ?? 0);

    }


    /*
     * User registration statistics
     */

    $stmt =
        $conn->prepare(
            "SELECT
                DATE(created_at) AS report_date,
                COUNT(*) AS total
             FROM users
             WHERE DATE(created_at)
             BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY report_date"
        );


    if ($stmt) {

        $stmt->bind_param(
            "ss",
            $fromDate,
            $toDate
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $userStats[] =
                $row;

        }


        $stmt->close();

    }

}


/* =========================================================
   AGENT STATISTICS
========================================================= */

if (tableExists($conn, "agents")) {

    $result =
        $conn->query(
            "SELECT COUNT(*) AS total
             FROM agents"
        );

    if ($result) {

        $row =
            $result->fetch_assoc();

        $totalAgents =
            (int)($row["total"] ?? 0);

    }

}


/* =========================================================
   ENQUIRY STATISTICS
========================================================= */

if (tableExists($conn, "enquiries")) {

    $result =
        $conn->query(
            "SELECT COUNT(*) AS total
             FROM enquiries
             WHERE DATE(created_at)
             BETWEEN '" .
             $conn->real_escape_string($fromDate) .
             "' AND '" .
             $conn->real_escape_string($toDate) .
             "'"
        );


    if ($result) {

        $row =
            $result->fetch_assoc();

        $totalEnquiries =
            (int)($row["total"] ?? 0);

    }


    /*
     * Enquiry status
     */

    $result =
        $conn->query(
            "SELECT
                status,
                COUNT(*) AS total
             FROM enquiries
             WHERE DATE(created_at)
             BETWEEN '" .
             $conn->real_escape_string($fromDate) .
             "' AND '" .
             $conn->real_escape_string($toDate) .
             "'
             GROUP BY status"
        );


    if ($result) {

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $enquiryStats[] =
                $row;

        }

    }

}


/* =========================================================
   REVENUE
========================================================= */

if (tableExists($conn, "payments")) {

    $result =
        $conn->query(
            "SELECT
                COALESCE(
                    SUM(amount),
                    0
                ) AS revenue
             FROM payments
             WHERE DATE(created_at)
             BETWEEN '" .
             $conn->real_escape_string($fromDate) .
             "' AND '" .
             $conn->real_escape_string($toDate) .
             "'"
        );


    if ($result) {

        $row =
            $result->fetch_assoc();

        $totalRevenue =
            (float)(
                $row["revenue"]
                ?? 0
            );

    }

}


/* =========================================================
   PROPERTY STATUS DATA FOR JS
========================================================= */

$propertyLabels = [];
$propertyValues = [];


foreach (
    $propertyStats
    as $item
) {

    $propertyLabels[] =
        $item["status"]
        ?: "Unknown";

    $propertyValues[] =
        (int)$item["total"];

}


/* =========================================================
   ENQUIRY DATA FOR JS
========================================================= */

$enquiryLabels = [];
$enquiryValues = [];


foreach (
    $enquiryStats
    as $item
) {

    $enquiryLabels[] =
        $item["status"]
        ?: "Unknown";

    $enquiryValues[] =
        (int)$item["total"];

}


/* =========================================================
   USER CHART DATA
========================================================= */

$userLabels = [];
$userValues = [];


foreach (
    $userStats
    as $item
) {

    $userLabels[] =
        $item["report_date"];

    $userValues[] =
        (int)$item["total"];

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
    Reports | RealEstate Admin
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

}


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

    color:
        var(--accent);

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

    max-width:1300px;

    margin:auto;

    padding:30px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:20px;

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
   FILTER
========================================================= */

.date-filter {

    display:flex;

    align-items:flex-end;

    gap:8px;

    padding:15px;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

}


.field {

    display:flex;

    flex-direction:column;

    gap:5px;

}


.field label {

    color:
        var(--muted);

    font-size:7px;

    font-weight:700;

}


.field input {

    height:38px;

    padding:
        0 10px;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    outline:none;

    font-size:8px;

}


.field input:focus {

    border-color:
        var(--primary);

}


.btn {

    height:38px;

    padding:
        0 14px;

    border:none;

    border-radius:6px;

    background:
        var(--primary);

    color:white;

    cursor:pointer;

    font-size:7px;

    font-weight:800;

}


.btn:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   STAT CARDS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:13px;

    margin-bottom:20px;

}


.stat {

    padding:17px;

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:9px;

}


.stat-icon {

    width:36px;
    height:36px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:8px;

    background:
        #edf2ef;

    font-size:17px;

}


.stat-value {

    margin-top:11px;

    font-size:20px;

    font-weight:800;

}


.stat-label {

    margin-top:4px;

    color:
        var(--muted);

    font-size:7px;

}


/* =========================================================
   CHART GRID
========================================================= */

.chart-grid {

    display:grid;

    grid-template-columns:
        1.5fr 1fr;

    gap:18px;

    margin-bottom:18px;

}


.chart-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    padding:20px;

}


.chart-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:18px;

}


.chart-header h3 {

    font-size:11px;

}


.chart-header span {

    color:
        var(--muted);

    font-size:7px;

}


.chart-container {

    position:relative;

    height:280px;

}


.chart-container canvas {

    width:100% !important;

    height:100% !important;

}


/* =========================================================
   REPORT TABLE
========================================================= */

.table-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

}


.table-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:18px 20px;

    border-bottom:
        1px solid
        var(--border);

}


.table-header h3 {

    font-size:11px;

}


.table-header span {

    color:
        var(--muted);

    font-size:7px;

}


table {

    width:100%;

    border-collapse:collapse;

}


th {

    padding:
        12px 20px;

    background:
        #f7f9f8;

    color:
        var(--muted);

    text-align:left;

    font-size:7px;

    text-transform:uppercase;

}


td {

    padding:
        14px 20px;

    border-top:
        1px solid
        #edf0ee;

    font-size:8px;

}


.badge {

    display:inline-flex;

    padding:
        5px 9px;

    border-radius:20px;

    background:
        #edf2ef;

    font-size:6px;

    font-weight:800;

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


@media(max-width:900px) {

    .chart-grid {

        grid-template-columns:1fr;

    }

    .page-header {

        flex-direction:column;

        align-items:stretch;

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

        grid-template-columns:
            repeat(2,1fr);

    }


    .date-filter {

        flex-wrap:wrap;

    }

}


@media(max-width:550px) {

    .content {

        padding:
            20px 14px;

    }


    .stats {

        grid-template-columns:1fr;

    }


    .date-filter {

        display:grid;

        grid-template-columns:1fr;

    }


    .field input,
    .btn {

        width:100%;

    }


    .table-card {

        overflow-x:auto;

    }


    table {

        min-width:500px;

    }

}

</style>


<!-- =====================================================
     CHART.JS
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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


<a
    href="reports.php"
    class="active"
>

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
    Reports
</h1>

<p>
    RealEstate analytics and performance reports
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
    Analytics Dashboard
</h2>

<p>
    Analyze your RealEstate platform performance.
</p>

</div>


<form
    method="GET"
    class="date-filter"
>


<div class="field">

<label>
    FROM
</label>

<input
    type="date"
    name="from"
    value="<?php
        echo safe($fromDate);
    ?>"
>

</div>


<div class="field">

<label>
    TO
</label>

<input
    type="date"
    name="to"
    value="<?php
        echo safe($toDate);
    ?>"
>

</div>


<button
    type="submit"
    class="btn"
>
    Apply
</button>


</form>


</div>


<!-- =====================================================
     STATISTICS
========================================================= -->

<div class="stats">


<div class="stat">

<div class="stat-icon">
    🏠
</div>


<div class="stat-value">

<?php
echo number_format(
    $totalProperties
);
?>

</div>


<div class="stat-label">
    Total Properties
</div>

</div>


<div class="stat">

<div class="stat-icon">
    👥
</div>


<div class="stat-value">

<?php
echo number_format(
    $totalUsers
);
?>

</div>


<div class="stat-label">
    Registered Users
</div>

</div>


<div class="stat">

<div class="stat-icon">
    🧑‍💼
</div>


<div class="stat-value">

<?php
echo number_format(
    $totalAgents
);
?>

</div>


<div class="stat-label">
    Active Agents
</div>

</div>


<div class="stat">

<div class="stat-icon">
    📩
</div>


<div class="stat-value">

<?php
echo number_format(
    $totalEnquiries
);
?>

</div>


<div class="stat-label">
    Enquiries
</div>

</div>


<div class="stat">

<div class="stat-icon">
    ₹
</div>


<div class="stat-value">

₹<?php
echo number_format(
    $totalRevenue,
    2
);
?>

</div>


<div class="stat-label">
    Revenue
</div>

</div>


</div>


<!-- =====================================================
     CHARTS
========================================================= -->

<div class="chart-grid">


<!-- PROPERTY CHART -->

<div class="chart-card">


<div class="chart-header">

<h3>
    Property Status
</h3>

<span>
    Current distribution
</span>

</div>


<div class="chart-container">

<canvas
    id="propertyChart"
></canvas>

</div>


</div>


<!-- ENQUIRY CHART -->

<div class="chart-card">


<div class="chart-header">

<h3>
    Enquiry Status
</h3>

<span>
    Selected period
</span>

</div>


<div class="chart-container">

<canvas
    id="enquiryChart"
></canvas>

</div>


</div>


</div>


<!-- =====================================================
     USER REGISTRATION CHART
========================================================= -->

<div class="chart-card"
     style="margin-bottom:18px;">


<div class="chart-header">

<h3>
    User Registration Trend
</h3>

<span>

<?php
echo safe($fromDate);
?>

&nbsp; — &nbsp;

<?php
echo safe($toDate);
?>

</span>

</div>


<div class="chart-container">

<canvas
    id="userChart"
></canvas>

</div>


</div>


<!-- =====================================================
     PROPERTY STATUS TABLE
========================================================= -->

<div class="table-card">


<div class="table-header">

<h3>
    Property Status Report
</h3>

<span>
    All properties
</span>

</div>


<table>


<thead>

<tr>

<th>
    Status
</th>

<th>
    Properties
</th>

<th>
    Percentage
</th>

</tr>

</thead>


<tbody>


<?php

$totalPropertyCount =
    max(
        $totalProperties,
        1
    );


if (
    empty(
        $propertyStats
    )
):

?>


<tr>

<td colspan="3"
    style="
        text-align:center;
        color:#737c78;
    "
>

No property status data available.

</td>

</tr>


<?php else: ?>


<?php foreach (
    $propertyStats
    as $item
):


    $count =
        (int)$item["total"];


    $percentage =
        ($count /
        $totalPropertyCount)
        * 100;

?>


<tr>


<td>

<span class="badge">

<?php
echo safe(
    $item["status"]
    ?: "Unknown"
);
?>

</span>

</td>


<td>

<?php
echo number_format(
    $count
);
?>

</td>


<td>

<?php
echo number_format(
    $percentage,
    1
);
?>%

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>


</table>


</div>


</main>


</div>


<!-- =====================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   PROPERTY CHART
========================================================= */

const propertyLabels =
    <?php
    echo json_encode(
        $propertyLabels
    );
    ?>;


const propertyValues =
    <?php
    echo json_encode(
        $propertyValues
    );
    ?>;


new Chart(
    document.getElementById(
        "propertyChart"
    ),
    {

        type:"doughnut",

        data: {

            labels:
                propertyLabels,

            datasets: [

                {

                    data:
                        propertyValues,

                    borderWidth:2

                }

            ]

        },

        options: {

            responsive:true,

            maintainAspectRatio:false,

            plugins: {

                legend: {

                    position:"bottom"

                }

            }

        }

    }
);


/* =========================================================
   ENQUIRY CHART
========================================================= */

const enquiryLabels =
    <?php
    echo json_encode(
        $enquiryLabels
    );
    ?>;


const enquiryValues =
    <?php
    echo json_encode(
        $enquiryValues
    );
    ?>;


new Chart(
    document.getElementById(
        "enquiryChart"
    ),
    {

        type:"doughnut",

        data: {

            labels:
                enquiryLabels,

            datasets: [

                {

                    data:
                        enquiryValues,

                    borderWidth:2

                }

            ]

        },

        options: {

            responsive:true,

            maintainAspectRatio:false,

            plugins: {

                legend: {

                    position:"bottom"

                }

            }

        }

    }
);


/* =========================================================
   USER CHART
========================================================= */

const userLabels =
    <?php
    echo json_encode(
        $userLabels
    );
    ?>;


const userValues =
    <?php
    echo json_encode(
        $userValues
    );
    ?>;


new Chart(
    document.getElementById(
        "userChart"
    ),
    {

        type:"line",

        data: {

            labels:
                userLabels,

            datasets: [

                {

                    label:
                        "New Users",

                    data:
                        userValues,

                    tension:.35,

                    fill:true,

                    borderWidth:2

                }

            ]

        },

        options: {

            responsive:true,

            maintainAspectRatio:false,

            scales: {

                y: {

                    beginAtZero:true,

                    ticks: {

                        precision:0

                    }

                }

            },

            plugins: {

                legend: {

                    display:true

                }

            }

        }

    }
);

</script>


</body>

</html>