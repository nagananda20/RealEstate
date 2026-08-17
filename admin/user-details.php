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

$stmt->bind_param("i", $userId);
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

$name = $user["name"] ?? "Unknown User";
$email = $user["email"] ?? "";
$phone = $user["phone"] ?? "";
$role = $user["role"] ?? "user";
$status = $user["status"] ?? "inactive";
$createdAt = $user["created_at"] ?? "";
$initial = strtoupper(substr($name, 0, 1));


/* =========================================================
   DATE
========================================================= */

$joinedDate = "N/A";
if (!empty($createdAt)) {
    $timestamp = strtotime($createdAt);
    if ($timestamp) {
        $joinedDate = date("d M Y, h:i A", $timestamp);
    }
}


/* =========================================================
   PROPERTY COUNT
========================================================= */

$propertyCount = 0;
$table = $conn->query("SHOW TABLES LIKE 'properties'");
if ($table && $table->num_rows > 0) {
    $sql = "SELECT COUNT(*) AS total FROM properties WHERE agent_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $propertyCount = (int)($row["total"] ?? 0);
        $stmt->close();
    }
}


/* =========================================================
   ENQUIRY COUNT
========================================================= */

$enquiryCount = 0;
$table = $conn->query("SHOW TABLES LIKE 'enquiries'");
if ($table && $table->num_rows > 0) {
    $sql = "SELECT COUNT(*) AS total FROM enquiries WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $enquiryCount = (int)($row["total"] ?? 0);
        $stmt->close();
    }
}


/* =========================================================
   VISIT COUNT
========================================================= */

$visitCount = 0;
$table = $conn->query("SHOW TABLES LIKE 'visits'");
if ($table && $table->num_rows > 0) {
    $sql = "SELECT COUNT(*) AS total FROM visits WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $visitCount = (int)($row["total"] ?? 0);
        $stmt->close();
    }
}


/* =========================================================
   FAVORITES
========================================================= */

$favoriteCount = 0;
$table = $conn->query("SHOW TABLES LIKE 'favorites'");
if ($table && $table->num_rows > 0) {
    $sql = "SELECT COUNT(*) AS total FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $favoriteCount = (int)($row["total"] ?? 0);
        $stmt->close();
    }
}


/* =========================================================
   USER PROPERTIES
========================================================= */

$userProperties = [];
$table = $conn->query("SHOW TABLES LIKE 'properties'");
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
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userProperties[] = $row;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo safe($name); ?> | User Details</title>

<style>
/* =========================================================
   RESET & VARIABLES
========================================================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #174a3a;
    --primary-light: #236852;
    --primary-dark: #10372b;
    --accent: #d7a94b;
    --accent-light: #fbeecb;
    --bg: #f4f7f6;
    --white: #ffffff;
    --text: #1b2622;
    --muted: #6b7772;
    --border: #e2e8e5;
    --green: #15803d;
    --green-bg: #dcfce7;
    --red: #b91c1c;
    --red-bg: #fee2e2;
    --blue: #2563eb;
    --blue-bg: #dbeafe;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 8px 24px rgba(23,74,58,0.08);
    --shadow-lg: 0 16px 36px rgba(23,74,58,0.12);
    --radius: 12px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
}

/* =========================================================
   ANIMATIONS & KEYFRAMES
========================================================= */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(22px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulseGlow {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(23, 74, 58, 0.4);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 0 12px rgba(23, 74, 58, 0);
        transform: scale(1.03);
    }
}

@keyframes pulseDot {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.4);
        opacity: 0.6;
    }
}

