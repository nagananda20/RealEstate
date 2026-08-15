<?php

session_start();

require_once "../config/database.php";

/* =========================
   ADMIN AUTH
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

$errors = [];
$success = "";

/* =========================
   DEFAULT VALUES
========================= */

$title = "";
$property_type = "";
$listing_type = "sale";
$price = "";
$city = "";
$address = "";
$bedrooms = "";
$bathrooms = "";
$area = "";
$parking = 0;
$furnished = "unfurnished";
$description = "";
$status = "draft";
$agent_id = "";

/* =========================
   FORM SUBMIT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $property_type = trim($_POST["property_type"] ?? "");
    $listing_type = trim($_POST["listing_type"] ?? "sale");
    $price = trim($_POST["price"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $bedrooms = (int)($_POST["bedrooms"] ?? 0);
    $bathrooms = (int)($_POST["bathrooms"] ?? 0);
    $area = trim($_POST["area"] ?? "");
    $parking = isset($_POST["parking"]) ? 1 : 0;
    $furnished = trim($_POST["furnished"] ?? "unfurnished");
    $description = trim($_POST["description"] ?? "");
    $status = trim($_POST["status"] ?? "draft");
    $agent_id = (int)($_POST["agent_id"] ?? 0);

    /* =========================
       VALIDATION
    ========================= */

    if ($title === "") {
        $errors[] = "Property title is required.";
    }

    if ($property_type === "") {
        $errors[] = "Property type is required.";
    }

    if ($price === "" || !is_numeric($price) || $price <= 0) {
        $errors[] = "Enter a valid property price.";
    }

    if ($city === "") {
        $errors[] = "City is required.";
    }

    if ($address === "") {
        $errors[] = "Address is required.";
    }

    if ($area === "" || !is_numeric($area)) {
        $errors[] = "Enter a valid property area.";
    }

    if ($description === "") {
        $errors[] = "Property description is required.";
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

    if (!in_array($property_type, $allowedTypes, true)) {
        $errors[] = "Invalid property type.";
    }

    $allowedListing = [
        "sale",
        "rent"
    ];

    if (!in_array($listing_type, $allowedListing, true)) {
        $errors[] = "Invalid listing type.";
    }

    $allowedFurniture = [
        "unfurnished",
        "semi-furnished",
        "fully-furnished"
    ];

    if (!in_array($furnished, $allowedFurniture, true)) {
        $errors[] = "Invalid furnishing option.";
    }

    $allowedStatus = [
        "draft",
        "published"
    ];

    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = "Invalid property status.";
    }

    /* =========================
       INSERT PROPERTY
    ========================= */

    if (empty($errors)) {

        /*
         * IMPORTANT:
         * This query expects these columns:
         *
         * title
         * price
         * city
         * address
         * property_type
         * listing_type
         * bedrooms
         * bathrooms
         * area
         * parking
         * furnished
         * description
         * status
         * agent_id
         */

        $sql = "
            INSERT INTO properties
            (
                title,
                price,
                city,
                address,
                property_type,
                listing_type,
                bedrooms,
                bathrooms,
                area,
                parking,
                furnished,
                description,
                status,
                agent_id,
                created_at
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $errors[] =
                "Database error: " .
                $conn->error;

        } else {

            $priceValue = (float)$price;
            $areaValue = (float)$area;

            $stmt->bind_param(
                "sdssssiidisssi",
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
                $agent_id
            );

            if ($stmt->execute()) {

                $newPropertyId =
                    $stmt->insert_id;

                /*
                 * Redirect after successful insert.
                 * Prevents duplicate submission on refresh.
                 */

                header(
                    "Location: properties.php?added=1&id=" .
                    $newPropertyId
                );

                exit;

            } else {

                $errors[] =
                    "Unable to save property: " .
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
    Add Property | RealEstate
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
        var(--bg);

    color:
        var(--text);

}


/* =========================
   SIDEBAR
========================= */

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

}

.sidebar-bottom {

    position:
        absolute;

    bottom:
        0;

    left:
        0;

    right:
        0;

    padding:
        15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

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
        10;

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

    color:
        var(--text);

    text-decoration:
        none;

    font-size:
        9px;

}


/* =========================
   CONTENT
========================= */

.content {

    padding:
        28px 30px 60px;

    max-width:
        1250px;

}


/* =========================
   ERROR
========================= */

.error-box {

    background:
        var(--danger-bg);

    color:
        var(--danger);

    border-radius:
        8px;

    padding:
        15px 18px;

    margin-bottom:
        18px;

    font-size:
        10px;

}

.error-box ul {

    margin-left:
        18px;

    margin-top:
        7px;

}

.error-box li {

    margin-bottom:
        4px;

}


/* =========================
   FORM LAYOUT
========================= */

.form-layout {

    display:
        grid;

    grid-template-columns:
        1fr 320px;

    gap:
        20px;

}


/* =========================
   CARD
========================= */

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
        repeat(2, 1fr);

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

    resize:
        vertical;

    padding:
        12px;

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
   PRICE INPUT
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

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    min-height:
        42px;

    border:
        1px solid
        var(--border);

    padding:
        0 12px;

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

    font-weight:
        600;

}


