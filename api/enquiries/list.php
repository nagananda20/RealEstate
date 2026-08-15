<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

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
            p.city AS property_city,
            p.price AS property_price,

            u.name AS user_name,
            u.email AS user_email

        FROM enquiries e

        LEFT JOIN properties p
            ON e.property_id = p.id

        LEFT JOIN users u
            ON e.user_id = u.id

        ORDER BY e.created_at DESC
    ");

    $stmt->execute();

    $enquiries = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Enquiries fetched successfully",
        "count" => count($enquiries),
        "data" => $enquiries
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch enquiries",
        "error" => $e->getMessage()
    ]);
}

?>