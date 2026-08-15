<?php

session_start();

require_once "../config/database.php";

/* =========================
   ADMIN AUTHENTICATION
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================
   HELPER
========================= */

function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================
   PROPERTY ID
========================= */

$propertyId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$propertyId || $propertyId <= 0) {
    header("Location: properties.php");
    exit;
}


/* =========================
   GET PROPERTY
========================= */

$sql = "
    SELECT *
    FROM properties
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit("Database error.");
}

$stmt->bind_param(
    "i",
    $propertyId
);

$stmt->execute();

$result = $stmt->get_result();

$property = $result->fetch_assoc();

$stmt->close();


if (!$property) {
    header("Location: properties.php?error=not_found");
    exit;
}


/* =========================
   DEFAULT DATA
========================= */

$title = $property["title"] ?? "";
$property_type = $property["property_type"] ?? "";
$listing_type = $property["listing_type"] ?? "sale";
$price = $property["price"] ?? "";
$city = $property["city"] ?? "";
$address = $property["address"] ?? "";
$bedrooms = $property["bedrooms"] ?? 0;
$bathrooms = $property["bathrooms"] ?? 0;
$area = $property["area"] ?? "";
$parking = $property["parking"] ?? 0;
$furnished = $property["furnished"] ?? "unfurnished";
$description = $property["description"] ?? "";
$status = $property["status"] ?? "draft";
$agent_id = $property["agent_id"] ?? 0;

$errors = [];
$success = "";


/* =========================
   UPDATE PROPERTY
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");

    $property_type =
        trim($_POST["property_type"] ?? "");

    $listing_type =
        trim($_POST["listing_type"] ?? "sale");

    $price =
        trim($_POST["price"] ?? "");

    $city =
        trim($_POST["city"] ?? "");

    $address =
        trim($_POST["address"] ?? "");

    $bedrooms =
        (int)($_POST["bedrooms"] ?? 0);

    $bathrooms =
        (int)($_POST["bathrooms"] ?? 0);

    $area =
        trim($_POST["area"] ?? "");

    $parking =
        isset($_POST["parking"]) ? 1 : 0;

    $furnished =
        trim($_POST["furnished"] ?? "unfurnished");

    $description =
        trim($_POST["description"] ?? "");

    $status =
        trim($_POST["status"] ?? "draft");

    $agent_id =
        (int)($_POST["agent_id"] ?? 0);


    /* =========================
       VALIDATION
    ========================= */

    if ($title === "") {
        $errors[] =
            "Property title is required.";
    }

    if ($property_type === "") {
        $errors[] =
            "Property type is required.";
    }

    if (
        $price === "" ||
        !is_numeric($price) ||
        $price <= 0
    ) {
        $errors[] =
            "Enter a valid property price.";
    }

    if ($city === "") {
        $errors[] =
            "City is required.";
    }

    if ($address === "") {
        $errors[] =
            "Address is required.";
    }

    if (
        $area === "" ||
        !is_numeric($area) ||
        $area <= 0
    ) {
        $errors[] =
            "Enter a valid property area.";
    }

    if ($description === "") {
        $errors[] =
            "Property description is required.";
    }


    $allowedTypes = [
        "Apartment",
        "Villa",
        "House",
        "Plot",
        "Commercial",
        "Office",
        "Shop"
    ];

    if (
        !in_array(
            $property_type,
            $allowedTypes,
            true
        )
    ) {
        $errors[] =
            "Invalid property type.";
    }


    $allowedListing = [
        "sale",
        "rent"
    ];

    if (
        !in_array(
            $listing_type,
            $allowedListing,
            true
        )
    ) {
        $errors[] =
            "Invalid listing type.";
    }


    $allowedFurniture = [
        "unfurnished",
        "semi-furnished",
        "fully-furnished"
    ];

    if (
        !in_array(
            $furnished,
            $allowedFurniture,
            true
        )
    ) {
        $errors[] =
            "Invalid furnishing option.";
    }


    $allowedStatus = [
        "draft",
        "published"
    ];

    if (
        !in_array(
            $status,
            $allowedStatus,
            true
        )
    ) {
        $errors[] =
            "Invalid property status.";
    }


    /* =========================
       UPDATE DATABASE
    ========================= */

    if (empty($errors)) {

        $sql = "
            UPDATE properties
            SET
                title = ?,
                price = ?,
                city = ?,
                address = ?,
                property_type = ?,
                listing_type = ?,
                bedrooms = ?,
                bathrooms = ?,
                area = ?,
                parking = ?,
                furnished = ?,
                description = ?,
                status = ?,
                agent_id = ?
            WHERE id = ?
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {

            $errors[] =
                "Database error: " .
                $conn->error;

        } else {

            $priceValue =
                (float)$price;

            $areaValue =
                (float)$area;


            $stmt->bind_param(
                "sdssssiidisssii",
                $title,
                $priceValue,
                $city,
                $address,
                $property_type,
                $listing_type,
                $bedrooms,
                $bathrooms,
                $areaValue,
                $parking,
                $furnished,
                $description,
                $status,
                $agent_id,
                $propertyId
            );


            if ($stmt->execute()) {

                $success =
                    "Property updated successfully.";

                /*
                 * Refresh database values
                 */

                $property["title"] =
                    $title;

                $property["price"] =
                    $priceValue;

                $property["city"] =
                    $city;

                $property["address"] =
                    $address;

                $property["property_type"] =
                    $property_type;

                $property["listing_type"] =
                    $listing_type;

                $property["bedrooms"] =
                    $bedrooms;

                $property["bathrooms"] =
                    $bathrooms;

                $property["area"] =
                    $areaValue;

                $property["parking"] =
                    $parking;

                $property["furnished"] =
                    $furnished;

                $property["description"] =
                    $description;

                $property["status"] =
                    $status;

                $property["agent_id"] =
                    $agent_id;

            } else {

                $errors[] =
                    "Unable to update property: " .
                    $stmt->error;
            }

            $stmt->close();
        }
    }
}


