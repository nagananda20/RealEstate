<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
|
| This page expects:
| $_SESSION["user_id"]
| $_SESSION["user_role"]
|
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$userRole = $_SESSION["user_role"] ?? "";

if ($userRole !== "admin") {
    http_response_code(403);

    echo "
        <h1>403 - Access Denied</h1>
        <p>You do not have permission to access the admin panel.</p>
        <a href='../page/dashboard.php'>Return to Dashboard</a>
    ";

    exit;
}


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| Admin Information
|--------------------------------------------------------------------------
*/

$adminSQL = "
    SELECT
        id,
        name,
        email,
        profile_image
    FROM users
    WHERE id = ?
    LIMIT 1
";

$adminStmt =
    $conn->prepare($adminSQL);

$adminStmt->bind_param(
    "i",
    $userId
);

$adminStmt->execute();

$adminResult =
    $adminStmt->get_result();

$admin =
    $adminResult->fetch_assoc();

$adminStmt->close();


if (!$admin) {

    session_destroy();

    header(
        "Location: ../auth/login.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Platform Statistics
|--------------------------------------------------------------------------
*/

function getCount(
    $conn,
    $sql
) {

    $stmt =
        $conn->prepare($sql);

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $stmt->close();

    return (int)
        ($row["total"] ?? 0);
}


$totalUsers =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM users
        "
    );


$totalProperties =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM properties
        "
    );


$publishedProperties =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM properties
        WHERE status = 'published'
        "
    );


$totalFavorites =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM favorites
        "
    );


$totalEnquiries =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM enquiries
        "
    );


$newEnquiries =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM enquiries
        WHERE status = 'new'
        "
    );


$totalVisits =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM visits
        "
    );


$pendingVisits =
    getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM visits
        WHERE status = 'pending'
        "
    );


/*
|--------------------------------------------------------------------------
| Recent Properties
|--------------------------------------------------------------------------
*/

$propertySQL = "
    SELECT
        p.id,
        p.title,
        p.price,
        p.city,
        p.status,
        p.created_at,

        u.name AS owner_name

    FROM properties p

    LEFT JOIN users u
        ON p.agent_id = u.id

    ORDER BY p.created_at DESC

    LIMIT 6
";

$propertyStmt =
    $conn->prepare($propertySQL);

$propertyStmt->execute();

$propertyResult =
    $propertyStmt->get_result();

$recentProperties = [];

while (
    $row =
    $propertyResult->fetch_assoc()
) {

    $recentProperties[] =
        $row;
}

$propertyStmt->close();


/*
|--------------------------------------------------------------------------
| Recent Enquiries
|--------------------------------------------------------------------------
*/

$enquirySQL = "
    SELECT
        e.id,
        e.name,
        e.phone,
        e.status,
        e.created_at,

        p.title

    FROM enquiries e

    LEFT JOIN properties p
        ON e.property_id = p.id

    ORDER BY e.created_at DESC

    LIMIT 6
";

$enquiryStmt =
    $conn->prepare($enquirySQL);

$enquiryStmt->execute();

$enquiryResult =
    $enquiryStmt->get_result();

$recentEnquiries = [];

while (
    $row =
    $enquiryResult->fetch_assoc()
) {

    $recentEnquiries[] =
        $row;
}

$enquiryStmt->close();


/*
|--------------------------------------------------------------------------
| Recent Visits
|--------------------------------------------------------------------------
*/

$visitSQL = "
    SELECT
        v.id,
        v.visit_date,
        v.visit_time,
        v.status,

        p.title,

        u.name AS user_name

    FROM visits v

    LEFT JOIN properties p
        ON v.property_id = p.id

    LEFT JOIN users u
        ON v.user_id = u.id

    ORDER BY v.created_at DESC

    LIMIT 5
";

$visitStmt =
    $conn->prepare($visitSQL);

$visitStmt->execute();

$visitResult =
    $visitStmt->get_result();

$recentVisits = [];

while (
    $row =
    $visitResult->fetch_assoc()
) {

    $recentVisits[] =
        $row;
}

$visitStmt->close();


/*
|--------------------------------------------------------------------------
| Format Price
|--------------------------------------------------------------------------
*/

function formatPrice($price)
{

    $price =
        (float)$price;

    if ($price >= 10000000) {

        return "₹" .
            number_format(
                $price / 10000000,
                2
            ) .
            " Cr";
    }

    if ($price >= 100000) {

        return "₹" .
            number_format(
                $price / 100000,
                2
            ) .
            " L";
    }

    return "₹" .
        number_format($price);
}


