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
   SEARCH / FILTER
========================================================= */

$search =
    trim($_GET["search"] ?? "");

$status =
    trim($_GET["status"] ?? "all");


/* =========================================================
   VALID STATUS
========================================================= */

$allowedStatuses = [
    "all",
    "active",
    "inactive"
];

if (!in_array(
    $status,
    $allowedStatuses,
    true
)) {
    $status = "all";
}


/* =========================================================
   CHECK PROPERTIES TABLE
========================================================= */

$hasPropertiesTable = false;

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'properties'"
);

if (
    $tableCheck &&
    $tableCheck->num_rows > 0
) {
    $hasPropertiesTable = true;
}


/* =========================================================
   GET AGENTS
========================================================= */

$agents = [];

if ($hasPropertiesTable) {

    $sql = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.created_at,
            COUNT(p.id) AS property_count
        FROM users u
        LEFT JOIN properties p
            ON p.agent_id = u.id
        WHERE u.role = 'agent'
    ";

}
else {

    $sql = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.created_at,
            0 AS property_count
        FROM users u
        WHERE u.role = 'agent'
    ";

}


/* =========================================================
   SEARCH
========================================================= */

$params = [];

$types = "";

if ($search !== "") {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
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


/* =========================================================
   STATUS FILTER
========================================================= */

if ($status !== "all") {

    $sql .= "
        AND u.status = ?
    ";

    $params[] =
        $status;

    $types .= "s";
}


/* =========================================================
   GROUP
========================================================= */

if ($hasPropertiesTable) {

    $sql .= "
        GROUP BY
            u.id,
            u.name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.created_at
    ";

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY u.id DESC
";


/* =========================================================
   EXECUTE
========================================================= */

$stmt =
    $conn->prepare($sql);

if (!$stmt) {
    exit("Database error.");
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


while (
    $row =
    $result->fetch_assoc()
) {

    $agents[] =
        $row;

}


$stmt->close();


/* =========================================================
   STATISTICS
========================================================= */

$totalAgents = 0;

$activeAgents = 0;

$inactiveAgents = 0;

$totalAgentProperties = 0;


foreach (
    $agents as $agent
) {

    $totalAgents++;

    if (
        $agent["status"] === "active"
    ) {

        $activeAgents++;

    }
    else {

        $inactiveAgents++;

    }

    $totalAgentProperties +=
        (int)$agent["property_count"];

}


/* =========================================================
   TOTAL AGENTS FROM DATABASE
========================================================= */

$countSql = "
    SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN status = 'active'
                THEN 1
                ELSE 0
            END
        ) AS active,
        SUM(
            CASE
                WHEN status = 'inactive'
                THEN 1
                ELSE 0
            END
        ) AS inactive
    FROM users
    WHERE role = 'agent'
";

$countResult =
    $conn->query($countSql);

if ($countResult) {

    $countRow =
        $countResult->fetch_assoc();

    $totalAgents =
        (int)(
            $countRow["total"]
            ?? 0
        );

    $activeAgents =
        (int)(
            $countRow["active"]
            ?? 0
        );

    $inactiveAgents =
        (int)(
            $countRow["inactive"]
            ?? 0
        );

}


/* =========================================================
   TOTAL PROPERTIES
========================================================= */

if ($hasPropertiesTable) {

    $propertyResult =
        $conn->query(
            "
            SELECT COUNT(*) AS total
            FROM properties p
            INNER JOIN users u
                ON u.id = p.agent_id
            WHERE u.role = 'agent'
            "
        );

    if ($propertyResult) {

        $propertyRow =
            $propertyResult->fetch_assoc();

        $totalAgentProperties =
            (int)(
                $propertyRow["total"]
                ?? 0
            );

    }

}


/* =========================================================
   CURRENT QUERY
========================================================= */

$queryBase = [];

if ($search !== "") {

    $queryBase["search"] =
        $search;

}

if ($status !== "all") {

    $queryBase["status"] =
        $status;

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
    Agents | RealEstate Admin
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


/* =========================================================
   VARIABLES
========================================================= */

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

    left: 0;

    top: 0;

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

    font-size: 8px;

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

    height: 44px;

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        0 13px;

    margin-bottom: 3px;

    border-radius: 7px;

    color:
        rgba(255,255,255,.7);

    text-decoration: none;

    font-size: 10px;

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

    left: 0;

    right: 0;

    bottom: 0;

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

    height: 75px;

    background:
        white;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 30px;

    position: sticky;

    top: 0;

    z-index: 20;

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


.admin-user {

    display: flex;

    align-items: center;

    gap: 9px;

}


.admin-avatar {

    width: 35px;

    height: 35px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        var(--primary);

    color:
        white;

    font-size:
        12px;

    font-weight:
        800;

}


.admin-name {

    font-size:
        8px;

    font-weight:
        700;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    padding:
        28px 30px 60px;

    max-width:
        1500px;

}


/* =========================================================
   STATS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        12px;

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


.stat-top {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

}


.stat-icon {

    width:
        35px;

    height:
        35px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        7px;

    background:
        var(--green-bg);

    font-size:
        16px;

}


.stat-number {

    margin-top:
        12px;

    font-size:
        22px;

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
   TOOLBAR
========================================================= */

.toolbar {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        9px;

    padding:
        15px;

    margin-bottom:
        15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

}


.search-form {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    flex:
        1;

}


.search-box {

    position:
        relative;

    max-width:
        430px;

    width:
        100%;

}


.search-box input {

    width:
        100%;

    height:
        40px;

    padding:
        0 12px 0 37px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    outline:
        none;

    font-size:
        8px;

}


.search-box input:focus {

    border-color:
        var(--primary);

}


.search-icon {

    position:
        absolute;

    left:
        12px;

    top:
        50%;

    transform:
        translateY(-50%);

    font-size:
        12px;

}


select {

    height:
        40px;

    padding:
        0 12px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    background:
        white;

    outline:
        none;

    font-size:
        8px;

}


.filter-btn {

    height:
        40px;

    padding:
        0 15px;

    border:
        none;

    border-radius:
        6px;

    background:
        var(--primary);

    color:
        white;

    font-size:
        8px;

    font-weight:
        700;

    cursor:
        pointer;

}


.clear-btn {

    height:
        40px;

    padding:
        0 13px;

    display:
        inline-flex;

    align-items:
        center;

    text-decoration:
        none;

    border-radius:
        6px;

    background:
        #eef1ef;

    color:
        var(--text);

    font-size:
        8px;

}


/* =========================================================
   AGENT GRID
========================================================= */

.agent-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        15px;

}


/* =========================================================
   AGENT CARD
========================================================= */

.agent-card {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    overflow:
        hidden;

    transition:
        .2s;

}


.agent-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(20,40,30,.07);

}


.agent-top {

    padding:
        20px;

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    border-bottom:
        1px solid
        #edf0ee;

}


.agent-avatar {

    width:
        55px;

    height:
        55px;

    flex-shrink:
        0;

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
        18px;

    font-weight:
        800;

}


.agent-name {

    font-size:
        11px;

    font-weight:
        800;

}


.agent-email {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;

    word-break:
        break-word;

}


.status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    margin-top:
        7px;

    padding:
        5px 8px;

    border-radius:
        20px;

    font-size:
        6px;

    font-weight:
        700;

}


