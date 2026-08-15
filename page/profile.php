<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| Get Current User
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        profile_image,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Update Profile
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($name === "") {

        $message =
            "Name cannot be empty.";

        $messageType = "error";

    } else {

        $updateSQL = "
            UPDATE users
            SET
                name = ?,
                phone = ?
            WHERE id = ?
        ";

        $updateStmt =
            $conn->prepare($updateSQL);

        $updateStmt->bind_param(
            "ssi",
            $name,
            $phone,
            $userId
        );

        if ($updateStmt->execute()) {

            $message =
                "Profile updated successfully.";

            $messageType = "success";

            $_SESSION["user_name"] =
                $name;

            $user["name"] =
                $name;

            $user["phone"] =
                $phone;

        } else {

            $message =
                "Unable to update profile.";

            $messageType = "error";
        }

        $updateStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| Change Password
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $currentPassword =
        $_POST["current_password"] ?? "";

    $newPassword =
        $_POST["new_password"] ?? "";

    $confirmPassword =
        $_POST["confirm_password"] ?? "";


    if (
        $currentPassword === "" ||
        $newPassword === "" ||
        $confirmPassword === ""
    ) {

        $message =
            "All password fields are required.";

        $messageType = "error";

    } elseif (
        strlen($newPassword) < 8
    ) {

        $message =
            "New password must contain at least 8 characters.";

        $messageType = "error";

    } elseif (
        $newPassword !== $confirmPassword
    ) {

        $message =
            "New passwords do not match.";

        $messageType = "error";

    } else {


        /*
        |--------------------------------------------------------------
        | Get Password
        |--------------------------------------------------------------
        */

        $passwordSQL = "
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ";

        $passwordStmt =
            $conn->prepare($passwordSQL);

        $passwordStmt->bind_param(
            "i",
            $userId
        );

        $passwordStmt->execute();

        $passwordResult =
            $passwordStmt->get_result();

        $passwordRow =
            $passwordResult->fetch_assoc();

        $passwordStmt->close();


        if (
            !$passwordRow ||
            !password_verify(
                $currentPassword,
                $passwordRow["password"]
            )
        ) {

            $message =
                "Current password is incorrect.";

            $messageType = "error";

        } else {


            /*
            |----------------------------------------------------------
            | Update Password
            |----------------------------------------------------------
            */

            $hashedPassword =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

            $updatePasswordSQL = "
                UPDATE users
                SET password = ?
                WHERE id = ?
            ";

            $updatePasswordStmt =
                $conn->prepare(
                    $updatePasswordSQL
                );

            $updatePasswordStmt->bind_param(
                "si",
                $hashedPassword,
                $userId
            );

            if (
                $updatePasswordStmt->execute()
            ) {

                $message =
                    "Password changed successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Unable to change password.";

                $messageType =
                    "error";
            }

            $updatePasswordStmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Profile Image
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["upload_photo"])
) {

    if (
        !isset($_FILES["profile_image"]) ||
        $_FILES["profile_image"]["error"]
        !== UPLOAD_ERR_OK
    ) {

        $message =
            "Please select an image.";

        $messageType =
            "error";

    } else {

        $file =
            $_FILES["profile_image"];

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (
            !in_array(
                $file["type"],
                $allowedTypes,
                true
            )
        ) {

            $message =
                "Only JPG, PNG and WEBP images are allowed.";

            $messageType =
                "error";

        } elseif (
            $file["size"] > 5 * 1024 * 1024
        ) {

            $message =
                "Image size must be less than 5MB.";

            $messageType =
                "error";

        } else {


            $extension =
                strtolower(
                    pathinfo(
                        $file["name"],
                        PATHINFO_EXTENSION
                    )
                );

            $fileName =
                "user_" .
                $userId .
                "_" .
                time() .
                "." .
                $extension;

            $uploadDirectory =
                "../uploads/profiles/";

            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                mkdir(
                    $uploadDirectory,
                    0755,
                    true
                );
            }

            $destination =
                $uploadDirectory .
                $fileName;


            if (
                move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )
            ) {

                $oldImage =
                    $user["profile_image"];


                $imageSQL = "
                    UPDATE users
                    SET profile_image = ?
                    WHERE id = ?
                ";

                $imageStmt =
                    $conn->prepare(
                        $imageSQL
                    );

                $imageStmt->bind_param(
                    "si",
                    $fileName,
                    $userId
                );

                $imageStmt->execute();

                $imageStmt->close();


                /*
                |------------------------------------------------------
                | Delete Old Image
                |------------------------------------------------------
                */

                if (
                    !empty($oldImage)
                ) {

                    $oldPath =
                        $uploadDirectory .
                        basename(
                            $oldImage
                        );

                    if (
                        file_exists(
                            $oldPath
                        )
                    ) {

                        unlink(
                            $oldPath
                        );
                    }
                }


                $user["profile_image"] =
                    $fileName;

                $message =
                    "Profile photo updated.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Unable to upload image.";

                $messageType =
                    "error";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| User Statistics
|--------------------------------------------------------------------------
*/

$favoriteCount = 0;
$visitCount = 0;
$enquiryCount = 0;


/* Favorites */

$countSQL = "
    SELECT COUNT(*) AS total
    FROM favorites
    WHERE user_id = ?
";

$countStmt =
    $conn->prepare($countSQL);

$countStmt->bind_param(
    "i",
    $userId
);

$countStmt->execute();

$countResult =
    $countStmt->get_result();

$favoriteCount =
    (int)$countResult
        ->fetch_assoc()["total"];

$countStmt->close();


/* Visits */

$visitSQL = "
    SELECT COUNT(*) AS total
    FROM visits
    WHERE user_id = ?
";

$visitStmt =
    $conn->prepare($visitSQL);

$visitStmt->bind_param(
    "i",
    $userId
);

$visitStmt->execute();

$visitResult =
    $visitStmt->get_result();

$visitCount =
    (int)$visitResult
        ->fetch_assoc()["total"];

$visitStmt->close();


/* Enquiries */

$enquirySQL = "
    SELECT COUNT(*) AS total
    FROM enquiries
    WHERE user_id = ?
";

$enquiryStmt =
    $conn->prepare($enquirySQL);

$enquiryStmt->bind_param(
    "i",
    $userId
);

$enquiryStmt->execute();

$enquiryResult =
    $enquiryStmt->get_result();

$enquiryCount =
    (int)$enquiryResult
        ->fetch_assoc()["total"];

$enquiryStmt->close();


$profileImage =
    !empty($user["profile_image"])
        ? "../uploads/profiles/" .
          $user["profile_image"]
        : "";


$initial =
    strtoupper(
        substr(
            $user["name"],
            0,
            1
        )
    );

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
    My Profile | RealEstateHub
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

    --bg: #f5f7f5;
    --white: #ffffff;

    --text: #18231f;
    --muted: #707b76;

    --border: #e0e7e3;

    --success: #17643b;
    --success-bg: #e3f5ea;

    --danger: #b8323f;
    --danger-bg: #fde9eb;

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


/* =====================================================
   HEADER
===================================================== */

.header {

    height: 72px;

    background:
        white;

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 5%;

    position: sticky;

    top: 0;

    z-index: 100;

}

.logo {

    font-size:
        21px;

    font-weight:
        800;

    text-decoration:
        none;

}

.logo span {
    color: var(--primary);
}

.logo strong {
    color: var(--accent);
}

.nav {

    display:
        flex;

    gap:
        25px;

    font-size:
        12px;

}

.nav a {

    color:
        var(--muted);

    text-decoration:
        none;

}

.nav a:hover {

    color:
        var(--primary);

}

.user {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    font-size:
        11px;

}

.user-avatar {

    width:
        36px;

    height:
        36px;

    border-radius:
        50%;

    overflow:
        hidden;

    background:
        #e8f2ed;

    color:
        var(--primary);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

}

.user-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    max-width:
        1150px;

    margin:
        auto;

    padding:
        38px 5% 70px;

}


/* =====================================================
   PAGE TITLE
===================================================== */

.page-title {

    margin-bottom:
        25px;

}

.page-title h1 {

    font-size:
        30px;

    margin-bottom:
        7px;

}

.page-title p {

    color:
        var(--muted);

    font-size:
        11px;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding:
        13px 16px;

    border-radius:
        8px;

    font-size:
        11px;

    margin-bottom:
        20px;

}

.alert.success {

    background:
        var(--success-bg);

    color:
        var(--success);

    border:
        1px solid #c5e6d1;

}

.alert.error {

    background:
        var(--danger-bg);

    color:
        var(--danger);

    border:
        1px solid #f2c4c9;

}


/* =====================================================
   LAYOUT
===================================================== */

.profile-layout {

    display:
        grid;

    grid-template-columns:
        300px 1fr;

    gap:
        22px;

}


/* =====================================================
   CARD
===================================================== */

.card {

    background:
        white;

    border:
        1px solid var(--border);

    border-radius:
        12px;

    padding:
        24px;

}

.card-title {

    font-size:
        15px;

    margin-bottom:
        5px;

}

.card-subtitle {

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        20px;

}


/* =====================================================
   PROFILE CARD
===================================================== */

.profile-card {

    text-align:
        center;

}

.profile-picture {

    width:
        105px;

    height:
        105px;

    border-radius:
        50%;

    margin:
        0 auto 15px;

    overflow:
        hidden;

    background:
        #e8f2ed;

    border:
        4px solid white;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        var(--primary);

    font-size:
        35px;

    font-weight:
        800;

}

.profile-picture img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}

.profile-card h2 {

    font-size:
        18px;

    margin-bottom:
        5px;

}

.profile-email {

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        20px;

}

.photo-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    height:
        38px;

    padding:
        0 15px;

    background:
        var(--primary);

    color:
        white;

    border:
        none;

    border-radius:
        6px;

    font-size:
        9px;

    font-weight:
        700;

    cursor:
        pointer;

}

