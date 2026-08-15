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
| Get Notification ID
|--------------------------------------------------------------------------
*/

if (isset($_GET["id"])) {

    $notification_id = (int)$_GET["id"];

} elseif (isset($data["id"])) {

    $notification_id = (int)$data["id"];

} else {

    $notification_id = 0;
}


/*
|--------------------------------------------------------------------------
| Validate Notification ID
|--------------------------------------------------------------------------
*/

if ($notification_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid notification ID is required"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check Notification Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM notifications
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $notification_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Notification not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Notification
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM notifications
        WHERE id = ?
    ");

    $stmt->execute([
        $notification_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Notification deleted successfully",
        "notification_id" => $notification_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete notification",
        "error" => $e->getMessage()
    ]);
}

?>