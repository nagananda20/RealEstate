<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
*/

$user_id = isset($_GET["id"])
    ? (int)$_GET["id"]
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


/*
|--------------------------------------------------------------------------
| Get User Details
|--------------------------------------------------------------------------
*/

try {

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

        WHERE id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $user_id
    ]);

    $user = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | User Not Found
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Return User
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "User details fetched successfully",
        "data" => $user
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch user details",
        "error" => $e->getMessage()
    ]);
}

?>