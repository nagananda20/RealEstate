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

$userId = (int)$_SESSION["user_id"];


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

$name = trim(
    $_POST["name"] ?? ""
);

$phone = trim(
    $_POST["phone"] ?? ""
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


if ($name === "") {

    echo json_encode([
        "success" => false,
        "message" => "Please enter your name."
    ]);

    exit;
}


if ($phone === "") {

    echo json_encode([
        "success" => false,
        "message" => "Please enter your phone number."
    ]);

    exit;
}


if (strlen($phone) < 10) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid phone number."
    ]);

    exit;
}


if ($message === "") {

    $message =
        "I am interested in this property.";
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
    ? (int)$property["agent_id"]
    : null;


/*
|--------------------------------------------------------------------------
| Insert Enquiry
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO enquiries
    (
        user_id,
        property_id,
        agent_id,
        name,
        phone,
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
        'new',
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
    $name,
    $phone,
    $message
);


/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Your enquiry has been sent successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to send enquiry. Please try again."
    ]);

}


$stmt->close();

$conn->close();

?>