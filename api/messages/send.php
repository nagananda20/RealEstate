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
| Read JSON Data
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
| Get Message Data
|--------------------------------------------------------------------------
*/

$sender_id = isset($data["sender_id"])
    ? (int)$data["sender_id"]
    : 0;

$receiver_id = isset($data["receiver_id"])
    ? (int)$data["receiver_id"]
    : 0;

$message = trim(
    $data["message"] ?? ""
);


/*
|--------------------------------------------------------------------------
| Validate Sender
|--------------------------------------------------------------------------
*/

if ($sender_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid sender ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Receiver
|--------------------------------------------------------------------------
*/

if ($receiver_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid receiver ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent Sending Message To Yourself
|--------------------------------------------------------------------------
*/

if ($sender_id === $receiver_id) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "You cannot send a message to yourself"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Message
|--------------------------------------------------------------------------
*/

if (empty($message)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Message cannot be empty"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Message Length
|--------------------------------------------------------------------------
*/

if (strlen($message) > 5000) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Message cannot exceed 5000 characters"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check Sender
    |--------------------------------------------------------------------------
    */

    $senderCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $senderCheck->execute([
        $sender_id
    ]);

    if (!$senderCheck->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Sender not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Receiver
    |--------------------------------------------------------------------------
    */

    $receiverCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $receiverCheck->execute([
        $receiver_id
    ]);

    if (!$receiverCheck->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Receiver not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Message
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO messages
        (
            sender_id,
            receiver_id,
            message,
            is_read
        )

        VALUES
        (
            ?,
            ?,
            ?,
            0
        )
    ");

    $stmt->execute([
        $sender_id,
        $receiver_id,
        $message
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get New Message ID
    |--------------------------------------------------------------------------
    */

    $message_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Message sent successfully",
        "message_id" => $message_id,
        "sender_id" => $sender_id,
        "receiver_id" => $receiver_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to send message",
        "error" => $e->getMessage()
    ]);
}

?>