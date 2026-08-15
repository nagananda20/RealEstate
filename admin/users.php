<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

function safe($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

/* =========================================================
   SEARCH / FILTERS
========================================================= */

$search = trim($_GET["search"] ?? "");
$role   = trim($_GET["role"] ?? "");
$status = trim($_GET["status"] ?? "");

$page = max(
    1,
    (int)($_GET["page"] ?? 1)
);

$limit = 10;
$offset = ($page - 1) * $limit;


/* =========================================================
   STATISTICS
========================================================= */

$totalUsers = 0;
$totalAdmins = 0;
$totalAgents = 0;
$totalActive = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(role = 'admin') AS admins,
        SUM(role = 'agent') AS agents,
        SUM(status = 'active') AS active_users
    FROM users
");

if ($result) {
    $stats = $result->fetch_assoc();

    $totalUsers  = (int)($stats["total"] ?? 0);
    $totalAdmins = (int)($stats["admins"] ?? 0);
    $totalAgents = (int)($stats["agents"] ?? 0);
    $totalActive = (int)($stats["active_users"] ?? 0);
}


/* =========================================================
   WHERE CONDITIONS
========================================================= */

$where = [];
$params = [];
$types = "";

if ($search !== "") {

    $where[] = "
        (
            name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

if ($role !== "") {

    $where[] = "role = ?";

    $params[] = $role;

    $types .= "s";
}

if ($status !== "") {

    $where[] = "status = ?";

    $params[] = $status;

    $types .= "s";
}

$whereSQL = "";

if (!empty($where)) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$countSQL = "
    SELECT COUNT(*) AS total
    FROM users
    $whereSQL
";

$countStmt = $conn->prepare($countSQL);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();

$countResult = $countStmt->get_result();

$totalRows = (int)(
    $countResult->fetch_assoc()["total"] ?? 0
);

$countStmt->close();

$totalPages = max(
    1,
    (int)ceil($totalRows / $limit)
);


/* =========================================================
   GET USERS
========================================================= */

$userSQL = "
    SELECT
        id,
        name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    $whereSQL
    ORDER BY id DESC
    LIMIT ? OFFSET ?
";

$userStmt = $conn->prepare($userSQL);

$bindTypes = $types . "ii";
$bindParams = $params;
$bindParams[] = $limit;
$bindParams[] = $offset;

$userStmt->bind_param(
    $bindTypes,
    ...$bindParams
);

$userStmt->execute();

$usersResult = $userStmt->get_result();

$users = [];

while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}

$userStmt->close();


/* =========================================================
   QUERY BUILDER
========================================================= */

function pageUrl($pageNumber)
{
    $query = $_GET;
    $query["page"] = $pageNumber;

    return "?" . http_build_query($query);
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
    Users | RealEstate Admin
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
    --white: #fff;

    --text: #18231f;
    --muted: #737c78;

    --border: #dfe6e2;

    --green: #17643b;
    --green-bg: #e8f6ed;

    --red: #b43843;
    --red-bg: #fdebed;

    --blue: #365caa;
    --blue-bg: #edf3ff;

    --orange: #996b12;
    --orange-bg: #fff5d8;
}

body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: var(--bg);
    color: var(--text);
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

    background: var(--primary);
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
    color: var(--accent);
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
    padding: 0 12px;
}

.menu a {
    height: 44px;

    display: flex;
    align-items: center;

    gap: 12px;

    padding: 0 13px;

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

    color: white;
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
    margin-left: 240px;

    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
    height: 75px;

    background: white;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

    position: sticky;

    top: 0;

    z-index: 20;
}

.topbar h1 {
    font-size: 18px;
}

.topbar p {
    margin-top: 4px;

    color: var(--muted);

    font-size: 8px;
}

.admin-badge {
    padding: 8px 12px;

    border-radius: 20px;

    background:
        var(--green-bg);

    color: var(--green);

    font-size: 8px;

    font-weight: 700;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
    max-width: 1450px;

    padding:
        28px 30px 60px;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 20px;
}

.stat-card {
    background: white;

    border:
        1px solid
        var(--border);

    border-radius: 9px;

    padding: 18px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.stat-title {
    color: var(--muted);

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.stat-number {
    margin-top: 8px;

    font-size: 22px;

    font-weight: 800;
}

.stat-icon {
    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background: #f0f4f2;

    font-size: 18px;
}


/* =========================================================
   MAIN CARD
========================================================= */

.card {
    background: white;

    border:
        1px solid
        var(--border);

    border-radius: 10px;

    overflow: hidden;
}

.card-header {
    padding:
        18px 20px;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;
}

.card-header h2 {
    font-size: 13px;
}

.total-label {
    color: var(--muted);

    font-size: 8px;
}


/* =========================================================
   FILTERS
========================================================= */

.filters {
    padding: 15px 20px;

    background: #fafcfb;

    border-bottom:
        1px solid
        var(--border);

    display: grid;

    grid-template-columns:
        1.8fr .8fr .8fr auto auto;

    gap: 8px;
}

.input,
.select {
    width: 100%;

    height: 40px;

    border:
        1px solid
        var(--border);

    border-radius: 6px;

    padding:
        0 11px;

    outline: none;

    background: white;

    color: var(--text);

    font-size: 8px;
}

.input:focus,
.select:focus {
    border-color:
        var(--primary);
}

.filter-btn {
    height: 40px;

    border: 0;

    border-radius: 6px;

    padding:
        0 15px;

    background:
        var(--primary);

    color: white;

    font-size: 8px;

    font-weight: 700;

    cursor: pointer;
}

.reset-btn {
    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 0 15px;

    border:
        1px solid
        var(--border);

    border-radius: 6px;

    text-decoration: none;

    color: var(--text);

    font-size: 8px;

    font-weight: 700;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 900px;
}

thead {
    background:
        #f7f9f8;
}

th {
    padding:
        13px 15px;

    text-align: left;

    color: var(--muted);

    font-size: 7px;

    text-transform: uppercase;

    letter-spacing: .5px;

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

    font-size: 8px;
}

tbody tr:hover {
    background:
        #fbfcfc;
}


/* =========================================================
   USER
========================================================= */

.user-info {
    display: flex;

    align-items: center;

    gap: 10px;
}

.avatar {
    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        var(--primary);

    color: white;

    font-weight: 800;

    font-size: 11px;
}

.user-name {
    font-weight: 800;

    font-size: 9px;
}

.user-email {
    margin-top: 3px;

    color: var(--muted);

    font-size: 7px;
}


/* =========================================================
   BADGES
========================================================= */

.badge {
    display: inline-flex;

    padding:
        5px 8px;

    border-radius: 20px;

    font-size: 7px;

    font-weight: 700;
}

.role-admin {
    color: #704f08;
    background: #fff4d1;
}

.role-agent {
    color: var(--blue);
    background: var(--blue-bg);
}

.role-user {
    color: var(--green);
    background: var(--green-bg);
}

.status-active {
    color: var(--green);
    background: var(--green-bg);
}

.status-inactive {
    color: var(--red);
    background: var(--red-bg);
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {
    display: flex;

    gap: 5px;
}

.action {
    width: 30px;
    height: 30px;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        var(--border);

    border-radius: 5px;

    text-decoration: none;

    color: var(--text);

    font-size: 11px;
}

.action:hover {
    background: #f2f5f3;
}

.action.delete {
    color: var(--red);
}


/* =========================================================
   EMPTY
========================================================= */

.empty {
    padding: 60px 20px;

    text-align: center;

    color: var(--muted);
}

.empty-icon {
    font-size: 35px;

    margin-bottom: 10px;
}

.empty-title {
    font-size: 12px;

    color: var(--text);

    font-weight: 800;
}

.empty-text {
    margin-top: 5px;

    font-size: 8px;
}


/* =========================================================
   PAGINATION
========================================================= */

.pagination {
    padding: 15px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}

.page-info {
    color: var(--muted);

    font-size: 8px;
}

.pages {
    display: flex;

    gap: 4px;
}

.page {
    min-width: 32px;
    height: 32px;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        var(--border);

    border-radius: 5px;

    text-decoration: none;

    color: var(--text);

    font-size: 8px;

    font-weight: 700;
}

.page:hover,
.page.current {
    background:
        var(--primary);

    color: white;

    border-color:
        var(--primary);
}


/* =========================================================
   RESPONSIVE
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
        width: 65px;
    }

    .logo {
        padding: 0;

        justify-content: center;

        font-size: 0;
    }

    .logo::after {
        content: "RE";

        font-size: 14px;
    }

    .menu-title {
        display: none;
    }

    .menu a {
        justify-content: center;
        padding: 0;
    }

    .menu a span:not(.icon) {
        display: none;
    }

    .main {
        margin-left: 65px;
    }

    .content {
        padding: 20px 15px;
    }

    .topbar {
        padding: 0 15px;
    }
}

@media(max-width:550px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .filters {
        grid-template-columns: 1fr;
    }

    .admin-badge {
        display: none;
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

<div>

<h1>
    User Management
</h1>

<p>
    Manage platform users, roles and accounts
</p>

</div>


<div class="admin-badge">

🛡️ Administrator

</div>

</header>


<main class="content">


<!-- =====================================================
     STATISTICS
===================================================== -->

<section class="stats">


<div class="stat-card">

<div>

<div class="stat-title">
    Total Users
</div>

<div class="stat-number">
    <?php echo number_format($totalUsers); ?>
</div>

</div>

<div class="stat-icon">
    👥
</div>

</div>


<div class="stat-card">

<div>

<div class="stat-title">
    Administrators
</div>

<div class="stat-number">
    <?php echo number_format($totalAdmins); ?>
</div>

</div>

<div class="stat-icon">
    🛡️
</div>

</div>


<div class="stat-card">

<div>

<div class="stat-title">
    Agents
</div>

<div class="stat-number">
    <?php echo number_format($totalAgents); ?>
</div>

</div>

<div class="stat-icon">
    🧑‍💼
</div>

</div>


<div class="stat-card">

<div>

<div class="stat-title">
    Active Accounts
</div>

<div class="stat-number">
    <?php echo number_format($totalActive); ?>
</div>

</div>

<div class="stat-icon">
    🟢
</div>

</div>


</section>


<!-- =====================================================
     USERS CARD
===================================================== -->

<section class="card">


<div class="card-header">

<h2>
    All Users
</h2>

<div class="total-label">

<?php
echo number_format($totalRows);
?>
 matching users

</div>

</div>


<!-- FILTERS -->

<form
    method="GET"
    class="filters"
>


<input
    type="text"
    name="search"
    class="input"
    placeholder="Search name, email or phone..."
    value="<?php echo safe($search); ?>"
>


<select
    name="role"
    class="select"
>

<option value="">
    All Roles
</option>

<option
    value="admin"
    <?php
    echo $role === "admin"
        ? "selected"
        : "";
    ?>
>
    Administrator
</option>

<option
    value="agent"
    <?php
    echo $role === "agent"
        ? "selected"
        : "";
    ?>
>
    Agent
</option>

<option
    value="user"
    <?php
    echo $role === "user"
        ? "selected"
        : "";
    ?>
>
    User
</option>

</select>


<select
    name="status"
    class="select"
>

<option value="">
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
    🔍 Search
</button>


<a
    href="users.php"
    class="reset-btn"
>
    Reset
</a>


</form>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-wrapper">


<?php if (!empty($users)): ?>


<table>


<thead>

<tr>

<th>
    User
</th>

<th>
    Phone
</th>

<th>
    Role
</th>

<th>
    Status
</th>

<th>
    Joined
</th>

<th>
    Actions
</th>

</tr>

</thead>


<tbody>


<?php foreach ($users as $user): ?>


<?php

$userName =
    $user["name"] ?? "Unknown";

$firstLetter =
    strtoupper(
        substr(
            $userName,
            0,
            1
        )
    );

$userRole =
    $user["role"] ?? "user";

$userStatus =
    $user["status"] ?? "inactive";

$joined =
    !empty($user["created_at"])
        ? date(
            "d M Y",
            strtotime(
                $user["created_at"]
            )
        )
        : "N/A";

?>


<tr>


<td>


<div class="user-info">


<div class="avatar">

<?php
echo safe($firstLetter);
?>

</div>


<div>

<div class="user-name">

<?php
echo safe($userName);
?>

</div>


<div class="user-email">

<?php
echo safe(
    $user["email"]
);
?>

</div>


</div>


</div>


</td>


<td>

<?php
echo safe(
    $user["phone"] ?: "—"
);
?>

</td>


<td>


<span class="badge
<?php

if ($userRole === "admin") {

    echo " role-admin";

}
elseif ($userRole === "agent") {

    echo " role-agent";

}
else {

    echo " role-user";

}

?>
">

<?php

if ($userRole === "admin") {

    echo "Administrator";

}
elseif ($userRole === "agent") {

    echo "Agent";

}
else {

    echo "User";

}

?>

</span>


</td>


<td>


<span class="badge
<?php

echo $userStatus === "active"
    ? " status-active"
    : " status-inactive";

?>
">

<?php

echo $userStatus === "active"
    ? "Active"
    : "Inactive";

?>

</span>


</td>


<td>

<?php
echo safe($joined);
?>

</td>


<td>


<div class="actions">


<a
    href="user-details.php?id=<?php
    echo (int)$user["id"];
    ?>"
    class="action"
    title="View"
>
    👁️
</a>


<a
    href="user-edit.php?id=<?php
    echo (int)$user["id"];
    ?>"
    class="action"
    title="Edit"
>
    ✏️
</a>


<?php if (
    (int)$user["id"] !==
    (int)$_SESSION["user_id"]
): ?>

<a
    href="user-delete.php?id=<?php
    echo (int)$user["id"];
    ?>"
    class="action delete delete-user"
    title="Delete"
>
    🗑️
</a>

<?php endif; ?>


</div>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


<?php else: ?>


<div class="empty">

<div class="empty-icon">
    👥
</div>

<div class="empty-title">
    No users found
</div>

<div class="empty-text">
    Try changing your search or filters.
</div>

</div>


<?php endif; ?>


</div>


<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if ($totalRows > 0): ?>


<div class="pagination">


<div class="page-info">

Page
<?php echo $page; ?>
of
<?php echo $totalPages; ?>

</div>


<div class="pages">


<?php if ($page > 1): ?>

<a
    href="<?php
    echo safe(
        pageUrl($page - 1)
    );
    ?>"
    class="page"
>
    ‹
</a>

<?php endif; ?>


<?php

$startPage =
    max(
        1,
        $page - 2
    );

$endPage =
    min(
        $totalPages,
        $page + 2
    );

for (
    $i = $startPage;
    $i <= $endPage;
    $i++
):

?>

<a
    href="<?php
    echo safe(
        pageUrl($i)
    );
    ?>"
    class="page
    <?php
    echo $i === $page
        ? " current"
        : "";
    ?>"
>
    <?php echo $i; ?>
</a>

<?php endfor; ?>


<?php if (
    $page < $totalPages
): ?>

<a
    href="<?php
    echo safe(
        pageUrl($page + 1)
    );
    ?>"
    class="page"
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


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

document
    .querySelectorAll(".delete-user")
    .forEach(function(button) {

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

    });


/* =========================================================
   AUTO SEARCH SHORTCUT
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.ctrlKey &&
            event.key.toLowerCase() === "k"
        ) {

            event.preventDefault();

            const search =
                document.querySelector(
                    'input[name="search"]'
                );

            if (search) {
                search.focus();
            }

        }

    }
);

</script>

</body>
</html>