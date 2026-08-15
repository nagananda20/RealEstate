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
   DEFAULT VALUES
========================================================= */

$customerName = "";
$customerEmail = "";
$customerPhone = "";

$propertyId = "";
$agentId = "";

$visitDate = "";
$visitTime = "";

$status = "pending";

$notes = "";

$errors = [];

$success = "";


/* =========================================================
   LOAD PROPERTIES
========================================================= */

$properties = [];

$propertyTable =
    $conn->query(
        "SHOW TABLES LIKE 'properties'"
    );


if (
    $propertyTable &&
    $propertyTable->num_rows > 0
) {

    $propertyResult =
        $conn->query(
            "SELECT id, title, location
             FROM properties
             ORDER BY id DESC"
        );


    if ($propertyResult) {

        while (
            $property =
            $propertyResult->fetch_assoc()
        ) {

            $properties[] =
                $property;

        }

    }

}


/* =========================================================
   LOAD AGENTS
========================================================= */

$agents = [];

$agentTable =
    $conn->query(
        "SHOW TABLES LIKE 'users'"
    );


if (
    $agentTable &&
    $agentTable->num_rows > 0
) {

    $agentResult =
        $conn->query(
            "SELECT id, name, email
             FROM users
             WHERE role = 'agent'
             ORDER BY name ASC"
        );


    if ($agentResult) {

        while (
            $agent =
            $agentResult->fetch_assoc()
        ) {

            $agents[] =
                $agent;

        }

    }

}


/* =========================================================
   CHECK VISITS TABLE
========================================================= */

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'visits'"
    );


if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {

    $errors[] =
        "The visits table does not exist. Please create the visits table first.";

}


/* =========================================================
   GET VISITS COLUMNS
========================================================= */

$columns = [];


if (empty($errors)) {

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

}


