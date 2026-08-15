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
| Get Visit ID
|--------------------------------------------------------------------------
*/

$visit_id = 0;

if (isset($_GET["id"])) {

    $visit_id = (int)$_GET["id"];

} else {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        is_array($data) &&
        isset($data["id"])
    ) {

        $visit_id = (int)$data["id"];
    }
}


/*
|--------------------------------------------------------------------------
| Validate Visit ID
|--------------------------------------------------------------------------
*/

if ($visit_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid visit ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Visit
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Check Visit Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM visits
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $visit_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Visit not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Visit
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM visits
        WHERE id = ?
    ");

    $stmt->execute([
        $visit_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Visit deleted successfully",
        "visit_id" => $visit_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete visit",
        "error" => $e->getMessage()
    ]);
}

?>