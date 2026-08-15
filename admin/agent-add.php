<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   AUTHENTICATION
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
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
}


/* =========================================================
   VARIABLES
========================================================= */

$name = "";
$email = "";
$phone = "";
$experience = "";
$specialization = "";
$license_number = "";
$bio = "";
$status = "active";

$errors = [];


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $experience =
        trim($_POST["experience"] ?? "");

    $specialization =
        trim($_POST["specialization"] ?? "");

    $license_number =
        trim($_POST["license_number"] ?? "");

    $bio =
        trim($_POST["bio"] ?? "");

    $status =
        trim($_POST["status"] ?? "active");

    $csrfToken =
        $_POST["csrf_token"] ?? "";


    /* =====================================================
       CSRF
    ===================================================== */

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $csrfToken
        )
    ) {

        $errors[] =
            "Security verification failed.";

    }


    /* =====================================================
       NAME
    ===================================================== */

    if ($name === "") {

        $errors[] =
            "Agent name is required.";

    }
    elseif (strlen($name) < 2) {

        $errors[] =
            "Agent name must contain at least 2 characters.";

    }


    /* =====================================================
       EMAIL
    ===================================================== */

    if ($email === "") {

        $errors[] =
            "Email address is required.";

    }
    elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $errors[] =
            "Please enter a valid email address.";

    }


    /* =====================================================
       PHONE
    ===================================================== */

    if ($phone !== "") {

        $phoneDigits =
            preg_replace(
                "/[^0-9]/",
                "",
                $phone
            );

        if (strlen($phoneDigits) < 10) {

            $errors[] =
                "Please enter a valid phone number.";

        }

    }


    /* =====================================================
       PASSWORD
    ===================================================== */

    if ($password === "") {

        $errors[] =
            "Password is required.";

    }
    elseif (strlen($password) < 8) {

        $errors[] =
            "Password must contain at least 8 characters.";

    }


    /* =====================================================
       EXPERIENCE
    ===================================================== */

    if ($experience !== "") {

        if (
            !ctype_digit($experience)
            ||
            (int)$experience < 0
        ) {

            $errors[] =
                "Experience must be a valid number.";

        }

    }


    /* =====================================================
       STATUS
    ===================================================== */

    $allowedStatus = [
        "active",
        "inactive"
    ];

    if (
        !in_array(
            $status,
            $allowedStatus,
            true
        )
    ) {

        $errors[] =
            "Invalid agent status.";

        $status = "active";

    }


    /* =====================================================
       CHECK EMAIL
    ===================================================== */

    if (empty($errors)) {

        $check =
            $conn->prepare(
                "SELECT id
                 FROM agents
                 WHERE email = ?
                 LIMIT 1"
            );


        if ($check) {

            $check->bind_param(
                "s",
                $email
            );

            $check->execute();

            $result =
                $check->get_result();

            if ($result->num_rows > 0) {

                $errors[] =
                    "An agent with this email already exists.";

            }

            $check->close();

        }
        else {

            $errors[] =
                "Unable to check agent email.";

        }

    }


    /* =====================================================
       PROFILE IMAGE
    ===================================================== */

    $profileImage = "";


    if (
        isset($_FILES["profile_image"])
        &&
        $_FILES["profile_image"]["error"]
        !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES["profile_image"]["error"]
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                "Profile image upload failed.";

        }
        else {

            $file =
                $_FILES["profile_image"];

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $fileType =
                mime_content_type(
                    $file["tmp_name"]
                );


            if (
                !in_array(
                    $fileType,
                    $allowedTypes,
                    true
                )
            ) {

                $errors[] =
                    "Only JPG, PNG and WEBP images are allowed.";

            }


            if (
                $file["size"] >
                5 * 1024 * 1024
            ) {

                $errors[] =
                    "Profile image must be less than 5MB.";

            }


            if (empty($errors)) {

                $uploadDir =
                    "../uploads/agents/";


                if (
                    !is_dir($uploadDir)
                ) {

                    mkdir(
                        $uploadDir,
                        0755,
                        true
                    );

                }


                $extension =
                    strtolower(
                        pathinfo(
                            $file["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $profileImage =
                    "agent_" .
                    time() .
                    "_" .
                    bin2hex(
                        random_bytes(5)
                    ) .
                    "." .
                    $extension;


                $destination =
                    $uploadDir .
                    $profileImage;


                if (
                    !move_uploaded_file(
                        $file["tmp_name"],
                        $destination
                    )
                ) {

                    $errors[] =
                        "Unable to save profile image.";

                    $profileImage = "";

                }

            }

        }

    }


    /* =====================================================
       INSERT AGENT
    ===================================================== */

    if (empty($errors)) {

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $stmt =
            $conn->prepare(
                "INSERT INTO agents
                (
                    name,
                    email,
                    phone,
                    password,
                    experience,
                    specialization,
                    license_number,
                    bio,
                    profile_image,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )"
            );


        if (!$stmt) {

            $errors[] =
                "Database error while creating agent.";

        }
        else {

            $stmt->bind_param(
                "ssssisssss",
                $name,
                $email,
                $phone,
                $hashedPassword,
                $experience,
                $specialization,
                $license_number,
                $bio,
                $profileImage,
                $status
            );


            /*
             * If experience is empty, convert it to NULL.
             */
            if ($experience === "") {
                $experience = null;
            }


            if ($stmt->execute()) {

                $_SESSION["success"] =
                    "Agent created successfully.";

                $stmt->close();

                header(
                    "Location: agents.php"
                );

                exit;

            }
            else {

                $errors[] =
                    "Unable to create agent.";

            }


            $stmt->close();

        }

    }

}


/* =========================================================
   ADMIN
========================================================= */

$adminName =
    $_SESSION["user_name"]
    ??
    "Administrator";

$adminInitial =
    strtoupper(
        substr(
            $adminName,
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
    Add Agent | RealEstate
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin:0;
    padding:0;
    box-sizing:border-box;

}


:root {

    --primary:#174a3a;
    --primary-dark:#10372b;
    --accent:#d7a94b;
    --bg:#f4f6f5;
    --white:#ffffff;
    --text:#18231f;
    --muted:#737c78;
    --border:#dfe6e2;
    --red:#b43843;
    --red-bg:#fdebed;
    --green:#24734e;
    --green-bg:#eaf6ef;

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


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position:fixed;

    left:0;
    top:0;
    bottom:0;

    width:240px;

    background:
        var(--primary);

    color:white;

}


.logo {

    height:75px;

    display:flex;

    align-items:center;

    padding:0 25px;

    color:white;

    text-decoration:none;

    font-size:20px;

    font-weight:800;

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

    font-size:8px;

    text-transform:uppercase;

    letter-spacing:1.5px;

}


.menu {

    padding:0 12px;

}


.menu a {

    height:44px;

    display:flex;

    align-items:center;

    gap:12px;

    padding:0 13px;

    margin-bottom:3px;

    border-radius:7px;

    color:
        rgba(255,255,255,.7);

    text-decoration:none;

    font-size:10px;

    transition:.2s;

}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.1);

    color:white;

}