/* =========================
   IMAGE UPLOAD
========================= */

.upload-box {

    border:
        2px dashed
        #cfd8d3;

    border-radius:
        9px;

    min-height:
        210px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    text-align:
        center;

    padding:
        20px;

    cursor:
        pointer;

    transition:
        .2s;

}

.upload-box:hover {

    border-color:
        var(--primary);

    background:
        #fafcfb;

}

.upload-icon {

    font-size:
        35px;

    margin-bottom:
        10px;

}

.upload-box strong {

    font-size:
        11px;

}

.upload-box p {

    color:
        var(--muted);

    font-size:
        8px;

    margin-top:
        6px;

}

#images {

    display:
        none;

}

.preview-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        8px;

    margin-top:
        12px;

}

.preview {

    height:
        80px;

    border-radius:
        6px;

    overflow:
        hidden;

    position:
        relative;

    background:
        #edf2ef;

}

.preview img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* =========================
   SIDE CARD
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

.status-option:has(input:checked) {

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
   BUTTONS
========================= */

.form-actions {

    display:
        flex;

    gap:
        8px;

    margin-top:
        5px;

}

.btn {

    height:
        43px;

    border-radius:
        6px;

    padding:
        0 18px;

    border:
        none;

    cursor:
        pointer;

    font-size:
        9px;

    font-weight:
        700;

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

}


/* =========================
   MOBILE
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

            <span class="icon">📊</span>

            <span>Dashboard</span>

        </a>


        <a
            href="properties.php"
            class="active"
        >

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
            class="menu a"
            style="
                color:#ffb8bf;
                text-decoration:none;
            "
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


<!-- =========================
     MAIN
========================= -->

<div class="main">


<header class="topbar">

    <div>

        <h1>
            Add New Property
        </h1>

        <p>
            Create a professional property listing.
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

    <div class="error-box">

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


<form
    method="POST"
    enctype="multipart/form-data"
    id="propertyForm"
>


<div class="form-layout">


<!-- =========================
     LEFT
========================= -->

<div>


<!-- PROPERTY INFORMATION -->

<section class="card">

    <div class="card-header">

        <h2>
            Property Information
        </h2>

        <p>
            Basic information about the property.
        </p>

    </div>


    <div class="card-body">

        <div class="form-grid">


            <div class="form-group full">

                <label>
                    Property Title
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    class="input"
                    placeholder="Example: Premium 3 BHK Apartment"
                    value="<?php
                    echo safe($title);
                    ?>"
                    maxlength="150"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Property Type
                    <span class="required">*</span>
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
                        as $item
                    ): ?>

                        <option
                            value="<?php
                            echo $item;
                            ?>"
                            <?php
                            echo $property_type ===
                                $item
                                ? "selected"
                                : "";
                            ?>
                        >

                            <?php
                            echo $item;
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
                        echo $listing_type === "sale"
                            ? "selected"
                            : "";
                        ?>
                    >
                        For Sale
                    </option>

                    <option
                        value="rent"
                        <?php
                        echo $listing_type === "rent"
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
                    <span class="required">*</span>
                </label>

                <div class="price-wrapper">

                    <span class="price-symbol">
                        ₹
                    </span>

                    <input
                        type="number"
                        name="price"
                        class="input price-input"
                        placeholder="5000000"
                        value="<?php
                        echo safe($price);
                        ?>"
                        min="1"
                        step="0.01"
                        required
                    >

                </div>

                <span class="help">
                    Enter the complete property price.
                </span>

            </div>


            <div class="form-group">

                <label>
                    Area
                    <span class="required">*</span>
                </label>

                <input
                    type="number"
                    name="area"
                    class="input"
                    placeholder="1500"
                    value="<?php
                    echo safe($area);
                    ?>"
                    min="1"
                    step="0.01"
                    required
                >

                <span class="help">
                    Area in square feet.
                </span>

            </div>


        </div>

    </div>

</section>


<!-- LOCATION -->

<section class="card">

    <div class="card-header">

        <h2>
            Location
        </h2>

        <p>
            Add the exact property location.
        </p>

    </div>


    <div class="card-body">

        <div class="form-grid">


            <div class="form-group">

                <label>
                    City
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="city"
                    class="input"
                    placeholder="Bengaluru"
                    value="<?php
                    echo safe($city);
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Address
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="address"
                    class="input"
                    placeholder="Street, Area, Landmark"
                    value="<?php
                    echo safe($address);
                    ?>"
                    required
                >

            </div>


        </div>

    </div>

</section>


<!-- PROPERTY DETAILS -->

<section class="card">

    <div class="card-header">

        <h2>
            Property Details
        </h2>

        <p>
            Add rooms, parking and furnishing details.
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
                        id="parking"
                        name="parking"
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
                    <span class="required">*</span>
                </label>

                <textarea
                    name="description"
                    id="description"
                    class="textarea"
                    placeholder="Describe the property, location advantages, amenities, nearby facilities, etc."
                    maxlength="3000"
                    required
                ><?php
                echo safe($description);
                ?></textarea>

                <span
                    class="help"
                    id="descriptionCount"
                >
                    0 / 3000 characters
                </span>

            </div>


        </div>

    </div>

</section>


<!-- IMAGES -->

<section class="card">

    <div class="card-header">

        <h2>
            Property Images
        </h2>

        <p>
            Add property photos for the listing.
        </p>

    </div>


    <div class="card-body">


        <label
            class="upload-box"
            for="images"
        >

            <div class="upload-icon">
                📷
            </div>

            <strong>
                Click to upload property images
            </strong>

            <p>
                JPG, JPEG or PNG • Maximum 5 images
            </p>

        </label>


        <input
            type="file"
            id="images"
            name="images[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
        >


        <div
            class="preview-grid"
            id="previewGrid"
        ></div>


    </div>

</section>


</div>


<!-- =========================
     RIGHT
========================= -->

<div>


<div class="sticky">


<!-- STATUS -->

<section class="card">

    <div class="card-header">

        <h2>
            Publishing
        </h2>

        <p>
            Choose how this property should appear.
        </p>

    </div>


    <div class="card-body">


        <label class="status-option">

            <input
                type="radio"
                name="status"
                value="draft"
                <?php
                echo $status === "draft"
                    ? "checked"
                    : "";
                ?>
            >

            <div>

                <strong>
                    Save as Draft
                </strong>

                <span>
                    Keep the property private.
                </span>

            </div>

        </label>


        <label class="status-option">

            <input
                type="radio"
                name="status"
                value="published"
                <?php
                echo $status === "published"
                    ? "checked"
                    : "";
                ?>
            >

            <div>

                <strong>
                    Publish Property
                </strong>

                <span>
                    Make the property visible.
                </span>

            </div>

        </label>


    </div>

</section>


<!-- AGENT -->

<section class="card">

    <div class="card-header">

        <h2>
            Assign Agent
        </h2>

        <p>
            Select the agent responsible for this property.
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


<!-- ACTIONS -->

<section class="card">

    <div class="card-body">


        <div class="form-actions">


            <a
                href="properties.php"
                class="btn btn-secondary"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    text-decoration:none;
                "
            >

                Cancel

            </a>


            <button
                type="submit"
                class="btn btn-primary"
                id="submitButton"
            >

                + Add Property

            </button>


        </div>


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


function updateDescriptionCount()
{

    const length =
        description.value.length;

    descriptionCount.textContent =
        length +
        " / 3000 characters";

}


description.addEventListener(
    "input",
    updateDescriptionCount
);

updateDescriptionCount();


/* =========================
   IMAGE PREVIEW
========================= */

const imageInput =
    document.getElementById(
        "images"
    );

const previewGrid =
    document.getElementById(
        "previewGrid"
    );


imageInput.addEventListener(
    "change",
    function()
    {

        previewGrid.innerHTML = "";

        const files =
            Array.from(
                this.files
            );

        if (files.length > 5) {

            alert(
                "You can upload a maximum of 5 images."
            );

            this.value = "";

            return;
        }


        files.forEach(
            function(file)
            {

                if (
                    !file.type.startsWith(
                        "image/"
                    )
                ) {
                    return;
                }


                const reader =
                    new FileReader();


                reader.onload =
                    function(event)
                    {

                        const preview =
                            document.createElement(
                                "div"
                            );

                        preview.className =
                            "preview";


                        const image =
                            document.createElement(
                                "img"
                            );

                        image.src =
                            event.target.result;


                        preview.appendChild(
                            image
                        );


                        previewGrid.appendChild(
                            preview
                        );

                    };


                reader.readAsDataURL(
                    file
                );

            }
        );

    }
);


/* =========================
   FORM SUBMIT
========================= */

const form =
    document.getElementById(
        "propertyForm"
    );

const submitButton =
    document.getElementById(
        "submitButton"
    );


form.addEventListener(
    "submit",
    function()
    {

        submitButton.disabled =
            true;

        submitButton.textContent =
            "Saving Property...";

    }
);

</script>

</body>

</html>