.photo-button:hover {

    background:
        var(--primary-dark);

}

.photo-input {

    display:
        none;

}

.profile-stats {

    border-top:
        1px solid var(--border);

    margin-top:
        22px;

    padding-top:
        20px;

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

}

.profile-stat strong {

    display:
        block;

    color:
        var(--primary);

    font-size:
        19px;

    margin-bottom:
        4px;

}

.profile-stat span {

    color:
        var(--muted);

    font-size:
        8px;

}


/* =====================================================
   FORM
===================================================== */

.form-group {

    margin-bottom:
        17px;

}

.form-group label {

    display:
        block;

    font-size:
        10px;

    font-weight:
        700;

    margin-bottom:
        7px;

}

.form-group input {

    width:
        100%;

    height:
        43px;

    border:
        1px solid var(--border);

    border-radius:
        7px;

    padding:
        0 13px;

    font-size:
        11px;

    outline:
        none;

    transition:
        .2s;

}

.form-group input:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23,74,58,.08);

}

.form-group input:disabled {

    background:
        #f4f6f5;

    color:
        var(--muted);

}

.form-help {

    display:
        block;

    color:
        var(--muted);

    font-size:
        8px;

    margin-top:
        5px;

}

.submit-button {

    height:
        42px;

    padding:
        0 20px;

    background:
        var(--primary);

    color:
        white;

    border:
        none;

    border-radius:
        6px;

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        700;

}

