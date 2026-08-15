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
    | Get Favorite Properties
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            f.id AS favorite_id,
            f.user_id,
            f.property_id,

            p.title,
            p.description,
            p.price,
            p.location,
            p.city,
            p.state,
            p.property_type,
            p.status,
            p.image,
            p.created_at

        FROM favorites f

        INNER JOIN properties p
            ON f.property_id = p.id

        WHERE f.user_id = ?

        ORDER BY f.id DESC
    ");

    $stmt->execute([
        $user_id
    ]);

    $favorites = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Return Favorites
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Favorites fetched successfully",
        "count" => count($favorites),
        "data" => $favorites
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch favorites",
        "error" => $e->getMessage()
    ]);
}

?>