.status.active {

    background:
        var(--green-bg);

    color:
        var(--green);

}


.status.inactive {

    background:
        var(--red-bg);

    color:
        var(--red);

}


/* =========================================================
   AGENT DETAILS
========================================================= */

.agent-details {

    padding:
        17px 20px;

}


.detail-row {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        9px 0;

    border-bottom:
        1px solid
        #f0f2f1;

}


.detail-row:last-child {

    border-bottom:
        none;

}


.detail-label {

    color:
        var(--muted);

    font-size:
        7px;

}


.detail-value {

    font-size:
        8px;

    font-weight:
        700;

}


.property-count {

    color:
        var(--primary);

}


.agent-actions {

    padding:
        12px 20px;

    display:
        flex;

    gap:
        6px;

    background:
        #fafbfa;

    border-top:
        1px solid
        #edf0ee;

}


.action {

    flex:
        1;

    height:
        34px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        5px;

    border-radius:
        5px;

    text-decoration:
        none;

    font-size:
        7px;

    font-weight:
        700;

}


.action.view {

    background:
        var(--primary);

    color:
        white;

}


.action.edit {

    background:
        #eef1ef;

    color:
        var(--text);

}


.action.properties {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    grid-column:
        1 / -1;

    padding:
        70px 20px;

    text-align:
        center;

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

}


