<?php

session_start();

require_once "../config/database.php";

/* =========================================================
   ADMIN AUTHENTICATION
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

function formatPrice($price)
{
    $price = (float)$price;

    if ($price >= 10000000) {
        return "₹" . number_format($price / 10000000, 2) . " Cr";
    }

    if ($price >= 100000) {
        return "₹" . number_format($price / 100000, 2) . " L";
    }

    return "₹" . number_format($price);
}


/* =========================================================
   DELETE PROPERTY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_property"])
) {

    $propertyId = (int)($_POST["property_id"] ?? 0);

    if ($propertyId > 0) {

        $deleteSQL = "
            DELETE FROM properties
            WHERE id = ?
        ";

        $stmt = $conn->prepare($deleteSQL);

        $stmt->bind_param(
            "i",
            $propertyId
        );

        $stmt->execute();

        $stmt->close();
    }

    header("Location: properties.php?deleted=1");
    exit;
}


/* =========================================================
   CHANGE PROPERTY STATUS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_status"])
) {

    $propertyId = (int)($_POST["property_id"] ?? 0);

    $newStatus = $_POST["new_status"] ?? "";

    $allowedStatuses = [
        "draft",
        "published",
        "sold",
        "rented"
    ];

    if (
        $propertyId > 0 &&
        in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $statusSQL = "
            UPDATE properties
            SET status = ?
            WHERE id = ?
        ";

        $stmt =
            $conn->prepare(
                $statusSQL
            );

        $stmt->bind_param(
            "si",
            $newStatus,
            $propertyId
        );

        $stmt->execute();

        $stmt->close();
    }

    header(
        "Location: properties.php?updated=1"
    );

    exit;
}


/* =========================================================
   FILTERS
========================================================= */

$search =
    trim(
        $_GET["search"] ?? ""
    );

$status =
    trim(
        $_GET["status"] ?? ""
    );

$type =
    trim(
        $_GET["type"] ?? ""
    );

$city =
    trim(
        $_GET["city"] ?? ""
    );


/* =========================================================
   PAGINATION
========================================================= */

$perPage = 10;

$page =
    max(
        1,
        (int)(
            $_GET["page"] ?? 1
        )
    );

$offset =
    ($page - 1) *
    $perPage;


/* =========================================================
   BUILD FILTER
========================================================= */

$where = [];

$params = [];

$types = "";


if ($search !== "") {

    $where[] = "
        (
            p.title LIKE ?
            OR p.city LIKE ?
            OR p.address LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $types .= "sss";
}


if ($status !== "") {

    $where[] =
        "p.status = ?";

    $params[] =
        $status;

    $types .= "s";
}


if ($type !== "") {

    $where[] =
        "p.property_type = ?";

    $params[] =
        $type;

    $types .= "s";
}


if ($city !== "") {

    $where[] =
        "p.city = ?";

    $params[] =
        $city;

    $types .= "s";
}


$whereSQL = "";

if (!empty($where)) {

    $whereSQL =
        " WHERE " .
        implode(
            " AND ",
            $where
        );
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$countSQL = "
    SELECT COUNT(*) AS total
    FROM properties p
    $whereSQL
";

$countStmt =
    $conn->prepare(
        $countSQL
    );

if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}

$countStmt->execute();

$countResult =
    $countStmt->get_result();

$totalProperties =
    (int)(
        $countResult
        ->fetch_assoc()["total"]
        ?? 0
    );

$countStmt->close();


$totalPages =
    max(
        1,
        (int)ceil(
            $totalProperties /
            $perPage
        )
    );


/* =========================================================
   GET PROPERTIES
========================================================= */

$propertySQL = "
    SELECT

        p.id,
        p.title,
        p.price,
        p.city,
        p.address,
        p.property_type,
        p.bedrooms,
        p.bathrooms,
        p.area,
        p.status,
        p.created_at,

        u.name AS agent_name

    FROM properties p

    LEFT JOIN users u
        ON p.agent_id = u.id

    $whereSQL

    ORDER BY p.created_at DESC

    LIMIT ? OFFSET ?
";


$propertyStmt =
    $conn->prepare(
        $propertySQL
    );


$queryParams =
    $params;

$queryTypes =
    $types . "ii";

$queryParams[] =
    $perPage;

$queryParams[] =
    $offset;


$propertyStmt->bind_param(
    $queryTypes,
    ...$queryParams
);


$propertyStmt->execute();

$result =
    $propertyStmt->get_result();


$properties = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $properties[] =
        $row;
}

