<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only PUT / POST allowed
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] !== "PUT" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only PUT or POST method is allowed"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Read JSON data
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
| Get Property ID
|--------------------------------------------------------------------------
*/

$property_id = isset($data["id"])
    ? (int)$data["id"]
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
| Check Property Exists
|--------------------------------------------------------------------------
*/

try {

    $check = $pdo->prepare("
        SELECT id
        FROM properties
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([$property_id]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Property not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Values
    |--------------------------------------------------------------------------
    */

    $title = trim($data["title"] ?? "");
    $description = trim($data["description"] ?? "");

    $property_type = trim(
        $data["property_type"] ?? ""
    );

    $listing_type = trim(
        $data["listing_type"] ?? ""
    );

    $price = $data["price"] ?? 0;

    $address = trim(
        $data["address"] ?? ""
    );

    $city = trim(
        $data["city"] ?? ""
    );

    $state = trim(
        $data["state"] ?? ""
    );

    $country = trim(
        $data["country"] ?? "India"
    );

    $pincode = trim(
        $data["pincode"] ?? ""
    );

    $bedrooms = (int)(
        $data["bedrooms"] ?? 0
    );

    $bathrooms = (int)(
        $data["bathrooms"] ?? 0
    );

    $area = (float)(
        $data["area"] ?? 0
    );

    $agent_id = !empty($data["agent_id"])
        ? (int)$data["agent_id"]
        : null;

    $image = trim(
        $data["image"] ?? ""
    );

    $status = trim(
        $data["status"] ?? "available"
    );

    $featured = !empty($data["featured"])
        ? 1
        : 0;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($title) ||
        empty($property_type) ||
        empty($listing_type) ||
        empty($price)
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Title, property type, listing type and price are required"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Property Types
    |--------------------------------------------------------------------------
    */

    $allowed_types = [
        "house",
        "apartment",
        "villa",
        "plot",
        "commercial",
        "office",
        "land"
    ];


    if (!in_array(
        $property_type,
        $allowed_types,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid property type"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Listing Types
    |--------------------------------------------------------------------------
    */

    $allowed_listing_types = [
        "sale",
        "rent"
    ];


    if (!in_array(
        $listing_type,
        $allowed_listing_types,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid listing type"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Status
    |--------------------------------------------------------------------------
    */

    $allowed_status = [
        "available",
        "sold",
        "rented",
        "pending"
    ];


    if (!in_array(
        $status,
        $allowed_status,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid property status"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Property
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE properties

        SET
            agent_id = ?,
            title = ?,
            description = ?,
            property_type = ?,
            listing_type = ?,
            price = ?,
            address = ?,
            city = ?,
            state = ?,
            country = ?,
            pincode = ?,
            bedrooms = ?,
            bathrooms = ?,
            area = ?,
            image = ?,
            status = ?,
            featured = ?

        WHERE id = ?
    ");


    $stmt->execute([

        $agent_id,
        $title,
        $description,
        $property_type,
        $listing_type,
        $price,
        $address,
        $city,
        $state,
        $country,
        $pincode,
        $bedrooms,
        $bathrooms,
        $area,
        $image,
        $status,
        $featured,
        $property_id

    ]);


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Property updated successfully",
        "property_id" => $property_id
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update property",
        "error" => $e->getMessage()
    ]);
}