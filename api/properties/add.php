<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST method is allowed"
    ]);

    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$title = trim($data["title"] ?? "");
$description = trim($data["description"] ?? "");
$property_type = trim($data["property_type"] ?? "");
$listing_type = trim($data["listing_type"] ?? "");
$price = $data["price"] ?? 0;

$address = trim($data["address"] ?? "");
$city = trim($data["city"] ?? "");
$state = trim($data["state"] ?? "");
$country = trim($data["country"] ?? "India");
$pincode = trim($data["pincode"] ?? "");

$bedrooms = (int)($data["bedrooms"] ?? 0);
$bathrooms = (int)($data["bathrooms"] ?? 0);
$area = (float)($data["area"] ?? 0);

$agent_id = !empty($data["agent_id"])
    ? (int)$data["agent_id"]
    : null;

$image = trim($data["image"] ?? "");

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
        "message" => "Title, property type, listing type and price are required"
    ]);

    exit;
}


$allowed_types = [
    "house",
    "apartment",
    "villa",
    "plot",
    "commercial",
    "office",
    "land"
];

$allowed_listing_types = [
    "sale",
    "rent"
];


if (!in_array($property_type, $allowed_types)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid property type"
    ]);

    exit;
}


if (!in_array($listing_type, $allowed_listing_types)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid listing type"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Insert Property
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        INSERT INTO properties
        (
            agent_id,
            title,
            description,
            property_type,
            listing_type,
            price,
            address,
            city,
            state,
            country,
            pincode,
            bedrooms,
            bathrooms,
            area,
            image,
            status,
            featured
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'available',
            ?
        )
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
        $featured
    ]);

    $property_id = $pdo->lastInsertId();

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Property added successfully",
        "property_id" => $property_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add property",
        "error" => $e->getMessage()
    ]);
}