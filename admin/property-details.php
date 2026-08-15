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


/* =========================================================
   PROPERTY ID
========================================================= */

$propertyId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$propertyId || $propertyId <= 0) {
    header("Location: properties.php?error=invalid_id");
    exit;
}


/* =========================================================
   GET PROPERTY
========================================================= */

$sql = "
    SELECT
        p.*,
        u.name AS agent_name,
        u.email AS agent_email,
        u.phone AS agent_phone
    FROM properties p
    LEFT JOIN users u
        ON p.agent_id = u.id
    WHERE p.id = ?
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
    header(
        "Location: properties.php?error=not_found"
    );
    exit;
}


/* =========================================================
   PROPERTY VALUES
========================================================= */

$title =
    $property["title"] ?? "Untitled Property";

$description =
    $property["description"] ?? "";

$propertyType =
    $property["property_type"] ?? "Property";

$listingType =
    $property["listing_type"] ?? "sale";

$status =
    $property["status"] ?? "draft";

$price =
    (float)($property["price"] ?? 0);

$area =
    (float)($property["area"] ?? 0);

$bedrooms =
    (int)($property["bedrooms"] ?? 0);

$bathrooms =
    (int)($property["bathrooms"] ?? 0);

$parking =
    $property["parking"] ?? "Not specified";

$city =
    $property["city"] ?? "";

$state =
    $property["state"] ?? "";

$address =
    $property["address"] ?? "";

$createdAt =
    $property["created_at"] ?? "";


/* =========================================================
   GET PROPERTY IMAGES
========================================================= */

$images = [];

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'property_images'"
);

if (
    $tableCheck &&
    $tableCheck->num_rows > 0
) {

    $imageSQL = "
        SELECT
            id,
            image_path
        FROM property_images
        WHERE property_id = ?
        ORDER BY id ASC
    ";

    $imageStmt =
        $conn->prepare($imageSQL);

    if ($imageStmt) {

        $imageStmt->bind_param(
            "i",
            $propertyId
        );

        $imageStmt->execute();

        $imageResult =
            $imageStmt->get_result();

        while (
            $row =
            $imageResult->fetch_assoc()
        ) {

            if (
                !empty(
                    $row["image_path"]
                )
            ) {

                $images[] =
                    $row["image_path"];
            }
        }

        $imageStmt->close();
    }
}


/* =========================================================
   FALLBACK IMAGE
========================================================= */

if (empty($images)) {

    $images[] =
        "../assets/images/property-placeholder.jpg";
}


/* =========================================================
   ENQUIRY COUNT
========================================================= */

$enquiryCount = 0;

$enquiryTable =
    $conn->query(
        "SHOW TABLES LIKE 'enquiries'"
    );

if (
    $enquiryTable &&
    $enquiryTable->num_rows > 0
) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM enquiries
        WHERE property_id = ?
    ";

    $stmt =
        $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $propertyId
        );

        $stmt->execute();

        $row =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $enquiryCount =
            (int)(
                $row["total"] ?? 0
            );

        $stmt->close();
    }
}


/* =========================================================
   VISIT COUNT
========================================================= */

$visitCount = 0;

$visitTable =
    $conn->query(
        "SHOW TABLES LIKE 'visits'"
    );

if (
    $visitTable &&
    $visitTable->num_rows > 0
) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM visits
        WHERE property_id = ?
    ";

    $stmt =
        $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $propertyId
        );

        $stmt->execute();

        $row =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $visitCount =
            (int)(
                $row["total"] ?? 0
            );

        $stmt->close();
    }
}


/* =========================================================
   FAVORITES COUNT
========================================================= */

$favoriteCount = 0;

$favoriteTable =
    $conn->query(
        "SHOW TABLES LIKE 'favorites'"
    );

if (
    $favoriteTable &&
    $favoriteTable->num_rows > 0
) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM favorites
        WHERE property_id = ?
    ";

    $stmt =
        $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $propertyId
        );

        $stmt->execute();

        $row =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $favoriteCount =
            (int)(
                $row["total"] ?? 0
            );

        $stmt->close();
    }
}


/* =========================================================
   DATE FORMAT
========================================================= */

$formattedDate = "N/A";