/* =========================================================
   FORM SUBMISSION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    empty($errors)
) {


    $customerName =
        trim(
            $_POST["customer_name"]
            ?? ""
        );


    $customerEmail =
        trim(
            $_POST["customer_email"]
            ?? ""
        );


    $customerPhone =
        trim(
            $_POST["customer_phone"]
            ?? ""
        );


    $propertyId =
        trim(
            $_POST["property_id"]
            ?? ""
        );


    $agentId =
        trim(
            $_POST["agent_id"]
            ?? ""
        );


    $visitDate =
        trim(
            $_POST["visit_date"]
            ?? ""
        );


    $visitTime =
        trim(
            $_POST["visit_time"]
            ?? ""
        );


    $status =
        trim(
            $_POST["status"]
            ?? "pending"
        );


    $notes =
        trim(
            $_POST["notes"]
            ?? ""
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($customerName === "") {

        $errors[] =
            "Customer name is required.";

    }


    if (
        $customerEmail !== "" &&
        !filter_var(
            $customerEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if ($propertyId === "") {

        $errors[] =
            "Please select a property.";

    }


    if ($visitDate === "") {

        $errors[] =
            "Visit date is required.";

    }


    if ($visitTime === "") {

        $errors[] =
            "Visit time is required.";

    }


    $allowedStatuses = [
        "pending",
        "confirmed",
        "completed",
        "cancelled"
    ];


    if (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        $errors[] =
            "Invalid visit status.";

    }


    /* =====================================================
       DATE VALIDATION
    ===================================================== */

    if ($visitDate !== "") {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $visitDate
            );


        if (
            !$dateObject ||
            $dateObject->format("Y-m-d")
            !== $visitDate
        ) {

            $errors[] =
                "Invalid visit date.";

        }

    }


    /* =====================================================
       INSERT
    ===================================================== */

    if (empty($errors)) {


        /*
         * The code below adapts to the columns
         * already present in your visits table.
         */

        $insertColumns = [];

        $placeholders = [];

        $params = [];

        $types = "";


        /* CUSTOMER NAME */

        if (
            in_array(
                "name",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "name";

            $placeholders[] =
                "?";

            $params[] =
                $customerName;

            $types .= "s";

        }


        /* EMAIL */

        if (
            in_array(
                "email",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "email";

            $placeholders[] =
                "?";

            $params[] =
                $customerEmail;

            $types .= "s";

        }


        /* PHONE */

        if (
            in_array(
                "phone",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "phone";

            $placeholders[] =
                "?";

            $params[] =
                $customerPhone;

            $types .= "s";

        }


        /* PROPERTY */

        if (
            in_array(
                "property_id",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "property_id";

            $placeholders[] =
                "?";

            $params[] =
                (int)$propertyId;

            $types .= "i";

        }


        /* AGENT */

        if (
            in_array(
                "agent_id",
                $columns,
                true
            )
        ) {

            if ($agentId === "") {

                /*
                 * Do not insert NULL through a parameter.
                 * We will add NULL directly.
                 */

                $insertColumns[] =
                    "agent_id";

                $placeholders[] =
                    "NULL";

            } else {

                $insertColumns[] =
                    "agent_id";

                $placeholders[] =
                    "?";

                $params[] =
                    (int)$agentId;

                $types .= "i";

            }

        }


        /* DATE */

        if (
            in_array(
                "visit_date",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "visit_date";

            $placeholders[] =
                "?";

            $params[] =
                $visitDate;

            $types .= "s";

        }


        /* TIME */

        if (
            in_array(
                "visit_time",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "visit_time";

            $placeholders[] =
                "?";

            $params[] =
                $visitTime;

            $types .= "s";

        }


        /* STATUS */

        if (
            in_array(
                "status",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "status";

            $placeholders[] =
                "?";

            $params[] =
                $status;

            $types .= "s";

        }


        /* NOTES */

        if (
            in_array(
                "notes",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "notes";

            $placeholders[] =
                "?";

            $params[] =
                $notes;

            $types .= "s";

        }


        /* CREATED BY */

        if (
            in_array(
                "created_by",
                $columns,
                true
            )
        ) {

            $insertColumns[] =
                "created_by";

            $placeholders[] =
                "?";

            $params[] =
                (int)$_SESSION["user_id"];

            $types .= "i";

        }


        /* =================================================
           CHECK REQUIRED COLUMNS
        ================================================= */

        if (
            empty($insertColumns)
        ) {

            $errors[] =
                "No compatible columns were found in the visits table.";

        }


        /* =================================================
           INSERT QUERY
        ================================================= */

        if (empty($errors)) {

            $sql =
                "INSERT INTO visits ("
                .
                implode(
                    ", ",
                    $insertColumns
                )
                .
                ") VALUES ("
                .
                implode(
                    ", ",
                    $placeholders
                )
                .
                ")";


            $stmt =
                $conn->prepare($sql);


            if (!$stmt) {

                $errors[] =
                    "Database error: "
                    .
                    $conn->error;

            } else {


                if (!empty($params)) {

                    $stmt->bind_param(
                        $types,
                        ...$params
                    );

                }


                if (
                    $stmt->execute()
                ) {

                    $success =
                        "Property visit scheduled successfully.";

                    /*
                     * Clear form
                     */

                    $customerName = "";
                    $customerEmail = "";
                    $customerPhone = "";
                    $propertyId = "";
                    $agentId = "";
                    $visitDate = "";
                    $visitTime = "";
                    $status = "pending";
                    $notes = "";


                } else {

                    $errors[] =
                        "Unable to schedule visit: "
                        .
                        $stmt->error;

                }


                $stmt->close();

            }

        }

    }

}


/* =========================================================
   TODAY
========================================================= */

$today =
    date("Y-m-d");

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
    Schedule Visit | RealEstate
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

    --green:#17643b;
    --green-bg:#e8f6ed;

    --red:#b43843;
    --red-bg:#fdebed;

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

    top:0;
    left:0;
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


.topbar-left {

    display:flex;

    align-items:center;

    gap:12px;

}


.back {

    width:36px;

    height:36px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#eef1ef;

    color:var(--text);

    text-decoration:none;

    border-radius:6px;

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

    max-width:1050px;

    margin:auto;

    padding:
        30px;

}


/* =========================================================
   TITLE
========================================================= */

.page-title {

    margin-bottom:20px;

}


.page-title h2 {

    font-size:22px;

}


.page-title p {

    margin-top:6px;

    color:var(--muted);

    font-size:8px;

}


/* =========================================================
   ALERTS
========================================================= */

.alert {

    padding:
        14px 16px;

    border-radius:7px;

    margin-bottom:15px;

    font-size:8px;

    line-height:1.5;

}


.alert-error {

    background:
        var(--red-bg);

    color:
        var(--red);

}


.alert-success {

    background:
        var(--green-bg);

    color:
        var(--green);

}


.alert ul {

    margin:
        7px 0 0 18px;

}


/* =========================================================
   LAYOUT
========================================================= */

.layout {

    display:grid;

    grid-template-columns:
        2fr 1fr;

    gap:18px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    overflow:hidden;

    margin-bottom:18px;

}


.card-header {

    padding:
        17px 20px;

    border-bottom:
        1px solid
        #edf0ee;

    font-size:11px;

    font-weight:800;

}


.card-body {

    padding:22px;

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:17px;

}


.form-group {

    display:flex;

    flex-direction:column;

    gap:7px;

}


.full {

    grid-column:
        1 / -1;

}


label {

    font-size:8px;

    font-weight:700;

    color:#4d5752;

}


.required {

    color:
        var(--red);

}


input,
select,
textarea {

    width:100%;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    background:white;

    color:var(--text);

    outline:none;

    font-family:inherit;

    font-size:9px;

}


input,
select {

    height:42px;

    padding:
        0 12px;

}


textarea {

    min-height:125px;

    padding:12px;

    resize:vertical;

    line-height:1.6;

}


input:focus,
select:focus,
textarea:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23,74,58,.08);

}


/* =========================================================
   PROPERTY SELECT
========================================================= */

.property-option {

    font-size:9px;

}


/* =========================================================
   SIDEBAR INFO
========================================================= */

.info-box {

    padding:15px;

    background:#fafbfa;

    border:
        1px solid
        #edf0ee;

    border-radius:7px;

}


.info-title {

    font-size:9px;

    font-weight:800;

    margin-bottom:12px;

}


.info-item {

    display:flex;

    gap:10px;

    padding:11px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.info-item:last-child {

    border-bottom:none;

}


.info-icon {

    width:31px;

    height:31px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#eef2ef;

    border-radius:6px;

}


.info-text strong {

    display:block;

    font-size:8px;

}


.info-text span {

    display:block;

    margin-top:4px;

    color:var(--muted);

    font-size:7px;

    line-height:1.5;

}


/* =========================================================
   BUTTONS
========================================================= */

.actions {

    display:flex;

    justify-content:flex-end;

    gap:9px;

}


.btn {

    min-width:120px;

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:none;

    border-radius:6px;

    padding:
        0 18px;

    cursor:pointer;

    text-decoration:none;

    font-size:8px;

    font-weight:700;

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


.btn-light {

    background:#eef1ef;

    color:var(--text);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px) {

    .layout {

        grid-template-columns:1fr;

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


@media(max-width:600px) {

    .form-grid {

        grid-template-columns:1fr;

    }


    .full {

        grid-column:auto;

    }


    .actions {

        flex-direction:column-reverse;

    }


    .btn {

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


<div class="topbar-left">


<a
    href="visits.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Schedule Visit
</h1>

<p>
    Create a new property viewing appointment
</p>

</div>


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


<div class="page-title">

<h2>
    Schedule Property Visit
</h2>

<p>
    Add customer details, select a property and schedule the appointment.
</p>

</div>


<!-- =====================================================
     ALERTS
========================================================= -->

<?php if (
    !empty($errors)
): ?>


<div class="alert alert-error">

<strong>
    Please fix the following:
</strong>


<ul>

<?php foreach (
    $errors as $error
): ?>

<li>
    <?php
    echo safe($error);
    ?>
</li>

<?php endforeach; ?>

</ul>

</div>


<?php endif; ?>


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


<div class="layout">


<!-- =====================================================
     FORM
========================================================= -->

<div>


<form
    method="POST"
    id="visitForm"
>


<section class="card">


<div class="card-header">
    Customer Information
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label for="customer_name">

Customer Name
<span class="required">*</span>

</label>


<input
    type="text"
    id="customer_name"
    name="customer_name"
    value="<?php echo safe($customerName); ?>"
    placeholder="Enter customer name"
    required
>

</div>


<div class="form-group">

<label for="customer_email">
    Email Address
</label>


<input
    type="email"
    id="customer_email"
    name="customer_email"
    value="<?php echo safe($customerEmail); ?>"
    placeholder="customer@example.com"
>

</div>


<div class="form-group">

<label for="customer_phone">
    Phone Number
</label>


<input
    type="tel"
    id="customer_phone"
    name="customer_phone"
    value="<?php echo safe($customerPhone); ?>"
    placeholder="+91 XXXXX XXXXX"
>

</div>


<div class="form-group">

<label for="status">
    Appointment Status
</label>


<select
    id="status"
    name="status"
>


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

</div>


</div>


</div>


</section>


<!-- =====================================================
     PROPERTY
========================================================= -->

<section class="card">


<div class="card-header">
    Property & Agent
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group full">

<label for="property_id">

Select Property
<span class="required">*</span>

</label>


<select
    id="property_id"
    name="property_id"
    required
>


<option value="">
    — Select Property —
</option>


<?php foreach (
    $properties as $property
): ?>


<option
    value="<?php echo (int)$property["id"]; ?>"
    <?php
    echo (string)$propertyId ===
         (string)$property["id"]
        ? "selected"
        : "";
    ?>
>

<?php

echo safe(
    $property["title"]
);

if (
    !empty(
        $property["location"]
    )
) {

    echo
        " — "
        .
        safe(
            $property["location"]
        );

}

?>

</option>


<?php endforeach; ?>


</select>


</div>


<div class="form-group full">

<label for="agent_id">
    Assign Agent
</label>


<select
    id="agent_id"
    name="agent_id"
>


<option value="">
    — No Agent Assigned —
</option>


<?php foreach (
    $agents as $agent
): ?>


<option
    value="<?php echo (int)$agent["id"]; ?>"
    <?php
    echo (string)$agentId ===
         (string)$agent["id"]
        ? "selected"
        : "";
    ?>
>

<?php
echo safe(
    $agent["name"]
);
?>

<?php if (
    !empty(
        $agent["email"]
    )
): ?>

 —
<?php
echo safe(
    $agent["email"]
);
?>

<?php endif; ?>

</option>


<?php endforeach; ?>


</select>


</div>


</div>


</div>


</section>


<!-- =====================================================
     DATE & TIME
========================================================= -->

<section class="card">


<div class="card-header">
    Appointment Schedule
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label for="visit_date">

Visit Date
<span class="required">*</span>

</label>


<input
    type="date"
    id="visit_date"
    name="visit_date"
    value="<?php echo safe($visitDate); ?>"
    min="<?php echo $today; ?>"
    required
>

</div>


<div class="form-group">

<label for="visit_time">

Visit Time
<span class="required">*</span>

</label>


<input
    type="time"
    id="visit_time"
    name="visit_time"
    value="<?php echo safe($visitTime); ?>"
    required
>

</div>


<div class="form-group full">

<label for="notes">
    Additional Notes
</label>


<textarea
    id="notes"
    name="notes"
    placeholder="Add customer requirements, meeting instructions, special requests..."
><?php echo safe($notes); ?></textarea>


</div>


</div>


</div>


</section>


<!-- =====================================================
     ACTIONS
========================================================= -->

<section class="card">


<div class="card-body">


<div class="actions">


<a
    href="visits.php"
    class="btn btn-light"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-primary"
>
    📅 Schedule Visit
</button>


</div>


</div>


</section>


</form>


</div>


<!-- =====================================================
     SIDE INFORMATION
========================================================= -->

<div>


<section class="card">


<div class="card-header">
    Scheduling Guide
</div>


<div class="card-body">


<div class="info-box">


<div class="info-title">
    Visit Workflow
</div>


<div class="info-item">


<div class="info-icon">
    👤
</div>


<div class="info-text">

<strong>
    Customer
</strong>

<span>
    Enter the customer's contact information so the agent can communicate with them.
</span>

</div>


</div>


<div class="info-item">


<div class="info-icon">
    🏠
</div>


<div class="info-text">

<strong>
    Property
</strong>

<span>
    Select the property the customer wants to visit.
</span>

</div>


</div>


<div class="info-item">


<div class="info-icon">
    📅
</div>


<div class="info-text">

<strong>
    Schedule
</strong>

<span>
    Choose a future date and convenient time for the property viewing.
</span>

</div>


</div>


<div class="info-item">


<div class="info-icon">
    🧑‍💼
</div>


<div class="info-text">

<strong>
    Agent
</strong>

<span>
    Assign an available agent who will handle the property visit.
</span>

</div>


</div>


</div>


</div>


</section>


<section class="card">


<div class="card-header">
    Appointment Tips
</div>


<div class="card-body">


<div
    style="
        font-size:8px;
        color:#737c78;
        line-height:1.7;
    "
>

✓ Confirm the customer's phone number.

<br><br>

✓ Check agent availability before scheduling.

<br><br>

✓ Add useful instructions in the notes.

<br><br>

✓ Keep the appointment status as
<strong>Pending</strong>
until confirmation.

</div>


</div>


</section>


</div>


</div>


</main>


</div>


<script>

/* =========================================================
   FORM VALIDATION
========================================================= */

const form =
    document.getElementById(
        "visitForm"
    );


form.addEventListener(
    "submit",
    function(event) {

        const name =
            document
                .getElementById(
                    "customer_name"
                )
                .value
                .trim();


        const property =
            document
                .getElementById(
                    "property_id"
                )
                .value;


        const date =
            document
                .getElementById(
                    "visit_date"
                )
                .value;


        const time =
            document
                .getElementById(
                    "visit_time"
                )
                .value;


        if (
            name.length < 2
        ) {

            event.preventDefault();

            alert(
                "Please enter a valid customer name."
            );

            return;

        }


        if (!property) {

            event.preventDefault();

            alert(
                "Please select a property."
            );

            return;

        }


        if (!date) {

            event.preventDefault();

            alert(
                "Please select the visit date."
            );

            return;

        }


        if (!time) {

            event.preventDefault();

            alert(
                "Please select the visit time."
            );

            return;

        }


        const confirmed =
            confirm(
                "Schedule this property visit?"
            );


        if (!confirmed) {

            event.preventDefault();

        }

    }
);


/* =========================================================
   PROPERTY CHANGE
========================================================= */

const propertySelect =
    document.getElementById(
        "property_id"
    );


propertySelect.addEventListener(
    "change",
    function() {

        if (
            this.value !== ""
        ) {

            this.style.borderColor =
                "#174a3a";

        } else {

            this.style.borderColor =
                "";

        }

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
        4500
    );

}

</script>


</body>

</html>