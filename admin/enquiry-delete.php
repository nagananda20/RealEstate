<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   ADMIN AUTHENTICATION
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
   GET ENQUIRY ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: enquiries.php");
    exit;
}


/* =========================================================
   CHECK ENQUIRY EXISTS
========================================================= */

$check = $conn->prepare(
    "SELECT id FROM enquiries WHERE id = ? LIMIT 1"
);

if (!$check) {
    $_SESSION["error"] =
        "Database error.";

    header("Location: enquiries.php");
    exit;
}

$check->bind_param(
    "i",
    $id
);

$check->execute();

$result =
    $check->get_result();

$exists =
    $result->fetch_assoc();

$check->close();


if (!$exists) {

    $_SESSION["error"] =
        "Enquiry not found.";

    header("Location: enquiries.php");

    exit;
}


/* =========================================================
   DELETE ENQUIRY
========================================================= */

$stmt = $conn->prepare(
    "DELETE FROM enquiries WHERE id = ?"
);

if (!$stmt) {

    $_SESSION["error"] =
        "Unable to prepare delete request.";

    header("Location: enquiries.php");

    exit;
}


$stmt->bind_param(
    "i",
    $id
);


if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        $_SESSION["success"] =
            "Enquiry #"
            . $id
            . " deleted successfully.";

    } else {

        $_SESSION["error"] =
            "Enquiry could not be deleted.";

    }

} else {

    $_SESSION["error"] =
        "Unable to delete enquiry.";

}


$stmt->close();


/* =========================================================
   REDIRECT
========================================================= */

header(
    "Location: enquiries.php"
);

exit;

?>