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
        bin2hex(
            random_bytes(32)
        );
}


/* =========================================================
   GET AGENT ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {

    $_SESSION["error"] =
        "Invalid agent ID.";

    header("Location: agents.php");
    exit;
}


/* =========================================================
   GET AGENT
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        email,
        profile_image
     FROM agents
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    exit("Database error.");
}

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result =
    $stmt->get_result();

$agent =
    $result->fetch_assoc();

$stmt->close();


if (!$agent) {

    $_SESSION["error"] =
        "Agent not found.";

    header("Location: agents.php");
    exit;
}


/* =========================================================
   DELETE PROCESS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken =
        $_POST["csrf_token"] ?? "";


    /* =====================================================
       CSRF VALIDATION
    ===================================================== */

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $csrfToken
        )
    ) {

        $_SESSION["error"] =
            "Security verification failed.";

        header(
            "Location: agent-delete.php?id="
            . $id
        );

        exit;
    }


    /* =====================================================
       CONFIRM DELETE
    ===================================================== */

    $confirm =
        $_POST["confirm_delete"] ?? "";

    if ($confirm !== "yes") {

        $_SESSION["error"] =
            "Delete operation was cancelled.";

        header(
            "Location: agent-delete.php?id="
            . $id
        );

        exit;
    }


    /* =====================================================
       DELETE AGENT
    ===================================================== */

    $delete =
        $conn->prepare(
            "DELETE FROM agents
             WHERE id = ?
             LIMIT 1"
        );


    if (!$delete) {

        $_SESSION["error"] =
            "Database error.";

        header(
            "Location: agent-delete.php?id="
            . $id
        );

        exit;
    }


    $delete->bind_param(
        "i",
        $id
    );


    if ($delete->execute()) {

        $delete->close();


        /* =================================================
           DELETE PROFILE IMAGE
        ================================================= */

        $profileImage =
            $agent["profile_image"]
            ?? "";


        if ($profileImage !== "") {

            $imagePath =
                "../uploads/agents/"
                . basename(
                    $profileImage
                );


            if (
                is_file(
                    $imagePath
                )
            ) {

                @unlink(
                    $imagePath
                );

            }

        }


        /* =================================================
           SUCCESS
        ================================================= */

        $_SESSION["success"] =
            "Agent '"
            . $agent["name"]
            . "' was deleted successfully.";


        header(
            "Location: agents.php"
        );

        exit;

    }


    /* =====================================================
       DELETE FAILED
    ================================================= */

    $delete->close();


    $_SESSION["error"] =
        "Unable to delete the agent.";

    header(
        "Location: agent-delete.php?id="
        . $id
    );

    exit;
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
    Delete Agent | RealEstate
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

    --red:#b43843;
    --red-dark:#922c36;

    --red-bg:#fdebed;

    --bg:#f4f6f5;

    --white:#ffffff;

    --text:#18231f;

    --muted:#737c78;

    --border:#dfe6e2;

}


