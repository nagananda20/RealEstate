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
   GET USER ID
========================================================= */

$userId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$userId || $userId <= 0) {
    header("Location: users.php?error=invalid_id");
    exit;
}


/* =========================================================
   PREVENT SELF DELETE
========================================================= */

if (
    $userId === (int)$_SESSION["user_id"]
) {
    header(
        "Location: user-details.php?id=" .
        $userId .
        "&error=self_delete"
    );
    exit;
}


/* =========================================================
   FETCH USER
========================================================= */

$sql = "
    SELECT
        id,
        name,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit("Database error.");
}

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result =
    $stmt->get_result();

$user =
    $result->fetch_assoc();

$stmt->close();


if (!$user) {
    header(
        "Location: users.php?error=user_not_found"
    );
    exit;
}


/* =========================================================
   DELETE PROCESS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $confirm =
        $_POST["confirm_delete"] ?? "";

    if ($confirm !== "DELETE") {

        header(
            "Location: user-delete.php?id=" .
            $userId .
            "&error=confirmation"
        );

        exit;
    }


    /*
     * Start transaction so related records can be
     * removed safely.
     */

    $conn->begin_transaction();


    try {

        /* =================================================
           DELETE FAVORITES
        ================================================= */

        $table =
            $conn->query(
                "SHOW TABLES LIKE 'favorites'"
            );

        if (
            $table &&
            $table->num_rows > 0
        ) {

            $sql = "
                DELETE FROM favorites
                WHERE user_id = ?
            ";

            $stmt =
                $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $userId
                );

                $stmt->execute();

                $stmt->close();
            }
        }


        /* =================================================
           DELETE ENQUIRIES
        ================================================= */

        $table =
            $conn->query(
                "SHOW TABLES LIKE 'enquiries'"
            );

        if (
            $table &&
            $table->num_rows > 0
        ) {

            $sql = "
                DELETE FROM enquiries
                WHERE user_id = ?
            ";

            $stmt =
                $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $userId
                );

                $stmt->execute();

                $stmt->close();
            }
        }


        /* =================================================
           DELETE VISITS
        ================================================= */

        $table =
            $conn->query(
                "SHOW TABLES LIKE 'visits'"
            );

        if (
            $table &&
            $table->num_rows > 0
        ) {

            $sql = "
                DELETE FROM visits
                WHERE user_id = ?
            ";

            $stmt =
                $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $userId
                );

                $stmt->execute();

                $stmt->close();
            }
        }


        /* =================================================
           HANDLE PROPERTIES
        ================================================= */

        /*
         * We do NOT automatically delete properties.
         *
         * Instead, if the properties table contains
         * agent_id, we set it to NULL.
         */

        $table =
            $conn->query(
                "SHOW TABLES LIKE 'properties'"
            );

        if (
            $table &&
            $table->num_rows > 0
        ) {

            /*
             * Check whether agent_id exists.
             */

            $columnCheck = $conn->query(
                "SHOW COLUMNS FROM properties LIKE 'agent_id'"
            );

            if (
                $columnCheck &&
                $columnCheck->num_rows > 0
            ) {

                $sql = "
                    UPDATE properties
                    SET agent_id = NULL
                    WHERE agent_id = ?
                ";

                $stmt =
                    $conn->prepare($sql);

                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $userId
                    );

                    $stmt->execute();

                    $stmt->close();
                }
            }
        }


        /* =================================================
           DELETE USER
        ================================================= */

        $sql = "
            DELETE FROM users
            WHERE id = ?
            LIMIT 1
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {

            throw new Exception(
                "Unable to prepare delete query."
            );
        }


        $stmt->bind_param(
            "i",
            $userId
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Unable to delete user."
            );
        }


        if ($stmt->affected_rows !== 1) {

            throw new Exception(
                "User was not deleted."
            );
        }


        $stmt->close();


        /* =================================================
           COMMIT
        ================================================= */

        $conn->commit();


        /*
         * Redirect back to users list.
         */

        header(
            "Location: users.php?success=user_deleted"
        );

        exit;

    }
    catch (Throwable $e) {

        /*
         * Rollback if anything fails.
         */

        $conn->rollback();


        header(
            "Location: user-delete.php?id=" .
            $userId .
            "&error=delete_failed"
        );

        exit;
    }
}


/* =========================================================
   ERROR MESSAGE
========================================================= */

$error = "";

if (
    isset($_GET["error"])
) {

    switch ($_GET["error"]) {

        case "confirmation":

            $error =
                "Please type DELETE exactly to confirm.";

            break;


        case "delete_failed":

            $error =
                "The user could not be deleted. Please try again.";

            break;


        default:

            $error =
                "An error occurred.";

            break;
    }
}


