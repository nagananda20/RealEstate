<?php

$propertyId = isset($_GET['id'])
    ? intval($_GET['id'])
    : 1;

/*
    Temporary property data.
    Later this will come from MySQL.
*/

$properties = [

    1 => [
        "title" => "Modern 2BHK Apartment",
        "location" => "Bengaluru, Karnataka",
        "price" => 5200000,
        "type" => "Apartment",
        "status" => "For Sale",
        "bedrooms" => 2,
        "bathrooms" => 2,
        "area" => 1250,
        "parking" => 1,
        "floor" => "8th Floor",
        "age" => "2 Years",
        "facing" => "East",
        "furnished" => "Semi Furnished",
        "description" =>
            "A beautiful modern 2BHK apartment located in a premium residential community. The property offers spacious bedrooms, a modern kitchen, excellent ventilation and convenient access to schools, hospitals, shopping centers and public transportation.",
        "images" => [
            "../assets/images/property-1.jpg",
            "../assets/images/property-2.jpg",
            "../assets/images/property-3.jpg"
        ]
    ],

    2 => [
        "title" => "Luxury Family Villa",
        "location" => "Mysuru, Karnataka",
        "price" => 12500000,
        "type" => "Villa",
        "status" => "For Sale",
        "bedrooms" => 4,
        "bathrooms" => 3,
        "area" => 2800,
        "parking" => 2,
        "floor" => "Ground + 1",
        "age" => "1 Year",
        "facing" => "North",
        "furnished" => "Fully Furnished",
        "description" =>
            "A spacious luxury villa designed for modern family living with premium interiors, private parking, landscaped surroundings and excellent connectivity.",
        "images" => [
            "../assets/images/property-2.jpg",
            "../assets/images/property-1.jpg",
            "../assets/images/property-3.jpg"
        ]
    ],

    3 => [
        "title" => "Premium Independent House",
        "location" => "Dharwad, Karnataka",
        "price" => 7800000,
        "type" => "Independent House",
        "status" => "For Sale",
        "bedrooms" => 3,
        "bathrooms" => 2,
        "area" => 1900,
        "parking" => 2,
        "floor" => "Ground + 1",
        "age" => "3 Years",
        "facing" => "East",
        "furnished" => "Semi Furnished",
        "description" =>
            "Premium independent house with spacious rooms, excellent natural lighting, parking space and a peaceful residential location.",
        "images" => [
            "../assets/images/property-3.jpg",
            "../assets/images/property-1.jpg",
            "../assets/images/property-2.jpg"
        ]
    ]

];

if (!isset($properties[$propertyId])) {
    $propertyId = 1;
}

