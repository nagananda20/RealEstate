<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>About Us | RealEstate</title>

    <meta
        name="description"
        content="Learn more about RealEstate, our mission, values and professional real estate services."
    >

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
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

                <a href="agents.php">
                    Agents
                </a>

                <a
                    href="about.php"
                    class="active"
                >
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
                    $_SESSION['logged_in'] === true
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
     HERO
====================================================== -->

<section class="properties-hero">

    <div class="container">

        <div class="properties-hero-content">

            <span
                style="
                    display:inline-block;
                    margin-bottom:10px;
                    font-size:10px;
                    font-weight:700;
                    letter-spacing:1px;
                    text-transform:uppercase;
                    color:#b8d8c8;
                "
            >
                About RealEstate
            </span>

            <h1>
                Helping You Find<br>
                Your Perfect Place
            </h1>

            <p>
                We make buying, selling and renting property
                simple, transparent and stress-free.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     ABOUT INTRODUCTION
====================================================== -->

<section class="section">

    <div class="container">

        <div
            class="grid"
            style="
                grid-template-columns:1fr 1fr;
                gap:50px;
                align-items:center;
            "
        >


            <!-- Image / Visual -->

            <div
                style="
                    position:relative;
                    min-height:400px;
                    border-radius:14px;
                    overflow:hidden;
                    background:
                        linear-gradient(
                            135deg,
                            #174a3a,
                            #2c7059
                        );
                    display:flex;
                    align-items:center;
                    justify-content:center;
                "
            >

                <div
                    style="
                        position:absolute;
                        width:220px;
                        height:220px;
                        border-radius:50%;
                        border:1px solid rgba(255,255,255,.15);
                    "
                ></div>

                <div
                    style="
                        position:absolute;
                        width:150px;
                        height:150px;
                        border-radius:50%;
                        border:1px solid rgba(255,255,255,.15);
                    "
                ></div>

                <div
                    style="
                        position:relative;
                        text-align:center;
                        color:#ffffff;
                    "
                >

                    <div
                        style="
                            font-size:70px;
                            margin-bottom:10px;
                        "
                    >
                        🏡
                    </div>

                    <h2
                        style="
                            color:#ffffff;
                            font-size:28px;
                            margin-bottom:8px;
                        "
                    >
                        RealEstate
                    </h2>

                    <p
                        style="
                            color:rgba(255,255,255,.75);
                            font-size:11px;
                        "
                    >
                        Your Property Partner
                    </p>

                </div>

            </div>


            <!-- Content -->

            <div>

                <span
                    style="
                        color:#174a3a;
                        font-size:10px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:1px;
                    "
                >
                    Who We Are
                </span>

                <h2
                    style="
                        margin-top:8px;
                        margin-bottom:18px;
                        font-size:30px;
                        color:#26352e;
                    "
                >
                    Your Trusted Real Estate Partner
                </h2>

                <p
                    style="
                        margin-bottom:15px;
                        color:#69756f;
                        font-size:11px;
                        line-height:1.9;
                    "
                >
                    RealEstate is a modern property platform
                    designed to make the real estate experience
                    easier for buyers, sellers, tenants and
                    property professionals.
                </p>

                <p
                    style="
                        margin-bottom:15px;
                        color:#69756f;
                        font-size:11px;
                        line-height:1.9;
                    "
                >
                    Our platform brings properties, professional
                    agents and customers together in one simple
                    and convenient place.
                </p>

                <p
                    style="
                        margin-bottom:20px;
                        color:#69756f;
                        font-size:11px;
                        line-height:1.9;
                    "
                >
                    We believe finding a property should be
                    transparent, efficient and enjoyable.
                    That's why we focus on providing useful
                    information and a smooth user experience.
                </p>

                <a
                    href="properties.php"
                    class="btn btn-primary"
                >
                    Explore Properties
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     STATS
====================================================== -->

<section
    class="section"
    style="
        background:#f4f7f5;
    "
