<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

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

            /* User information */
            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,

            /* Property information */
            p.title AS property_title,
            p.property_type,
            p.listing_type,
            p.price AS property_price,
            p.city AS property_city,
            p.state AS property_state,

            /* Agent information */
            a.name AS agent_name,
            a.email AS agent_email,
            a.phone AS agent_phone

        FROM visits v

        LEFT JOIN users u
            ON v.user_id = u.id

        LEFT JOIN properties p
            ON v.property_id = p.id

        LEFT JOIN agents a
            ON v.agent_id = a.id

        ORDER BY
            v.visit_date DESC,
            v.visit_time DESC,
            v.created_at DESC
    ");

    $stmt->execute();

    $visits = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Visits fetched successfully",
        "count" => count($visits),
        "data" => $visits
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch visits",
        "error" => $e->getMessage()
    ]);
}

?>