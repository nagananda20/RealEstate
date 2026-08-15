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
| Get JSON Request
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);


$propertyId = isset($data["property_id"])
    ? (int)$data["property_id"]
    : 0;


$userId = (int)$_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Validate Property ID
|--------------------------------------------------------------------------
*/

if ($propertyId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid property."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Property Exists
|--------------------------------------------------------------------------
*/

$propertySQL = "
    SELECT id
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


if ($propertyResult->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Property not found."
    ]);

    $propertyStmt->close();

    exit;
}


$propertyStmt->close();


/*
|--------------------------------------------------------------------------
| Check Existing Favorite
|--------------------------------------------------------------------------
*/

$checkSQL = "
    SELECT id
    FROM favorites
    WHERE user_id = ?
      AND property_id = ?
    LIMIT 1
";

$checkStmt =
    $conn->prepare($checkSQL);

$checkStmt->bind_param(
    "ii",
    $userId,
    $propertyId
);

$checkStmt->execute();

$checkResult =
    $checkStmt->get_result();


/*
|--------------------------------------------------------------------------
| Remove Favorite
|--------------------------------------------------------------------------
*/

if ($checkResult->num_rows > 0) {

    $favorite =
        $checkResult->fetch_assoc();

    $favoriteId =
        (int)$favorite["id"];


    $deleteSQL = "
        DELETE FROM favorites
        WHERE id = ?
          AND user_id = ?
    ";

    $deleteStmt =
        $conn->prepare($deleteSQL);

    $deleteStmt->bind_param(
        "ii",
        $favoriteId,
        $userId
    );

    $deleteStmt->execute();

    $deleteStmt->close();

    $checkStmt->close();


    echo json_encode([
        "success" => true,
        "action" => "removed",
        "message" => "Property removed from favorites."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Add Favorite
|--------------------------------------------------------------------------
*/

$insertSQL = "
    INSERT INTO favorites
    (
        user_id,
        property_id,
        created_at
    )
    VALUES
    (
        ?,
        ?,
        NOW()
    )
";


$insertStmt =
    $conn->prepare($insertSQL);


$insertStmt->bind_param(
    "ii",
    $userId,
    $propertyId
);


if ($insertStmt->execute()) {

    echo json_encode([
        "success" => true,
        "action" => "added",
        "message" => "Property added to favorites."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to save favorite."
    ]);

}


$insertStmt->close();

$checkStmt->close();

$conn->close();

?>