/* =========================
   GET AGENTS
========================= */

$agents = [];

$agentSQL = "
    SELECT id, name, email
    FROM agents
    WHERE status = 'active'
    ORDER BY name ASC
";

$agentResult =
    $conn->query($agentSQL);

if ($agentResult) {

    while (
        $row =
        $agentResult->fetch_assoc()
    ) {

        $agents[] = $row;
    }
}


/* =========================
   CURRENT VALUES
========================= */

$title =
    $property["title"] ?? "";

$property_type =
    $property["property_type"] ?? "";

$listing_type =
    $property["listing_type"] ?? "sale";

$price =
    $property["price"] ?? "";

$city =
    $property["city"] ?? "";

$address =
    $property["address"] ?? "";

$bedrooms =
    $property["bedrooms"] ?? 0;

$bathrooms =
    $property["bathrooms"] ?? 0;

$area =
    $property["area"] ?? "";

$parking =
    $property["parking"] ?? 0;

$furnished =
    $property["furnished"] ??
    "unfurnished";

$description =
    $property["description"] ?? "";

$status =
    $property["status"] ?? "draft";

$agent_id =
    $property["agent_id"] ?? 0;

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
    Edit Property | RealEstate
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

    --background: #f4f6f5;

    --white: #ffffff;

    --text: #18231f;

    --muted: #727c77;

    --border: #dfe6e2;

    --danger: #b43843;

    --danger-bg: #fdebed;

    --success: #17643b;

    --success-bg: #e7f6ec;

}


body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        var(--background);

    color:
        var(--text);

}


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;
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

    padding: 0 25px;

    text-decoration: none;

    color: white;

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

    text-decoration:
        none;

    color:
        rgba(255,255,255,.7);

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

}


/* =========================
   MAIN
========================= */

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
        20;

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


.back {

    height:
        36px;

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        0 13px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    text-decoration:
        none;

    color:
        var(--text);

    font-size:
        9px;

}


/* =========================
   CONTENT
========================= */

.content {

    max-width:
        1250px;

    padding:
        28px 30px 60px;

}


/* =========================
   ALERT
========================= */

.alert {

    padding:
        15px 18px;

    border-radius:
        8px;

    margin-bottom:
        18px;

    font-size:
        10px;

}


.alert-error {

    background:
        var(--danger-bg);

    color:
        var(--danger);

}


.alert-success {

    background:
        var(--success-bg);

    color:
        var(--success);

}


.alert ul {

    margin:
        7px 0 0 18px;

}


.alert li {

    margin-bottom:
        4px;

}


/* =========================
   LAYOUT
========================= */

.form-layout {

    display:
        grid;

    grid-template-columns:
        1fr 320px;

    gap:
        20px;

}


.card {

    background:
        white;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    margin-bottom:
        20px;

    overflow:
        hidden;

}


.card-header {

    padding:
        17px 20px;

    border-bottom:
        1px solid
        var(--border);

}


.card-header h2 {

    font-size:
        13px;

}


.card-header p {

    color:
        var(--muted);

    font-size:
        8px;

    margin-top:
        4px;

}


.card-body {

    padding:
        20px;

}


