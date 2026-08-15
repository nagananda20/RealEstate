<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get Visit ID
|--------------------------------------------------------------------------
*/

$visit_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate ID
|--------------------------------------------------------------------------
*/

if ($visit_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid visit ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Visit Details
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT

            v.id,

            v.user_id,
            v.property_id,
            v.agent_id,

            v.visit_date,
            v.visit_time,
            v.message,
            v.status,

            v.created_at,

            /* User details */

            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,

            /* Property details */

            p.title AS property_title,
            p.description AS property_description,
            p.property_type,
            p.listing_type,
            p.price AS property_price,

            p.address AS property_address,
            p.city AS property_city,
            p.state AS property_state,
            p.country AS property_country,
            p.pincode AS property_pincode,

            p.bedrooms,
            p.bathrooms,
            p.area,

            p.image AS property_image,

            /* Agent details */

            a.name AS agent_name,
            a.email AS agent_email,
            a.phone AS agent_phone,
            a.photo AS agent_photo,
            a.specialization AS agent_specialization,
            a.experience AS agent_experience,
            a.bio AS agent_bio

        FROM visits v

        LEFT JOIN users u
            ON v.user_id = u.id

        LEFT JOIN properties p
            ON v.property_id = p.id

        LEFT JOIN agents a
            ON v.agent_id = a.id

        WHERE v.id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $visit_id
    ]);

    $visit = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Visit Not Found
    |--------------------------------------------------------------------------
    */

    if (!$visit) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Visit not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Return Visit
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Visit details fetched successfully",
        "data" => $visit
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch visit details",
        "error" => $e->getMessage()
    ]);
}

?>