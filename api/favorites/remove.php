<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only DELETE / POST method allowed
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] !== "DELETE" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only DELETE or POST method is allowed"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Request Data
|--------------------------------------------------------------------------
*/

$data = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($data)) {
        $data = [];
    }
}


/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
*/

if (isset($_GET["user_id"])) {

    $user_id = (int)$_GET["user_id"];

} elseif (isset($data["user_id"])) {

    $user_id = (int)$data["user_id"];

} else {

    $user_id = 0;
}


/*
|--------------------------------------------------------------------------
| Get Property ID
|--------------------------------------------------------------------------
*/

if (isset($_GET["property_id"])) {

    $property_id = (int)$_GET["property_id"];

} elseif (isset($data["property_id"])) {

    $property_id = (int)$data["property_id"];

} else {

    $property_id = 0;
}


/*
|--------------------------------------------------------------------------
| Validate User ID
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


/*
|--------------------------------------------------------------------------
| Validate Property ID
|--------------------------------------------------------------------------
*/

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
    | Check Favorite Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM favorites
        WHERE user_id = ?
        AND property_id = ?
        LIMIT 1
    ");

    $check->execute([
        $user_id,
        $property_id
    ]);

    $favorite = $check->fetch();


    /*
    |--------------------------------------------------------------------------
    | Favorite Not Found
    |--------------------------------------------------------------------------
    */

    if (!$favorite) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Favorite property not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Favorite
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM favorites
        WHERE user_id = ?
        AND property_id = ?
    ");

    $stmt->execute([
        $user_id,
        $property_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Property removed from favorites",
        "user_id" => $user_id,
        "property_id" => $property_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to remove favorite",
        "error" => $e->getMessage()
    ]);
}

?>