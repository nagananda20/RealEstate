<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get Enquiry ID
|--------------------------------------------------------------------------
*/

$enquiry_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate ID
|--------------------------------------------------------------------------
*/

if ($enquiry_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid enquiry ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Enquiry Details
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT

            e.id,

            e.user_id,
            e.property_id,

            e.name,
            e.email,
            e.phone,
            e.message,
            e.status,

            e.created_at,

            p.title AS property_title,
            p.description AS property_description,
            p.property_type,
            p.listing_type,
            p.price AS property_price,

            p.address AS property_address,
            p.city AS property_city,
            p.state AS property_state,

            p.image AS property_image,

            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone

        FROM enquiries e

        LEFT JOIN properties p
            ON e.property_id = p.id

        LEFT JOIN users u
            ON e.user_id = u.id

        WHERE e.id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $enquiry_id
    ]);

    $enquiry = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Enquiry Not Found
    |--------------------------------------------------------------------------
    */

    if (!$enquiry) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Enquiry not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Return Enquiry
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Enquiry details fetched successfully",
        "data" => $enquiry
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch enquiry details",
        "error" => $e->getMessage()
    ]);
}

?>