$property = $properties[$propertyId];

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
        <?php echo htmlspecialchars($property["title"]); ?>
        | RealEstateHub
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css"
    >

    <style>

        /* ================================
           PROPERTY DETAILS PAGE
        ================================= */

        .details-page {

            padding: 45px 7% 80px;

        }


        /* ================================
           BREADCRUMB
        ================================= */

        .breadcrumb {

            display: flex;

            gap: 8px;

            font-size: 13px;

            color: #707b76;

            margin-bottom: 25px;

        }

        .breadcrumb a {

            color: #174a3a;

            font-weight: 600;

        }


        /* ================================
           TOP HEADER
        ================================= */

        .property-heading {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 30px;

            margin-bottom: 30px;

        }

        .property-heading h1 {

            font-size: 42px;

            line-height: 1.15;

            margin-bottom: 8px;

        }

        .property-heading p {

            color: #65706b;

        }

        .property-price-large {

            color: #174a3a;

            font-size: 34px;

            font-weight: 800;

            white-space: nowrap;

        }


        /* ================================
           ACTION BUTTONS
        ================================= */

        .property-actions {

            display: flex;

            gap: 10px;

            margin-top: 15px;

        }

        .action-button {

            border: 1px solid #dfe5e1;

            background: white;

            padding: 10px 15px;

            border-radius: 8px;

            cursor: pointer;

            font-weight: 600;

        }

        .action-button:hover {

            border-color: #174a3a;

            color: #174a3a;

        }

        .action-button.favorite-active {

            color: #e25555;

        }


        /* ================================
           IMAGE GALLERY
        ================================= */

        .property-gallery {

            display: grid;

            grid-template-columns: 2fr 1fr;

            gap: 10px;

            height: 520px;

            margin-bottom: 45px;

        }

        .main-image {

            position: relative;

            overflow: hidden;

            border-radius: 15px;

        }

        .main-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }

        .gallery-badge {

            position: absolute;

            left: 20px;

            top: 20px;

            background: white;

            color: #174a3a;

            padding: 8px 13px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 800;

        }

        .thumbnail-grid {

            display: grid;

            grid-template-rows: 1fr 1fr;

            gap: 10px;

        }

        .thumbnail {

            position: relative;

            overflow: hidden;

            border-radius: 12px;

            cursor: pointer;

        }

        .thumbnail img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            transition: .3s;

        }

        .thumbnail:hover img {

            transform: scale(1.05);

        }

        .more-images {

            position: absolute;

            inset: 0;

            background: rgba(0,0,0,.42);

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-weight: 700;

            font-size: 18px;

        }


        /* ================================
           CONTENT LAYOUT
        ================================= */

        .details-layout {

            display: grid;

            grid-template-columns: 1.7fr 1fr;

            gap: 40px;

        }


        /* ================================
           INFO BOX
        ================================= */

        .property-info-box {

            background: white;

            border: 1px solid #e4e9e6;

            border-radius: 15px;

            padding: 28px;

            margin-bottom: 25px;

        }

        .property-info-box h2 {

            font-size: 25px;

            margin-bottom: 20px;

        }


        /* ================================
           FEATURES
        ================================= */

        .feature-grid {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 15px;

        }

        .feature {

            padding: 16px;

            background: #f7f8f6;

            border-radius: 10px;

        }

        .feature-icon {

            font-size: 24px;

            margin-bottom: 7px;

        }

        .feature span {

            display: block;

            color: #7a8580;

            font-size: 12px;

        }

        .feature strong {

            font-size: 14px;

        }


        /* ================================
           DESCRIPTION
        ================================= */

        .description {

            color: #65706b;

            line-height: 1.8;

        }


        /* ================================
           AMENITIES
        ================================= */

        .amenities-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 15px;

        }

        .amenity {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #4f5b56;

        }

        .amenity span {

            width: 30px;

            height: 30px;

            border-radius: 50%;

            background: #e8f1ec;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #174a3a;

        }


        /* ================================
           AGENT CARD
        ================================= */

        .agent-card {

            background: white;

            border: 1px solid #e4e9e6;

            border-radius: 15px;

            padding: 25px;

            position: sticky;

            top: 100px;

        }

        .agent-header {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

        }

        .agent-avatar {

            width: 60px;

            height: 60px;

            border-radius: 50%;

            background: #dce9e2;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

        }

        .agent-header h3 {

            margin-bottom: 2px;

        }

        .agent-header p {

            color: #77827d;

            font-size: 13px;

        }


        .agent-rating {

            margin-bottom: 20px;

            color: #b68725;

        }


        /* ================================
           CONTACT FORM
        ================================= */

        .contact-form {

            border-top: 1px solid #e4e9e6;

            padding-top: 20px;

        }

        .form-group {

            margin-bottom: 14px;

        }

        .form-group label {

            display: block;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 6px;

        }

        .form-group input,

        .form-group textarea {

            width: 100%;

            border: 1px solid #dfe5e1;

            border-radius: 8px;

            padding: 12px;

            outline: none;

            resize: vertical;

        }

        .form-group textarea {

            min-height: 90px;

        }

        .form-group input:focus,

        .form-group textarea:focus {

            border-color: #174a3a;

        }

        .contact-button {

            width: 100%;

            border: none;

            padding: 13px;

            background: #174a3a;

            color: white;

            border-radius: 8px;

            font-weight: 700;

            cursor: pointer;

        }


        /* ================================
           EMI CALCULATOR
        ================================= */

        .emi-box {

            background: #174a3a;

            color: white;

            border-radius: 15px;

            padding: 28px;

            margin-top: 25px;

        }

        .emi-box h2 {

            margin-bottom: 20px;

        }

        .emi-input {

            margin-bottom: 15px;

        }

        .emi-input label {

            display: block;

            font-size: 12px;

            margin-bottom: 5px;

            color: rgba(255,255,255,.75);

        }

        .emi-input input {

            width: 100%;

            padding: 11px;

            border: none;

            border-radius: 7px;

            outline: none;

        }

        .emi-result {

            margin-top: 20px;

            padding: 20px;

            border-radius: 10px;

            background: rgba(255,255,255,.1);

        }

        .emi-result small {

            color: rgba(255,255,255,.7);

        }

        .emi-result strong {

            display: block;

            font-size: 28px;

            margin-top: 4px;

        }

        .calculate-emi {

            width: 100%;

            border: none;

            padding: 12px;

            border-radius: 8px;

            background: #d8b45b;

            color: #17231f;

            font-weight: 800;

            cursor: pointer;

            margin-top: 8px;

        }


        /* ================================
           LOCATION
        ================================= */

        .map-placeholder {

            height: 300px;

            border-radius: 12px;

            background:

                linear-gradient(
                    135deg,
                    #dce8e1,
                    #edf2ef
                );

            display: flex;

            align-items: center;

            justify-content: center;

            flex-direction: column;

            gap: 10px;

            color: #174a3a;

            font-weight: 700;

        }

        .map-placeholder span {

            font-size: 45px;

        }


        /* ================================
           RESPONSIVE
        ================================= */

        @media(max-width: 900px) {

            .property-heading {

                display: block;

            }

            .property-price-large {

                margin-top: 15px;

            }

            .property-gallery {

                height: 420px;

            }

            .details-layout {

                grid-template-columns: 1fr;

            }

            .agent-card {

                position: static;

            }

        }


        @media(max-width: 600px) {

            .details-page {

                padding: 30px 5% 60px;

            }

            .property-heading h1 {

                font-size: 30px;

            }

            .property-gallery {

                display: block;

                height: auto;

            }

            .main-image {

                height: 300px;

            }

            .thumbnail-grid {

                display: grid;

                grid-template-columns: 1fr 1fr;

                grid-template-rows: 160px;

                margin-top: 10px;

            }

            .feature-grid {

                grid-template-columns: 1fr 1fr;

            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     NAVBAR
=========================================== -->

<header class="navbar">

    <div class="logo">

        <span>Real</span>EstateHub

    </div>


    <nav>

        <a href="../index.php">
            Home
        </a>

        <a href="properties.php" class="active">
            Properties
        </a>

        <a href="buy.php">
            Buy
        </a>

        <a href="rent.php">
            Rent
        </a>

        <a href="agents.php">
            Agents
        </a>

        <a href="about.php">
            About
        </a>

    </nav>


    <div class="nav-actions">

        <a
            href="../auth/login.php"
            class="login-btn"
        >
            Login
        </a>

        <a
            href="sell.php"
            class="add-property-btn"
        >
            + Add Property
        </a>

    </div>

</header>


<!-- ==========================================
     MAIN
=========================================== -->

<main class="details-page">


    <!-- BREADCRUMB -->

    <div class="breadcrumb">

        <a href="../index.php">
            Home
        </a>

        <span>›</span>

        <a href="properties.php">
            Properties
        </a>

        <span>›</span>

        <span>
            <?php echo htmlspecialchars($property["title"]); ?>
        </span>

    </div>


    <!-- PROPERTY HEADER -->

    <section class="property-heading">

        <div>

            <h1>

                <?php
                echo htmlspecialchars(
                    $property["title"]
                );
                ?>

            </h1>

            <p>

                📍

                <?php
                echo htmlspecialchars(
                    $property["location"]
                );
                ?>

            </p>


            <div class="property-actions">

                <button
                    class="action-button"
                    id="favoriteButton"
                >
                    ♡ Save
                </button>

                <button
                    class="action-button"
                    id="shareButton"
                >
                    ↗ Share
                </button>

                <button
                    class="action-button"
                    id="printButton"
                >
                    🖨 Print
                </button>

            </div>

        </div>


        <div>

            <div class="property-price-large">

                ₹<?php
                echo number_format(
                    $property["price"]
                );
                ?>

            </div>

            <small>
                <?php
                echo htmlspecialchars(
                    $property["status"]
                );
                ?>
            </small>

        </div>

    </section>


    <!-- ==========================================
         IMAGE GALLERY
    =========================================== -->

    <section class="property-gallery">


        <div class="main-image">

            <img
                id="mainPropertyImage"
                src="<?php
                    echo $property["images"][0];
                ?>"
                alt="Property"
            >

            <span class="gallery-badge">
                ✓ Verified Property
            </span>

        </div>


        <div class="thumbnail-grid">

            <?php

            foreach (
                $property["images"]
                as $index => $image
            ):

            ?>

                <div
                    class="thumbnail"
                    data-image="<?php
                        echo htmlspecialchars($image);
                    ?>"
                >

                    <img
                        src="<?php
                            echo htmlspecialchars($image);
                        ?>"
                        alt="Property Image"
                    >

                    <?php if ($index === 2): ?>

                        <div class="more-images">
                            + More Photos
                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- ==========================================
         CONTENT
    =========================================== -->

    <div class="details-layout">


        <!-- LEFT CONTENT -->

        <div>


            <!-- PROPERTY OVERVIEW -->

            <section class="property-info-box">

                <h2>
                    Property Overview
                </h2>


                <div class="feature-grid">


                    <div class="feature">

                        <div class="feature-icon">
                            🛏
                        </div>

                        <span>
                            Bedrooms
                        </span>

                        <strong>
                            <?php
                            echo $property["bedrooms"];
                            ?>
                        </strong>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            🚿
                        </div>

                        <span>
                            Bathrooms
                        </span>

                        <strong>
                            <?php
                            echo $property["bathrooms"];
                            ?>
                        </strong>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            📐
                        </div>

                        <span>
                            Property Area
                        </span>

                        <strong>
                            <?php
                            echo number_format(
                                $property["area"]
                            );
                            ?>
                            sqft
                        </strong>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            🚗
                        </div>

                        <span>
                            Parking
                        </span>

                        <strong>
                            <?php
                            echo $property["parking"];
                            ?>
                            Car
                        </strong>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            🏢
                        </div>

                        <span>
                            Floor
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $property["floor"]
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            🧭
                        </div>

                        <span>
                            Facing
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $property["facing"]
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            </section>


            <!-- DESCRIPTION -->

            <section class="property-info-box">

                <h2>
                    About This Property
                </h2>

                <p class="description">

                    <?php
                    echo htmlspecialchars(
                        $property["description"]
                    );
                    ?>

                </p>

            </section>


            <!-- AMENITIES -->

            <section class="property-info-box">

                <h2>
                    Amenities
                </h2>


                <div class="amenities-grid">

                    <div class="amenity">
                        <span>✓</span>
                        Swimming Pool
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        Gym & Fitness Center
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        24/7 Security
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        Power Backup
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        Children's Play Area
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        Club House
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        CCTV Surveillance
                    </div>

                    <div class="amenity">
                        <span>✓</span>
                        Visitor Parking
                    </div>

                </div>

            </section>


            <!-- LOCATION -->

            <section class="property-info-box">

                <h2>
                    Property Location
                </h2>

                <div class="map-placeholder">

                    <span>📍</span>

                    <div>
                        <?php
                        echo htmlspecialchars(
                            $property["location"]
                        );
                        ?>
                    </div>

                    <small>
                        Interactive map will be
                        connected later.
                    </small>

                </div>

            </section>


            <!-- EMI CALCULATOR -->

            <section class="emi-box">

                <h2>
                    Home Loan EMI Calculator
                </h2>


                <div class="emi-input">

                    <label>
                        Property Price
                    </label>

                    <input
                        type="number"
                        id="propertyPrice"
                        value="<?php
                            echo $property["price"];
                        ?>"
                    >

                </div>


                <div class="emi-input">

                    <label>
                        Down Payment
                    </label>

                    <input
                        type="number"
                        id="downPayment"
                        value="<?php
                            echo round(
                                $property["price"] * .20
                            );
                        ?>"
                    >

                </div>


                <div class="emi-input">

                    <label>
                        Interest Rate (%)
                    </label>

                    <input
                        type="number"
                        id="interestRate"
                        value="8.5"
                        step="0.1"
                    >

                </div>


                <div class="emi-input">

                    <label>
                        Loan Period (Years)
                    </label>

                    <input
                        type="number"
                        id="loanYears"
                        value="20"
                    >

                </div>


                <button
                    class="calculate-emi"
                    id="calculateEMI"
                >

                    Calculate EMI

                </button>


                <div class="emi-result">

                    <small>
                        Estimated Monthly EMI
                    </small>

                    <strong id="emiResult">
                        ₹0
                    </strong>

                </div>

            </section>

        </div>


        <!-- RIGHT SIDEBAR -->

        <aside>


            <!-- AGENT -->

            <div class="agent-card">

                <div class="agent-header">

                    <div class="agent-avatar">
                        👨‍💼
                    </div>

                    <div>

                        <h3>
                            Rahul Sharma
                        </h3>

                        <p>
                            Certified Property Agent
                        </p>

                    </div>

                </div>


                <div class="agent-rating">

                    ⭐⭐⭐⭐⭐
                    <strong>
                        4.9
                    </strong>

                    <small>
                        (128 reviews)
                    </small>

                </div>


                <div class="contact-form">

                    <div class="form-group">

                        <label>
                            Your Name
                        </label>

                        <input
                            type="text"
                            id="visitorName"
                            placeholder="Enter your name"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="visitorPhone"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Message
                        </label>

                        <textarea
                            id="visitorMessage"
                        >I am interested in this property.</textarea>

                    </div>


                    <button
                        class="contact-button"
                        id="contactAgent"
                    >

                        Contact Agent

                    </button>

                </div>

            </div>


            <!-- VISIT -->

            <div
                class="property-info-box"
                style="margin-top:25px;"
            >

                <h2>
                    Schedule a Visit
                </h2>

                <div class="form-group">

                    <label>
                        Select Date
                    </label>

                    <input
                        type="date"
                        id="visitDate"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Select Time
                    </label>

                    <select id="visitTime">

                        <option>
                            10:00 AM
                        </option>

                        <option>
                            12:00 PM
                        </option>

                        <option>
                            2:00 PM
                        </option>

                        <option>
                            4:00 PM
                        </option>

                        <option>
                            6:00 PM
                        </option>

                    </select>

                </div>


                <button
                    class="contact-button"
                    id="scheduleVisit"
                >

                    📅 Schedule Visit

                </button>

            </div>

        </aside>

    </div>

</main>


<!-- ==========================================
     FOOTER
=========================================== -->

<footer>

    <div class="footer-content">

        <div>

            <div class="logo">
                <span>Real</span>EstateHub
            </div>

            <p>
                Your trusted platform for buying,
                renting and selling property.
            </p>

        </div>


        <div>

            <h4>
                Explore
            </h4>

            <a href="properties.php">
                Properties
            </a>

            <a href="buy.php">
                Buy
            </a>

            <a href="rent.php">
                Rent
            </a>

        </div>


        <div>

            <h4>
                Company
            </h4>

            <a href="about.php">
                About
            </a>

            <a href="agents.php">
                Agents
            </a>

            <a href="contact.php">
                Contact
            </a>

        </div>

    </div>


    <div class="footer-bottom">

        © 2026 RealEstateHub.
        All rights reserved.

    </div>

</footer>


<script src="../assets/js/main.js"></script>

<script src="../assets/js/property-details.js"></script>


</body>

</html>