body {

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

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
   CONTAINER
========================================================= */

.delete-container {

    width:100%;

    max-width:560px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:
        var(--white);

    border:
        1px solid
        var(--border);

    border-radius:14px;

    overflow:hidden;

    box-shadow:
        0 15px 45px
        rgba(20,50,40,.08);

}


/* =========================================================
   HEADER
========================================================= */

.header {

    padding:25px;

    text-align:center;

    border-bottom:
        1px solid
        var(--border);

}


.warning-icon {

    width:65px;
    height:65px;

    display:flex;

    align-items:center;
    justify-content:center;

    margin:
        0 auto 15px;

    border-radius:50%;

    background:
        var(--red-bg);

    color:
        var(--red);

    font-size:28px;

}


.header h1 {

    font-size:21px;

}


.header p {

    margin-top:7px;

    color:
        var(--muted);

    font-size:9px;

}


/* =========================================================
   BODY
========================================================= */

.body {

    padding:25px;

}


/* =========================================================
   AGENT
========================================================= */

.agent {

    display:flex;

    align-items:center;

    gap:15px;

    padding:16px;

    margin-bottom:20px;

    background:
        #f7f9f8;

    border:
        1px solid
        var(--border);

    border-radius:9px;

}


.agent-image {

    width:60px;
    height:60px;

    display:flex;

    align-items:center;
    justify-content:center;

    flex-shrink:0;

    overflow:hidden;

    border-radius:50%;

    background:
        #e6eeea;

    color:
        var(--primary);

    font-size:21px;

    font-weight:800;

}


.agent-image img {

    width:100%;
    height:100%;

    object-fit:cover;

}


.agent-name {

    font-size:12px;

    font-weight:800;

}


.agent-email {

    margin-top:5px;

    color:
        var(--muted);

    font-size:8px;

}


/* =========================================================
   WARNING
========================================================= */

.warning {

    padding:16px;

    margin-bottom:20px;

    background:
        var(--red-bg);

    border:
        1px solid
        #f3ccd1;

    border-radius:8px;

    color:
        var(--red);

    font-size:8px;

    line-height:1.7;

}


.warning strong {

    display:block;

    margin-bottom:4px;

}


/* =========================================================
   CHECKBOX
========================================================= */

.confirm-box {

    display:flex;

    align-items:flex-start;

    gap:10px;

    margin-bottom:20px;

    padding:14px;

    background:#f8faf9;

    border:
        1px solid
        var(--border);

    border-radius:8px;

}


.confirm-box input {

    width:17px;
    height:17px;

    margin-top:1px;

    accent-color:
        var(--red);

    cursor:pointer;

}


.confirm-box label {

    color:#424b47;

    font-size:8px;

    line-height:1.6;

    cursor:pointer;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:10px;

}


.btn {

    height:45px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:none;

    border-radius:7px;

    text-decoration:none;

    cursor:pointer;

    font-size:8px;

    font-weight:800;

    transition:.2s;

}


.btn-cancel {

    background:
        #e9eeeb;

    color:
        var(--text);

}


.btn-cancel:hover {

    background:
        #dfe6e2;

}


.btn-delete {

    background:
        var(--red);

    color:white;

}


.btn-delete:hover {

    background:
        var(--red-dark);

}


.btn-delete:disabled {

    opacity:.5;

    cursor:not-allowed;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:center;

    padding:16px 25px;

    border-top:
        1px solid
        var(--border);

    color:
        var(--muted);

    font-size:7px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:500px) {

    .actions {

        grid-template-columns:1fr;

    }

    .header {

        padding:20px;

    }

    .body {

        padding:20px;

    }

}

</style>

</head>


<body>


<div class="delete-container">


<div class="card">


<!-- =====================================================
     HEADER
========================================================= -->

<div class="header">


<div class="warning-icon">

⚠

</div>


<h1>
    Delete Agent?
</h1>


<p>
    This action cannot be undone.
</p>


</div>


<!-- =====================================================
     BODY
========================================================= -->

<div class="body">


<!-- AGENT INFORMATION -->

<div class="agent">


<div class="agent-image">


<?php

$profileImage =
    $agent["profile_image"]
    ?? "";

$imagePath = "";

if ($profileImage !== "") {

    $imagePath =
        "../uploads/agents/"
        . basename(
            $profileImage
        );

}


if (
    $imagePath
    &&
    is_file($imagePath)
):

?>

<img
    src="<?php echo safe($imagePath); ?>"
    alt="<?php echo safe($agent["name"]); ?>"
>


<?php else: ?>

<?php

echo safe(
    strtoupper(
        substr(
            $agent["name"],
            0,
            1
        )
    )
);

?>

<?php endif; ?>


</div>


<div>


<div class="agent-name">

<?php
echo safe(
    $agent["name"]
);
?>

</div>


<div class="agent-email">

<?php
echo safe(
    $agent["email"]
);
?>

</div>


</div>


</div>


<!-- WARNING -->

<div class="warning">

<strong>
    ⚠ Permanent deletion
</strong>

Deleting this agent will permanently remove
their account and profile information from the
RealEstate system.

</div>


<!-- =====================================================
     FORM
========================================================= -->

<form
    method="POST"
    id="deleteForm"
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


<input
    type="hidden"
    name="confirm_delete"
    value="yes"
>


<div class="confirm-box">


<input
    type="checkbox"
    id="confirm"
    required
>


<label for="confirm">

I understand that deleting
<strong>
    <?php echo safe($agent["name"]); ?>
</strong>
will permanently remove this agent
and this action cannot be undone.

</label>


</div>


<div class="actions">


<a
    href="agent-details.php?id=<?php echo (int)$id; ?>"
    class="btn btn-cancel"
>
    ← Cancel
</a>


<button
    type="submit"
    class="btn btn-delete"
    id="deleteButton"
    disabled
>
    🗑 Delete Agent
</button>


</div>


</form>


</div>


<!-- FOOTER -->

<div class="footer">

RealEstate Administration Panel

</div>


</div>


</div>


<script>

/* =========================================================
   CONFIRMATION
========================================================= */

const checkbox =
    document.getElementById(
        "confirm"
    );

const deleteButton =
    document.getElementById(
        "deleteButton"
    );


checkbox.addEventListener(
    "change",
    function () {

        deleteButton.disabled =
            !this.checked;

    }
);


/* =========================================================
   FINAL CONFIRMATION
========================================================= */

const form =
    document.getElementById(
        "deleteForm"
    );


form.addEventListener(
    "submit",
    function (event) {

        if (!checkbox.checked) {

            event.preventDefault();

            return;

        }


        const confirmed =
            confirm(
                "Are you absolutely sure you want to delete this agent?"
            );


        if (!confirmed) {

            event.preventDefault();

            return;

        }


        deleteButton.disabled =
            true;

        deleteButton.textContent =
            "Deleting...";

    }
);

</script>


</body>

</html>