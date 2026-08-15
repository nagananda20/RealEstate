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
| Get Property ID
|--------------------------------------------------------------------------
*/

$property_id = 0;

if (isset($_GET["id"])) {

    $property_id = (int)$_GET["id"];

} else {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (is_array($data) && isset($data["id"])) {

        $property_id = (int)$data["id"];
    }
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


/*
|--------------------------------------------------------------------------
| Delete Property
|--------------------------------------------------------------------------
*/

try {

    // Check property exists
    $check = $pdo->prepare("
        SELECT id
        FROM properties
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([$property_id]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Property not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Property
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM properties
        WHERE id = ?
    ");

    $stmt->execute([
        $property_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Property deleted successfully",
        "property_id" => $property_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete property",
        "error" => $e->getMessage()
    ]);
}

?>