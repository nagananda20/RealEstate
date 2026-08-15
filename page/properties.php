<?php
$pageTitle = "Properties | RealEstateHub";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle; ?></title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">

    <style>

        /* ================= PROPERTY PAGE ================= */

        .property-page {
            padding: 50px 7%;
        }

        .property-page-header {
            margin-bottom: 30px;
        }

        .property-page-header h1 {
            font-size: 42px;
            margin-bottom: 8px;
        }

        .property-page-header p {
            color: #5f6c67;
        }


        /* ================= FILTER BAR ================= */

        .property-toolbar {
            background: white;
            border: 1px solid #e4e9e6;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 35px;
        }

        .filter-row {
            display: grid;
            grid-template-columns:
                1.5fr
                1fr
                1fr
                1fr
                auto;

            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 11px;
            font-weight: 700;
            color: #78827e;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            height: 45px;
            padding: 0 12px;
            border: 1px solid #dfe5e1;
            border-radius: 8px;
            outline: none;
            background: white;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #174a3a;
        }

        .filter-button {
            height: 45px;
            align-self: end;
            padding: 0 22px;
            border: none;
            border-radius: 8px;
            background: #174a3a;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }


        /* ================= SECOND TOOLBAR ================= */

        .results-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .results-count {
            color: #5f6c67;
            font-size: 14px;
        }

        .sort-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sort-box select {
            border: 1px solid #dfe5e1;
            padding: 10px 15px;
            border-radius: 8px;
            background: white;
            outline: none;
        }


        /* ================= PROPERTY GRID ================= */

        .listing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }


        /* ================= PROPERTY CARD ================= */

        .listing-card {
            background: white;
            border: 1px solid #e4e9e6;
            border-radius: 15px;
            overflow: hidden;
            transition: .35s;
        }

        .listing-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 20px 45px rgba(0,0,0,.10);
        }

        .listing-image {
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .listing-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .5s;
        }

        .listing-card:hover .listing-image img {
            transform: scale(1.06);
        }

        .listing-status {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 10px;
            border-radius: 6px;
            background: white;
            color: #174a3a;
            font-size: 11px;
            font-weight: 800;
        }

        .listing-favorite {
            position: absolute;
            right: 15px;
            top: 15px;

            width: 38px;
            height: 38px;

            border: none;
            border-radius: 50%;

            background: white;

            font-size: 20px;

            cursor: pointer;
        }

        .listing-favorite.active {
            color: #e25555;
        }

        .listing-body {
            padding: 20px;
        }

        .listing-price {
            color: #174a3a;
            font-size: 22px;
            font-weight: 800;
        }

        .listing-title {
            font-size: 18px;
            margin: 5px 0;
        }

        .listing-location {
            color: #68736e;
            font-size: 13px;
        }

        .listing-details {
            display: flex;
            gap: 15px;

            border-top: 1px solid #e4e9e6;

            margin-top: 17px;
            padding-top: 15px;

            color: #68736e;
            font-size: 12px;
        }

        .details-button {
            display: block;
            text-align: center;

            margin-top: 18px;
            padding: 11px;

            background: #174a3a;
            color: white;

            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;
        }


        /* ================= PAGINATION ================= */

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;

            margin-top: 50px;
        }

        .pagination button {
            width: 40px;
            height: 40px;

            border: 1px solid #dfe5e1;
            background: white;

            border-radius: 8px;

            cursor: pointer;
        }

        .pagination button.active {
            background: #174a3a;
            color: white;
            border-color: #174a3a;
        }


        /* ================= MOBILE ================= */

        @media(max-width: 900px) {

            .filter-row {
                grid-template-columns: 1fr 1fr;
            }

            .listing-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media(max-width: 600px) {

            .property-page {
                padding: 35px 5%;
            }

            .property-page-header h1 {
                font-size: 32px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .filter-button {
                width: 100%;
            }

            .results-toolbar {
                display: block;
            }

            .sort-box {
                margin-top: 15px;
            }

            .listing-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- ================= NAVBAR ================= -->

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

        <a href="../auth/login.php"
           class="login-btn">
            Login
        </a>

        <a href="sell.php"
           class="add-property-btn">
            + Add Property
        </a>

    </div>

</header>


<!-- ================= MAIN ================= -->

<main class="property-page">


    <!-- PAGE HEADER -->

    <div class="property-page-header">

        <span class="section-tag">
            PROPERTY MARKETPLACE
        </span>

        <h1>
            Find Your Perfect Property
        </h1>

        <p>
            Explore verified homes, apartments,
            villas, land and commercial properties.
        </p>

    </div>


    <!-- ================= FILTER ================= -->

    <div class="property-toolbar">

        <div class="filter-row">


            <div class="filter-group">

                <label>
                    Location
                </label>

                <input
                    type="text"
                    id="locationFilter"
                    placeholder="City or neighborhood"
                >

            </div>


            <div class="filter-group">

                <label>
                    Property Type
                </label>

                <select id="typeFilter">

                    <option value="all">
                        All Types
                    </option>

                    <option value="apartment">
                        Apartment
                    </option>

                    <option value="villa">
                        Villa
                    </option>

                    <option value="house">
                        House
                    </option>

                    <option value="land">
                        Land
                    </option>

                    <option value="office">
                        Office
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    Bedrooms
                </label>

                <select id="bedroomFilter">

                    <option value="all">
                        Any
                    </option>

                    <option value="1">
                        1 Bedroom
                    </option>

                    <option value="2">
                        2 Bedrooms
                    </option>

                    <option value="3">
                        3 Bedrooms
                    </option>

                    <option value="4">
                        4+ Bedrooms
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    Price
                </label>

                <select id="priceFilter">

                    <option value="all">
                        Any Price
                    </option>

                    <option value="25">
                        Under ₹25L
                    </option>

                    <option value="50">
                        ₹25L - ₹50L
                    </option>

                    <option value="100">
                        ₹50L - ₹1Cr
                    </option>

                    <option value="above">
                        Above ₹1Cr
                    </option>

                </select>

            </div>


            <button
                class="filter-button"
                id="filterButton">

                🔍 Search

            </button>

        </div>

    </div>


    <!-- ================= RESULTS ================= -->

    <div class="results-toolbar">

        <div class="results-count">

            <strong id="resultCount">
                6
            </strong>

            properties found

        </div>


        <div class="sort-box">

            <label>
                Sort:
            </label>

            <select id="sortProperty">

                <option value="featured">
                    Featured
                </option>

                <option value="low">
                    Price: Low to High
                </option>

                <option value="high">
                    Price: High to Low
                </option>

                <option value="newest">
                    Newest
                </option>

            </select>

        </div>

    </div>


    <!-- ================= PROPERTY LIST ================= -->

    <div class="listing-grid"
         id="propertyGrid">


        <!-- PROPERTY 1 -->

        <article
            class="listing-card"
            data-type="apartment"
            data-bedrooms="2"
            data-price="52"
            data-location="bengaluru">

            <div class="listing-image">

                <img
                    src="../assets/images/property-1.jpg"
                    alt="Modern Apartment">

                <span class="listing-status">
                    VERIFIED
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹52,00,000
                </div>

                <h3 class="listing-title">
                    Modern 2BHK Apartment
                </h3>

                <p class="listing-location">
                    📍 Bengaluru, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        🛏 2 Beds
                    </span>

                    <span>
                        🚿 2 Baths
                    </span>

                    <span>
                        📐 1,250 sqft
                    </span>

                </div>

                <a
                    href="property-details.php?id=1"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


        <!-- PROPERTY 2 -->

        <article
            class="listing-card"
            data-type="villa"
            data-bedrooms="4"
            data-price="125"
            data-location="mysuru">

            <div class="listing-image">

                <img
                    src="../assets/images/property-2.jpg"
                    alt="Luxury Villa">

                <span class="listing-status">
                    FEATURED
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹1.25 Cr
                </div>

                <h3 class="listing-title">
                    Luxury Family Villa
                </h3>

                <p class="listing-location">
                    📍 Mysuru, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        🛏 4 Beds
                    </span>

                    <span>
                        🚿 3 Baths
                    </span>

                    <span>
                        📐 2,800 sqft
                    </span>

                </div>

                <a
                    href="property-details.php?id=2"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


        <!-- PROPERTY 3 -->

        <article
            class="listing-card"
            data-type="house"
            data-bedrooms="3"
            data-price="78"
            data-location="dharwad">

            <div class="listing-image">

                <img
                    src="../assets/images/property-3.jpg"
                    alt="Independent House">

                <span class="listing-status">
                    NEW
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹78,00,000
                </div>

                <h3 class="listing-title">
                    Premium Independent House
                </h3>

                <p class="listing-location">
                    📍 Dharwad, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        🛏 3 Beds
                    </span>

                    <span>
                        🚿 2 Baths
                    </span>

                    <span>
                        📐 1,900 sqft
                    </span>

                </div>

                <a
                    href="property-details.php?id=3"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


        <!-- PROPERTY 4 -->

        <article
            class="listing-card"
            data-type="apartment"
            data-bedrooms="3"
            data-price="65"
            data-location="hubballi">

            <div class="listing-image">

                <img
                    src="../assets/images/property-4.jpg"
                    alt="Premium Apartment">

                <span class="listing-status">
                    VERIFIED
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹65,00,000
                </div>

                <h3 class="listing-title">
                    Premium City Apartment
                </h3>

                <p class="listing-location">
                    📍 Hubballi, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        🛏 3 Beds
                    </span>

                    <span>
                        🚿 2 Baths
                    </span>

                    <span>
                        📐 1,550 sqft
                    </span>

                </div>

                <a
                    href="property-details.php?id=4"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


        <!-- PROPERTY 5 -->

        <article
            class="listing-card"
            data-type="land"
            data-bedrooms="0"
            data-price="32"
            data-location="dharwad">

            <div class="listing-image">

                <img
                    src="../assets/images/property-5.jpg"
                    alt="Residential Land">

                <span class="listing-status">
                    VERIFIED
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹32,00,000
                </div>

                <h3 class="listing-title">
                    Residential Plot
                </h3>

                <p class="listing-location">
                    📍 Dharwad, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        📐 2,400 sqft
                    </span>

                    <span>
                        🛣 Road Facing
                    </span>

                </div>

                <a
                    href="property-details.php?id=5"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


        <!-- PROPERTY 6 -->

        <article
            class="listing-card"
            data-type="office"
            data-bedrooms="0"
            data-price="95"
            data-location="bengaluru">

            <div class="listing-image">

                <img
                    src="../assets/images/property-6.jpg"
                    alt="Commercial Office">

                <span class="listing-status">
                    COMMERCIAL
                </span>

                <button
                    class="listing-favorite">

                    ♡

                </button>

            </div>


            <div class="listing-body">

                <div class="listing-price">
                    ₹95,00,000
                </div>

                <h3 class="listing-title">
                    Premium Commercial Office
                </h3>

                <p class="listing-location">
                    📍 Bengaluru, Karnataka
                </p>

                <div class="listing-details">

                    <span>
                        📐 2,100 sqft
                    </span>

                    <span>
                        🚗 Parking
                    </span>

                </div>

                <a
                    href="property-details.php?id=6"
                    class="details-button">

                    View Property

                </a>

            </div>

        </article>


    </div>


    <!-- ================= PAGINATION ================= -->

    <div class="pagination">

        <button>
            ‹
        </button>

        <button class="active">
            1
        </button>

        <button>
            2
        </button>

        <button>
            3
        </button>

        <button>
            4
        </button>

        <button>
            ›
        </button>

    </div>


</main>


<!-- ================= FOOTER ================= -->

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
<script src="../assets/js/properties.js"></script>


</body>

</html>