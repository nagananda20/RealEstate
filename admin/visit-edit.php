<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   AUTH
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
   VISIT ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: visits.php");
    exit;
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
   CHECK VISITS TABLE
========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'visits'"
);

if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {
    exit("The visits table does not exist.");
}


/* =========================================================
   GET VISIT COLUMNS
========================================================= */

$columns = [];

$columnResult = $conn->query(
    "SHOW COLUMNS FROM visits"
);

while (
    $column = $columnResult->fetch_assoc()
) {
    $columns[] = $column["Field"];
}


/* =========================================================
   LOAD VISIT
========================================================= */

$stmt = $conn->prepare(
    "SELECT * FROM visits WHERE id = ? LIMIT 1"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();

$visit = $result->fetch_assoc();

$stmt->close();


if (!$visit) {

    $_SESSION["error"] =
        "Visit appointment not found.";

    header("Location: visits.php");
    exit;
}


/* =========================================================
   DEFAULT VALUES
========================================================= */

$customerName =
    $visit["name"] ?? "";

$customerEmail =
    $visit["email"] ?? "";

$customerPhone =
    $visit["phone"] ?? "";

$propertyId =
    $visit["property_id"] ?? "";

$agentId =
    $visit["agent_id"] ?? "";

$visitDate =
    $visit["visit_date"] ?? "";

$visitTime =
    $visit["visit_time"] ?? "";

$status =
    $visit["status"] ?? "pending";

$notes =
    $visit["notes"] ?? "";


$errors = [];

$success = "";


/* =========================================================
   LOAD PROPERTIES
========================================================= */

$properties = [];

$propertyTable = $conn->query(
    "SHOW TABLES LIKE 'properties'"
);

if (
    $propertyTable &&
    $propertyTable->num_rows > 0
) {

    $propertyResult = $conn->query(
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

$userTable = $conn->query(
    "SHOW TABLES LIKE 'users'"
);

if (
    $userTable &&
    $userTable->num_rows > 0
) {

    $agentResult = $conn->query(
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
   FORM SUBMISSION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    $customerName =
        trim(
            $_POST["customer_name"] ?? ""
        );


    $customerEmail =
        trim(
            $_POST["customer_email"] ?? ""
        );


    $customerPhone =
        trim(
            $_POST["customer_phone"] ?? ""
        );


    $propertyId =
        trim(
            $_POST["property_id"] ?? ""
        );


    $agentId =
        trim(
            $_POST["agent_id"] ?? ""
        );


    $visitDate =
        trim(
            $_POST["visit_date"] ?? ""
        );


    $visitTime =
        trim(
            $_POST["visit_time"] ?? ""
        );


    $status =
        trim(
            $_POST["status"] ?? "pending"
        );


    $notes =
        trim(
            $_POST["notes"] ?? ""
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        strlen($customerName) < 2
    ) {

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
            "Invalid appointment status.";

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
       UPDATE
    ===================================================== */

    if (empty($errors)) {


        $updates = [];

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

            $updates[] =
                "name = ?";

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

            $updates[] =
                "email = ?";

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

            $updates[] =
                "phone = ?";

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

            $updates[] =
                "property_id = ?";

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

                $updates[] =
                    "agent_id = NULL";

            } else {

                $updates[] =
                    "agent_id = ?";

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

            $updates[] =
                "visit_date = ?";

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

            $updates[] =
                "visit_time = ?";

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

            $updates[] =
                "status = ?";

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

            $updates[] =
                "notes = ?";

            $params[] =
                $notes;

            $types .= "s";

        }


        /* UPDATED AT */

        if (
            in_array(
                "updated_at",
                $columns,
                true
            )
        ) {

            $updates[] =
                "updated_at = NOW()";

        }


        /* =================================================
           EXECUTE UPDATE
        ================================================= */

        if (!empty($updates)) {

            $sql =
                "UPDATE visits SET "
                .
                implode(
                    ", ",
                    $updates
                )
                .
                " WHERE id = ?";


            $params[] =
                $id;

            $types .= "i";


            $stmt =
                $conn->prepare($sql);


            if (!$stmt) {

                $errors[] =
                    "Database error: "
                    .
                    $conn->error;

            } else {


                $stmt->bind_param(
                    $types,
                    ...$params
                );


                if (
                    $stmt->execute()
                ) {

                    $_SESSION["success"] =
                        "Visit updated successfully.";

                    header(
                        "Location: visit-details.php?id="
                        . $id
                    );

                    exit;

                } else {

                    $errors[] =
                        "Unable to update visit: "
                        .
                        $stmt->error;

                }


                $stmt->close();

            }

        } else {

            $errors[] =
                "No editable fields were found.";

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
    Edit Visit | RealEstate
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
   PAGE TITLE
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
   ALERT
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


.alert ul {

    margin:
        7px 0 0 18px;

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

    font-size:10px;

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
   STATUS
========================================================= */

.status-preview {

    margin-top:8px;

    padding:10px;

    border-radius:6px;

    background:#fafbfa;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   ACTIONS
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
   DANGER
========================================================= */

.danger-zone {

    border:
        1px solid
        #f1d9dc;

    background:
        #fffafb;

}


.danger-title {

    color:
        var(--red);

    font-size:9px;

    font-weight:800;

}


.danger-text {

    margin-top:6px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

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
    href="visit-details.php?id=<?php echo (int)$id; ?>"
    class="back"
>
    ←
</a>


<div>

<h1>
    Edit Visit
</h1>

<p>
    Appointment #<?php echo (int)$id; ?>
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
    Edit Property Visit
</h2>

<p>
    Update customer, property, agent, schedule and appointment status.
</p>

</div>


<!-- =====================================================
     ERRORS
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
    <?php echo safe($error); ?>
</li>

<?php endforeach; ?>

</ul>

</div>


<?php endif; ?>


<form
    method="POST"
    id="editVisitForm"
>


<!-- =====================================================
     CUSTOMER
========================================================= -->

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


<div
    class="status-preview"
    id="statusPreview"
>
    Current status:
    <strong>
        <?php echo safe(ucfirst($status)); ?>
    </strong>
</div>


</div>


</div>


</div>


</section>


<!-- =====================================================
     PROPERTY & AGENT
========================================================= -->

<section class="card">


<div class="card-header">
    Property & Agent
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group full">

<label for="property_id">

Property
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
    Assigned Agent
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
     SCHEDULE
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
    Notes
</label>


<textarea
    id="notes"
    name="notes"
    placeholder="Customer requirements, special instructions, meeting details..."
><?php echo safe($notes); ?></textarea>


</div>


</div>


</div>


</section>


<!-- =====================================================
     SAVE
========================================================= -->

<section class="card">


<div class="card-body">


<div class="actions">


<a
    href="visit-details.php?id=<?php echo (int)$id; ?>"
    class="btn btn-light"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-primary"
>
    ✓ Save Changes
</button>


</div>


</div>


</section>


<!-- =====================================================
     DANGER ZONE
========================================================= -->

<section class="card danger-zone">


<div class="card-body">


<div class="danger-title">
    Delete Appointment
</div>


<div class="danger-text">

Deleting this visit is permanent and cannot be undone.

</div>


<div
    style="
        margin-top:12px;
    "
>


<a
    href="visit-delete.php?id=<?php echo (int)$id; ?>"
    id="deleteButton"
    class="btn"
    style="
        display:inline-flex;
        min-width:145px;
        background:#fdebed;
        color:#b43843;
    "
>
    🗑 Delete Visit
</a>


</div>


</div>


</section>


</form>


</main>


</div>


<script>

/* =========================================================
   FORM CONFIRMATION
========================================================= */

const form =
    document.getElementById(
        "editVisitForm"
    );


form.addEventListener(
    "submit",
    function(event) {

        const confirmed =
            confirm(
                "Save changes to this property visit?"
            );


        if (!confirmed) {

            event.preventDefault();

        }

    }
);


/* =========================================================
   STATUS PREVIEW
========================================================= */

const status =
    document.getElementById(
        "status"
    );


const statusPreview =
    document.getElementById(
        "statusPreview"
    );


function updateStatusPreview()
{

    const value =
        status.value;


    statusPreview.innerHTML =
        "Current status: <strong>"
        +
        value.charAt(0).toUpperCase()
        +
        value.slice(1)
        +
        "</strong>";

}


status.addEventListener(
    "change",
    updateStatusPreview
);


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteButton =
    document.getElementById(
        "deleteButton"
    );


deleteButton.addEventListener(
    "click",
    function(event) {

        const confirmed =
            confirm(
                "Delete this appointment permanently?"
            );


        if (!confirmed) {

            event.preventDefault();

        }

    }
);


/* =========================================================
   INPUT ANIMATION
========================================================= */

document
    .querySelectorAll(
        "input, select, textarea"
    )
    .forEach(
        function(element) {

            element.addEventListener(
                "focus",
                function() {

                    this.parentElement.style
                        .transform =
                        "translateY(-1px)";

                }
            );


            element.addEventListener(
                "blur",
                function() {

                    this.parentElement.style
                        .transform =
                        "translateY(0)";

                }
            );

        }
    );

</script>


</body>

</html>