if (!empty($createdAt)) {

    $timestamp =
        strtotime($createdAt);

    if ($timestamp) {

        $formattedDate =
            date(
                "d M Y, h:i A",
                $timestamp
            );
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
    <?php echo safe($title); ?>
    | RealEstate Admin
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

    --success: #17643b;
    --success-bg: #e7f6ec;

    --danger: #b43843;
    --danger-bg: #fdebed;

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
        20;

}


.topbar-left {

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

}


.back {

    width:
        38px;

    height:
        38px;

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
        7px;

    text-decoration:
        none;

    color:
        var(--text);

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


.top-actions {

    display:
        flex;

    gap:
        7px;

}


.btn {

    height:
        38px;

    padding:
        0 13px;

    border-radius:
        6px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;

}


.btn-edit {

    background:
        var(--primary);

    color:
        white;

}


.btn-delete {

    background:
        var(--danger-bg);

    color:
        var(--danger);

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1400px;

    padding:
        28px 30px 60px;

}


/* =========================================================
   GRID
========================================================= */

.details-grid {

    display:
        grid;

    grid-template-columns:
        1.55fr .75fr;

    gap:
        20px;

}


/* =========================================================
   CARD
========================================================= */

.card {

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


.card-body {

    padding:
        20px;

}


/* =========================================================
   GALLERY
========================================================= */

.gallery {

    position:
        relative;

}


.main-image {

    width:
        100%;

    height:
        390px;

    object-fit:
        cover;

    display:
        block;

    background:
        #edf1ef;

}


.gallery-label {

    position:
        absolute;

    top:
        15px;

    left:
        15px;

    padding:
        7px 10px;

    border-radius:
        5px;

    background:
        rgba(0,0,0,.65);

    color:
        white;

    font-size:
        8px;

}


.thumbnails {

    display:
        grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:
        7px;

    padding:
        10px;

}


.thumbnail {

    height:
        65px;

    width:
        100%;

    object-fit:
        cover;

    border-radius:
        5px;

    cursor:
        pointer;

    border:
        2px solid
        transparent;

}


.thumbnail.active {

    border-color:
        var(--primary);

}


/* =========================================================
   PROPERTY TITLE
========================================================= */

.property-heading {

    padding:
        20px;

}


.property-heading h2 {

    font-size:
        20px;

    line-height:
        1.35;

}


.location {

    margin-top:
        8px;

    color:
        var(--muted);

    font-size:
        9px;

}


.tags {

    margin-top:
        15px;

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        6px;

}


.tag {

    padding:
        6px 9px;

    border-radius:
        5px;

    background:
        #f0f4f2;

    color:
        var(--primary);

    font-size:
        8px;

    font-weight:
        700;

}


.tag.sale {

    background:
        var(--blue-bg);

    color:
        var(--blue);

}


.tag.rent {

    background:
        #f7edfc;

    color:
        #7c4b98;

}


.tag.published {

    background:
        var(--success-bg);

    color:
        var(--success);

}


.tag.draft {

    background:
        #fff7df;

    color:
        #986d12;

}


/* =========================================================
   PRICE
========================================================= */

.price-box {

    margin-top:
        20px;

    padding:
        20px;

    border-radius:
        8px;

    background:
        #f7f9f8;

}


.price-label {

    color:
        var(--muted);

    font-size:
        8px;

    text-transform:
        uppercase;

}


.price {

    margin-top:
        6px;

    font-size:
        25px;

    font-weight:
        800;

    color:
        var(--primary);

}


/* =========================================================
   FEATURES
========================================================= */

.features {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    border-top:
        1px solid
        var(--border);

    border-bottom:
        1px solid
        var(--border);

}


.feature {

    padding:
        18px;

    text-align:
        center;

    border-right:
        1px solid
        var(--border);

}


.feature:last-child {

    border-right:
        none;

}


.feature-icon {

    font-size:
        18px;

}


.feature-number {

    margin-top:
        7px;

    font-weight:
        800;

    font-size:
        12px;

}


.feature-label {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;

}


/* =========================================================
   DESCRIPTION
========================================================= */

.description {

    color:
        #59635e;

    font-size:
        9px;

    line-height:
        1.8;

    white-space:
        pre-line;

}


/* =========================================================
   INFO GRID
========================================================= */

.info-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        0;

}


.info-item {

    padding:
        13px 0;

    border-bottom:
        1px solid
        #edf0ee;

}


.info-item:nth-child(odd) {

    margin-right:
        20px;

}


.info-label {

    color:
        var(--muted);

    font-size:
        7px;

    text-transform:
        uppercase;

}


.info-value {

    margin-top:
        5px;

    font-size:
        9px;

    font-weight:
        700;

}


/* =========================================================
   SIDE STATISTICS
========================================================= */

.side-stat {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        10px;

}


.mini-stat {

    padding:
        15px;

    border:
        1px solid
        var(--border);

    border-radius:
        7px;

}


.mini-icon {

    font-size:
        16px;

}


.mini-number {

    margin-top:
        7px;

    font-size:
        18px;

    font-weight:
        800;

}


.mini-label {

    margin-top:
        3px;

    color:
        var(--muted);

    font-size:
        7px;

}


/* =========================================================
   AGENT
========================================================= */

.agent {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.agent-avatar {

    width:
        45px;

    height:
        45px;

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

    font-weight:
        800;

    font-size:
        14px;

}


.agent-name {

    font-size:
        10px;

    font-weight:
        800;

}


.agent-contact {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;

    line-height:
        1.6;

}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {

    display:
        grid;

    gap:
        8px;

}


.quick-btn {

    height:
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

    text-decoration:
        none;

    color:
        var(--text);

    font-size:
        8px;

    font-weight:
        700;

}


.quick-btn:hover {

    background:
        #f6f8f7;

}


/* =========================================================
   TIMELINE
========================================================= */

.timeline {

    position:
        relative;

}


.timeline-item {

    position:
        relative;

    padding:
        0 0 20px 25px;

}


.timeline-item::before {

    content:
        "";

    position:
        absolute;

    left:
        5px;

    top:
        6px;

    width:
        9px;

    height:
        9px;

    border-radius:
        50%;

    background:
        var(--primary);

}


.timeline-item:not(:last-child)::after {

    content:
        "";

    position:
        absolute;

    left:
        9px;

    top:
        15px;

    width:
        1px;

    height:
        calc(100% - 5px);

    background:
        var(--border);

}


.timeline-title {

    font-size:
        8px;

    font-weight:
        800;

}


.timeline-date {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        7px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .details-grid {

        grid-template-columns:
            1fr;

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

    .top-actions .btn span {

        display:
            none;

    }

    .content {

        padding:
            20px 15px;

    }

}


@media(max-width:600px) {

    .topbar {

        padding:
            0 15px;

    }

    .topbar h1 {

        font-size:
            15px;

    }

    .topbar p {

        display:
            none;

    }

    .content {

        padding:
            15px;

    }

    .main-image {

        height:
            250px;

    }

    .features {

        grid-template-columns:
            repeat(2,1fr);

    }

    .feature:nth-child(2) {

        border-right:
            none;

    }

    .feature:nth-child(1),
    .feature:nth-child(2) {

        border-bottom:
            1px solid
            var(--border);

    }

    .info-grid {

        grid-template-columns:
            1fr;

    }

    .info-item:nth-child(odd) {

        margin-right:
            0;

    }

}

</style>

</head>


<body>


<!-- =========================================================
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

    <span>
        Dashboard
    </span>

</a>


<a
    href="properties.php"
    class="active"
>

    <span class="icon">🏠</span>

    <span>
        Properties
    </span>

</a>


<a href="users.php">

    <span class="icon">👥</span>

    <span>
        Users
    </span>

</a>


<a href="agents.php">

    <span class="icon">🧑‍💼</span>

    <span>
        Agents
    </span>

</a>


<a href="enquiries.php">

    <span class="icon">💬</span>

    <span>
        Enquiries
    </span>

</a>


<a href="visits.php">

    <span class="icon">📅</span>

    <span>
        Visits
    </span>

</a>


<a href="settings.php">

    <span class="icon">⚙️</span>

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


<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


<header class="topbar">


<div class="topbar-left">


<a
    href="properties.php"
    class="back"
>

    ←

</a>


<div>

<h1>
    Property Details
</h1>

<p>
    Complete property information
</p>

</div>


</div>


<div class="top-actions">


<a
    href="property-edit.php?id=<?php
    echo (int)$propertyId;
    ?>"
    class="btn btn-edit"
>

    ✏️

    <span>
        Edit Property
    </span>

</a>


<a
    href="property-delete.php?id=<?php
    echo (int)$propertyId;
    ?>"
    class="btn btn-delete"
    id="deleteProperty"
>

    🗑️

    <span>
        Delete
    </span>

</a>


</div>


</header>


<main class="content">


<div class="details-grid">


<!-- =====================================================
     LEFT COLUMN
===================================================== -->

<div>


<!-- GALLERY -->

<section class="card">


<div class="gallery">


<img
    src="<?php
    echo safe($images[0]);
    ?>"
    class="main-image"
    id="mainImage"
    alt="<?php
    echo safe($title);
    ?>"
>


<div class="gallery-label">

    <?php
    echo count($images);
    ?>

    Image(s)

</div>


</div>


<div class="thumbnails">


<?php foreach (
    $images
    as $index => $image
): ?>


<img
    src="<?php
    echo safe($image);
    ?>"
    class="thumbnail
    <?php
    echo $index === 0
        ? 'active'
        : '';
    ?>"
    data-image="<?php
    echo safe($image);
    ?>"
    alt="Property image"
>


<?php endforeach; ?>


</div>


</section>


<!-- PROPERTY HEADING -->

<section class="card"
    style="margin-top:20px;"
>


<div class="property-heading">


<h2>

<?php
echo safe($title);
?>

</h2>


<div class="location">

    📍

    <?php
    echo safe($address);
    ?>

    <?php if ($city): ?>

    ,

    <?php
    echo safe($city);
    ?>

    <?php endif; ?>


    <?php if ($state): ?>

    ,

    <?php
    echo safe($state);
    ?>

    <?php endif; ?>

</div>


<div class="tags">


<span class="tag">

<?php
echo safe($propertyType);
?>

</span>


<span class="tag
<?php
echo $listingType === "rent"
    ? " rent"
    : " sale";
?>">

<?php

echo $listingType === "rent"
    ? "For Rent"
    : "For Sale";

?>

</span>


<span class="tag
<?php
echo $status === "published"
    ? " published"
    : " draft";
?>">

<?php
echo $status === "published"
    ? "Published"
    : "Draft";
?>

</span>


</div>


<div class="price-box">


<div class="price-label">
    Property Price
</div>


<div class="price">

₹<?php

echo number_format(
    $price
);

?>


<?php if (
    $listingType === "rent"
): ?>

<span style="
    font-size:10px;
    font-weight:500;
    color:#727c77;
">

    / month

</span>

<?php endif; ?>


</div>


</div>


</div>


<!-- FEATURES -->

<div class="features">


<div class="feature">

<div class="feature-icon">
    🛏️
</div>

<div class="feature-number">

<?php
echo $bedrooms;
?>

</div>

<div class="feature-label">
    Bedrooms
</div>

</div>


<div class="feature">

<div class="feature-icon">
    🛁
</div>

<div class="feature-number">

<?php
echo $bathrooms;
?>

</div>

<div class="feature-label">
    Bathrooms
</div>

</div>


<div class="feature">

<div class="feature-icon">
    📐
</div>

<div class="feature-number">

<?php
echo number_format(
    $area
);
?>

</div>

<div class="feature-label">
    Sq. Ft.
</div>

</div>


<div class="feature">

<div class="feature-icon">
    🚗
</div>

<div class="feature-number">

<?php
echo safe($parking);
?>

</div>

<div class="feature-label">
    Parking
</div>

</div>


</div>


</section>


<!-- DESCRIPTION -->

<section class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Property Description
</h2>

</div>


<div class="card-body">


<div class="description">

<?php

if (
    trim($description) !== ""
) {

    echo safe(
        $description
    );

} else {

    echo "No description available for this property.";

}

?>

</div>


</div>


</section>


<!-- PROPERTY INFORMATION -->

<section class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Property Information
</h2>

</div>


<div class="card-body">


<div class="info-grid">


<div class="info-item">

<div class="info-label">
    Property ID
</div>

<div class="info-value">

#<?php
echo (int)$propertyId;
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Property Type
</div>

<div class="info-value">

<?php
echo safe($propertyType);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Listing Type
</div>

<div class="info-value">

<?php

echo $listingType === "rent"
    ? "For Rent"
    : "For Sale";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Status
</div>

<div class="info-value">

<?php
echo ucfirst(
    safe($status)
);
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    City
</div>

<div class="info-value">

<?php
echo safe($city ?: "N/A");
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    State
</div>

<div class="info-value">

<?php
echo safe($state ?: "N/A");
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Address
</div>

<div class="info-value">

<?php
echo safe($address ?: "N/A");
?>

</div>

</div>


<div class="info-item">

<div class="info-label">
    Added On
</div>

<div class="info-value">

<?php
echo safe($formattedDate);
?>

</div>

</div>


</div>


</div>


</section>


</div>


<!-- =====================================================
     RIGHT COLUMN
===================================================== -->

<div>


<!-- STATISTICS -->

<section class="card">


<div class="card-header">

<h2>
    Property Analytics
</h2>

</div>


<div class="card-body">


<div class="side-stat">


<div class="mini-stat">

<div class="mini-icon">
    💬
</div>

<div class="mini-number">

<?php
echo number_format(
    $enquiryCount
);
?>

</div>

<div class="mini-label">
    Enquiries
</div>

</div>


<div class="mini-stat">

<div class="mini-icon">
    📅
</div>

<div class="mini-number">

<?php
echo number_format(
    $visitCount
);
?>

</div>

<div class="mini-label">
    Visits
</div>

</div>


<div class="mini-stat">

<div class="mini-icon">
    ❤️
</div>

<div class="mini-number">

<?php
echo number_format(
    $favoriteCount
);
?>

</div>

<div class="mini-label">
    Favorites
</div>

</div>


<div class="mini-stat">

<div class="mini-icon">
    🖼️
</div>

<div class="mini-number">

<?php
echo number_format(
    count($images)
);
?>

</div>

<div class="mini-label">
    Images
</div>

</div>


</div>


</div>


</section>


<!-- AGENT -->

<section class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Assigned Agent
</h2>

</div>


<div class="card-body">


<?php if (
    !empty(
        $property["agent_name"]
    )
): ?>


<div class="agent">


<div class="agent-avatar">

<?php

$agentName =
    $property["agent_name"];

echo strtoupper(
    substr(
        $agentName,
        0,
        1
    )
);

?>

</div>


<div>

<div class="agent-name">

<?php
echo safe(
    $property["agent_name"]
);
?>

</div>


<div class="agent-contact">

<?php
echo safe(
    $property["agent_email"]
    ?? ""
);
?>

<br>

<?php
echo safe(
    $property["agent_phone"]
    ?? ""
);
?>

</div>


</div>


</div>


<?php else: ?>


<div style="
    color:#727c77;
    font-size:9px;
">

    No agent assigned.

</div>


<?php endif; ?>


</div>


</section>


<!-- QUICK ACTIONS -->

<section class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Quick Actions
</h2>

</div>


<div class="card-body">


<div class="quick-actions">


<a
    href="property-edit.php?id=<?php
    echo (int)$propertyId;
    ?>"
    class="quick-btn"
>

    ✏️

    Edit Property

</a>


<a
    href="../page/property-details.php?id=<?php
    echo (int)$propertyId;
    ?>"
    target="_blank"
    class="quick-btn"
>

    🌐

    View Public Property

</a>


<a
    href="enquiries.php?property_id=<?php
    echo (int)$propertyId;
    ?>"
    class="quick-btn"
>

    💬

    View Enquiries

</a>


<a
    href="visits.php?property_id=<?php
    echo (int)$propertyId;
    ?>"
    class="quick-btn"
>

    📅

    View Scheduled Visits

</a>


<a
    href="property-delete.php?id=<?php
    echo (int)$propertyId;
    ?>"
    class="quick-btn"
    style="
        color:#b43843;
    "
    id="quickDelete"
>

    🗑️

    Delete Property

</a>


</div>


</div>


</section>


<!-- ACTIVITY -->

<section class="card"
    style="margin-top:20px;"
>


<div class="card-header">

<h2>
    Activity
</h2>

</div>


<div class="card-body">


<div class="timeline">


<div class="timeline-item">


<div class="timeline-title">

Property Created

</div>


<div class="timeline-date">

<?php
echo safe($formattedDate);
?>

</div>


</div>


<div class="timeline-item">


<div class="timeline-title">

Property Status

</div>


<div class="timeline-date">

<?php

echo $status === "published"
    ? "Currently visible to public users"
    : "Currently saved as draft";

?>

</div>


</div>


<div class="timeline-item">


<div class="timeline-title">

Listing Type

</div>


<div class="timeline-date">

<?php

echo $listingType === "rent"
    ? "Available for rental"
    : "Available for purchase";

?>

</div>


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
   IMAGE GALLERY
========================================================= */

const mainImage =
    document.getElementById(
        "mainImage"
    );

const thumbnails =
    document.querySelectorAll(
        ".thumbnail"
    );


thumbnails.forEach(
    function(thumbnail)
    {

        thumbnail.addEventListener(
            "click",
            function()
            {

                const image =
                    this.dataset.image;


                if (image) {

                    mainImage.src =
                        image;

                }


                thumbnails.forEach(
                    function(item)
                    {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                this.classList.add(
                    "active"
                );

            }
        );

    }
);


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const deleteButtons = [

    document.getElementById(
        "deleteProperty"
    ),

    document.getElementById(
        "quickDelete"
    )

];


deleteButtons.forEach(
    function(button)
    {

        if (!button) {
            return;
        }


        button.addEventListener(
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

    }
);

</script>


</body>

</html>