/* =========================================================
   USER INITIAL
========================================================= */

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
    Delete User | RealEstate
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --primary: #174a3a;

    --primary-dark: #10372b;

    --accent: #d7a94b;

    --bg: #f4f6f5;

    --white: #ffffff;

    --text: #18231f;

    --muted: #737c78;

    --border: #dfe6e2;

    --red: #b43843;

    --red-dark: #8f2630;

    --red-bg: #fdebed;

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

    top: 0;

    left: 0;

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

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: 1.5px;

}


.menu {

    padding:
        0 12px;

}


.menu a {

    height: 44px;

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        0 13px;

    margin-bottom: 3px;

    border-radius: 7px;

    color:
        rgba(255,255,255,.7);

    text-decoration: none;

    font-size: 10px;

}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.1);

    color: white;

}


.icon {

    width: 20px;

    text-align: center;

}


.sidebar-bottom {

    position: absolute;

    left: 0;

    right: 0;

    bottom: 0;

    padding: 15px;

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


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 75px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 30px;

    background:
        white;

    border-bottom:
        1px solid
        var(--border);

}


.top-left {

    display: flex;

    align-items: center;

    gap: 12px;

}


.back {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        var(--border);

    border-radius: 7px;

    text-decoration: none;

    color:
        var(--text);

}


.topbar h1 {

    font-size: 18px;

}