@keyframes floatIcon {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

.anim-fade-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.delay-1 { animation-delay: 0.08s; opacity: 0; animation-fill-mode: forwards; }
.delay-2 { animation-delay: 0.16s; opacity: 0; animation-fill-mode: forwards; }
.delay-3 { animation-delay: 0.24s; opacity: 0; animation-fill-mode: forwards; }
.delay-4 { animation-delay: 0.32s; opacity: 0; animation-fill-mode: forwards; }
.delay-5 { animation-delay: 0.40s; opacity: 0; animation-fill-mode: forwards; }

/* =========================================================
   SIDEBAR
========================================================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 240px;
    background: var(--primary);
    color: white;
    z-index: 100;
    transition: var(--transition);
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
    border-bottom: 1px solid rgba(255,255,255,0.08);
    transition: var(--transition);
}

.logo:hover {
    color: var(--accent);
}

.logo strong {
    color: var(--accent);
    margin-left: 2px;
}

.menu-title {
    padding: 20px 25px 8px;
    color: rgba(255,255,255,0.45);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.menu {
    padding: 0 12px;
}

.menu a {
    height: 44px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 14px;
    margin-bottom: 4px;
    border-radius: 8px;
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: var(--transition);
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,0.12);
    color: white;
    transform: translateX(4px);
}

.menu a.active {
    background: var(--primary-light);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.icon {
    width: 22px;
    text-align: center;
    font-size: 15px;
}

.sidebar-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.logout-link {
    color: #ffb8bf;
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    transition: var(--transition);
}

.logout-link:hover {
    background: rgba(255, 184, 191, 0.15);
    color: #ffffff;
    transform: translateX(3px);
}

/* =========================================================
   MAIN LAYOUT & TOPBAR
========================================================= */
.main {
    margin-left: 240px;
    min-height: 100vh;
    transition: var(--transition);
}

.topbar {
    height: 75px;
    background: var(--white);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    position: sticky;
    top: 0;
    z-index: 30;
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.95);
    box-shadow: var(--shadow-sm);
}

.top-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.back {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    background: var(--white);
    transition: var(--transition);
}

.back:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    transform: translateX(-3px);
    box-shadow: var(--shadow-sm);
}

.topbar h1 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
}

.topbar p {
    margin-top: 2px;
    color: var(--muted);
    font-size: 12px;
}

.top-actions {
    display: flex;
    gap: 10px;
}

.btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
    border: none;
}

.btn:active {
    transform: scale(0.97);
}

.btn-edit {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 12px rgba(23, 74, 58, 0.2);
}

.btn-edit:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(23, 74, 58, 0.28);
}

.btn-delete {
    background: var(--red-bg);
    color: var(--red);
}

.btn-delete:hover {
    background: var(--red);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(185, 28, 28, 0.25);
}

/* =========================================================
   CONTENT & PROFILE CARD
========================================================= */
.content {
    max-width: 1400px;
    padding: 32px;
}

.profile-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.profile-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
}

.profile-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.profile-left {
    display: flex;
    align-items: center;
    gap: 22px;
}

.avatar {
    width: 82px;
    height: 82px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    font-size: 32px;
    font-weight: 800;
    box-shadow: 0 8px 24px rgba(23, 74, 58, 0.25);
    animation: pulseGlow 4s infinite ease-in-out;
    transition: var(--transition);
}

.profile-card:hover .avatar {
    transform: scale(1.08) rotate(3deg);
}

.profile-name {
    font-size: 24px;
    font-weight: 800;
    color: var(--text);
}

.profile-email {
    margin-top: 4px;
    color: var(--muted);
    font-size: 13px;
}

.profile-tags {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    transition: var(--transition);
}

.badge:hover {
    transform: scale(1.05);
}

.role-admin {
    color: #78350f;
    background: #fef3c7;
    border: 1px solid #fde68a;
}

.role-agent {
    color: var(--blue);
    background: var(--blue-bg);
    border: 1px solid #bfdbfe;
}

.role-user {
    color: var(--green);
    background: var(--green-bg);
    border: 1px solid #bbf7d0;
}

.status-active {
    color: var(--green);
    background: var(--green-bg);
    border: 1px solid #bbf7d0;
}

.status-inactive {
    color: var(--red);
    background: var(--red-bg);
    border: 1px solid #fecaca;
}

.joined {
    text-align: right;
    color: var(--muted);
    font-size: 12px;
}

.joined strong {
    display: block;
    color: var(--text);
    font-size: 14px;
    margin-top: 6px;
    font-weight: 700;
}

/* =========================================================
   STATISTICS
========================================================= */
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat:hover {
    transform: translateY(-5px);
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
}

.stat:hover .stat-icon {
    animation: floatIcon 0.8s ease-in-out infinite;
}

.stat-icon {
    font-size: 26px;
    display: inline-block;
    transition: var(--transition);
}

.stat-number {
    margin-top: 12px;
    font-size: 28px;
    font-weight: 800;
    color: var(--text);
}

