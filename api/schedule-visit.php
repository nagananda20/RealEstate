<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Get Form Data
|--------------------------------------------------------------------------
*/

$propertyId = filter_input(
    INPUT_POST,
    "property_id",
    FILTER_VALIDATE_INT
);

$visitDate = trim(
    $_POST["visit_date"] ?? ""
);

$visitTime = trim(
    $_POST["visit_time"] ?? ""
);

$message = trim(
    $_POST["message"] ?? ""
);


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (!$propertyId) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid property."
    ]);

    exit;
}


if ($visitDate === "") {

    echo json_encode([
        "success" => false,
        "message" => "Please select a visit date."
    ]);

    exit;
}


if ($visitTime === "") {

    echo json_encode([
        "success" => false,
        "message" => "Please select a visit time."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Date
|--------------------------------------------------------------------------
*/

$dateObject = DateTime::createFromFormat(
    "Y-m-d",
    $visitDate
);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $visitDate
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid visit date."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent Past Date
|--------------------------------------------------------------------------
*/

$today = new DateTime(
    date("Y-m-d")
);

$selectedDate = new DateTime(
    $visitDate
);

if ($selectedDate < $today) {

    echo json_encode([
        "success" => false,
        "message" => "Please select a future date."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Time
|--------------------------------------------------------------------------
*/

$allowedTimes = [
    "09:00",
    "11:00",
    "13:00",
    "15:00",
    "17:00"
];

if (!in_array($visitTime, $allowedTimes, true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid visit time."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Property
|--------------------------------------------------------------------------
*/

$propertySQL = "
    SELECT
        id,
        title,
        agent_id
    FROM properties
    WHERE id = ?
      AND status = 'published'
    LIMIT 1
";

$propertyStmt =
    $conn->prepare($propertySQL);

$propertyStmt->bind_param(
    "i",
    $propertyId
);

$propertyStmt->execute();

$propertyResult =
    $propertyStmt->get_result();

$property =
    $propertyResult->fetch_assoc();

$propertyStmt->close();


if (!$property) {

    echo json_encode([
        "success" => false,
        "message" => "Property not found."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Agent
|--------------------------------------------------------------------------
*/

$agentId = !empty($property["agent_id"])
    ? (int) $property["agent_id"]
    : null;


/*
|--------------------------------------------------------------------------
| Check Existing Booking
|--------------------------------------------------------------------------
*/

$checkSQL = "
    SELECT id
    FROM visits
    WHERE property_id = ?
      AND visit_date = ?
      AND visit_time = ?
      AND status IN ('pending', 'confirmed')
    LIMIT 1
";

$checkStmt =
    $conn->prepare($checkSQL);

$checkStmt->bind_param(
    "iss",
    $propertyId,
    $visitDate,
    $visitTime
);

$checkStmt->execute();

$checkResult =
    $checkStmt->get_result();


if ($checkResult->num_rows > 0) {

    $checkStmt->close();

    echo json_encode([
        "success" => false,
        "message" =>
            "This time slot is already booked. Please choose another time."
    ]);

    exit;
}

$checkStmt->close();


/*
|--------------------------------------------------------------------------
| Insert Visit
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO visits
    (
        user_id,
        property_id,
        agent_id,
        visit_date,
        visit_time,
        message,
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
        'pending',
        NOW()
    )
";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(
    "iiisss",
    $userId,
    $propertyId,
    $agentId,
    $visitDate,
    $visitTime,
    $message
);


/*
|--------------------------------------------------------------------------
| Save Visit
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Property visit requested successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to schedule visit. Please try again."
    ]);

}


$stmt->close();

$conn->close();

?>