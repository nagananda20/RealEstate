<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Get Agent ID
|--------------------------------------------------------------------------
*/

$agent_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($agent_id <= 0) {
    header('Location: agents.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch Agent
|--------------------------------------------------------------------------
*/

$agent = null;

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        photo,
        specialization,
        experience,
        status,
        bio
    FROM agents
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param('i', $agent_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $agent = $result->fetch_assoc();

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Agent Not Found
|--------------------------------------------------------------------------
*/

if (!$agent) {
    header('Location: agents.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Agent Photo
|--------------------------------------------------------------------------
*/

$photo = '../assets/images/agents/default-agent.jpg';

if (!empty($agent['photo'])) {

    $photoFile =
        __DIR__ .
        '/../assets/images/agents/' .
        $agent['photo'];

    if (file_exists($photoFile)) {

        $photo =
            '../assets/images/agents/' .
            $agent['photo'];
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Agent Properties
|--------------------------------------------------------------------------
*/

$properties = [];

$propertySql = "
    SELECT
        id,
        title,
        location,
        price,
        property_type,
        bedrooms,
        bathrooms,
        area,
        image,
        status
    FROM properties
    WHERE agent_id = ?
    ORDER BY id DESC
";

$propertyStmt = $conn->prepare($propertySql);

if ($propertyStmt) {

    $propertyStmt->bind_param('i', $agent_id);

    $propertyStmt->execute();

    $propertyResult = $propertyStmt->get_result();

    while ($row = $propertyResult->fetch_assoc()) {
        $properties[] = $row;
    }

    $propertyStmt->close();
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
        <?php echo htmlspecialchars($agent['name']); ?>
        | RealEstate
    </title>

    <meta
        name="description"
        content="View real estate agent profile and properties."
    >

    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <!-- Properties CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/properties.css"
    >

    <!-- Responsive CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css"
    >

</head>


<body>


<!-- =====================================================
     HEADER
====================================================== -->

<header class="site-header">

    <div class="container">

        <nav class="navbar">

            <!-- Logo -->

            <a
                href="../index.php"
                class="logo"
            >

                <span class="logo-icon">
                    🏠
                </span>

                <span>
                    RealEstate
                </span>

            </a>


            <!-- Navigation -->

            <div class="nav-links">

                <a href="../index.php">
                    Home
                </a>

                <a href="properties.php">
                    Properties
                </a>

                <a
                    href="agents.php"
                    class="active"
                >
                    Agents
                </a>

                <a href="about.php">
                    About
                </a>

                <a href="contact.php">
                    Contact
                </a>

            </div>


            <!-- Authentication -->

            <div class="nav-auth">

                <?php if (
                    isset($_SESSION['logged_in']) &&
                    $_SESSION['logged_in']
                ): ?>

                    <a
                        href="../auth/logout.php"
                        class="btn btn-outline"
                    >
                        Logout
                    </a>

                <?php else: ?>

                    <a
                        href="../auth/login.php"
                        class="btn btn-outline"
                    >
                        Login
                    </a>

                    <a
                        href="../auth/register.php"
                        class="btn btn-primary"
                    >
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </nav>

    </div>

</header>


<!-- =====================================================
     BREADCRUMB
====================================================== -->

<section
    style="
        padding:20px 0;
        background:#ffffff;
        border-bottom:1px solid #e2e8e5;
    "
>

    <div class="container">

        <div
            style="
                display:flex;
                gap:8px;
                align-items:center;
                font-size:10px;
                color:#7c8781;
            "
        >

            <a href="../index.php">
                Home
            </a>

            <span>›</span>

            <a href="agents.php">
                Agents
            </a>

            <span>›</span>

            <strong
                style="color:#174a3a;"
            >
                <?php
                echo htmlspecialchars(
                    $agent['name']
                );
                ?>
            </strong>

        </div>

    </div>

</section>


<!-- =====================================================
     AGENT DETAILS
====================================================== -->

<section class="section">

    <div class="container">

        <div
            class="card"
            style="
                overflow:hidden;
                margin-bottom:30px;
            "
        >

            <div
                style="
                    display:grid;
                    grid-template-columns:300px 1fr;
                    gap:0;
                "
            >

                <!-- Agent Image -->

                <div
                    style="
                        min-height:330px;
                        background:#e9eeeb;
                    "
                >

                    <img
                        src="<?php echo htmlspecialchars($photo); ?>"
                        alt="<?php echo htmlspecialchars($agent['name']); ?>"
                        style="
                            width:100%;
                            height:100%;
                            min-height:330px;
                            object-fit:cover;
                        "
                    >

                </div>


                <!-- Agent Information -->

                <div
                    style="
                        padding:35px;
                    "
                >

                    <span
                        class="badge badge-primary"
                    >
                        Professional Agent
                    </span>


                    <h1
                        style="
                            margin-top:12px;
                            margin-bottom:6px;
                            font-size:30px;
                            color:#26352e;
                        "
                    >

                        <?php
                        echo htmlspecialchars(
                            $agent['name']
                        );
                        ?>

                    </h1>


                    <?php if (
                        !empty($agent['specialization'])
                    ): ?>

                        <p
                            style="
                                margin-bottom:15px;
                                color:#174a3a;
                                font-size:12px;
                                font-weight:700;
                            "
                        >

                            <?php
                            echo htmlspecialchars(
                                $agent['specialization']
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <!-- Agent Stats -->

                    <div
                        style="
                            display:flex;
                            flex-wrap:wrap;
                            gap:25px;
                            margin:20px 0;
                        "
                    >

                        <div>

                            <strong
                                style="
                                    display:block;
                                    color:#174a3a;
                                    font-size:20px;
                                "
                            >
                                <?php
                                echo !empty($agent['experience'])
                                    ? htmlspecialchars(
                                        $agent['experience']
                                    )
                                    : '0';
                                ?>
                            </strong>

                            <span
                                style="
                                    color:#89938e;
                                    font-size:9px;
                                "
                            >
                                Years Experience
                            </span>

                        </div>


                        <div>

                            <strong
                                style="
                                    display:block;
                                    color:#174a3a;
                                    font-size:20px;
                                "
                            >
                                <?php
                                echo count($properties);
                                ?>
                            </strong>

                            <span
                                style="
                                    color:#89938e;
                                    font-size:9px;
                                "
                            >
                                Properties
                            </span>

                        </div>

                    </div>


                    <!-- Contact Information -->

                    <div
                        style="
                            display:flex;
                            flex-direction:column;
                            gap:9px;
                            margin-bottom:20px;
                        "
                    >

                        <?php if (!empty($agent['phone'])): ?>

                            <a
                                href="tel:<?php echo htmlspecialchars($agent['phone']); ?>"
                                style="
                                    color:#59665f;
                                    font-size:10px;
                                "
                            >
                                📞
                                <?php
                                echo htmlspecialchars(
                                    $agent['phone']
                                );
                                ?>
                            </a>

                        <?php endif; ?>


                        <?php if (!empty($agent['email'])): ?>

                            <a
                                href="mailto:<?php echo htmlspecialchars($agent['email']); ?>"
                                style="
                                    color:#59665f;
                                    font-size:10px;
                                "
                            >
                                ✉
                                <?php
                                echo htmlspecialchars(
                                    $agent['email']
                                );
                                ?>
                            </a>

                        <?php endif; ?>

                    </div>


                    <!-- Actions -->

                    <div
                        style="
                            display:flex;
                            flex-wrap:wrap;
                            gap:10px;
                        "
                    >

                        <?php if (!empty($agent['phone'])): ?>

                            <a
                                href="tel:<?php echo htmlspecialchars($agent['phone']); ?>"
                                class="btn btn-primary"
                            >
                                📞 Call Agent
                            </a>

                        <?php endif; ?>


                        <?php if (!empty($agent['email'])): ?>

                            <a
                                href="mailto:<?php echo htmlspecialchars($agent['email']); ?>"
                                class="btn btn-outline"
                            >
                                ✉ Email Agent
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             ABOUT AGENT
        ================================================== -->

        <div
            class="card"
            style="
                margin-bottom:30px;
            "
        >

            <div class="card-body">

                <h2
                    style="
                        margin-bottom:12px;
                        font-size:20px;
                        color:#26352e;
                    "
                >
                    About <?php echo htmlspecialchars($agent['name']); ?>
                </h2>


                <?php if (!empty($agent['bio'])): ?>

                    <p
                        style="
                            color:#69756f;
                            font-size:11px;
                            line-height:1.8;
                        "
                    >

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $agent['bio']
                            )
                        );
                        ?>

                    </p>

                <?php else: ?>

                    <p
                        style="
                            color:#69756f;
                            font-size:11px;
                            line-height:1.8;
                        "
                    >
                        Our professional real estate agent is
                        available to help you find the right
                        property and guide you throughout the
                        buying or renting process.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             AGENT PROPERTIES
        ================================================== -->

        <div>

            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    margin-bottom:18px;
                "
            >

                <div>

                    <h2
                        style="
                            font-size:22px;
                            color:#26352e;
                        "
                    >
                        Properties by Agent
                    </h2>

                    <p
                        style="
                            color:#89938e;
                            font-size:10px;
                        "
                    >
                        Properties managed by
                        <?php
                        echo htmlspecialchars(
                            $agent['name']
                        );
                        ?>
                    </p>

                </div>

            </div>


            <?php if (!empty($properties)): ?>

                <div class="property-grid">

                    <?php foreach ($properties as $property): ?>

                        <?php

                        $propertyImage =
                            '../assets/images/properties/default-property.jpg';

                        if (!empty($property['image'])) {

                            $propertyFile =
                                __DIR__ .
                                '/../assets/images/properties/' .
                                $property['image'];

                            if (file_exists($propertyFile)) {

                                $propertyImage =
                                    '../assets/images/properties/' .
                                    $property['image'];
                            }
                        }

                        ?>

                        <article class="property-item">

                            <!-- Image -->

                            <div class="property-item-image">

                                <img
                                    src="<?php echo htmlspecialchars($propertyImage); ?>"
                                    alt="<?php echo htmlspecialchars($property['title']); ?>"
                                >

                                <?php if (
                                    !empty($property['property_type'])
                                ): ?>

                                    <span class="property-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $property['property_type']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- Body -->

                            <div class="property-item-body">

                                <h3 class="property-item-title">

                                    <?php
                                    echo htmlspecialchars(
                                        $property['title']
                                    );
                                    ?>

                                </h3>


                                <p class="property-item-location">

                                    📍

                                    <?php
                                    echo htmlspecialchars(
                                        $property['location']
                                    );
                                    ?>

                                </p>


                                <div class="property-item-price">

                                    ₹<?php
                                    echo number_format(
                                        (float)$property['price']
                                    );
                                    ?>

                                </div>


                                <div class="property-features">

                                    <?php if (
                                        isset($property['bedrooms'])
                                    ): ?>

                                        <span class="property-feature">
                                            🛏
                                            <strong>
                                                <?php
                                                echo (int)
                                                    $property['bedrooms'];
                                                ?>
                                            </strong>
                                            Beds
                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        isset($property['bathrooms'])
                                    ): ?>

                                        <span class="property-feature">
                                            🛁
                                            <strong>
                                                <?php
                                                echo (int)
                                                    $property['bathrooms'];
                                                ?>
                                            </strong>
                                            Baths
                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty($property['area'])
                                    ): ?>

                                        <span class="property-feature">
                                            📐
                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $property['area']
                                                );
                                                ?>
                                            </strong>
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="property-item-footer"
                                >

                                    <a
                                        href="property-details.php?id=<?php echo (int)$property['id']; ?>"
                                        class="property-view-btn"
                                    >
                                        View Property
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>

                <div class="properties-empty">

                    <div class="properties-empty-icon">
                        🏠
                    </div>

                    <h3>
                        No Properties Found
                    </h3>

                    <p>
                        This agent currently has no listed properties.
                    </p>

                    <a
                        href="properties.php"
                        class="btn btn-primary"
                    >
                        Browse Properties
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     FOOTER
====================================================== -->

<footer class="site-footer">

    <div class="container">

        <div class="footer-grid">

            <div>

                <div class="logo">

                    <span class="logo-icon">
                        🏠
                    </span>

                    RealEstate

                </div>

                <p class="footer-text">

                    Find your perfect property with
                    trusted real estate professionals.

                </p>

            </div>


            <div>

                <h3 class="footer-title">
                    Quick Links
                </h3>

                <div class="footer-links">

                    <a href="../index.php">
                        Home
                    </a>

                    <a href="properties.php">
                        Properties
                    </a>

                    <a href="agents.php">
                        Agents
                    </a>

                </div>

            </div>


            <div>

                <h3 class="footer-title">
                    Company
                </h3>

                <div class="footer-links">

                    <a href="about.php">
                        About
                    </a>

                    <a href="contact.php">
                        Contact
                    </a>

                </div>

            </div>


            <div>

                <h3 class="footer-title">
                    Account
                </h3>

                <div class="footer-links">

                    <a href="../auth/login.php">
                        Login
                    </a>

                    <a href="../auth/register.php">
                        Register
                    </a>

                </div>

            </div>

        </div>


        <div class="footer-bottom">

            © <?php echo date('Y'); ?>
            RealEstate. All Rights Reserved.

        </div>

    </div>

</footer>


<!-- JavaScript -->

<script src="../assets/js/main.js"></script>

</body>

</html>