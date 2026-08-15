<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$message = '';
$message_type = '';

$name = '';
$email = '';
$phone = '';
$subject = '';
$enquiry_message = '';

/*
|--------------------------------------------------------------------------
| Logged-in User
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {

    $name = $_SESSION['user_name'] ?? '';
    $email = $_SESSION['user_email'] ?? '';
}


/*
|--------------------------------------------------------------------------
| Contact Form Submit
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $enquiry_message = trim($_POST['message'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $name === '' ||
        $email === '' ||
        $enquiry_message === ''
    ) {

        $message = 'Please fill in all required fields.';
        $message_type = 'danger';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';
        $message_type = 'danger';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Enquiry
            |--------------------------------------------------------------------------
            |
            | Expected enquiries columns:
            | id
            | user_id
            | name
            | email
            | phone
            | subject
            | message
            | status
            | created_at
            |
            */

            $user_id = $_SESSION['user_id'] ?? null;

            $sql = "
                INSERT INTO enquiries
                (
                    user_id,
                    name,
                    email,
                    phone,
                    subject,
                    message,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, 'new')
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    'Unable to prepare database query.'
                );
            }

            $stmt->bind_param(
                'isssss',
                $user_id,
                $name,
                $email,
                $phone,
                $subject,
                $enquiry_message
            );

            if ($stmt->execute()) {

                $message =
                    'Thank you! Your enquiry has been sent successfully.';

                $message_type = 'success';

                /*
                | Clear form after successful submission
                */

                if (
                    !isset($_SESSION['logged_in']) ||
                    !$_SESSION['logged_in']
                ) {
                    $name = '';
                    $email = '';
                }

                $phone = '';
                $subject = '';
                $enquiry_message = '';

            } else {

                $message =
                    'Unable to send your enquiry. Please try again.';

                $message_type = 'danger';
            }

            $stmt->close();

        } catch (Throwable $e) {

            $message =
                'A server error occurred. Please try again later.';

            $message_type = 'danger';
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
        Contact Us | RealEstate
    </title>

    <meta
        name="description"
        content="Contact RealEstate for property enquiries and assistance."
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

                <a href="about.php">
                    About
                </a>

                <a
                    href="contact.php"
                    class="active"
                >
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
     CONTACT HERO
====================================================== -->

<section class="properties-hero">

    <div class="container">

        <div class="properties-hero-content">

            <h1>
                Contact Us
            </h1>

            <p>
                Have a question about a property?
                Our team is ready to help.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     CONTACT SECTION
====================================================== -->

<section class="section">

    <div class="container">

        <div
            class="grid"
            style="
                grid-template-columns:1fr 1.5fr;
                gap:30px;
                align-items:start;
            "
        >


            <!-- =================================================
                 CONTACT INFORMATION
            ================================================== -->

            <div>

                <h2 class="section-title">
                    Get In Touch
                </h2>

                <p class="section-subtitle">
                    Whether you're buying, selling, renting,
                    or simply looking for information,
                    we'd love to hear from you.
                </p>


                <!-- Address -->

                <div
                    class="card"
                    style="margin-bottom:12px;"
                >

                    <div
                        class="card-body"
                        style="
                            display:flex;
                            gap:15px;
                            align-items:flex-start;
                        "
                    >

                        <div
                            style="
                                width:40px;
                                height:40px;
                                flex-shrink:0;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:8px;
                                background:#edf5f1;
                                color:#174a3a;
                            "
                        >
                            📍
                        </div>

                        <div>

                            <h3
                                style="
                                    margin-bottom:4px;
                                    font-size:12px;
                                "
                            >
                                Our Office
                            </h3>

                            <p
                                style="
                                    color:#7c8781;
                                    font-size:10px;
                                "
                            >
                                RealEstate Business Center<br>
                                Bengaluru, Karnataka<br>
                                India
                            </p>

                        </div>

                    </div>

                </div>


                <!-- Phone -->

                <div
                    class="card"
                    style="margin-bottom:12px;"
                >

                    <div
                        class="card-body"
                        style="
                            display:flex;
                            gap:15px;
                            align-items:flex-start;
                        "
                    >

                        <div
                            style="
                                width:40px;
                                height:40px;
                                flex-shrink:0;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:8px;
                                background:#edf5f1;
                                color:#174a3a;
                            "
                        >
                            📞
                        </div>

                        <div>

                            <h3
                                style="
                                    margin-bottom:4px;
                                    font-size:12px;
                                "
                            >
                                Phone
                            </h3>

                            <a
                                href="tel:+919876543210"
                                style="
                                    color:#7c8781;
                                    font-size:10px;
                                "
                            >
                                +91 98765 43210
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Email -->

                <div
                    class="card"
                    style="margin-bottom:12px;"
                >

                    <div
                        class="card-body"
                        style="
                            display:flex;
                            gap:15px;
                            align-items:flex-start;
                        "
                    >

                        <div
                            style="
                                width:40px;
                                height:40px;
                                flex-shrink:0;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:8px;
                                background:#edf5f1;
                                color:#174a3a;
                            "
                        >
                            ✉
                        </div>

                        <div>

                            <h3
                                style="
                                    margin-bottom:4px;
                                    font-size:12px;
                                "
                            >
                                Email
                            </h3>

                            <a
                                href="mailto:info@realestate.com"
                                style="
                                    color:#7c8781;
                                    font-size:10px;
                                "
                            >
                                info@realestate.com
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Working Hours -->

                <div
                    class="card"
                >

                    <div
                        class="card-body"
                        style="
                            display:flex;
                            gap:15px;
                            align-items:flex-start;
                        "
                    >

                        <div
                            style="
                                width:40px;
                                height:40px;
                                flex-shrink:0;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:8px;
                                background:#edf5f1;
                                color:#174a3a;
                            "
                        >
                            🕒
                        </div>

                        <div>

                            <h3
                                style="
                                    margin-bottom:4px;
                                    font-size:12px;
                                "
                            >
                                Working Hours
                            </h3>

                            <p
                                style="
                                    color:#7c8781;
                                    font-size:10px;
                                    line-height:1.7;
                                "
                            >
                                Monday - Saturday<br>
                                9:00 AM - 6:00 PM
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONTACT FORM
            ================================================== -->

            <div class="card">

                <div class="card-header">

                    <h2
                        style="
                            font-size:19px;
                            color:#26352e;
                        "
                    >
                        Send Us A Message
                    </h2>

                    <p
                        style="
                            margin-top:4px;
                            color:#89938e;
                            font-size:10px;
                        "
                    >
                        Fill in the form and our team will
                        contact you shortly.
                    </p>

                </div>


                <div class="card-body">


                    <!-- Message -->

                    <?php if ($message !== ''): ?>

                        <div
                            class="alert alert-<?php echo $message_type; ?>"
                        >

                            <?php
                            echo htmlspecialchars($message);
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- Form -->

                    <form
                        method="POST"
                        action="contact.php"
                        id="contactForm"
                    >


                        <!-- Name -->

                        <div class="form-group">

                            <label
                                for="name"
                                class="form-label"
                            >
                                Full Name *
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Enter your full name"
                                value="<?php echo htmlspecialchars($name); ?>"
                                required
                            >

                        </div>


                        <!-- Email -->

                        <div class="form-group">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email Address *
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                            >

                        </div>


                        <!-- Phone -->

                        <div class="form-group">

                            <label
                                for="phone"
                                class="form-label"
                            >
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="Enter your phone number"
                                value="<?php echo htmlspecialchars($phone); ?>"
                            >

                        </div>


                        <!-- Subject -->

                        <div class="form-group">

                            <label
                                for="subject"
                                class="form-label"
                            >
                                Subject
                            </label>

                            <select
                                id="subject"
                                name="subject"
                                class="form-control"
                            >

                                <option value="">
                                    Select a subject
                                </option>

                                <option
                                    value="Property Enquiry"
                                    <?php echo $subject === 'Property Enquiry' ? 'selected' : ''; ?>
                                >
                                    Property Enquiry
                                </option>

                                <option
                                    value="Buying Property"
                                    <?php echo $subject === 'Buying Property' ? 'selected' : ''; ?>
                                >
                                    Buying Property
                                </option>

                                <option
                                    value="Selling Property"
                                    <?php echo $subject === 'Selling Property' ? 'selected' : ''; ?>
                                >
                                    Selling Property
                                </option>

                                <option
                                    value="Renting Property"
                                    <?php echo $subject === 'Renting Property' ? 'selected' : ''; ?>
                                >
                                    Renting Property
                                </option>

                                <option
                                    value="Other"
                                    <?php echo $subject === 'Other' ? 'selected' : ''; ?>
                                >
                                    Other
                                </option>

                            </select>

                        </div>


                        <!-- Message -->

                        <div class="form-group">

                            <label
                                for="message"
                                class="form-label"
                            >
                                Message *
                            </label>

                            <textarea
                                id="message"
                                name="message"
                                class="form-control"
                                placeholder="Write your message..."
                                required
                            ><?php echo htmlspecialchars($enquiry_message); ?></textarea>

                        </div>


                        <!-- Submit -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Send Message
                        </button>

                    </form>

                </div>

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


<!-- Main JavaScript -->

<script src="../assets/js/main.js"></script>


</body>

</html>