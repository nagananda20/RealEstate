<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Get Users
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            role,
            profile_image,
            status,
            created_at,
            updated_at

        FROM users

        ORDER BY created_at DESC
    ");

    $stmt->execute();

    $users = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Return Users
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Users fetched successfully",
        "count" => count($users),
        "data" => $users
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch users",
        "error" => $e->getMessage()
    ]);
}

?>