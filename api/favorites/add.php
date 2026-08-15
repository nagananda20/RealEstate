<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only POST method allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST method is allowed"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Read JSON data
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get User ID and Property ID
|--------------------------------------------------------------------------
*/

$user_id = isset($data["user_id"])
    ? (int)$data["user_id"]
    : 0;

$property_id = isset($data["property_id"])
    ? (int)$data["property_id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate IDs
|--------------------------------------------------------------------------
*/

if ($user_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid user ID is required"
    ]);

    exit;
}

if ($property_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid property ID is required"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check User Exists
    |--------------------------------------------------------------------------
    */

    $userCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userCheck->execute([
        $user_id
    ]);

    if (!$userCheck->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Property Exists
    |--------------------------------------------------------------------------
    */

    $propertyCheck = $pdo->prepare("
        SELECT id
        FROM properties
        WHERE id = ?
        LIMIT 1
    ");

    $propertyCheck->execute([
        $property_id
    ]);

    if (!$propertyCheck->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Property not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Existing Favorite
    |--------------------------------------------------------------------------
    */

    $favoriteCheck = $pdo->prepare("
        SELECT id
        FROM favorites
        WHERE user_id = ?
        AND property_id = ?
        LIMIT 1
    ");

    $favoriteCheck->execute([
        $user_id,
        $property_id
    ]);

    if ($favoriteCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Property is already in favorites"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Add Favorite
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO favorites
        (
            user_id,
            property_id
        )

        VALUES
        (
            ?,
            ?
        )
    ");

    $stmt->execute([
        $user_id,
        $property_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get Favorite ID
    |--------------------------------------------------------------------------
    */

    $favorite_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Property added to favorites",
        "favorite_id" => $favorite_id,
        "user_id" => $user_id,
        "property_id" => $property_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add favorite",
        "error" => $e->getMessage()
    ]);
}

?>