/* =========================
   FORM
========================= */

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        16px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

    gap:
        6px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    font-size:
        9px;

    font-weight:
        700;

}


.required {

    color:
        var(--danger);

}


.input,
.select,
.textarea {

    width:
        100%;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    outline:
        none;

    font-family:
        inherit;

    font-size:
        10px;

    background:
        white;

}


.input,
.select {

    height:
        42px;

    padding:
        0 12px;

}


.textarea {

    min-height:
        150px;

    padding:
        12px;

    resize:
        vertical;

    line-height:
        1.6;

}


.input:focus,
.select:focus,
.textarea:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23,74,58,.07);

}


.help {

    color:
        var(--muted);

    font-size:
        7px;

}


/* =========================
   PRICE
========================= */

.price-wrapper {

    position:
        relative;

}


.price-symbol {

    position:
        absolute;

    left:
        12px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--muted);

    font-size:
        10px;

}


.price-input {

    padding-left:
        28px;

}


/* =========================
   CHECKBOX
========================= */

.checkbox-row {

    min-height:
        42px;

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    padding:
        0 12px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

}


.checkbox-row input {

    width:
        16px;

    height:
        16px;

    accent-color:
        var(--primary);

}


.checkbox-row label {

    font-size:
        9px;

}


/* =========================
   STATUS
========================= */

.sticky {

    position:
        sticky;

    top:
        100px;

}


.status-option {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    padding:
        12px;

    border:
        1px solid
        var(--border);

    border-radius:
        7px;

    margin-bottom:
        8px;

    cursor:
        pointer;

}


.status-option:has(
    input:checked
) {

    border-color:
        var(--primary);

    background:
        #f1f7f4;

}


.status-option input {

    accent-color:
        var(--primary);

}


.status-option strong {

    display:
        block;

    font-size:
        9px;

}


.status-option span {

    display:
        block;

    font-size:
        7px;

    color:
        var(--muted);

    margin-top:
        3px;

}


/* =========================
   PROPERTY ID
========================= */

.property-id {

    display:
        inline-flex;

    align-items:
        center;

    padding:
        5px 8px;

    border-radius:
        5px;

    background:
        #edf2ef;

    color:
        var(--primary);

    font-size:
        8px;

    font-weight:
        700;

    margin-top:
        7px;

}


/* =========================
   BUTTONS
========================= */

.actions {

    display:
        flex;

    gap:
        8px;

}


.btn {

    height:
        43px;

    border:
        none;

    border-radius:
        6px;

    padding:
        0 18px;

    font-size:
        9px;

    font-weight:
        700;

    cursor:
        pointer;

}


.btn-primary {

    background:
        var(--primary);

    color:
        white;

}


.btn-primary:hover {

    background:
        var(--primary-dark);

}


.btn-secondary {

    background:
        #eef1ef;

    color:
        var(--text);

    text-decoration:
        none;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

}


/* =========================
   DANGER
========================= */

.delete-link {

    display:
        block;

    margin-top:
        10px;

    padding:
        12px;

    text-align:
        center;

    border:
        1px solid
        #f0c6cb;

    border-radius:
        6px;

    color:
        var(--danger);

    text-decoration:
        none;

    font-size:
        8px;

}