.stat-label {
    margin-top: 4px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* =========================================================
   GRID & CARDS
========================================================= */
.grid {
    display: grid;
    grid-template-columns: 1.5fr 0.9fr;
    gap: 24px;
}

.card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--shadow-md);
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fafcfb;
}

.card-header h2 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
}

.card-body {
    padding: 24px;
}

/* =========================================================
   INFO GRID
========================================================= */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0 28px;
}

.info-item {
    padding: 16px 0;
    border-bottom: 1px solid #edf0ee;
    transition: var(--transition);
}

.info-item:hover {
    padding-left: 6px;
    background: rgba(23, 74, 58, 0.02);
}

.info-label {
    color: var(--muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
}

.info-value {
    margin-top: 6px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}

/* =========================================================
   QUICK ACTIONS
========================================================= */
.quick-actions {
    display: grid;
    gap: 10px;
}

.quick {
    min-height: 48px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    background: var(--white);
    transition: var(--transition);
}

.quick:hover {
    background: #f7faf8;
    border-color: var(--primary);
    color: var(--primary);
    transform: translateX(6px);
    box-shadow: var(--shadow-sm);
}

.quick.danger {
    color: var(--red);
}

.quick.danger:hover {
    background: var(--red-bg);
    border-color: var(--red);
}

/* =========================================================
   PROPERTY LIST
========================================================= */
.property {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 12px;
    border-radius: 8px;
    border-bottom: 1px solid #edf0ee;
    transition: var(--transition);
}

.property:hover {
    background: #f7faf8;
    transform: translateX(4px);
    border-left: 3px solid var(--primary);
}

.property:last-child {
    border-bottom: none;
}

.property-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
}

.property-meta {
    margin-top: 4px;
    color: var(--muted);
    font-size: 12px;
}

.property-price {
    font-size: 15px;
    font-weight: 800;
    color: var(--primary);
}

.property-status {
    margin-top: 4px;
    font-size: 11px;
    font-weight: 600;
    color: var(--green);
}

/* =========================================================
   ACTIVITY TIMELINE
========================================================= */
.activity {
    position: relative;
    padding-left: 10px;
}

.activity-item {
    position: relative;
    padding: 0 0 24px 28px;
}

.activity-item::before {
    content: "";
    position: absolute;
    left: 4px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 0 4px rgba(23, 74, 58, 0.15);
    animation: pulseDot 2.5s infinite ease-in-out;
}

.activity-item:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 9px;
    top: 18px;
    width: 2px;
    height: calc(100% - 10px);
    background: var(--border);
}

.activity-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
}

.activity-date {
    margin-top: 4px;
    color: var(--muted);
    font-size: 12px;
}

.empty {
    padding: 40px 15px;
    text-align: center;
    color: var(--muted);
    font-size: 13px;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media(max-width: 1100px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 800px) {
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
        font-size: 16px;
        font-weight: 800;
        color: var(--accent);
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
        padding: 20px 16px;
    }
    .profile-card {
        align-items: flex-start;
        flex-direction: column;
    }
    .joined {
        text-align: left;
    }
}