.icon {

    width:20px;

    text-align:center;

}


.sidebar-bottom {

    position:absolute;

    left:0;
    right:0;
    bottom:0;

    padding:15px;

    border-top:
        1px solid
        rgba(255,255,255,.1);

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:240px;

    min-height:100vh;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height:75px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    background:white;

    border-bottom:
        1px solid
        var(--border);

}


.topbar-left {

    display:flex;

    align-items:center;

    gap:12px;

}


.back {

    width:36px;
    height:36px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:6px;

    background:#eef1ef;

    color:var(--text);

    text-decoration:none;

}


.topbar h1 {

    font-size:18px;

}


.topbar p {

    margin-top:4px;

    color:var(--muted);

    font-size:8px;

}


.admin {

    display:flex;

    align-items:center;

    gap:9px;

}


.admin-avatar {

    width:35px;
    height:35px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:
        var(--primary);

    color:white;

    font-size:12px;

    font-weight:800;

}


.admin-name {

    font-size:8px;

    font-weight:700;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:1100px;

    margin:auto;

    padding:30px;

}


.page-header {

    margin-bottom:22px;

}


.page-header h2 {

    font-size:23px;

}


.page-header p {

    margin-top:7px;

    color:var(--muted);

    font-size:8px;

}


/* =========================================================
   ERROR
========================================================= */