.delete-link:hover {

    background:
        var(--danger-bg);

}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:1000px) {

    .form-layout {

        grid-template-columns:
            1fr;

    }

    .sticky {

        position:
            static;

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

    .form-grid {

        grid-template-columns:
            1fr;

    }

    .form-group.full {

        grid-column:
            auto;

    }

    .topbar p {

        display:
            none;

    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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
    style="
        color:#ffb8bf;
        text-decoration:none;
        font-size:10px;
        padding:12px;
        display:block;
    "
>

    🚪
    Logout

</a>

</div>


</aside>


<!-- =========================
     MAIN
========================= -->

<div class="main">


<header class="topbar">


<div>

<h1>
    Edit Property
</h1>

<p>
    Update property information and listing settings.
</p>

</div>


<a
    href="properties.php"
    class="back"
>

    ←

    Back to Properties

</a>


</header>


<main class="content">


<?php if (!empty($errors)): ?>

<div class="alert alert-error">

<strong>
    Please fix the following:
</strong>


<ul>

<?php foreach (
    $errors
    as $error
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


<?php if ($success !== ""): ?>

<div class="alert alert-success">

    ✓

    <?php
    echo safe($success);
    ?>

</div>

<?php endif; ?>


<form
    method="POST"
    id="propertyForm"
>


<div class="form-layout">


<!-- =========================
     LEFT
========================= -->

<div>


<section class="card">


<div class="card-header">

<h2>
    Property Information
</h2>

<p>
    Update the main property information.
</p>

<div class="property-id">

    PROPERTY #

    <?php
    echo (int)$propertyId;
    ?>

</div>

</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group full">

<label>

    Property Title

    <span class="required">
        *
    </span>

</label>


<input
    type="text"
    name="title"
    class="input"
    maxlength="150"
    value="<?php
    echo safe($title);
    ?>"
    required
>


</div>


<div class="form-group">

<label>

    Property Type

    <span class="required">
        *
    </span>

</label>


<select
    name="property_type"
    class="select"
    required
>


<option value="">
    Select property type
</option>


<?php foreach (
    [
        "Apartment",
        "Villa",
        "House",
        "Plot",
        "Commercial",
        "Office",
        "Shop"
    ]
    as $type
): ?>

<option
    value="<?php
    echo $type;
    ?>"
    <?php
    echo $property_type ===
        $type
        ? "selected"
        : "";
    ?>
>

<?php
echo $type;
?>

</option>

<?php endforeach; ?>


</select>

</div>


<div class="form-group">

<label>
    Listing Type
</label>


<select
    name="listing_type"
    class="select"
>


<option
    value="sale"
    <?php
    echo $listing_type ===
        "sale"
        ? "selected"
        : "";
    ?>
>
    For Sale
</option>


<option
    value="rent"
    <?php
    echo $listing_type ===
        "rent"
        ? "selected"
        : "";
    ?>
>
    For Rent
</option>


</select>

</div>


<div class="form-group">

<label>

    Price

    <span class="required">
        *
    </span>

</label>


<div class="price-wrapper">


<span class="price-symbol">
    ₹
</span>


<input
    type="number"
    name="price"
    class="input price-input"
    value="<?php
    echo safe($price);
    ?>"
    min="1"
    step="0.01"
    required
>


</div>

</div>


<div class="form-group">

<label>

    Area

    <span class="required">
        *
    </span>

</label>


<input
    type="number"
    name="area"
    class="input"
    value="<?php
    echo safe($area);
    ?>"
    min="1"
    step="0.01"
    required
>


<span class="help">
    Square feet
</span>


</div>


</div>

</div>

</section>


<!-- =========================
     LOCATION
========================= -->

<section class="card">


<div class="card-header">

<h2>
    Location
</h2>

<p>
    Update property location.
</p>

</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label>

    City

    <span class="required">
        *
    </span>

</label>


<input
    type="text"
    name="city"
    class="input"
    value="<?php
    echo safe($city);
    ?>"
    required
>


</div>


<div class="form-group">

<label>

    Address

    <span class="required">
        *
    </span>

</label>


<input
    type="text"
    name="address"
    class="input"
    value="<?php
    echo safe($address);
    ?>"
    required
>


</div>


</div>

</div>

</section>


<!-- =========================
     DETAILS
========================= -->

<section class="card">


<div class="card-header">

<h2>
    Property Details
</h2>

<p>
    Update rooms, furnishing and parking.
</p>

</div>


<div class="card-body">


<div class="form-grid">


<div class="form-group">

<label>
    Bedrooms
</label>


<input
    type="number"
    name="bedrooms"
    class="input"
    value="<?php
    echo safe($bedrooms);
    ?>"
    min="0"
    max="50"
>


</div>


<div class="form-group">

<label>
    Bathrooms
</label>


<input
    type="number"
    name="bathrooms"
    class="input"
    value="<?php
    echo safe($bathrooms);
    ?>"
    min="0"
    max="50"
>


</div>


<div class="form-group">

<label>
    Furnishing
</label>


<select
    name="furnished"
    class="select"
>


<option
    value="unfurnished"
    <?php
    echo $furnished ===
        "unfurnished"
        ? "selected"
        : "";
    ?>
>
    Unfurnished
</option>


<option
    value="semi-furnished"
    <?php
    echo $furnished ===
        "semi-furnished"
        ? "selected"
        : "";
    ?>
>
    Semi-Furnished
</option>


<option
    value="fully-furnished"
    <?php
    echo $furnished ===
        "fully-furnished"
        ? "selected"
        : "";
    ?>
>
    Fully Furnished
</option>


</select>

</div>


<div class="form-group">

<label>
    Parking
</label>


<div class="checkbox-row">


<input
    type="checkbox"
    name="parking"
    id="parking"
    <?php
    echo $parking
        ? "checked"
        : "";
    ?>
>


<label for="parking">
    Parking Available
</label>


</div>

</div>


<div class="form-group full">

<label>

    Description

    <span class="required">
        *
    </span>

</label>


<textarea
    name="description"
    id="description"
    class="textarea"
    maxlength="3000"
    required
><?php
echo safe($description);
?></textarea>


<span
    class="help"
    id="descriptionCount"
>
</span>


</div>


</div>

</div>

</section>


<!-- =========================
     SAVE
========================= -->

<section class="card">


<div class="card-body">


<div class="actions">


<a
    href="properties.php"
    class="btn btn-secondary"
>

    Cancel

</a>


<button
    type="submit"
    class="btn btn-primary"
    id="saveButton"
>

    ✓ Save Changes

</button>


</div>


</div>

</section>


</div>


<!-- =========================
     RIGHT
========================= -->

<div>


<div class="sticky">


<!-- PUBLISHING -->

<section class="card">


<div class="card-header">

<h2>
    Publishing
</h2>

<p>
    Control property visibility.
</p>

</div>


<div class="card-body">


<label class="status-option">


<input
    type="radio"
    name="status"
    value="draft"
    <?php
    echo $status ===
        "draft"
        ? "checked"
        : "";
    ?>
>


<div>

<strong>
    Draft
</strong>

<span>
    Property remains hidden.
</span>

</div>


</label>


<label class="status-option">


<input
    type="radio"
    name="status"
    value="published"
    <?php
    echo $status ===
        "published"
        ? "checked"
        : "";
    ?>
>


<div>

<strong>
    Published
</strong>

<span>
    Property is visible to users.
</span>

</div>


</label>


</div>

</section>


<!-- AGENT -->

<section class="card">


<div class="card-header">

<h2>
    Assigned Agent
</h2>

<p>
    Manage property responsibility.
</p>

</div>


<div class="card-body">


<select
    name="agent_id"
    class="select"
>


<option value="0">
    No Agent Assigned
</option>


<?php foreach (
    $agents
    as $agent
): ?>


<option
    value="<?php
    echo (int)$agent["id"];
    ?>"
    <?php
    echo (int)$agent_id ===
        (int)$agent["id"]
        ? "selected"
        : "";
    ?>
>


<?php
echo safe(
    $agent["name"]
);
?>

-

<?php
echo safe(
    $agent["email"]
);
?>


</option>


<?php endforeach; ?>


</select>


</div>

</section>


<!-- PROPERTY SUMMARY -->

<section class="card">


<div class="card-header">

<h2>
    Property Summary
</h2>

</div>


<div class="card-body">


<div
    style="
        font-size:9px;
        line-height:2;
        color:#727c77;
    "
>


<div>

<strong
    style="color:#18231f"
>
    ID:
</strong>

#

<?php
echo (int)$propertyId;
?>

</div>


<div>

<strong
    style="color:#18231f"
>
    Created:
</strong>

<?php

echo !empty(
    $property["created_at"]
)
    ? date(
        "d M Y",
        strtotime(
            $property["created_at"]
        )
    )
    : "—";

?>

</div>


<div>

<strong
    style="color:#18231f"
>
    Status:
</strong>

<?php
echo ucfirst(
    $status
);
?>

</div>


</div>


</div>

</section>


<!-- DELETE -->

<section class="card">


<div class="card-body">


<a
    href="property-delete.php?id=<?php
    echo (int)$propertyId;
    ?>"
    class="delete-link"
    id="deleteProperty"
>

    🗑 Delete Property

</a>


</div>

</section>


</div>

</div>


</div>


</form>


</main>


</div>


<script>

/* =========================
   DESCRIPTION COUNTER
========================= */

const description =
    document.getElementById(
        "description"
    );

const descriptionCount =
    document.getElementById(
        "descriptionCount"
    );


function updateCounter()
{

    const count =
        description.value.length;

    descriptionCount.textContent =
        count +
        " / 3000 characters";

}


description.addEventListener(
    "input",
    updateCounter
);

updateCounter();


/* =========================
   FORM SUBMIT
========================= */

const form =
    document.getElementById(
        "propertyForm"
    );

const saveButton =
    document.getElementById(
        "saveButton"
    );


form.addEventListener(
    "submit",
    function()
    {

        saveButton.disabled =
            true;

        saveButton.textContent =
            "Saving Changes...";

    }
);


/* =========================
   DELETE CONFIRMATION
========================= */

const deleteProperty =
    document.getElementById(
        "deleteProperty"
    );


deleteProperty.addEventListener(
    "click",
    function(event)
    {

        const confirmed =
            confirm(
                "Are you sure you want to delete this property?\n\nThis action cannot be undone."
            );


        if (!confirmed) {

            event.preventDefault();

        }

    }
);

</script>


</body>

</html>