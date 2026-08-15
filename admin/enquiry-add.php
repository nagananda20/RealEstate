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
   ADMIN INFORMATION
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
   CHECK ENQUIRIES TABLE
========================================================= */

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'enquiries'"
    );


if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {

    exit(
        "The enquiries table does not exist."
    );

}


/* =========================================================
   GET ENQUIRY COLUMNS
========================================================= */

$columns = [];

$columnResult =
    $conn->query(
        "SHOW COLUMNS FROM enquiries"
    );


while (
    $column =
    $columnResult->fetch_assoc()
) {

    $columns[] =
        $column["Field"];

}


/* =========================================================
   FORM VALUES
========================================================= */

$name = "";

$email = "";

$phone = "";

$propertyId = "";

$message = "";

$status = "pending";

$errors = [];


/* =========================================================
   LOAD PROPERTIES
========================================================= */

$properties = [];

$propertyCheck =
    $conn->query(
        "SHOW TABLES LIKE 'properties'"
    );


if (
    $propertyCheck &&
    $propertyCheck->num_rows > 0
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
   FORM SUBMISSION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


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


    $propertyId =
        trim(
            $_POST["property_id"] ?? ""
        );


    $message =
        trim(
            $_POST["message"] ?? ""
        );


    $status =
        trim(
            $_POST["status"] ?? "pending"
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        strlen($name) < 2
    ) {

        $errors[] =
            "Customer name is required.";

    }


    if (
        $email === ""
    ) {

        $errors[] =
            "Email address is required.";

    }
    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if (
        $phone === ""
    ) {

        $errors[] =
            "Phone number is required.";

    }


    if (
        $message === ""
    ) {

        $errors[] =
            "Please enter the enquiry message.";

    }


    $allowedStatuses = [
        "pending",
        "contacted",
        "closed",
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
            "Invalid enquiry status.";

    }


    /* =====================================================
       PROPERTY VALIDATION
    ===================================================== */

    if (
        $propertyId !== ""
    ) {

        if (
            !ctype_digit(
                $propertyId
            )
        ) {

            $errors[] =
                "Invalid property selected.";

        }

    }


    /* =====================================================
       INSERT
    ===================================================== */

    if (
        empty($errors)
    ) {


        $insertFields = [];

        $placeholders = [];

        $params = [];

        $types = "";


        /* NAME */

        if (
            in_array(
                "name",
                $columns,
                true
            )
        ) {

            $insertFields[] =
                "name";

            $placeholders[] =
                "?";

            $params[] =
                $name;

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

            $insertFields[] =
                "email";

            $placeholders[] =
                "?";

            $params[] =
                $email;

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

            $insertFields[] =
                "phone";

            $placeholders[] =
                "?";

            $params[] =
                $phone;

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

            if (
                $propertyId !== ""
            ) {

                $insertFields[] =
                    "property_id";

                $placeholders[] =
                    "?";

                $params[] =
                    (int)$propertyId;

                $types .= "i";

            }

        }


        /* MESSAGE */

        if (
            in_array(
                "message",
                $columns,
                true
            )
        ) {

            $insertFields[] =
                "message";

            $placeholders[] =
                "?";

            $params[] =
                $message;

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

            $insertFields[] =
                "status";

            $placeholders[] =
                "?";

            $params[] =
                $status;

            $types .= "s";

        }


        /* USER ID */

        if (
            in_array(
                "user_id",
                $columns,
                true
            )
        ) {

            $insertFields[] =
                "user_id";

            $placeholders[] =
                "?";

            $params[] =
                (int)$_SESSION["user_id"];

            $types .= "i";

        }


        /* CREATED AT */

        if (
            in_array(
                "created_at",
                $columns,
                true
            )
        ) {

            $insertFields[] =
                "created_at";

            $placeholders[] =
                "NOW()";

        }


        /* =================================================
           CHECK
        ================================================= */

        if (
            empty($insertFields)
        ) {

            $errors[] =
                "No compatible enquiry fields were found.";

        }
        else {


            $sql =
                "INSERT INTO enquiries ("
                .
                implode(
                    ", ",
                    $insertFields
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
                $conn->prepare(
                    $sql
                );


            if (!$stmt) {

                $errors[] =
                    "Database error: "
                    .
                    $conn->error;

            }
            else {


                if (
                    !empty($params)
                ) {

                    $stmt->bind_param(
                        $types,
                        ...$params
                    );

                }


                if (
                    $stmt->execute()
                ) {

                    $_SESSION["success"] =
                        "New enquiry created successfully.";

                    header(
                        "Location: enquiries.php"
                    );

                    exit;

                }
                else {

                    $errors[] =
                        "Unable to create enquiry: "
                        .
                        $stmt->error;

                }


                $stmt->close();

            }

        }

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
    Add Enquiry | RealEstate
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

    --red:#b43843;

    --red-bg:#fdebed;

    --green:#17643b;

    --green-bg:#e8f6ed;

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

    padding:
        0 25px;

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

    z-index:50;

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

    border-radius:6px;

    background:#eef1ef;

    color:var(--text);

    text-decoration:none;

}


.topbar h1 {

    font-size:18px;

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

    max-width:1050px;

    margin:auto;

    padding:
        30px;

}


.page-title {

    margin-bottom:20px;

}


.page-title h2 {

    font-size:22px;

}


.page-title p {

    margin-top:6px;

    color:
        var(--muted);

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

    min-height:150px;

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
   PROPERTY INFO
========================================================= */

.property-info {

    margin-top:7px;

    padding:9px 11px;

    background:#f7f9f8;

    border-radius:6px;

    color:
        var(--muted);

    font-size:7px;

}


.property-info strong {

    color:
        var(--text);

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

    min-width:125px;

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:
        0 18px;

    border:none;

    border-radius:6px;

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

    background:
        #eef1ef;

    color:
        var(--text);

}


/* =========================================================
   CHARACTER COUNT
========================================================= */

.message-footer {

    display:flex;

    justify-content:space-between;

    color:
        var(--muted);

    font-size:7px;

}


#messageCount {

    font-weight:700;

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
    href="enquiries.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Add Enquiry
</h1>

<p>
    Create a new customer property enquiry
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
    New Customer Enquiry
</h2>

<p>
    Enter the customer's information and enquiry details.
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
    id="enquiryForm"
>


<!-- =====================================================
     CUSTOMER INFORMATION
========================================================= -->

<section class="card">


<div class="card-header">
    Customer Information
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label for="name">

Customer Name
<span class="required">*</span>

</label>


<input
    type="text"
    id="name"
    name="name"
    value="<?php echo safe($name); ?>"
    placeholder="Enter customer name"
    autocomplete="name"
    required
>

</div>


<div class="form-group">

<label for="email">

Email Address
<span class="required">*</span>

</label>


<input
    type="email"
    id="email"
    name="email"
    value="<?php echo safe($email); ?>"
    placeholder="customer@example.com"
    autocomplete="email"
    required
>

</div>


<div class="form-group">

<label for="phone">

Phone Number
<span class="required">*</span>

</label>


<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php echo safe($phone); ?>"
    placeholder="+91 98765 43210"
    autocomplete="tel"
    required
>

</div>


<div class="form-group">

<label for="status">
    Status
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
    value="contacted"
    <?php
    echo $status === "contacted"
        ? "selected"
        : "";
    ?>
>
    Contacted
</option>


<option
    value="closed"
    <?php
    echo $status === "closed"
        ? "selected"
        : "";
    ?>
>
    Closed
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
    Property Information
</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group full">

<label for="property_id">
    Property
</label>


<select
    id="property_id"
    name="property_id"
>


<option value="">
    — General Enquiry / No Property —
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


<div
    class="property-info"
    id="propertyInfo"
>

Select a property to see its information.

</div>


</div>


</div>


</div>


</section>


<!-- =====================================================
     MESSAGE
========================================================= -->

<section class="card">


<div class="card-header">
    Enquiry Message
</div>


<div class="card-body">


<div class="form-group">


<label for="message">

Message
<span class="required">*</span>

</label>


<textarea
    id="message"
    name="message"
    maxlength="2000"
    placeholder="Enter customer's enquiry, requirements, budget, preferred location, questions..."
    required
><?php echo safe($message); ?></textarea>


<div class="message-footer">

<span>
    Maximum 2000 characters
</span>


<span id="messageCount">
    0 / 2000
</span>

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
    href="enquiries.php"
    class="btn btn-light"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-primary"
>
    ✓ Create Enquiry
</button>


</div>


</div>


</section>


</form>


</main>


</div>


<script>

/* =========================================================
   MESSAGE CHARACTER COUNT
========================================================= */

const message =
    document.getElementById(
        "message"
    );


const messageCount =
    document.getElementById(
        "messageCount"
    );


function updateMessageCount()
{

    const length =
        message.value.length;


    messageCount.textContent =
        length
        +
        " / 2000";

}


message.addEventListener(
    "input",
    updateMessageCount
);


updateMessageCount();


/* =========================================================
   PROPERTY INFORMATION
========================================================= */

const propertySelect =
    document.getElementById(
        "property_id"
    );


const propertyInfo =
    document.getElementById(
        "propertyInfo"
    );


const properties =
    <?php
    echo json_encode(
        $properties,
        JSON_UNESCAPED_UNICODE
    );
    ?>;


function updatePropertyInfo()
{

    const selectedId =
        propertySelect.value;


    if (!selectedId) {

        propertyInfo.innerHTML =
            "General enquiry — no specific property selected.";

        return;

    }


    const property =
        properties.find(
            function(item) {

                return String(item.id)
                    ===
                    String(selectedId);

            }
        );


    if (!property) {

        propertyInfo.innerHTML =
            "Property information unavailable.";

        return;

    }


    propertyInfo.innerHTML =
        "<strong>"
        +
        escapeHTML(
            property.title
        )
        +
        "</strong>"
        +
        (
            property.location
            ?
            " — "
            +
            escapeHTML(
                property.location
            )
            :
            ""
        );

}


function escapeHTML(value)
{

    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        value ?? "";


    return div.innerHTML;

}


propertySelect.addEventListener(
    "change",
    updatePropertyInfo
);


updatePropertyInfo();


/* =========================================================
   PHONE VALIDATION
========================================================= */

const phone =
    document.getElementById(
        "phone"
    );


phone.addEventListener(
    "input",
    function() {

        this.value =
            this.value.replace(
                /[^0-9+\-\s()]/g,
                ""
            );

    }
);


/* =========================================================
   FORM CONFIRMATION
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


        const email =
            document.getElementById(
                "email"
            ).value.trim();


        const messageValue =
            message.value.trim();


        if (
            name.length < 2
        ) {

            alert(
                "Please enter a valid customer name."
            );

            event.preventDefault();

            return;

        }


        if (
            !email.includes("@")
        ) {

            alert(
                "Please enter a valid email address."
            );

            event.preventDefault();

            return;

        }


        if (
            messageValue.length < 5
        ) {

            alert(
                "Please enter a detailed enquiry message."
            );

            event.preventDefault();

            return;

        }


        const confirmed =
            confirm(
                "Create this new property enquiry?"
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