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
   GET ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: enquiries.php");
    exit;
}


/* =========================================================
   CHECK TABLE
========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'enquiries'"
);

if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {
    exit("Enquiries table not found.");
}


/* =========================================================
   GET COLUMNS
========================================================= */

$columns = [];

$columnResult = $conn->query(
    "SHOW COLUMNS FROM enquiries"
);

while (
    $column = $columnResult->fetch_assoc()
) {
    $columns[] = $column["Field"];
}


/* =========================================================
   LOAD ENQUIRY
========================================================= */

$stmt = $conn->prepare(
    "SELECT * FROM enquiries WHERE id = ? LIMIT 1"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();

$enquiry = $result->fetch_assoc();

$stmt->close();


if (!$enquiry) {
    exit("Enquiry not found.");
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
   FORM VALUES
========================================================= */

$name =
    $enquiry["name"]
    ?? "";

$email =
    $enquiry["email"]
    ?? "";

$phone =
    $enquiry["phone"]
    ?? "";

$message =
    $enquiry["message"]
    ?? "";

$status =
    $enquiry["status"]
    ?? "new";

$priority =
    $enquiry["priority"]
    ?? "medium";

$agentId =
    $enquiry["agent_id"]
    ?? "";


/* =========================================================
   STATUS / PRIORITY
========================================================= */

$statuses = [
    "new",
    "contacted",
    "qualified",
    "closed"
];

$priorities = [
    "low",
    "medium",
    "high"
];


/* =========================================================
   PROCESS FORM
========================================================= */

$errors = [];

$success = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim(
            $_POST["name"] ?? ""
        );

    $email =
        trim(
            $_POST["email"] ?? ""
        );

    $phone =
        trim(
            $_POST["phone"] ?? ""
        );

    $message =
        trim(
            $_POST["message"] ?? ""
        );

    $status =
        $_POST["status"]
        ?? "new";

    $priority =
        $_POST["priority"]
        ?? "medium";

    $agentId =
        $_POST["agent_id"]
        ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($name === "") {

        $errors[] =
            "Customer name is required.";

    }


    if (
        $email !== "" &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if (
        !in_array(
            $status,
            $statuses,
            true
        )
    ) {

        $errors[] =
            "Invalid enquiry status.";

    }


    if (
        !in_array(
            $priority,
            $priorities,
            true
        )
    ) {

        $errors[] =
            "Invalid priority.";

    }


    /* =====================================================
       UPDATE
    ===================================================== */

    if (empty($errors)) {

        $updateParts = [];

        $params = [];

        $types = "";


        if (
            in_array(
                "name",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "name = ?";

            $params[] =
                $name;

            $types .= "s";

        }


        if (
            in_array(
                "email",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "email = ?";

            $params[] =
                $email;

            $types .= "s";

        }


        if (
            in_array(
                "phone",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "phone = ?";

            $params[] =
                $phone;

            $types .= "s";

        }


        if (
            in_array(
                "message",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "message = ?";

            $params[] =
                $message;

            $types .= "s";

        }


        if (
            in_array(
                "status",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "status = ?";

            $params[] =
                $status;

            $types .= "s";

        }


        if (
            in_array(
                "priority",
                $columns,
                true
            )
        ) {

            $updateParts[] =
                "priority = ?";

            $params[] =
                $priority;

            $types .= "s";

        }


        if (
            in_array(
                "agent_id",
                $columns,
                true
            )
        ) {

            if ($agentId === "") {

                $updateParts[] =
                    "agent_id = NULL";

            } else {

                $updateParts[] =
                    "agent_id = ?";

                $params[] =
                    (int)$agentId;

                $types .= "i";

            }

        }


        if (!empty($updateParts)) {

            $sql =
                "UPDATE enquiries SET "
                .
                implode(
                    ", ",
                    $updateParts
                )
                .
                " WHERE id = ?";


            $params[] =
                $id;

            $types .= "i";


            $update =
                $conn->prepare(
                    $sql
                );


            if ($update) {

                $update->bind_param(
                    $types,
                    ...$params
                );


                if (
                    $update->execute()
                ) {

                    $success =
                        "Enquiry updated successfully.";

                    /* Refresh */

                    $refresh =
                        $conn->prepare(
                            "SELECT *
                             FROM enquiries
                             WHERE id = ?
                             LIMIT 1"
                        );

                    $refresh->bind_param(
                        "i",
                        $id
                    );

                    $refresh->execute();

                    $refreshResult =
                        $refresh->get_result();

                    $enquiry =
                        $refreshResult
                        ->fetch_assoc();

                    $refresh->close();

                } else {

                    $errors[] =
                        "Unable to update enquiry.";

                }


                $update->close();

            } else {

                $errors[] =
                    "Database update error.";

            }

        }

    }

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
    Edit Enquiry | RealEstate
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

    z-index:10;

}


.topbar-left {

    display:flex;

    align-items:center;

    gap:14px;

}


.back {

    width:36px;

    height:36px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#eef1ef;

    border-radius:6px;

    text-decoration:none;

    color:var(--text);

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

    background:var(--primary);

    color:white;

    border-radius:50%;

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

    max-width:1100px;

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
        13px 15px;

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


/* =========================================================
   CARD
========================================================= */

.card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:10px;

    margin-bottom:18px;

    overflow:hidden;

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
   FORM GRID
========================================================= */

.form-grid {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:17px;

}


.form-group {

    display:flex;

    flex-direction:column;

    gap:7px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    font-size:8px;

    font-weight:700;

    color:#4d5752;

}


input,
select,
textarea {

    width:100%;

    border:
        1px solid
        var(--border);

    border-radius:6px;

    outline:none;

    background:white;

    color:var(--text);

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

    min-height:130px;

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
   SELECT COLORS
========================================================= */

.status-preview {

    margin-top:8px;

}


.priority-help {

    margin-top:5px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   BUTTONS
========================================================= */

.form-actions {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

}


.left-actions {

    display:flex;

    gap:8px;

}


.btn {

    height:42px;

    padding:
        0 18px;

    border:none;

    border-radius:6px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    text-decoration:none;

    cursor:pointer;

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


.btn-danger {

    background:
        var(--red-bg);

    color:
        var(--red);

}


/* =========================================================
   INFO
========================================================= */

.info-box {

    padding:14px;

    background:#fafbfa;

    border:
        1px solid
        #edf0ee;

    border-radius:7px;

    margin-bottom:18px;

}


.info-row {

    display:flex;

    justify-content:space-between;

    gap:15px;

    padding:9px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.info-row:last-child {

    border-bottom:none;

}


.info-label {

    color:var(--muted);

    font-size:7px;

}


.info-value {

    font-size:8px;

    font-weight:700;

    text-align:right;

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


    .form-group.full {

        grid-column:auto;

    }


    .form-actions {

        flex-direction:column-reverse;

        align-items:stretch;

    }


    .left-actions {

        width:100%;

    }


    .left-actions .btn {

        flex:1;

    }


    .form-actions > .btn {

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


<a
    href="enquiries.php"
    class="active"
>

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
========================================================= -->

<div class="main">


<header class="topbar">


<div class="topbar-left">


<a
    href="enquiry-details.php?id=<?php echo $id; ?>"
    class="back"
>
    ←
</a>


<div>

<h1>
    Edit Enquiry
</h1>

<p>
    Update customer lead information
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
    Edit Enquiry #<?php echo $id; ?>
</h2>

<p>
    Update the enquiry information and lead management status.
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

<ul
    style="
        margin:8px 0 0 18px;
    "
>

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


<?php if (
    $success !== ""
): ?>

<div class="alert alert-success">

✓
<?php
echo safe($success);
?>

</div>

<?php endif; ?>


<!-- =====================================================
     CURRENT INFORMATION
========================================================= -->

<section class="card">


<div class="card-header">
    Enquiry Information
</div>


<div class="card-body">


<div class="info-box">


<div class="info-row">

<span class="info-label">
    Enquiry ID
</span>

<span class="info-value">
    #<?php echo $id; ?>
</span>

</div>


<div class="info-row">

<span class="info-label">
    Created
</span>

<span class="info-value">

<?php

if (
    !empty(
        $enquiry["created_at"]
    )
) {

    echo safe(
        date(
            "d M Y, h:i A",
            strtotime(
                $enquiry["created_at"]
            )
        )
    );

} else {

    echo "N/A";

}

?>

</span>

</div>


<div class="info-row">

<span class="info-label">
    Property ID
</span>

<span class="info-value">

<?php

echo safe(
    $enquiry["property_id"]
    ?? "General enquiry"
);

?>

</span>

</div>


</div>


</div>


</section>


<!-- =====================================================
     EDIT FORM
========================================================= -->

<form
    method="POST"
    id="enquiryForm"
>


<section class="card">


<div class="card-header">
    Customer Details
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label for="name">
    Customer Name *
</label>


<input
    type="text"
    id="name"
    name="name"
    value="<?php echo safe($name); ?>"
    placeholder="Enter customer name"
    required
>

</div>


<div class="form-group">

<label for="email">
    Email Address
</label>


<input
    type="email"
    id="email"
    name="email"
    value="<?php echo safe($email); ?>"
    placeholder="customer@example.com"
>

</div>


<div class="form-group">

<label for="phone">
    Phone Number
</label>


<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php echo safe($phone); ?>"
    placeholder="+91 XXXXX XXXXX"
>

</div>


<div class="form-group">

<label for="status">
    Enquiry Status *
</label>


<select
    id="status"
    name="status"
    required
>


<?php foreach (
    $statuses as $item
): ?>

<option
    value="<?php echo safe($item); ?>"
    <?php
    echo $status === $item
        ? "selected"
        : "";
    ?>
>

<?php
echo ucfirst(
    $item
);
?>

</option>

<?php endforeach; ?>


</select>


</div>


<div class="form-group">

<label for="priority">
    Priority *
</label>


<select
    id="priority"
    name="priority"
    required
>


<?php foreach (
    $priorities as $item
): ?>

<option
    value="<?php echo safe($item); ?>"
    <?php
    echo $priority === $item
        ? "selected"
        : "";
    ?>
>

<?php
echo ucfirst(
    $item
);
?>

</option>

<?php endforeach; ?>


</select>


<div class="priority-help">

Low = normal follow-up ·
Medium = important ·
High = urgent lead

</div>


</div>


<div class="form-group">

<label for="agent_id">
    Assign Agent
</label>


<select
    id="agent_id"
    name="agent_id"
>


<option value="">
    — Unassigned —
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


<div class="form-group full">

<label for="message">
    Customer Message
</label>


<textarea
    id="message"
    name="message"
    placeholder="Enter customer enquiry message..."
><?php echo safe($message); ?></textarea>


</div>


</div>


</div>


</section>


<!-- =====================================================
     ACTIONS
========================================================= -->

<section class="card">


<div class="card-body">


<div class="form-actions">


<div class="left-actions">


<a
    href="enquiry-details.php?id=<?php echo $id; ?>"
    class="btn btn-light"
>
    Cancel
</a>


<a
    href="enquiry-delete.php?id=<?php echo $id; ?>"
    class="btn btn-danger"
    id="deleteLink"
>
    🗑 Delete
</a>


</div>


<button
    type="submit"
    class="btn btn-primary"
>
    ✓ Save Changes
</button>


</div>


</div>


</section>


</form>


</main>


</div>


<script>

/* =========================================================
   FORM VALIDATION
========================================================= */

const form =
    document.getElementById(
        "enquiryForm"
    );


form.addEventListener(
    "submit",
    function(event) {

        const name =
            document.getElementById(
                "name"
            ).value.trim();


        if (name.length < 2) {

            event.preventDefault();

            alert(
                "Please enter a valid customer name."
            );

            document
                .getElementById("name")
                .focus();

            return;

        }


        const email =
            document.getElementById(
                "email"
            ).value.trim();


        if (
            email !== "" &&
            !email.includes("@")
        ) {

            event.preventDefault();

            alert(
                "Please enter a valid email address."
            );

            document
                .getElementById("email")
                .focus();

        }

    }
);


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteLink =
    document.getElementById(
        "deleteLink"
    );


deleteLink.addEventListener(
    "click",
    function(event) {

        const confirmed =
            confirm(
                "Are you sure you want to permanently delete this enquiry?"
            );


        if (!confirmed) {

            event.preventDefault();

        }

    }
);


/* =========================================================
   AUTO HIDE SUCCESS MESSAGE
========================================================= */

const success =
    document.querySelector(
        ".alert-success"
    );


if (success) {

    setTimeout(
        function() {

            success.style.transition =
                "opacity .5s ease";

            success.style.opacity =
                "0";

        },
        4000
    );

}

</script>


</body>

</html>