.error-box {

    background:
        var(--red-bg);

    color:
        var(--red);

    border:
        1px solid
        #f4cbd0;

    border-radius:8px;

    padding:14px 17px;

    margin-bottom:20px;

    font-size:8px;

}


.error-box strong {

    display:block;

    margin-bottom:7px;

}


.error-box ul {

    padding-left:18px;

}


.error-box li {

    margin-bottom:4px;

}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    background:white;

    border:
        1px solid
        var(--border);

    border-radius:11px;

    overflow:hidden;

}


.card-header {

    padding:
        18px 22px;

    border-bottom:
        1px solid
        #edf0ee;

}


.card-header h3 {

    font-size:11px;

}


.card-header p {

    margin-top:5px;

    color:var(--muted);

    font-size:7px;

}


.form-body {

    padding:25px;

}


.form-grid {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:20px;

}


.form-group {

    display:flex;

    flex-direction:column;

}


.form-group.full {

    grid-column:1 / -1;

}


label {

    margin-bottom:7px;

    font-size:8px;

    font-weight:700;

}


.required {

    color:
        var(--red);

}


input,
select,
textarea {

    width:100%;

    padding:
        12px 13px;

    border:
        1px solid
        var(--border);

    border-radius:7px;

    background:white;

    color:var(--text);

    outline:none;

    font-family:inherit;

    font-size:9px;

    transition:.2s;

}


input,
select {

    height:44px;

}


textarea {

    min-height:130px;

    resize:vertical;

}


input:focus,
select:focus,
textarea:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23,74,58,.08);

}


.help {

    margin-top:6px;

    color:var(--muted);

    font-size:7px;

}


/* =========================================================
   PROFILE IMAGE
========================================================= */

.image-upload {

    display:grid;

    grid-template-columns:
        120px 1fr;

    gap:18px;

    align-items:center;

}


.preview {

    width:120px;

    height:120px;

    display:flex;

    align-items:center;

    justify-content:center;

    overflow:hidden;

    border-radius:12px;

    background:
        #eef2f0;

    border:
        1px dashed
        #cbd5d0;

    color:var(--muted);

    font-size:32px;

}


.preview img {

    width:100%;

    height:100%;

    object-fit:cover;

}


.file-input {

    padding:10px;

    height:auto;

}


/* =========================================================
   STATUS
========================================================= */

.status-options {

    display:flex;

    gap:10px;

}


.status-option {

    position:relative;

}


.status-option input {

    position:absolute;

    opacity:0;

}


.status-label {

    display:block;

    padding:
        11px 16px;

    border:
        1px solid
        var(--border);

    border-radius:7px;

    cursor:pointer;

    font-size:8px;

    font-weight:700;

}


.status-option input:checked
+ .status-label {

    border-color:
        var(--primary);

    background:
        #f0f6f3;

    color:
        var(--primary);

}


/* =========================================================
   ACTIONS
========================================================= */

.form-actions {

    display:flex;

    justify-content:flex-end;

    gap:10px;

    margin-top:28px;

    padding-top:20px;

    border-top:
        1px solid
        #edf0ee;

}


.btn {

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:
        0 18px;

    border:none;

    border-radius:7px;

    cursor:pointer;

    text-decoration:none;

    font-size:8px;

    font-weight:700;

}


.btn-cancel {

    background:#eef1ef;

    color:var(--text);

}


.btn-submit {

    background:
        var(--primary);

    color:white;

}