>

    <div class="container">

        <div
            class="grid"
            style="
                grid-template-columns:
                    repeat(4,1fr);
                gap:20px;
            "
        >

            <!-- Stat -->

            <div
                class="card"
                style="
                    text-align:center;
                    padding:30px 15px;
                "
            >

                <div
                    style="
                        font-size:28px;
                        font-weight:800;
                        color:#174a3a;
                    "
                >
                    500+
                </div>

                <p
                    style="
                        margin-top:5px;
                        color:#7c8781;
                        font-size:10px;
                    "
                >
                    Properties Listed
                </p>

            </div>


            <!-- Stat -->

            <div
                class="card"
                style="
                    text-align:center;
                    padding:30px 15px;
                "
            >

                <div
                    style="
                        font-size:28px;
                        font-weight:800;
                        color:#174a3a;
                    "
                >
                    100+
                </div>

                <p
                    style="
                        margin-top:5px;
                        color:#7c8781;
                        font-size:10px;
                    "
                >
                    Professional Agents
                </p>

            </div>


            <!-- Stat -->

            <div
                class="card"
                style="
                    text-align:center;
                    padding:30px 15px;
                "
            >

                <div
                    style="
                        font-size:28px;
                        font-weight:800;
                        color:#174a3a;
                    "
                >
                    1,000+
                </div>

                <p
                    style="
                        margin-top:5px;
                        color:#7c8781;
                        font-size:10px;
                    "
                >
                    Happy Customers
                </p>

            </div>


            <!-- Stat -->

            <div
                class="card"
                style="
                    text-align:center;
                    padding:30px 15px;
                "
            >

                <div
                    style="
                        font-size:28px;
                        font-weight:800;
                        color:#174a3a;
                    "
                >
                    5+
                </div>

                <p
                    style="
                        margin-top:5px;
                        color:#7c8781;
                        font-size:10px;
                    "
                >
                    Years Experience
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     MISSION & VISION
====================================================== -->

<section class="section">

    <div class="container">

        <div class="text-center">

            <span
                style="
                    color:#174a3a;
                    font-size:10px;
                    font-weight:700;
                    text-transform:uppercase;
                    letter-spacing:1px;
                "
            >
                What Drives Us
            </span>

            <h2 class="section-title">
                Our Mission & Vision
            </h2>

            <p
                class="section-subtitle"
                style="margin-left:auto;margin-right:auto;"
            >
                We are committed to creating a better
                real estate experience for everyone.
            </p>

        </div>


        <div
            class="grid"
            style="
                grid-template-columns:1fr 1fr;
                gap:25px;
                margin-top:35px;
            "
        >

            <!-- Mission -->

            <div
                class="card"
                style="
                    padding:35px;
                "
            >

                <div
                    style="
                        width:50px;
                        height:50px;
                        border-radius:12px;
                        background:#eaf4ef;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:24px;
                        margin-bottom:18px;
                    "
                >
                    🎯
                </div>

                <h3
                    style="
                        margin-bottom:10px;
                        font-size:19px;
                    "
                >
                    Our Mission
                </h3>

                <p
                    style="
                        color:#69756f;
                        font-size:11px;
                        line-height:1.8;
                    "
                >
                    Our mission is to simplify the property
                    journey by connecting people with the
                    right properties and trusted professionals.
                    We aim to provide an easy-to-use platform
                    that saves time and builds confidence.
                </p>

            </div>


            <!-- Vision -->

            <div
                class="card"
                style="
                    padding:35px;
                "
            >

                <div
                    style="
                        width:50px;
                        height:50px;
                        border-radius:12px;
                        background:#eaf4ef;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:24px;
                        margin-bottom:18px;
                    "
                >
                    🔭
                </div>

                <h3
                    style="
                        margin-bottom:10px;
                        font-size:19px;
                    "
                >
                    Our Vision
                </h3>

                <p
                    style="
                        color:#69756f;
                        font-size:11px;
                        line-height:1.8;
                    "
                >
                    We envision a future where finding,
                    buying, selling and renting property is
                    simple, transparent and accessible to
                    everyone through modern technology.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     OUR VALUES
====================================================== -->

<section
    class="section"
    style="
        background:#f4f7f5;
    "
>

    <div class="container">

        <div class="text-center">

            <span
                style="
                    color:#174a3a;
                    font-size:10px;
                    font-weight:700;
                    text-transform:uppercase;
                    letter-spacing:1px;
                "
            >
                Our Values
            </span>

            <h2 class="section-title">
                What We Stand For
            </h2>

        </div>


        <div
            class="grid"
            style="
                grid-template-columns:
                    repeat(3,1fr);
                gap:20px;
                margin-top:35px;
            "
        >

            <!-- Value 1 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        🤝
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Trust
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        We believe strong relationships are
                        built on honesty, transparency and trust.
                    </p>

                </div>

            </div>


            <!-- Value 2 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        ⭐
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Excellence
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        We continuously work to provide
                        high-quality service and experiences.
                    </p>

                </div>

            </div>


            <!-- Value 3 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        💡
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Innovation
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        We use modern technology to make
                        property discovery easier and smarter.
                    </p>

                </div>

            </div>


            <!-- Value 4 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        ❤️
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Customer First
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        Every decision we make starts with
                        understanding our customers' needs.
                    </p>

                </div>

            </div>


            <!-- Value 5 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        🌱
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Growth
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        We continuously learn, improve and
                        grow with our customers and partners.
                    </p>

                </div>

            </div>


            <!-- Value 6 -->

            <div class="card">

                <div class="card-body">

                    <div
                        style="
                            font-size:28px;
                            margin-bottom:12px;
                        "
                    >
                        🏆
                    </div>

                    <h3
                        style="
                            margin-bottom:8px;
                            font-size:16px;
                        "
                    >
                        Reliability
                    </h3>

                    <p
                        style="
                            color:#69756f;
                            font-size:10px;
                            line-height:1.8;
                        "
                    >
                        We strive to be a dependable partner
                        throughout every property journey.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     WHY CHOOSE US