$adminName =
    $admin["name"] ?? "Admin";


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
    Admin Dashboard | RealEstateHub
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

    --muted: #727c77;

    --border: #e0e6e3;

    --success: #17643b;

    --success-bg: #e5f5eb;

    --warning: #996b00;

    --warning-bg: #fff4d9;

    --danger: #ad3541;

    --danger-bg: #fdebed;

    --blue: #315f8c;

    --blue-bg: #e8f0f8;

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


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    width: 240px;

    background:
        var(--primary);

    color:
        white;

    z-index: 200;

    display: flex;

    flex-direction: column;

}


.logo {

    height: 75px;

    display: flex;

    align-items: center;

    padding:
        0 25px;

    border-bottom:
        1px solid
        rgba(255,255,255,.1);

    color: white;

    text-decoration: none;

    font-size: 20px;

    font-weight: 800;

}

.logo strong {

    color:
        var(--accent);

}


.admin-label {

    padding:
        20px 25px 8px;

    font-size: 8px;

    text-transform:
        uppercase;

    letter-spacing:
        1.5px;

    color:
        rgba(255,255,255,.45);

}


.menu {

    padding:
        0 12px;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    height: 45px;

    padding:
        0 13px;

    border-radius:
        7px;

    color:
        rgba(255,255,255,.72);

    text-decoration:
        none;

    font-size:
        10px;

    margin-bottom:
        3px;

    transition:
        .2s;

}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.1);

    color:
        white;

}


.menu-icon {

    width:
        20px;

    text-align:
        center;

    font-size:
        15px;

}


.sidebar-bottom {

    margin-top:
        auto;

    padding:
        15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

}


.admin-profile {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        8px;

}


.admin-avatar {

    width:
        35px;

    height:
        35px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.15);

    display:
        flex;

    align-items: center;

    justify-content:
        center;

    font-size:
        12px;

    font-weight:
        800;

    overflow:
        hidden;

}


.admin-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.admin-info {

    min-width:
        0;

}


.admin-info strong {

    display:
        block;

    font-size:
        10px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

}


.admin-info span {

    display:
        block;

    font-size:
        8px;

    color:
        rgba(255,255,255,.5);

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left:
        240px;

    min-height:
        100vh;

}


/* =====================================================
   TOPBAR
===================================================== */

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

    top:
        0;

    z-index:
        100;

}


.topbar h1 {

    font-size:
        20px;

}


.topbar p {

    color:
        var(--muted);

    font-size:
        9px;

    margin-top:
        4px;

}


.top-actions {

    display:
        flex;

    gap:
        8px;

}


.top-button {

    height:
        36px;

    padding:
        0 13px;

    border:
        1px solid
        var(--border);

    background:
        white;

    border-radius:
        6px;

    color:
        var(--text);

    text-decoration:
        none;

    font-size:
        9px;

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

}


.top-button.primary {

    background:
        var(--primary);

    color:
        white;

    border-color:
        var(--primary);

}


/* =====================================================
   CONTENT
===================================================== */

.content {

    padding:
        28px 30px 60px;

}


/* =====================================================
   STAT CARDS
===================================================== */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        15px;

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
        10px;

    padding:
        18px;

    position:
        relative;

    overflow:
        hidden;

}


.stat::after {

    content:
        "";

    position:
        absolute;

    width:
        60px;

    height:
        60px;

    border-radius:
        50%;

    right:
        -20px;

    bottom:
        -25px;

    background:
        #eef5f1;

}


.stat-top {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        12px;

}


.stat-icon {

    width:
        38px;

    height:
        38px;

    border-radius:
        8px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #edf5f1;

    color:
        var(--primary);

    font-size:
        17px;

}


.stat-change {

    font-size:
        8px;

    color:
        var(--success);

    background:
        var(--success-bg);

    padding:
        4px 7px;

    border-radius:
        10px;

}


.stat strong {

    display:
        block;

    font-size:
        25px;

    margin-bottom:
        4px;

}


.stat span {

    color:
        var(--muted);

    font-size:
        9px;

}


/* =====================================================
   GRID
===================================================== */

.dashboard-grid {

    display:
        grid;

    grid-template-columns:
        1.4fr 1fr;

    gap:
        20px;

}


/* =====================================================
   PANEL
===================================================== */

