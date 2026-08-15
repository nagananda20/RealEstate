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
| Get Message ID
|--------------------------------------------------------------------------
*/

if (isset($_GET["id"])) {

    $message_id = (int)$_GET["id"];

} elseif (isset($data["id"])) {

    $message_id = (int)$data["id"];

} else {

    $message_id = 0;
}


/*
|--------------------------------------------------------------------------
| Validate Message ID
|--------------------------------------------------------------------------
*/

if ($message_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid message ID is required"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check Message Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM messages
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $message_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Message not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Message
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM messages
        WHERE id = ?
    ");

    $stmt->execute([
        $message_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Message deleted successfully",
        "message_id" => $message_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete message",
        "error" => $e->getMessage()
    ]);
}

?>