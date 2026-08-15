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


/* =========================================================
   ADMIN CHECK
========================================================= */

if (
    ($_SESSION["user_role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================================================
   GET VISIT ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


/* =========================================================
   INVALID ID
========================================================= */

if (!$id) {

    $_SESSION["error"] =
        "Invalid visit ID.";

    header(
        "Location: visits.php"
    );

    exit;
}


/* =========================================================
   CHECK VISITS TABLE
========================================================= */

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'visits'"
    );


if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {

    $_SESSION["error"] =
        "Visits table does not exist.";

    header(
        "Location: visits.php"
    );

    exit;
}


/* =========================================================
   CHECK VISIT EXISTS
========================================================= */

$stmt =
    $conn->prepare(
        "SELECT id FROM visits WHERE id = ? LIMIT 1"
    );


if (!$stmt) {

    $_SESSION["error"] =
        "Unable to verify appointment.";

    header(
        "Location: visits.php"
    );

    exit;
}


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


$result =
    $stmt->get_result();


$visit =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   NOT FOUND
========================================================= */

if (!$visit) {

    $_SESSION["error"] =
        "Visit appointment was not found.";

    header(
        "Location: visits.php"
    );

    exit;
}


/* =========================================================
   DELETE
========================================================= */

$stmt =
    $conn->prepare(
        "DELETE FROM visits WHERE id = ?"
    );


if (!$stmt) {

    $_SESSION["error"] =
        "Unable to prepare delete request.";

    header(
        "Location: visits.php"
    );

    exit;
}


$stmt->bind_param(
    "i",
    $id
);


/* =========================================================
   EXECUTE
========================================================= */

if ($stmt->execute()) {

    if (
        $stmt->affected_rows > 0
    ) {

        $_SESSION["success"] =
            "Visit appointment deleted successfully.";

    } else {

        $_SESSION["error"] =
            "Visit could not be deleted.";

    }

} else {

    $_SESSION["error"] =
        "Database error while deleting the visit.";

}


$stmt->close();


/* =========================================================
   REDIRECT
========================================================= */

header(
    "Location: visits.php"
);

exit;

?>