====================================================== -->

<section class="section">

    <div class="container">

        <div
            class="grid"
            style="
                grid-template-columns:1fr 1fr;
                gap:50px;
                align-items:center;
            "
        >

            <!-- Content -->

            <div>

                <span
                    style="
                        color:#174a3a;
                        font-size:10px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:1px;
                    "
                >
                    Why Choose Us
                </span>

                <h2
                    style="
                        margin-top:8px;
                        margin-bottom:18px;
                        font-size:28px;
                    "
                >
                    Everything You Need
                    In One Place
                </h2>


                <!-- Feature -->

                <div
                    style="
                        display:flex;
                        gap:15px;
                        margin-bottom:20px;
                    "
                >

                    <div
                        style="
                            min-width:40px;
                            height:40px;
                            border-radius:50%;
                            background:#eaf4ef;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        ✓
                    </div>

                    <div>

                        <h3
                            style="
                                margin-bottom:4px;
                                font-size:14px;
                            "
                        >
                            Verified Properties
                        </h3>

                        <p
                            style="
                                color:#69756f;
                                font-size:10px;
                                line-height:1.7;
                            "
                        >
                            Discover property listings with
                            useful details to help you make
                            informed decisions.
                        </p>

                    </div>

                </div>


                <!-- Feature -->

                <div
                    style="
                        display:flex;
                        gap:15px;
                        margin-bottom:20px;
                    "
                >

                    <div
                        style="
                            min-width:40px;
                            height:40px;
                            border-radius:50%;
                            background:#eaf4ef;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        ✓
                    </div>

                    <div>

                        <h3
                            style="
                                margin-bottom:4px;
                                font-size:14px;
                            "
                        >
                            Professional Agents
                        </h3>

                        <p
                            style="
                                color:#69756f;
                                font-size:10px;
                                line-height:1.7;
                            "
                        >
                            Connect with experienced agents
                            who can guide you through your
                            property journey.
                        </p>

                    </div>

                </div>


                <!-- Feature -->

                <div
                    style="
                        display:flex;
                        gap:15px;
                        margin-bottom:20px;
                    "
                >

                    <div
                        style="
                            min-width:40px;
                            height:40px;
                            border-radius:50%;
                            background:#eaf4ef;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        ✓
                    </div>

                    <div>

                        <h3
                            style="
                                margin-bottom:4px;
                                font-size:14px;
                            "
                        >
                            Easy Enquiries
                        </h3>

                        <p
                            style="
                                color:#69756f;
                                font-size:10px;
                                line-height:1.7;
                            "
                        >
                            Contact agents and send property
                            enquiries quickly and conveniently.
                        </p>

                    </div>

                </div>


                <a
                    href="contact.php"
                    class="btn btn-primary"
                >
                    Talk To Our Team
                </a>

            </div>


            <!-- CTA Card -->

            <div
                class="card"
                style="
                    padding:45px;
                    text-align:center;
                    background:#174a3a;
                    color:#ffffff;
                "
            >

                <div
                    style="
                        font-size:55px;
                        margin-bottom:15px;
                    "
                >
                    🏠
                </div>

                <h2
                    style="
                        color:#ffffff;
                        font-size:25px;
                        margin-bottom:10px;
                    "
                >
                    Ready To Find Your
                    Dream Property?
                </h2>

                <p
                    style="
                        color:rgba(255,255,255,.75);
                        font-size:11px;
                        line-height:1.8;
                        margin-bottom:25px;
                    "
                >
                    Explore our property listings and
                    discover a place that feels like home.
                </p>

                <a
                    href="properties.php"
                    class="btn btn-secondary"
                >
                    Browse Properties
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     FOOTER
====================================================== -->

<footer class="site-footer">

    <div class="container">

        <div class="footer-grid">


            <!-- About -->

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


            <!-- Quick Links -->

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


            <!-- Company -->

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


            <!-- Account -->

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