.panel {

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


.panel-header {

    padding:
        17px 18px;

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


.panel-header h2 {

    font-size:
        13px;

}


.panel-header a {

    color:
        var(--primary);

    font-size:
        8px;

    font-weight:
        700;

    text-decoration:
        none;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x:
        auto;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

}


th {

    text-align:
        left;

    padding:
        11px 15px;

    color:
        var(--muted);

    background:
        #fafbfa;

    font-size:
        8px;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;

}


td {

    padding:
        13px 15px;

    border-top:
        1px solid
        #edf0ee;

    font-size:
        9px;

    vertical-align:
        middle;

}


.property-name {

    font-weight:
        700;

    max-width:
        180px;

}


.property-city {

    color:
        var(--muted);

    font-size:
        8px;

    margin-top:
        3px;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display:
        inline-flex;

    padding:
        5px 8px;

    border-radius:
        12px;

    font-size:
        7px;

    font-weight:
        700;

    text-transform:
        capitalize;

}


.badge.published,
.badge.confirmed {

    color:
        var(--success);

    background:
        var(--success-bg);

}


.badge.pending,
.badge.new {

    color:
        var(--warning);

    background:
        var(--warning-bg);

}


.badge.draft {

    color:
        var(--muted);

    background:
        #eef0ef;

}


.badge.cancelled {

    color:
        var(--danger);

    background:
        var(--danger-bg);

}


.badge.completed {

    color:
        var(--blue);

    background:
        var(--blue-bg);

}


/* =====================================================
   ENQUIRIES
===================================================== */

.enquiry {

    padding:
        14px 17px;

    border-bottom:
        1px solid
        #edf0ee;

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

}


.enquiry:last-child {

    border-bottom:
        none;

}


.enquiry-avatar {

    width:
        34px;

    height:
        34px;

    border-radius:
        50%;

    background:
        #e8f2ed;

    color:
        var(--primary);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

    font-size:
        10px;

    flex-shrink:
        0;

}


.enquiry-body {

    flex:
        1;

    min-width:
        0;

}


.enquiry-body strong {

    display:
        block;

    font-size:
        9px;

    margin-bottom:
        3px;

}


.enquiry-body span {

    display:
        block;

    color:
        var(--muted);

    font-size:
        8px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

}


.enquiry-right {

    text-align:
        right;

}


/* =====================================================
   VISITS
===================================================== */

.visit-list {

    padding:
        5px 17px 10px;

}


.visit {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    padding:
        12px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.visit:last-child {

    border-bottom:
        none;

}


.visit-date {

    width:
        45px;

    height:
        45px;

    border-radius:
        7px;

    background:
        #edf5f1;

    color:
        var(--primary);

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

}


.visit-date strong {

    font-size:
        14px;

}


.visit-date span {

    font-size:
        7px;

}


.visit-body {

    flex:
        1;

    min-width:
        0;

}


.visit-body strong {

    display:
        block;

    font-size:
        9px;

    margin-bottom:
        3px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

}


.visit-body span {

    color:
        var(--muted);

    font-size:
        8px;

}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        10px;

    margin-top:
        20px;

}


.quick-action {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        9px;

    padding:
        16px;

    text-decoration:
        none;

    color:
        var(--text);

    transition:
        .2s;

}


.quick-action:hover {

    border-color:
        var(--primary);

    transform:
        translateY(-2px);

}


.quick-action-icon {

    font-size:
        20px;

    margin-bottom:
        10px;

}


.quick-action strong {

    display:
        block;

    font-size:
        10px;

    margin-bottom:
        4px;

}


.quick-action span {

    color:
        var(--muted);

    font-size:
        8px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width: 850px) {

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
            15px;

    }

    .logo span,
    .logo strong {

        font-size:
            0;

    }

    .logo::after {

        content:
            "RE";

        font-size:
            14px;

    }

    .admin-label,
    .menu a span,
    .admin-info {

        display:
            none;

    }

    .menu a {

        justify-content:
            center;

        padding:
            0;

    }

    .sidebar-bottom {

        padding:
            10px 5px;

    }

    .admin-profile {

        justify-content:
            center;

    }

    .main {

        margin-left:
            65px;

    }

    .dashboard-grid {

        grid-template-columns:
            1fr;

    }

}


@media(max-width: 600px) {

    .topbar {

        padding:
            0 15px;

    }

    .topbar p {

        display:
            none;

    }

    .content {

        padding:
            20px 15px 50px;

    }

    .stats {

        grid-template-columns:
            1fr 1fr;

    }

    .quick-actions {

        grid-template-columns:
            1fr;

    }

    .top-button span {

        display:
            none;

    }

}


@media(max-width: 420px) {

    .stats {

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


    <div class="admin-label">
        Administration
    </div>


    <nav class="menu">


        <a
            href="dashboard.php"
            class="active"
        >

            <span class="menu-icon">
                📊
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a href="properties.php">

            <span class="menu-icon">
                🏠
            </span>

            <span>
                Properties
            </span>

        </a>


        <a href="users.php">

            <span class="menu-icon">
                👥
            </span>

            <span>
                Users
            </span>

        </a>


        <a href="agents.php">

            <span class="menu-icon">
                🧑‍💼
            </span>

            <span>
                Agents
            </span>

        </a>


        <a href="enquiries.php">

            <span class="menu-icon">
                💬
            </span>

            <span>
                Enquiries
            </span>

        </a>


        <a href="visits.php">

            <span class="menu-icon">
                📅
            </span>

            <span>
                Visits
            </span>

        </a>


        <a href="settings.php">

            <span class="menu-icon">
                ⚙️
            </span>

            <span>
                Settings
            </span>

        </a>


    </nav>


    <div class="sidebar-bottom">


        <div class="admin-profile">


            <div class="admin-avatar">

                <?php if (
                    !empty(
                        $admin["profile_image"]
                    )
                ): ?>

                    <img
                        src="<?php
                        echo safe(
                            "../uploads/profiles/" .
                            $admin[
                                "profile_image"
                            ]
                        );
                        ?>"
                        alt="Admin"
                    >

                <?php else: ?>

                    <?php
                    echo $adminInitial;
                    ?>

                <?php endif; ?>

            </div>


            <div class="admin-info">

                <strong>
                    <?php
                    echo safe(
                        $adminName
                    );
                    ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>


        </div>


        <a
            href="../auth/logout.php"
            class="menu a"
            style="
                color:#ffb8bf;
                text-decoration:none;
            "
        >

            <span class="menu-icon">
                🚪
            </span>

            <span>
                Logout
            </span>

        </a>


    </div>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <h1>
                Admin Dashboard
            </h1>

            <p>
                Monitor and manage your real estate platform.
            </p>

        </div>


        <div class="top-actions">


            <a
                href="../page/properties.php"
                class="top-button"
            >

                🌐

                <span>
                    View Website
                </span>

            </a>


            <a
                href="properties.php"
                class="top-button primary"
            >

                +

                <span>
                    Add Property
                </span>

            </a>


        </div>


    </header>



    <!-- CONTENT -->

    <main class="content">


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats">


            <div class="stat">

                <div class="stat-top">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-change">
                        Platform
                    </div>

                </div>

                <strong>
                    <?php
                    echo number_format(
                        $totalUsers
                    );
                    ?>
                </strong>

                <span>
                    Total Users
                </span>

            </div>


            <div class="stat">

                <div class="stat-top">

                    <div class="stat-icon">
                        🏠
                    </div>

                    <div class="stat-change">
                        <?php
                        echo number_format(
                            $publishedProperties
                        );
                        ?>
                        Live
                    </div>

                </div>

                <strong>
                    <?php
                    echo number_format(
                        $totalProperties
                    );
                    ?>
                </strong>

                <span>
                    Total Properties
                </span>

            </div>


            <div class="stat">

                <div class="stat-top">

                    <div class="stat-icon">
                        💬
                    </div>

                    <?php if (
                        $newEnquiries > 0
                    ): ?>

                        <div class="stat-change">
                            <?php
                            echo $newEnquiries;
                            ?>
                            New
                        </div>

                    <?php endif; ?>

                </div>

                <strong>
                    <?php
                    echo number_format(
                        $totalEnquiries
                    );
                    ?>
                </strong>

                <span>
                    Total Enquiries
                </span>

            </div>


            <div class="stat">

                <div class="stat-top">

                    <div class="stat-icon">
                        📅
                    </div>

                    <div class="stat-change">
                        <?php
                        echo $pendingVisits;
                        ?>
                        Pending
                    </div>

                </div>

                <strong>
                    <?php
                    echo number_format(
                        $totalVisits
                    );
                    ?>
                </strong>

                <span>
                    Property Visits
                </span>

            </div>


        </section>



        <!-- =================================================
             MAIN DASHBOARD GRID
        ================================================== -->

        <div class="dashboard-grid">


            <!-- RECENT PROPERTIES -->

            <section class="panel">


                <div class="panel-header">

                    <h2>
                        Recent Properties
                    </h2>

                    <a href="properties.php">
                        View All →
                    </a>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Property
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Agent
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty(
                                $recentProperties
                            )
                        ): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    style="
                                        text-align:center;
                                        color:#777;
                                    "
                                >

                                    No properties found.

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach (
                                $recentProperties
                                as $property
                            ): ?>


                                <tr>


                                    <td>

                                        <div
                                            class="property-name"
                                        >

                                            <?php
                                            echo safe(
                                                $property[
                                                    "title"
                                                ]
                                            );
                                            ?>

                                        </div>


                                        <div
                                            class="property-city"
                                        >

                                            📍

                                            <?php
                                            echo safe(
                                                $property[
                                                    "city"
                                                ]
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <strong>

                                            <?php
                                            echo formatPrice(
                                                $property[
                                                    "price"
                                                ]
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo safe(
                                            $property[
                                                "owner_name"
                                            ] ??
                                            "Unassigned"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                            badge
                                            <?php
                                            echo safe(
                                                $property[
                                                    "status"
                                                ]
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo safe(
                                                $property[
                                                    "status"
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </section>



            <!-- RECENT ENQUIRIES -->

            <section class="panel">


                <div class="panel-header">

                    <h2>
                        Recent Enquiries
                    </h2>

                    <a href="enquiries.php">
                        View All →
                    </a>

                </div>


                <?php if (
                    empty(
                        $recentEnquiries
                    )
                ): ?>

                    <div
                        style="
                            padding:35px;
                            text-align:center;
                            color:#777;
                            font-size:10px;
                        "
                    >

                        No enquiries found.

                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentEnquiries
                        as $enquiry
                    ): ?>


                        <div class="enquiry">


                            <div class="enquiry-avatar">

                                <?php

                                echo strtoupper(
                                    substr(
                                        $enquiry[
                                            "name"
                                        ],
                                        0,
                                        1
                                    )
                                );

                                ?>

                            </div>


                            <div class="enquiry-body">

                                <strong>

                                    <?php
                                    echo safe(
                                        $enquiry[
                                            "name"
                                        ]
                                    );
                                    ?>

                                </strong>

                                <span>

                                    <?php
                                    echo safe(
                                        $enquiry[
                                            "title"
                                        ] ??
                                        "Property enquiry"
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="enquiry-right">

                                <span
                                    class="
                                    badge
                                    <?php
                                    echo safe(
                                        $enquiry[
                                            "status"
                                        ]
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo safe(
                                        $enquiry[
                                            "status"
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </section>


        </div>



        <!-- =================================================
             VISITS
        ================================================== -->

        <section
            class="panel"
            style="margin-top:20px;"
        >


            <div class="panel-header">

                <h2>
                    Recent Property Visits
                </h2>

                <a href="visits.php">
                    Manage Visits →
                </a>

            </div>


            <div class="visit-list">


                <?php if (
                    empty(
                        $recentVisits
                    )
                ): ?>


                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            color:#777;
                            font-size:10px;
                        "
                    >

                        No visits found.

                    </div>


                <?php else: ?>


                    <?php foreach (
                        $recentVisits
                        as $visit
                    ): ?>


                        <?php

                        $date =
                            strtotime(
                                $visit[
                                    "visit_date"
                                ]
                            );

                        ?>


                        <div class="visit">


                            <div class="visit-date">

                                <strong>

                                    <?php
                                    echo date(
                                        "d",
                                        $date
                                    );
                                    ?>

                                </strong>

                                <span>

                                    <?php
                                    echo date(
                                        "M",
                                        $date
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="visit-body">

                                <strong>

                                    <?php
                                    echo safe(
                                        $visit[
                                            "title"
                                        ]
                                    );
                                    ?>

                                </strong>

                                <span>

                                    <?php
                                    echo safe(
                                        $visit[
                                            "user_name"
                                        ]
                                    );
                                    ?>

                                    ·

                                    <?php
                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $visit[
                                                "visit_time"
                                            ]
                                        )
                                    );
                                    ?>

                                </span>

                            </div>


                            <span
                                class="
                                badge
                                <?php
                                echo safe(
                                    $visit[
                                        "status"
                                    ]
                                );
                                ?>"
                            >

                                <?php
                                echo safe(
                                    $visit[
                                        "status"
                                    ]
                                );
                                ?>

                            </span>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </section>



        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="quick-actions">


            <a
                href="properties.php"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    🏠
                </div>

                <strong>
                    Manage Properties
                </strong>

                <span>
                    Add, edit and publish listings.
                </span>

            </a>


            <a
                href="users.php"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    👥
                </div>

                <strong>
                    Manage Users
                </strong>

                <span>
                    View and manage platform users.
                </span>

            </a>


            <a
                href="enquiries.php"
                class="quick-action"
            >

                <div class="quick-action-icon">
                    💬
                </div>

                <strong>
                    Enquiries
                </strong>

                <span>
                    Respond to customer enquiries.
                </span>

            </a>


        </section>


    </main>


</div>


</body>

</html>