$propertyStmt->close();


/* =========================================================
   STATISTICS
========================================================= */

$totalCount = 0;
$publishedCount = 0;
$draftCount = 0;
$soldCount = 0;

$statsSQL = "
    SELECT

        COUNT(*) AS total,

        SUM(
            status = 'published'
        ) AS published,

        SUM(
            status = 'draft'
        ) AS draft,

        SUM(
            status = 'sold'
        ) AS sold

    FROM properties
";

$statsResult =
    $conn->query(
        $statsSQL
    );

if ($statsResult) {

    $stats =
        $statsResult->fetch_assoc();

    $totalCount =
        (int)($stats["total"] ?? 0);

    $publishedCount =
        (int)($stats["published"] ?? 0);

    $draftCount =
        (int)($stats["draft"] ?? 0);

    $soldCount =
        (int)($stats["sold"] ?? 0);
}


/* =========================================================
   CITIES
========================================================= */

$cities = [];

$citySQL = "
    SELECT DISTINCT city
    FROM properties
    WHERE city IS NOT NULL
    AND city != ''
    ORDER BY city ASC
";

$cityResult =
    $conn->query(
        $citySQL
    );

if ($cityResult) {

    while (
        $row =
        $cityResult->fetch_assoc()
    ) {

        $cities[] =
            $row["city"];
    }
}


/* =========================================================
   PROPERTY TYPES
========================================================= */

$propertyTypes = [];

$typeSQL = "
    SELECT DISTINCT property_type
    FROM properties
    WHERE property_type IS NOT NULL
    AND property_type != ''
    ORDER BY property_type ASC
";

$typeResult =
    $conn->query(
        $typeSQL
    );

if ($typeResult) {

    while (
        $row =
        $typeResult->fetch_assoc()
    ) {

        $propertyTypes[] =
            $row["property_type"];
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
    Property Management | RealEstateHub
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


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    width: 240px;

    background:
        var(--primary);

    color: white;

    z-index: 100;

}


.logo {

    height: 75px;

    display: flex;

    align-items: center;

    padding:
        0 25px;

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

    font-size:
        8px;

    text-transform:
        uppercase;

    letter-spacing:
        1.5px;

}


.menu {

    padding:
        0 12px;

}


.menu a {

    height:
        44px;

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

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

    width:
        20px;

    text-align:
        center;

    font-size:
        14px;

}


.sidebar-bottom {

    position:
        absolute;

    left: 0;

    right: 0;

    bottom: 0;

    padding:
        15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

}


.logout {

    color:
        #ffb8bf !important;

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
        50;

}


.title h1 {

    font-size:
        20px;

}


.title p {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        9px;

}


.add-button {

    height:
        38px;

    padding:
        0 16px;

    border:
        none;

    border-radius:
        6px;

    background:
        var(--primary);

    color:
        white;

    text-decoration:
        none;

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    font-size:
        9px;

    font-weight:
        700;

}


.add-button:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    padding:
        28px 30px 60px;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding:
        12px 15px;

    border-radius:
        7px;

    font-size:
        10px;

    margin-bottom:
        18px;

}


.alert.success {

    background:
        var(--success-bg);

    color:
        var(--success);

}


.alert.error {

    background:
        var(--danger-bg);

    color:
        var(--danger);

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        14px;

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
        9px;

    padding:
        17px;

}


.stat span {

    display:
        block;

    color:
        var(--muted);

    font-size:
        8px;

    margin-bottom:
        7px;

}


.stat strong {

    font-size:
        23px;

}


.stat.published strong {

    color:
        var(--success);

}


.stat.draft strong {

    color:
        var(--warning);

}


.stat.sold strong {

    color:
        var(--danger);

}


/* =========================================================
   FILTER PANEL
========================================================= */

.filter-panel {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    padding:
        17px;

    margin-bottom:
        18px;

}


.filter-title {

    font-size:
        11px;

    font-weight:
        700;

    margin-bottom:
        12px;

}


