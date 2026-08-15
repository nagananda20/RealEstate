<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Fetch Agents
|--------------------------------------------------------------------------
*/

$agents = [];

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        photo,
        specialization,
        experience,
        status
    FROM agents
    WHERE status = 'active'
    ORDER BY id DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
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

    <title>Our Agents | RealEstate</title>

    <meta
        name="description"
        content="Meet our professional real estate agents."
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

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>

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

            <h1>
                Meet Our Agents
            </h1>

            <p>
                Connect with experienced real estate professionals
                who can help you find your perfect property.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     AGENTS SECTION
====================================================== -->

<section class="section">

    <div class="container">

        <!-- Section Header -->

        <div class="text-center">

            <h2 class="section-title">
                Our Professional Agents
            </h2>

            <p class="section-subtitle" style="margin-left:auto;margin-right:auto;">
                Our dedicated team is here to guide you through
                every step of your real estate journey.
            </p>

        </div>


        <!-- Agents Grid -->

        <?php if (!empty($agents)): ?>

            <div
                class="grid grid-3"
                style="margin-top:30px;"
            >

                <?php foreach ($agents as $agent): ?>

                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Agent Photo
                    |--------------------------------------------------------------------------
                    */

                    $photo = '../assets/images/agents/default-agent.jpg';

                    if (!empty($agent['photo'])) {

                        $photoPath =
                            '../assets/images/agents/' .
                            $agent['photo'];

                        if (file_exists(
                            __DIR__ . '/../assets/images/agents/' .
                            $agent['photo']
                        )) {
                            $photo = $photoPath;
                        }
                    }

                    ?>


                    <!-- Agent Card -->

                    <article class="card agent-card">


                        <!-- Agent Image -->

                        <div class="agent-image">

                            <img
                                src="<?php echo htmlspecialchars($photo); ?>"
                                alt="<?php echo htmlspecialchars($agent['name']); ?>"
                            >

                        </div>


                        <!-- Agent Content -->

                        <div class="card-body">

                            <h3 class="agent-name">

                                <?php
                                echo htmlspecialchars(
                                    $agent['name']
                                );
                                ?>

                            </h3>


                            <!-- Specialization -->

                            <?php if (!empty($agent['specialization'])): ?>

                                <p class="agent-specialization">

                                    <?php
                                    echo htmlspecialchars(
                                        $agent['specialization']
                                    );
                                    ?>

                                </p>

                            <?php else: ?>

                                <p class="agent-specialization">
                                    Real Estate Consultant
                                </p>

                            <?php endif; ?>


                            <!-- Experience -->

                            <?php if (!empty($agent['experience'])): ?>

                                <p class="agent-experience">

                                    ⭐
                                    <?php
                                    echo htmlspecialchars(
                                        $agent['experience']
                                    );
                                    ?>
                                    years experience

                                </p>

                            <?php endif; ?>


                            <!-- Contact -->

                            <div class="agent-contact">

                                <?php if (!empty($agent['phone'])): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($agent['phone']); ?>"
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


                            <!-- View Profile -->

                            <a
                                href="agent-details.php?id=<?php echo (int)$agent['id']; ?>"
                                class="btn btn-primary w-100"
                                style="margin-top:15px;"
                            >
                                View Profile
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <!-- Empty State -->

            <div
                class="empty-state"
                style="margin-top:30px;"
            >

                <div class="empty-state-icon">
                    👤
                </div>

                <h3>
                    No Agents Available
                </h3>

                <p>
                    Our agents will appear here once they are added.
                </p>

            </div>


        <?php endif; ?>

    </div>

</section>


<!-- =====================================================
     CTA
====================================================== -->

<section
    class="section"
    style="padding-top:20px;"
>

    <div class="container">

        <div
            class="card"
            style="
                padding:40px;
                text-align:center;
                background:#174a3a;
                color:#ffffff;
            "
        >

            <h2
                style="
                    margin-bottom:10px;
                    font-size:24px;
                "
            >
                Need Help Finding a Property?
            </h2>

            <p
                style="
                    margin-bottom:20px;
                    color:rgba(255,255,255,.72);
                    font-size:11px;
                "
            >
                Our experienced agents are ready to help you
                find the right property.
            </p>

            <a
                href="contact.php"
                class="btn btn-secondary"
            >
                Contact Us
            </a>

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


<!-- Main JavaScript -->

<script src="../assets/js/main.js"></script>


</body>

</html>