@media(max-width: 600px) {
    .topbar {
        padding: 0 16px;
    }
    .top-actions .btn span {
        display: none;
    }
    .avatar {
        width: 65px;
        height: 65px;
        font-size: 24px;
    }
    .profile-name {
        font-size: 20px;
    }
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- =====================================================
     SIDEBAR
===================================================== -->
<aside class="sidebar">
    <a href="dashboard.php" class="logo">
        Real<strong>Estate</strong>
    </a>
    <div class="menu-title">Administration</div>
    <nav class="menu">
        <a href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a>
        <a href="properties.php"><span class="icon">🏠</span><span>Properties</span></a>
        <a href="users.php" class="active"><span class="icon">👥</span><span>Users</span></a>
        <a href="agents.php"><span class="icon">🧑‍💼</span><span>Agents</span></a>
        <a href="enquiries.php"><span class="icon">💬</span><span>Enquiries</span></a>
        <a href="visits.php"><span class="icon">📅</span><span>Visits</span></a>
        <a href="settings.php"><span class="icon">⚙️</span><span>Settings</span></a>
    </nav>
    <div class="sidebar-bottom">
        <a href="../auth/logout.php" class="logout-link">
            🚪 <span>Logout</span>
        </a>
    </div>
</aside>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->
<div class="main">
    <header class="topbar">
        <div class="top-left">
            <a href="users.php" class="back" title="Back to Users">←</a>
            <div>
                <h1>User Details</h1>
                <p>Complete account information and activity</p>
            </div>
        </div>
        <div class="top-actions">
            <a href="user-edit.php?id=<?php echo (int)$userId; ?>" class="btn btn-edit">
                ✏️ <span>Edit User</span>
            </a>
            <?php if ((int)$userId !== (int)$_SESSION["user_id"]): ?>
            <a href="user-delete.php?id=<?php echo (int)$userId; ?>" class="btn btn-delete" id="deleteUser">
                🗑️ <span>Delete</span>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="content">
        <!-- PROFILE CARD (ANIMATED) -->
        <section class="profile-card anim-fade-up">
            <div class="profile-left">
                <div class="avatar">
                    <?php echo safe($initial); ?>
                </div>
                <div>
                    <div class="profile-name"><?php echo safe($name); ?></div>
                    <div class="profile-email"><?php echo safe($email); ?></div>
                    <div class="profile-tags">
                        <span class="badge <?php
                            if ($role === "admin") echo "role-admin";
                            elseif ($role === "agent") echo "role-agent";
                            else echo "role-user";
                        ?>">
                            <?php
                            if ($role === "admin") echo "🛡️ Administrator";
                            elseif ($role === "agent") echo "🧑‍💼 Agent";
                            else echo "👤 User";
                            ?>
                        </span>
                        <span class="badge <?php echo $status === "active" ? "status-active" : "status-inactive"; ?>">
                            <?php echo $status === "active" ? "● Active Account" : "● Inactive Account"; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="joined">
                Member since
                <strong><?php echo safe($joinedDate); ?></strong>
            </div>
        </section>

        <!-- STATISTICS CARDS (ANIMATED) -->
        <section class="stats">
            <div class="stat anim-fade-up delay-1">
                <div class="stat-icon">🏠</div>
                <div class="stat-number count-up" data-target="<?php echo $propertyCount; ?>">
                    <?php echo number_format($propertyCount); ?>
                </div>
                <div class="stat-label">Properties</div>
            </div>
            <div class="stat anim-fade-up delay-2">
                <div class="stat-icon">💬</div>
                <div class="stat-number count-up" data-target="<?php echo $enquiryCount; ?>">
                    <?php echo number_format($enquiryCount); ?>
                </div>
                <div class="stat-label">Enquiries</div>
            </div>
            <div class="stat anim-fade-up delay-3">
                <div class="stat-icon">📅</div>
                <div class="stat-number count-up" data-target="<?php echo $visitCount; ?>">
                    <?php echo number_format($visitCount); ?>
                </div>
                <div class="stat-label">Visits</div>
            </div>
            <div class="stat anim-fade-up delay-4">
                <div class="stat-icon">❤️</div>
                <div class="stat-number count-up" data-target="<?php echo $favoriteCount; ?>">
                    <?php echo number_format($favoriteCount); ?>
                </div>
                <div class="stat-label">Favorites</div>
            </div>
        </section>

        <!-- MAIN GRID (ANIMATED) -->
        <div class="grid anim-fade-up delay-5">
            <!-- LEFT COLUMN -->
            <div>
                <!-- USER INFORMATION -->
                <section class="card">
                    <div class="card-header">
                        <h2>📋 Account Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">#<?php echo (int)$userId; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo safe($name); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo safe($email); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone Number</div>
                                <div class="info-value"><?php echo safe($phone ?: "Not provided"); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Role</div>
                                <div class="info-value"><?php echo ucfirst(safe($role)); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Status</div>
                                <div class="info-value"><?php echo ucfirst(safe($status)); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Registration Date</div>
                                <div class="info-value"><?php echo safe($joinedDate); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Profile Code</div>
                                <div class="info-value">RE-<?php echo str_pad($userId, 5, "0", STR_PAD_LEFT); ?></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- USER PROPERTIES -->
                <section class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h2>🏠 Associated Properties</h2>
                        <a href="properties.php?agent_id=<?php echo (int)$userId; ?>" style="color: var(--primary); text-decoration: none; font-size: 12px; font-weight: 700;">
                            View All →
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($userProperties)): ?>
                            <?php foreach ($userProperties as $property): ?>
                            <div class="property">
                                <div>
                                    <div class="property-title"><?php echo safe($property["title"]); ?></div>
                                    <div class="property-meta">
                                        <?php echo safe($property["city"] ?: "Location not available"); ?> &nbsp;•&nbsp; 
                                        <?php echo $property["listing_type"] === "rent" ? "For Rent" : "For Sale"; ?>
                                    </div>
                                    <div class="property-status">● <?php echo ucfirst(safe($property["status"])); ?></div>
                                </div>
                                <div class="property-price">
                                    ₹<?php echo number_format((float)($property["price"] ?? 0)); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty">
                                🏠<br><br>
                                No properties associated with this user.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- RIGHT COLUMN -->
            <div>
                <!-- QUICK ACTIONS -->
                <section class="card">
                    <div class="card-header">
                        <h2>⚡ Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="user-edit.php?id=<?php echo (int)$userId; ?>" class="quick">
                                <span>✏️</span> Edit Account
                            </a>
                            <?php if ($role === "agent"): ?>
                            <a href="properties.php?agent_id=<?php echo (int)$userId; ?>" class="quick">
                                <span>🏠</span> View Agent Properties
                            </a>
                            <?php endif; ?>
                            <a href="enquiries.php?user_id=<?php echo (int)$userId; ?>" class="quick">
                                <span>💬</span> View Enquiries
                            </a>
                            <a href="visits.php?user_id=<?php echo (int)$userId; ?>" class="quick">
                                <span>📅</span> View Visits
                            </a>
                            <?php if ((int)$userId !== (int)$_SESSION["user_id"]): ?>
                            <a href="user-delete.php?id=<?php echo (int)$userId; ?>" class="quick danger" id="quickDelete">
                                <span>🗑️</span> Delete Account
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ACCOUNT SECURITY -->
                <section class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h2>🔒 Account Security</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value">
                                <?php echo $status === "active" ? "🟢 Account is active" : "🔴 Account is inactive"; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">User Role</div>
                            <div class="info-value">
                                <?php
                                if ($role === "admin") echo "🛡️ Administrator";
                                elseif ($role === "agent") echo "🧑‍💼 Real Estate Agent";
                                else echo "👤 Registered User";
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Created</div>
                            <div class="info-value"><?php echo safe($joinedDate); ?></div>
                        </div>
                    </div>
                </section>

                <!-- ACTIVITY TIMELINE -->
                <section class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h2>⏱️ Account Activity</h2>
                    </div>
                    <div class="card-body">
                        <div class="activity">
                            <div class="activity-item">
                                <div class="activity-title">Account Created</div>
                                <div class="activity-date"><?php echo safe($joinedDate); ?></div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-title">Account Status</div>
                                <div class="activity-date"><?php echo $status === "active" ? "Currently active" : "Currently inactive"; ?></div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-title">Current Role</div>
                                <div class="activity-date"><?php echo ucfirst(safe($role)); ?></div>
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
   ANIMATED NUMBER COUNTERS
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const counters = document.querySelectorAll(".count-up");
    counters.forEach(counter => {
        const target = +counter.getAttribute("data-target");
        if (target === 0) return;
        
        let count = 0;
        const speed = target > 50 ? 20 : 60;
        const step = Math.max(1, Math.floor(target / 20));
        
        const updateCount = () => {
            count += step;
            if (count < target) {
                counter.innerText = count.toLocaleString();
                setTimeout(updateCount, speed);
            } else {
                counter.innerText = target.toLocaleString();
            }
        };
        updateCount();
    });
});

/* =========================================================
   DELETE CONFIRMATION
========================================================= */
const deleteButtons = [
    document.getElementById("deleteUser"),
    document.getElementById("quickDelete")
];

deleteButtons.forEach(button => {
    if (!button) return;
    button.addEventListener("click", event => {
        const confirmed = confirm(
            "Are you sure you want to delete this user?\n\nThis action cannot be undone."
        );
        if (!confirmed) {
            event.preventDefault();
        }
    });
});
</script>

</body>
</html>