.filters {

    display:
        grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr auto;

    gap:
        9px;

}


.input,
.select {

    height:
        40px;

    width:
        100%;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    padding:
        0 11px;

    outline:
        none;

    font-size:
        9px;

    background:
        white;

}


.input:focus,
.select:focus {

    border-color:
        var(--primary);

}


.filter-button {

    height:
        40px;

    padding:
        0 15px;

    background:
        var(--primary);

    color:
        white;

    border:
        none;

    border-radius:
        6px;

    cursor:
        pointer;

    font-size:
        9px;

    font-weight:
        700;

}


.clear-button {

    height:
        40px;

    padding:
        0 12px;

    display:
        flex;

    align-items:
        center;

    background:
        #f1f3f2;

    color:
        var(--text);

    border-radius:
        6px;

    text-decoration:
        none;

    font-size:
        9px;

}


/* =========================================================
   TABLE PANEL
========================================================= */

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

    min-height:
        60px;

    padding:
        0 18px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    border-bottom:
        1px solid
        var(--border);

}


.panel-header h2 {

    font-size:
        13px;

}


.result-count {

    color:
        var(--muted);

    font-size:
        8px;

}


/* =========================================================
   TABLE
========================================================= */

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

    background:
        #fafbfa;

    padding:
        12px 15px;

    text-align:
        left;

    color:
        var(--muted);

    font-size:
        8px;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;

    white-space:
        nowrap;

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


.property {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    min-width:
        230px;

}


.property-image {

    width:
        58px;

    height:
        50px;

    border-radius:
        6px;

    background:
        #edf2ef;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        20px;

    flex-shrink:
        0;

}


.property-info strong {

    display:
        block;

    max-width:
        190px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    margin-bottom:
        4px;

}


.property-info span {

    display:
        block;

    color:
        var(--muted);

    font-size:
        8px;

}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:
        inline-flex;

    padding:
        5px 9px;

    border-radius:
        12px;

    font-size:
        7px;

    font-weight:
        700;

}


.badge.published {

    color:
        var(--success);

    background:
        var(--success-bg);

}


.badge.draft {

    color:
        var(--warning);

    background:
        var(--warning-bg);

}


.badge.sold {

    color:
        var(--danger);

    background:
        var(--danger-bg);

}


.badge.rented {

    color:
        var(--blue);

    background:
        var(--blue-bg);

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display:
        flex;

    gap:
        5px;

}


.action {

    width:
        31px;

    height:
        31px;

    border:
        1px solid
        var(--border);

    background:
        white;

    border-radius:
        5px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    text-decoration:
        none;

    cursor:
        pointer;

    font-size:
        11px;

}


.action:hover {

    border-color:
        var(--primary);

}


.action.delete:hover {

    border-color:
        var(--danger);

    background:
        var(--danger-bg);

}


/* =========================================================
   PAGINATION
========================================================= */

.pagination {

    padding:
        16px 18px;

    border-top:
        1px solid
        var(--border);

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

}


.pagination-info {

    color:
        var(--muted);

    font-size:
        8px;

}


.pages {

    display:
        flex;

    gap:
        5px;

}


.page {

    width:
        31px;

    height:
        31px;

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
        5px;

    color:
        var(--text);

    text-decoration:
        none;

    font-size:
        8px;

}


.page.active {

    background:
        var(--primary);

    color:
        white;

    border-color:
        var(--primary);

}


.page:hover {

    border-color:
        var(--primary);

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:
        60px 20px;

    text-align:
        center;

}


.empty-icon {

    font-size:
        40px;

    margin-bottom:
        12px;

}


.empty h3 {

    font-size:
        14px;

    margin-bottom:
        5px;

}


