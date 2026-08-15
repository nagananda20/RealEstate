<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only PUT / POST allowed
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] !== "PUT" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only PUT or POST method is allowed"
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

    $data = [];
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
        SELECT
            id,
            user_id,
            is_read

        FROM notifications

        WHERE id = ?

        LIMIT 1
    ");

    $check->execute([
        $notification_id
    ]);

    $notification = $check->fetch();


    /*
    |--------------------------------------------------------------------------
    | Notification Not Found
    |--------------------------------------------------------------------------
    */

    if (!$notification) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Notification not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Already Read
    |--------------------------------------------------------------------------
    */

    if ((int)$notification["is_read"] === 1) {

        echo json_encode([
            "success" => true,
            "message" => "Notification is already marked as read",
            "notification_id" => $notification_id
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Mark Notification As Read
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE notifications

        SET is_read = 1

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
        "message" => "Notification marked as read",
        "notification_id" => $notification_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to mark notification as read",
        "error" => $e->getMessage()
    ]);
}

?>