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
    | Get Notifications
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            title,
            message,
            type,
            is_read,
            created_at

        FROM notifications

        WHERE user_id = ?

        ORDER BY created_at DESC
    ");

    $stmt->execute([
        $user_id
    ]);

    $notifications = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Count Unread Notifications
    |--------------------------------------------------------------------------
    */

    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    $unreadStmt->execute([
        $user_id
    ]);

    $unread_count = (int)$unreadStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Return Notifications
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Notifications fetched successfully",
        "count" => count($notifications),
        "unread_count" => $unread_count,
        "data" => $notifications
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch notifications",
        "error" => $e->getMessage()
    ]);
}

?>