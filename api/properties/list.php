<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

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

            a.id AS agent_id,
            a.name AS agent_name,
            a.email AS agent_email,
            a.phone AS agent_phone

        FROM properties p

        LEFT JOIN agents a
            ON p.agent_id = a.id

        ORDER BY p.created_at DESC
    ");

    $stmt->execute();

    $properties = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Properties fetched successfully",
        "count" => count($properties),
        "data" => $properties
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch properties",
        "error" => $e->getMessage()
    ]);
}