.empty p {

    color:
        var(--muted);

    font-size:
        9px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .filters {

        grid-template-columns:
            1fr 1fr;

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

}


@media(max-width:600px) {

    .topbar {

        padding:
            0 15px;

    }

    .content {

        padding:
            20px 15px 50px;

    }

    .stats {

        grid-template-columns:
            1fr;

    }

    .filters {

        grid-template-columns:
            1fr;

    }

    .title p {

        display:
            none;

    }

    .pagination {

        flex-direction:
            column;

        gap:
            12px;

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

            <span class="icon">
                📊
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="properties.php"
            class="active"
        >

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


        <a href="enquiries.php">

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
            class="menu a logout"
            style="text-decoration:none;"
        >

            <span class="icon">
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


    <header class="topbar">


        <div class="title">

            <h1>
                Property Management
            </h1>

            <p>
                Manage all real estate listings from one place.
            </p>

        </div>


        <a
            href="property-add.php"
            class="add-button"
        >

            +

            Add Property

        </a>


    </header>



    <main class="content">


        <?php if (
            isset(
                $_GET["deleted"]
            )
        ): ?>

            <div class="alert success">

                Property deleted successfully.

            </div>

        <?php endif; ?>


        <?php if (
            isset(
                $_GET["updated"]
            )
        ): ?>

            <div class="alert success">

                Property status updated successfully.

            </div>

        <?php endif; ?>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats">


            <div class="stat">

                <span>
                    Total Properties
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $totalCount
                    );
                    ?>
                </strong>

            </div>


            <div class="stat published">

                <span>
                    Published
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $publishedCount
                    );
                    ?>
                </strong>

            </div>


            <div class="stat draft">

                <span>
                    Draft
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $draftCount
                    );
                    ?>
                </strong>

            </div>


            <div class="stat sold">

                <span>
                    Sold
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $soldCount
                    );
                    ?>
                </strong>

            </div>


        </section>



        <!-- =================================================
             FILTERS
        ================================================== -->

        <section class="filter-panel">


            <div class="filter-title">
                Search & Filter Properties
            </div>


            <form
                method="GET"
                class="filters"
            >


                <input
                    type="text"
                    name="search"
                    class="input"
                    placeholder="Search property, city or address..."
                    value="<?php
                    echo safe($search);
                    ?>"
                >


                <select
                    name="status"
                    class="select"
                >

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="published"
                        <?php
                        echo $status ===
                            "published"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Published
                    </option>

                    <option
                        value="draft"
                        <?php
                        echo $status ===
                            "draft"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Draft
                    </option>

                    <option
                        value="sold"
                        <?php
                        echo $status ===
                            "sold"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Sold
                    </option>

                    <option
                        value="rented"
                        <?php
                        echo $status ===
                            "rented"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Rented
                    </option>

                </select>


                <select
                    name="type"
                    class="select"
                >

                    <option value="">
                        All Types
                    </option>


                    <?php foreach (
                        $propertyTypes
                        as $propertyType
                    ): ?>

                        <option
                            value="<?php
                            echo safe(
                                $propertyType
                            );
                            ?>"
                            <?php
                            echo $type ===
                                $propertyType
                                ? "selected"
                                : "";
                            ?>
                        >

                            <?php
                            echo safe(
                                $propertyType
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>


                <select
                    name="city"
                    class="select"
                >

                    <option value="">
                        All Cities
                    </option>


                    <?php foreach (
                        $cities
                        as $cityName
                    ): ?>

                        <option
                            value="<?php
                            echo safe(
                                $cityName
                            );
                            ?>"
                            <?php
                            echo $city ===
                                $cityName
                                ? "selected"
                                : "";
                            ?>
                        >

                            <?php
                            echo safe(
                                $cityName
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>


                <button
                    type="submit"
                    class="filter-button"
                >

                    Filter

                </button>


                <a
                    href="properties.php"
                    class="clear-button"
                >

                    Clear

                </a>


            </form>


        </section>



        <!-- =================================================
             PROPERTY TABLE
        ================================================== -->

        <section class="panel">


            <div class="panel-header">


                <h2>
                    Property Listings
                </h2>


                <span class="result-count">

                    <?php
                    echo number_format(
                        $totalProperties
                    );
                    ?>

                    results

                </span>


            </div>



            <?php if (
                empty(
                    $properties
                )
            ): ?>


                <div class="empty">

                    <div class="empty-icon">
                        🏠
                    </div>

                    <h3>
                        No properties found
                    </h3>

                    <p>
                        Try changing your filters or add a new property.
                    </p>

                </div>


            <?php else: ?>


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
                                    Type
                                </th>

                                <th>
                                    Details
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
                            $properties
                            as $property
                        ): ?>


                            <tr>


                                <!-- PROPERTY -->

                                <td>


                                    <div class="property">


                                        <div class="property-image">

                                            🏠

                                        </div>


                                        <div
                                            class="property-info"
                                        >

                                            <strong>

                                                <?php
                                                echo safe(
                                                    $property[
                                                        "title"
                                                    ]
                                                );
                                                ?>

                                            </strong>


                                            <span>

                                                📍

                                                <?php
                                                echo safe(
                                                    $property[
                                                        "city"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        </div>


                                    </div>


                                </td>



                                <!-- PRICE -->

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



                                <!-- TYPE -->

                                <td>

                                    <?php
                                    echo safe(
                                        $property[
                                            "property_type"
                                        ]
                                    );
                                    ?>

                                </td>



                                <!-- DETAILS -->

                                <td>

                                    <?php
                                    echo (int)
                                        $property[
                                            "bedrooms"
                                        ];
                                    ?>

                                    Beds

                                    ·

                                    <?php
                                    echo (int)
                                        $property[
                                            "bathrooms"
                                        ];
                                    ?>

                                    Baths

                                    <br>

                                    <small
                                        style="
                                            color:#777;
                                            font-size:7px;
                                        "
                                    >

                                        <?php
                                        echo safe(
                                            $property[
                                                "area"
                                            ]
                                        );
                                        ?>

                                        sq.ft

                                    </small>

                                </td>



                                <!-- AGENT -->

                                <td>

                                    <?php
                                    echo safe(
                                        $property[
                                            "agent_name"
                                        ] ??
                                        "Unassigned"
                                    );
                                    ?>

                                </td>



                                <!-- STATUS -->

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



                                <!-- ACTIONS -->

                                <td>


                                    <div class="actions">


                                        <!-- VIEW -->

                                        <a
                                            href="../page/property-details.php?id=<?php
                                            echo (int)
                                                $property[
                                                    "id"
                                                ];
                                            ?>"
                                            class="action"
                                            title="View"
                                        >

                                            👁

                                        </a>


                                        <!-- EDIT -->

                                        <a
                                            href="property-edit.php?id=<?php
                                            echo (int)
                                                $property[
                                                    "id"
                                                ];
                                            ?>"
                                            class="action"
                                            title="Edit"
                                        >

                                            ✏️

                                        </a>


                                        <!-- STATUS -->

                                        <button
                                            type="button"
                                            class="action"
                                            title="Change Status"
                                            onclick="openStatusModal(
                                                <?php
                                                echo (int)
                                                    $property[
                                                        'id'
                                                    ];
                                                ?>,
                                                '<?php
                                                echo safe(
                                                    $property[
                                                        'status'
                                                    ]
                                                );
                                                ?>'
                                            )"
                                        >

                                            🔄

                                        </button>


                                        <!-- DELETE -->

                                        <button
                                            type="button"
                                            class="action delete"
                                            title="Delete"
                                            onclick="deleteProperty(
                                                <?php
                                                echo (int)
                                                    $property[
                                                        'id'
                                                    ];
                                                ?>
                                            )"
                                        >

                                            🗑

                                        </button>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>



            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <?php if (
                $totalPages > 1
            ): ?>


                <div class="pagination">


                    <div class="pagination-info">

                        Page

                        <?php
                        echo $page;
                        ?>

                        of

                        <?php
                        echo $totalPages;
                        ?>

                    </div>


                    <div class="pages">


                        <?php if (
                            $page > 1
                        ): ?>

                            <a
                                class="page"
                                href="?<?php
                                echo http_build_query(
                                    array_merge(
                                        $_GET,
                                        [
                                            "page" =>
                                                $page - 1
                                        ]
                                    )
                                );
                                ?>"
                            >

                                ‹

                            </a>

                        <?php endif; ?>


                        <?php

                        $start =
                            max(
                                1,
                                $page - 2
                            );

                        $end =
                            min(
                                $totalPages,
                                $page + 2
                            );

                        ?>


                        <?php for (
                            $i = $start;
                            $i <= $end;
                            $i++
                        ): ?>


                            <a
                                class="
                                page
                                <?php
                                echo $i === $page
                                    ? "active"
                                    : "";
                                ?>"
                                href="?<?php
                                echo http_build_query(
                                    array_merge(
                                        $_GET,
                                        [
                                            "page" =>
                                                $i
                                        ]
                                    )
                                );
                                ?>"
                            >

                                <?php
                                echo $i;
                                ?>

                            </a>


                        <?php endfor; ?>


                        <?php if (
                            $page <
                            $totalPages
                        ): ?>

                            <a
                                class="page"
                                href="?<?php
                                echo http_build_query(
                                    array_merge(
                                        $_GET,
                                        [
                                            "page" =>
                                                $page + 1
                                        ]
                                    )
                                );
                                ?>"
                            >

                                ›

                            </a>

                        <?php endif; ?>


                    </div>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>



<!-- =========================================================
     DELETE FORM
========================================================= -->

<form
    method="POST"
    id="deleteForm"
    style="display:none;"
>

    <input
        type="hidden"
        name="property_id"
        id="deletePropertyId"
    >

    <input
        type="hidden"
        name="delete_property"
        value="1"
    >

</form>



<!-- =========================================================
     STATUS MODAL
========================================================= -->

<div
    id="statusModal"
    style="
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.45);
        z-index:500;
        align-items:center;
        justify-content:center;
    "
