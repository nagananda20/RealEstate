<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get Property ID
|--------------------------------------------------------------------------
*/

$property_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate ID
|--------------------------------------------------------------------------
*/

if ($property_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid property ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Property
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.title,
            p.description,
            p.property_type,
            p.listing_type,
            p.price,

            p.address,
            p.city,
            p.state,
            p.country,
            p.pincode,

            p.bedrooms,
            p.bathrooms,
            p.area,

            p.image,
            p.status,
            p.featured,

            p.created_at,
            p.updated_at,

            a.id AS agent_id,
            a.name AS agent_name,
            a.email AS agent_email,
            a.phone AS agent_phone,
            a.photo AS agent_photo,
            a.specialization AS agent_specialization,
            a.experience AS agent_experience,
            a.bio AS agent_bio

        FROM properties p

        LEFT JOIN agents a
            ON p.agent_id = a.id

        WHERE p.id = ?

        LIMIT 1
    ");

    $stmt->execute([$property_id]);

    $property = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Property Not Found
    |--------------------------------------------------------------------------
    */

    if (!$property) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Property not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Property Images
    |--------------------------------------------------------------------------
    */

    $imageStmt = $pdo->prepare("
        SELECT
            id,
            image
        FROM property_images
        WHERE property_id = ?
        ORDER BY id DESC
    ");

    $imageStmt->execute([$property_id]);

    $images = $imageStmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Add Images To Property
    |--------------------------------------------------------------------------
    */

    $property["gallery"] = $images;


    /*
    |--------------------------------------------------------------------------
    | Return Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Property details fetched successfully",
        "data" => $property
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch property details",
        "error" => $e->getMessage()
    ]);
}