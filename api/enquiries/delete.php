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
| Get Enquiry ID
|--------------------------------------------------------------------------
*/

$enquiry_id = 0;

if (isset($_GET["id"])) {

    $enquiry_id = (int)$_GET["id"];

} else {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        is_array($data) &&
        isset($data["id"])
    ) {

        $enquiry_id = (int)$data["id"];
    }
}


/*
|--------------------------------------------------------------------------
| Validate ID
|--------------------------------------------------------------------------
*/

if ($enquiry_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid enquiry ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Enquiry
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Check enquiry exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM enquiries
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $enquiry_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Enquiry not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM enquiries
        WHERE id = ?
    ");

    $stmt->execute([
        $enquiry_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Enquiry deleted successfully",
        "enquiry_id" => $enquiry_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete enquiry",
        "error" => $e->getMessage()
    ]);
}

?>