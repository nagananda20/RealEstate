<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
*/

$user_id = isset($_GET["user_id"])
    ? (int)$_GET["user_id"]
    : 0;


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


try {

    /*
    |--------------------------------------------------------------------------
    | Get Messages
    |--------------------------------------------------------------------------
    |
    | sender_id   = user who sent the message
    | receiver_id = user who receives the message
    |
    */

    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.sender_id,
            m.receiver_id,
            m.message,
            m.is_read,
            m.created_at,

            sender.name AS sender_name,
            receiver.name AS receiver_name

        FROM messages m

        LEFT JOIN users sender
            ON m.sender_id = sender.id

        LEFT JOIN users receiver
            ON m.receiver_id = receiver.id

        WHERE
            m.sender_id = ?
            OR
            m.receiver_id = ?

        ORDER BY m.created_at DESC
    ");

    $stmt->execute([
        $user_id,
        $user_id
    ]);

    $messages = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Return Messages
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Messages fetched successfully",
        "count" => count($messages),
        "data" => $messages
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch messages",
        "error" => $e->getMessage()
    ]);
}

?>