.topbar p {

    margin-top: 4px;

    color:
        var(--muted);

    font-size: 8px;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    min-height:
        calc(100vh - 75px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding:
        30px;

}


/* =========================================================
   DELETE CARD
========================================================= */

.delete-card {

    width:
        100%;

    max-width:
        600px;

    background:
        white;

    border:
        1px solid
        #efd0d3;

    border-radius:
        12px;

    overflow:
        hidden;

    box-shadow:
        0 15px 40px
        rgba(30,40,35,.08);

}


/* =========================================================
   HEADER
========================================================= */

.delete-header {

    padding:
        30px;

    text-align:
        center;

    background:
        var(--red-bg);

    border-bottom:
        1px solid
        #f1d4d7;

}


.warning-icon {

    width:
        65px;

    height:
        65px;

    margin:
        0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius:
        50%;

    background:
        var(--red);

    color:
        white;

    font-size:
        28px;

}


.delete-header h2 {

    color:
        var(--red-dark);

    font-size:
        20px;

}


.delete-header p {

    max-width:
        430px;

    margin:
        8px auto 0;

    color:
        #7d5559;

    font-size:
        8px;

    line-height:
        1.6;

}


/* =========================================================
   USER
========================================================= */

.user-preview {

    margin:
        25px 30px;

    padding:
        18px;

    display: flex;

    align-items: center;

    gap:
        15px;

    background:
        #fafbfa;

    border:
        1px solid
        var(--border);

    border-radius:
        8px;

}


.avatar {

    width:
        55px;

    height:
        55px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius:
        50%;

    background:
        var(--primary);

    color:
        white;

    font-size:
        19px;

    font-weight:
        800;

}


.user-name {

    font-size:
        11px;

    font-weight:
        800;

}


.user-email {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        8px;

}


.user-role {

    display:
        inline-block;

    margin-top:
        7px;

    padding:
        4px 8px;

    background:
        #edf3ff;

    color:
        #365caa;

    border-radius:
        20px;

    font-size:
        7px;

    font-weight:
        700;

}


/* =========================================================
   BODY
========================================================= */

.delete-body {

    padding:
        0 30px 30px;

}


.warning-box {

    padding:
        15px;

    background:
        #fff8f8;

    border:
        1px solid
        #f1d4d7;

    border-radius:
        7px;

    margin-bottom:
        20px;

}


.warning-box strong {

    display:
        block;

    margin-bottom:
        7px;

    color:
        var(--red-dark);

    font-size:
        8px;

}


.warning-box ul {

    padding-left:
        18px;

    color:
        #76585c;

    font-size:
        8px;

    line-height:
        1.8;

}


/* =========================================================
   CONFIRMATION
========================================================= */

.confirm-label {

    display:
        block;

    margin-bottom:
        7px;

    font-size:
        8px;

    font-weight:
        700;

}


.confirm-label span {

    color:
        var(--red);

}


input {

    width:
        100%;

    height:
        44px;

    padding:
        0 12px;

    border:
        1px solid
        var(--border);

    border-radius:
        6px;

    outline:
        none;

    font-size:
        9px;

}


input:focus {

    border-color:
        var(--red);

    box-shadow:
        0 0 0 3px
        rgba(180,56,67,.08);

}


.help {

    margin-top:
        6px;

    color:
        var(--muted);

    font-size:
        7px;

}


/* =========================================================
   ERROR
========================================================= */

.error {

    margin-bottom:
        18px;

    padding:
        12px 14px;

    border-radius:
        6px;

    background:
        var(--red-bg);

    color:
        var(--red);

    border:
        1px solid
        #f0c7ca;

    font-size:
        8px;

}


/* =========================================================
   BUTTONS
========================================================= */

.actions {

    display:
        flex;

    gap:
        8px;

    margin-top:
        22px;

}


.btn {

    flex: 1;

    height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border:
        none;

    border-radius:
        6px;

    text-decoration:
        none;

    font-size:
        8px;

    font-weight:
        700;

    cursor:
        pointer;

}


.btn-cancel {

    background:
        #eef1ef;

    color:
        var(--text);

}


.btn-delete {

    background:
        var(--red);

    color:
        white;

}


.btn-delete:hover {

    background:
        var(--red-dark);

}


/* =========================================================
   RESPONSIVE
========================================================= */

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
            15px;

    }


    .delete-header {

        padding:
            25px 18px;

    }


    .user-preview {

        margin:
            20px 18px;

    }


    .delete-body {

        padding:
            0 18px 20px;

    }


    .actions {

        flex-direction:
            column-reverse;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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


<a
    href="users.php"
    class="active"
>
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
===================================================== -->

<div class="main">


<header class="topbar">


<div class="top-left">


<a
    href="user-details.php?id=<?php echo (int)$userId; ?>"
    class="back"
>
    ←
</a>


<div>

<h1>
    Delete User
</h1>

<p>
    Permanent account deletion
</p>

</div>


</div>


</header>


<main class="content">


<section class="delete-card">


<!-- =====================================================
     WARNING HEADER
===================================================== -->

<div class="delete-header">


<div class="warning-icon">
    ⚠️
</div>


<h2>
    Delete User Account?
</h2>


<p>

This action is permanent. Once the account is deleted,
it cannot be restored.

</p>


</div>


<!-- =====================================================
     USER PREVIEW
===================================================== -->

<div class="user-preview">


<div class="avatar">

<?php
echo safe($initial);
?>

</div>


<div>

<div class="user-name">

<?php
echo safe($user["name"]);
?>

</div>


<div class="user-email">

<?php
echo safe($user["email"]);
?>

</div>


<div class="user-role">

<?php

echo ucfirst(
    safe(
        $user["role"]
    )
);

?>

</div>


</div>


</div>


<!-- =====================================================
     BODY
===================================================== -->

<div class="delete-body">


<?php if ($error !== ""): ?>

<div class="error">

⚠️

<?php
echo safe($error);
?>

</div>

<?php endif; ?>


<div class="warning-box">


<strong>
    The following data may be affected:
</strong>


<ul>

<li>
    User account and login credentials
</li>

<li>
    User enquiries and visits
</li>

<li>
    Favorite property records
</li>

<li>
    Agent association will be removed from properties
</li>

</ul>


</div>


<form
    method="POST"
    id="deleteForm"
>


<label
    for="confirm_delete"
    class="confirm-label"
>

Type

<span>
    DELETE
</span>

to confirm

</label>


<input
    type="text"
    id="confirm_delete"
    name="confirm_delete"
    placeholder="Type DELETE"
    autocomplete="off"
    required
>


<div class="help">

The confirmation text is case-sensitive.

</div>


<div class="actions">


<a
    href="user-details.php?id=<?php echo (int)$userId; ?>"
    class="btn btn-cancel"
>
    Cancel
</a>


<button
    type="submit"
    class="btn btn-delete"
>
    🗑️ Delete Permanently
</button>


</div>


</form>


</div>


</section>


</main>


</div>


<script>

/* =========================================================
   DELETE CONFIRMATION
========================================================= */

const form =
    document.getElementById(
        "deleteForm"
    );


const input =
    document.getElementById(
        "confirm_delete"
    );


form.addEventListener(
    "submit",
    function(event) {

        if (
            input.value !==
            "DELETE"
        ) {

            event.preventDefault();

            alert(
                "Please type DELETE exactly to confirm."
            );

            input.focus();

            return;

        }


        const finalConfirm =
            confirm(
                "FINAL WARNING\n\n" +
                "Are you absolutely sure you want to permanently delete this user?"
            );


        if (!finalConfirm) {

            event.preventDefault();

        }

    }
);

</script>


</body>

</html>