>


    <div
        style="
            width:350px;
            max-width:90%;
            background:white;
            border-radius:10px;
            padding:24px;
        "
    >


        <h2
            style="
                font-size:16px;
                margin-bottom:7px;
            "
        >

            Change Property Status

        </h2>


        <p
            style="
                color:#777;
                font-size:9px;
                margin-bottom:18px;
            "
        >

            Select the new status for this property.

        </p>


        <form method="POST">


            <input
                type="hidden"
                name="property_id"
                id="statusPropertyId"
            >


            <input
                type="hidden"
                name="change_status"
                value="1"
            >


            <select
                name="new_status"
                id="statusSelect"
                class="select"
                style="margin-bottom:15px;"
            >

                <option value="draft">
                    Draft
                </option>

                <option value="published">
                    Published
                </option>

                <option value="sold">
                    Sold
                </option>

                <option value="rented">
                    Rented
                </option>

            </select>


            <div
                style="
                    display:flex;
                    gap:8px;
                "
            >


                <button
                    type="button"
                    onclick="closeStatusModal()"
                    style="
                        flex:1;
                        height:40px;
                        border:1px solid #ddd;
                        background:white;
                        border-radius:6px;
                        cursor:pointer;
                        font-size:9px;
                    "
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    style="
                        flex:1;
                        height:40px;
                        border:none;
                        background:#174a3a;
                        color:white;
                        border-radius:6px;
                        cursor:pointer;
                        font-size:9px;
                        font-weight:700;
                    "
                >

                    Update

                </button>


            </div>


        </form>


    </div>