.empty-icon {

    font-size:
        35px;

}


.empty h3 {

    margin-top:
        12px;

    font-size:
        13px;

}


.empty p {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        8px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1200px) {

    .agent-grid {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:900px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .toolbar {

        align-items:
            stretch;

        flex-direction:
            column;

    }


    .search-form {

        width:
            100%;

    }


    .search-box {

        max-width:
            none;

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

    .content {

        padding:
            20px 15px;

    }


    .stats {

        grid-template-columns:
            1fr 1fr;

    }


    .agent-grid {

        grid-template-columns:
            1fr;

    }


    .search-form {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    select,
    .filter-btn,
    .clear-btn {

        width:
            100%;

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


<a
    href="agents.php"
    class="active"
>

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
    Agent Management
</h1>

<p>
    Manage and monitor your real estate agents
</p>

</div>


<div class="admin-user">

<div class="admin-avatar">

<?php

echo safe(
    strtoupper(
        substr(
            $_SESSION["user_name"] ?? "A",
            0,
            1
        )
    )
);

?>

</div>


<div class="admin-name">

<?php

echo safe(
    $_SESSION["user_name"]
    ?? "Administrator"
);

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

<div class="stat-top">

<span>
    Total Agents
</span>

<div class="stat-icon">
    🧑‍💼
</div>

</div>


<div class="stat-number">

<?php
echo number_format(
    $totalAgents
);
?>

</div>


<div class="stat-label">
    Registered agents
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Active Agents
</span>

<div class="stat-icon">
    🟢
</div>

</div>


<div class="stat-number">

<?php
echo number_format(
    $activeAgents
);
?>

</div>


<div class="stat-label">
    Currently active
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Inactive
</span>

<div class="stat-icon">
    🔴
</div>

</div>


<div class="stat-number">

<?php
echo number_format(
    $inactiveAgents
);
?>

</div>


<div class="stat-label">
    Currently inactive
</div>

</div>


<div class="stat">

<div class="stat-top">

<span>
    Agent Properties
</span>

<div class="stat-icon">
    🏠
</div>

</div>


<div class="stat-number">

<?php
echo number_format(
    $totalAgentProperties
);
?>

</div>


<div class="stat-label">
    Listed properties
</div>

</div>


</section>


<!-- =====================================================
     TOOLBAR
========================================================= -->

<section class="toolbar">


<form
    method="GET"
    class="search-form"
>


<div class="search-box">

<span class="search-icon">
    🔍
</span>


<input
    type="search"
    name="search"
    value="<?php echo safe($search); ?>"
    placeholder="Search agent name, email or phone..."
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
    value="active"
    <?php
    echo $status === "active"
        ? "selected"
        : "";
    ?>
>
    Active
</option>


<option
    value="inactive"
    <?php
    echo $status === "inactive"
        ? "selected"
        : "";
    ?>
>
    Inactive
</option>

</select>


<button
    type="submit"
    class="filter-btn"
>
    Search
</button>


<?php if (
    $search !== "" ||
    $status !== "all"
): ?>

<a
    href="agents.php"
    class="clear-btn"
>
    Clear
</a>

<?php endif; ?>


</form>


</section>


<!-- =====================================================
     AGENTS
========================================================= -->

<section class="agent-grid">


<?php if (
    empty($agents)
): ?>


<div class="empty">


<div class="empty-icon">
    🧑‍💼
</div>


<h3>
    No agents found
</h3>


<p>

Try changing your search or status filter.

</p>


</div>


<?php else: ?>


<?php foreach (
    $agents as $agent
): ?>


<?php

$agentName =
    $agent["name"]
    ?: "Unnamed Agent";


$agentInitial =
    strtoupper(
        substr(
            $agentName,
            0,
            1
        )
    );


$isActive =
    $agent["status"] === "active";


$createdDate =
    "N/A";


if (
    !empty(
        $agent["created_at"]
    )
) {

    $createdDate =
        date(
            "d M Y",
            strtotime(
                $agent["created_at"]
            )
        );

}

?>


<article class="agent-card">


<!-- =====================================================
     AGENT TOP
========================================================= -->

<div class="agent-top">


<div class="agent-avatar">

<?php
echo safe(
    $agentInitial
);
?>

</div>


<div>


<div class="agent-name">

<?php
echo safe(
    $agentName
);
?>

</div>


<div class="agent-email">

<?php
echo safe(
    $agent["email"]
);
?>

</div>


<span class="status
<?php

echo $isActive
    ? " active"
    : " inactive";

?>
">

<?php

echo $isActive
    ? "● Active"
    : "● Inactive";

?>

</span>


</div>


</div>


<!-- =====================================================
     DETAILS
========================================================= -->

<div class="agent-details">


<div class="detail-row">


<span class="detail-label">
    Phone
</span>


<span class="detail-value">

<?php

echo safe(
    $agent["phone"]
    ?: "Not provided"
);

?>

</span>


</div>


<div class="detail-row">


<span class="detail-label">
    Properties
</span>


<span class="detail-value property-count">

<?php

echo number_format(
    (int)$agent["property_count"]
);

?>

</span>


</div>


<div class="detail-row">


<span class="detail-label">
    Joined
</span>


<span class="detail-value">

<?php
echo safe(
    $createdDate
);
?>

</span>


</div>


<div class="detail-row">


<span class="detail-label">
    Verification
</span>


<span class="detail-value">

<?php

if ($isActive) {

    echo "✓ Verified";

}
else {

    echo "Pending";

}

?>

</span>


</div>


</div>


<!-- =====================================================
     ACTIONS
========================================================= -->

<div class="agent-actions">


<a
    href="user-details.php?id=<?php echo (int)$agent["id"]; ?>"
    class="action view"
>
    👁 View
</a>


<a
    href="user-edit.php?id=<?php echo (int)$agent["id"]; ?>"
    class="action edit"
>
    ✏️ Edit
</a>


<a
    href="properties.php?agent_id=<?php echo (int)$agent["id"]; ?>"
    class="action properties"
>
    🏠 Properties
</a>


</div>


</article>


<?php endforeach; ?>


<?php endif; ?>


</section>


</main>


</div>


<script>

/* =========================================================
   SEARCH UX
========================================================= */

const searchInput =
    document.querySelector(
        'input[name="search"]'
    );


if (searchInput) {

    searchInput.addEventListener(
        "keydown",
        function(event) {

            if (
                event.key ===
                "Escape"
            ) {

                searchInput.value = "";

            }

        }
    );

}


/* =========================================================
   AGENT CARD ANIMATION
========================================================= */

const cards =
    document.querySelectorAll(
        ".agent-card"
    );


cards.forEach(
    function(card, index) {

        card.style.opacity = "0";

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
            index * 60
        );

    }
);

</script>


</body>

</html>