.btn-submit:hover {

    background:
        var(--primary-dark);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:800px) {

    .sidebar {

        width:65px;

    }


    .logo {

        padding:0;

        justify-content:center;

        font-size:0;

    }


    .logo::after {

        content:"RE";

        font-size:14px;

    }


    .menu-title {

        display:none;

    }


    .menu a {

        justify-content:center;

        padding:0;

    }


    .menu a span:not(.icon) {

        display:none;

    }


    .main {

        margin-left:65px;

    }


    .admin-name {

        display:none;

    }

}


@media(max-width:650px) {

    .content {

        padding:
            20px 15px;

    }


    .form-grid {

        grid-template-columns:1fr;

    }


    .form-group.full {

        grid-column:auto;

    }


    .image-upload {

        grid-template-columns:1fr;

    }


    .preview {

        margin:auto;

    }


    .form-actions {

        flex-direction:column-reverse;

    }


    .form-actions .btn {

        width:100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
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
    <span>Dashboard</span>
</a>


<a href="properties.php">
    <span class="icon">🏠</span>
    <span>Properties</span>
</a>


<a href="users.php">
    <span class="icon">👥</span>
    <span>Users</span>
</a>


<a
    href="agents.php"
    class="active"
>
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


<!-- =====================================================
     MAIN
========================================================= -->

<div class="main">


<header class="topbar">


<div class="topbar-left">


<a
    href="agents.php"
    class="back"
>
    ←
</a>


<div>

<h1>
    Add Agent
</h1>

<p>
    Create a new real estate agent
</p>

</div>


</div>


<div class="admin">


<div class="admin-avatar">

<?php
echo safe($adminInitial);
?>

</div>


<div class="admin-name">

<?php
echo safe($adminName);
?>

</div>


</div>


</header>


<main class="content">


<div class="page-header">

<h2>
    Create New Agent
</h2>

<p>
    Add professional information and account credentials.
</p>

</div>


<!-- =====================================================
     ERRORS
========================================================= -->

<?php if (!empty($errors)): ?>

<div class="error-box">

<strong>
    ⚠ Please fix the following:
</strong>

<ul>

<?php foreach ($errors as $error): ?>

<li>
    <?php echo safe($error); ?>
</li>

<?php endforeach; ?>

</ul>

</div>

<?php endif; ?>


<!-- =====================================================
     FORM
========================================================= -->

<section class="form-card">


<div class="card-header">

<h3>
    Agent Information
</h3>

<p>
    Fields marked with * are required.
</p>

</div>


<form
    method="POST"
    enctype="multipart/form-data"
    id="agentForm"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo safe(
            $_SESSION["csrf_token"]
        );
    ?>"
>


<div class="form-body">


<div class="form-grid">


<!-- NAME -->

<div class="form-group">

<label for="name">

Full Name
<span class="required">*</span>

</label>


<input
    type="text"
    id="name"
    name="name"
    value="<?php echo safe($name); ?>"
    placeholder="Enter agent name"
    maxlength="100"
    required
>

</div>


<!-- EMAIL -->

<div class="form-group">

<label for="email">

Email
<span class="required">*</span>

</label>


<input
    type="email"
    id="email"
    name="email"
    value="<?php echo safe($email); ?>"
    placeholder="agent@example.com"
    maxlength="150"
    required
>

</div>


<!-- PHONE -->

<div class="form-group">

<label for="phone">
    Phone Number
</label>


<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php echo safe($phone); ?>"
    placeholder="+91 98765 43210"
    maxlength="20"
>

</div>


<!-- PASSWORD -->

<div class="form-group">

<label for="password">

Login Password
<span class="required">*</span>

</label>


<input
    type="password"
    id="password"
    name="password"
    placeholder="Minimum 8 characters"
    minlength="8"
    required
>

</div>


<!-- EXPERIENCE -->

<div class="form-group">

<label for="experience">
    Experience
</label>


<input
    type="number"
    id="experience"
    name="experience"
    value="<?php echo safe($experience); ?>"
    placeholder="Years of experience"
    min="0"
    max="60"
>

<div class="help">
    Example: 5 years
</div>

</div>


<!-- SPECIALIZATION -->

<div class="form-group">

<label for="specialization">
    Specialization
</label>


<input
    type="text"
    id="specialization"
    name="specialization"
    value="<?php echo safe($specialization); ?>"
    placeholder="Residential, Commercial, Luxury..."
    maxlength="150"
>

</div>


<!-- LICENSE -->

<div class="form-group">

<label for="license_number">
    License Number
</label>


<input
    type="text"
    id="license_number"
    name="license_number"
    value="<?php echo safe($license_number); ?>"
    placeholder="Agent license number"
    maxlength="100"
>

</div>


<!-- STATUS -->

<div class="form-group">

<label>
    Account Status
</label>


<div class="status-options">


<div class="status-option">

<input
    type="radio"
    id="active"
    name="status"
    value="active"
    <?php
    echo $status === "active"
        ? "checked"
        : "";
    ?>
>


<label
    class="status-label"
    for="active"
>
    ● Active
</label>

</div>


<div class="status-option">

<input
    type="radio"
    id="inactive"
    name="status"
    value="inactive"
    <?php
    echo $status === "inactive"
        ? "checked"
        : "";
    ?>
>


<label
    class="status-label"
    for="inactive"
>
    ○ Inactive
</label>

</div>


</div>

</div>


<!-- PROFILE IMAGE -->

<div class="form-group full">

<label>
    Profile Image
</label>


<div class="image-upload">


<div
    class="preview"
    id="imagePreview"
>
    👤
</div>


<div>


<input
    type="file"
    name="profile_image"
    id="profile_image"
    class="file-input"
    accept="image/jpeg,image/png,image/webp"
>


<div class="help">

JPG, PNG or WEBP.
Maximum file size: 5MB.

</div>


</div>


</div>

</div>


<!-- BIO -->

<div class="form-group full">

<label for="bio">
    Agent Biography
</label>


<textarea
    id="bio"
    name="bio"
    maxlength="1000"
    placeholder="Write a short professional biography..."
><?php
echo safe($bio);
?></textarea>


<div class="help">
    Maximum 1000 characters.
</div>

</div>


</div>


<!-- ACTIONS -->

<div class="form-actions">


<a
    href="agents.php"
    class="btn btn-cancel"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-submit"
    id="submitButton"
>
    ✓ Create Agent
</button>


</div>


</div>


</form>


</section>


</main>


</div>


<script>

/* =========================================================
   IMAGE PREVIEW
========================================================= */

const imageInput =
    document.getElementById(
        "profile_image"
    );


const imagePreview =
    document.getElementById(
        "imagePreview"
    );


imageInput.addEventListener(
    "change",
    function() {

        const file =
            this.files[0];


        if (!file) {

            imagePreview.innerHTML =
                "👤";

            return;

        }


        if (
            !file.type.startsWith(
                "image/"
            )
        ) {

            alert(
                "Please select a valid image."
            );

            this.value = "";

            imagePreview.innerHTML =
                "👤";

            return;

        }


        if (
            file.size >
            5 * 1024 * 1024
        ) {

            alert(
                "Image must be less than 5MB."
            );

            this.value = "";

            imagePreview.innerHTML =
                "👤";

            return;

        }


        const reader =
            new FileReader();


        reader.onload =
            function(event) {

                imagePreview.innerHTML =
                    `<img src="${event.target.result}" alt="Preview">`;

            };


        reader.readAsDataURL(file);

    }
);


/* =========================================================
   FORM SUBMIT
========================================================= */

const agentForm =
    document.getElementById(
        "agentForm"
    );


agentForm.addEventListener(
    "submit",
    function() {

        const button =
            document.getElementById(
                "submitButton"
            );


        button.disabled =
            true;

        button.textContent =
            "Creating Agent...";

    }
);


/* =========================================================
   BIO CHARACTER COUNTER
========================================================= */

const bio =
    document.getElementById(
        "bio"
    );


bio.addEventListener(
    "input",
    function() {

        if (
            this.value.length >= 1000
        ) {

            this.value =
                this.value.substring(
                    0,
                    1000
                );

        }

    }
);

</script>


</body>

</html>