.submit-button:hover {

    background:
        var(--primary-dark);

}


/* =====================================================
   DIVIDER
===================================================== */

.divider {

    height:
        1px;

    background:
        var(--border);

    margin:
        25px 0;

}


/* =====================================================
   DANGER ZONE
===================================================== */

.danger-zone {

    border:
        1px solid #f0d0d4;

    background:
        #fffafa;

}

.danger-zone h3 {

    color:
        var(--danger);

    font-size:
        14px;

    margin-bottom:
        6px;

}

.danger-zone p {

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        15px;

}

.logout-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    height:
        39px;

    padding:
        0 16px;

    background:
        white;

    color:
        var(--danger);

    border:
        1px solid #efc4c8;

    border-radius:
        6px;

    text-decoration:
        none;

    font-size:
        9px;

    font-weight:
        700;

}

.logout-button:hover {

    background:
        var(--danger-bg);

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 800px) {

    .nav {
        display: none;
    }

    .profile-layout {

        grid-template-columns:
            1fr;

    }

}

@media(max-width: 450px) {

    .header {

        padding:
            0 15px;

    }

    .container {

        padding-left:
            15px;

        padding-right:
            15px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">


    <a
        href="dashboard.php"
        class="logo"
    >

        <span>Real</span><strong>Estate</strong>Hub

    </a>


    <nav class="nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="properties.php">
            Properties
        </a>

        <a href="favorites.php">
            Favorites
        </a>

        <a href="visits.php">
            Visits
        </a>

        <a href="profile.php">
            Profile
        </a>

    </nav>


    <div class="user">

        <div class="user-avatar">

            <?php if ($profileImage): ?>

                <img
                    src="<?php
                    echo safe($profileImage);
                    ?>"
                    alt="Profile"
                >

            <?php else: ?>

                <?php echo $initial; ?>

            <?php endif; ?>

        </div>

        <?php
        echo safe($user["name"]);
        ?>

    </div>


</header>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


    <div class="page-title">

        <h1>
            My Profile
        </h1>

        <p>
            Manage your personal information and account security.
        </p>

    </div>



    <?php if ($message): ?>

        <div
            class="alert
            <?php
            echo safe($messageType);
            ?>"
        >

            <?php
            echo safe($message);
            ?>

        </div>

    <?php endif; ?>



    <div class="profile-layout">


        <!-- =================================================
             LEFT PROFILE CARD
        ================================================== -->

        <aside>


            <div class="card profile-card">


                <div class="profile-picture">

                    <?php if ($profileImage): ?>

                        <img
                            src="<?php
                            echo safe(
                                $profileImage
                            );
                            ?>"
                            alt="Profile Photo"
                        >

                    <?php else: ?>

                        <?php
                        echo $initial;
                        ?>

                    <?php endif; ?>

                </div>


                <h2>

                    <?php
                    echo safe(
                        $user["name"]
                    );
                    ?>

                </h2>


                <div class="profile-email">

                    <?php
                    echo safe(
                        $user["email"]
                    );
                    ?>

                </div>


                <!-- PHOTO FORM -->

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="photoForm"
                >

                    <input
                        type="file"
                        name="profile_image"
                        id="profileImage"
                        class="photo-input"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <button
                        type="button"
                        class="photo-button"
                        onclick="document.getElementById('profileImage').click()"
                    >

                        📷 Change Photo

                    </button>


                    <input
                        type="hidden"
                        name="upload_photo"
                        value="1"
                    >

                </form>



                <!-- STATS -->

                <div class="profile-stats">


                    <div class="profile-stat">

                        <strong>
                            <?php
                            echo $favoriteCount;
                            ?>
                        </strong>

                        <span>
                            Favorites
                        </span>

                    </div>


                    <div class="profile-stat">

                        <strong>
                            <?php
                            echo $visitCount;
                            ?>
                        </strong>

                        <span>
                            Visits
                        </span>

                    </div>


                    <div class="profile-stat">

                        <strong>
                            <?php
                            echo $enquiryCount;
                            ?>
                        </strong>

                        <span>
                            Enquiries
                        </span>

                    </div>


                </div>


            </div>


        </aside>



        <!-- =================================================
             RIGHT CONTENT
        ================================================== -->

        <section>


            <!-- PERSONAL INFORMATION -->

            <div class="card">

                <h2 class="card-title">
                    Personal Information
                </h2>

                <p class="card-subtitle">
                    Update your name and contact information.
                </p>


                <form method="POST">


                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?php
                            echo safe(
                                $user["name"]
                            );
                            ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            value="<?php
                            echo safe(
                                $user["email"]
                            );
                            ?>"
                            disabled
                        >

                        <span class="form-help">
                            Email address cannot be changed here.
                        </span>

                    </div>


                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            value="<?php
                            echo safe(
                                $user["phone"]
                            );
                            ?>"
                            maxlength="30"
                            placeholder="+91 XXXXX XXXXX"
                        >

                    </div>


                    <button
                        type="submit"
                        name="update_profile"
                        class="submit-button"
                    >

                        Save Changes

                    </button>


                </form>

            </div>



            <!-- PASSWORD -->

            <div
                class="card"
                style="margin-top: 20px;"
            >

                <h2 class="card-title">
                    Change Password
                </h2>

                <p class="card-subtitle">
                    Keep your account secure with a strong password.
                </p>


                <form method="POST">


                    <div class="form-group">

                        <label>
                            Current Password
                        </label>

                        <input
                            type="password"
                            name="current_password"
                            id="currentPassword"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            id="newPassword"
                            minlength="8"
                            required
                        >

                        <span class="form-help">
                            Minimum 8 characters.
                        </span>

                    </div>


                    <div class="form-group">

                        <label>
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirmPassword"
                            minlength="8"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        name="change_password"
                        class="submit-button"
                    >

                        Update Password

                    </button>


                </form>

            </div>



            <!-- DANGER -->

            <div
                class="card danger-zone"
                style="margin-top: 20px;"
            >

                <h3>
                    Account
                </h3>

                <p>
                    Sign out of your RealEstateHub account.
                </p>

                <a
                    href="../auth/logout.php"
                    class="logout-button"
                    onclick="return confirm('Are you sure you want to logout?')"
                >

                    Logout

                </a>

            </div>


        </section>


    </div>


</main>



<script>

/*
|--------------------------------------------------------------------------
| Automatic Profile Image Upload
|--------------------------------------------------------------------------
*/

const profileImage =
    document.getElementById(
        "profileImage"
    );

const photoForm =
    document.getElementById(
        "photoForm"
    );


if (profileImage) {

    profileImage.addEventListener(
        "change",
        function() {

            if (this.files.length > 0) {

                const file =
                    this.files[0];


                /*
                |----------------------------------------------------------
                | Size Check
                |----------------------------------------------------------
                */

                if (
                    file.size >
                    5 * 1024 * 1024
                ) {

                    alert(
                        "Image must be less than 5MB."
                    );

                    this.value = "";

                    return;
                }


                /*
                |----------------------------------------------------------
                | Upload
                |----------------------------------------------------------
                */

                photoForm.submit();

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| Password Confirmation
|--------------------------------------------------------------------------
*/

const passwordForm =
    document.querySelector(
        'form[action=""]'
    );

const newPassword =
    document.getElementById(
        "newPassword"
    );

const confirmPassword =
    document.getElementById(
        "confirmPassword"
    );


if (
    newPassword &&
    confirmPassword
) {

    confirmPassword.addEventListener(
        "input",
        function() {

            if (
                this.value !==
                newPassword.value
            ) {

                this.setCustomValidity(
                    "Passwords do not match."
                );

            } else {

                this.setCustomValidity(
                    ""
                );

            }

        }
    );

}

</script>


</body>

</html>