</div>



<script>

/* =========================================================
   DELETE PROPERTY
========================================================= */

function deleteProperty(id)
{

    const confirmed =
        confirm(
            "Are you sure you want to delete this property? This action cannot be undone."
        );

    if (!confirmed) {
        return;
    }

    document
        .getElementById(
            "deletePropertyId"
        )
        .value = id;

    document
        .getElementById(
            "deleteForm"
        )
        .submit();

}


/* =========================================================
   STATUS MODAL
========================================================= */

function openStatusModal(
    id,
    currentStatus
)
{

    document
        .getElementById(
            "statusPropertyId"
        )
        .value = id;


    document
        .getElementById(
            "statusSelect"
        )
        .value =
        currentStatus;


    document
        .getElementById(
            "statusModal"
        )
        .style.display =
        "flex";

}


function closeStatusModal()
{

    document
        .getElementById(
            "statusModal"
        )
        .style.display =
        "none";

}


/* =========================================================
   CLOSE MODAL
========================================================= */

document
    .getElementById(
        "statusModal"
    )
    .addEventListener(
        "click",
        function(event)
        {

            if (
                event.target ===
                this
            ) {

                closeStatusModal();

            }

        }
    );


/* =========================================================
   KEYBOARD ESC
========================================================= */

document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key ===
            "Escape"
        ) {

            closeStatusModal();

        }

    }
);

</script>


</body>

</html>