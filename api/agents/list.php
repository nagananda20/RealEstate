<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Get All Agents
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            photo,
            specialization,
            experience,
            bio,
            status,
            created_at,
            updated_at

        FROM agents

        ORDER BY created_at DESC
    ");

    $stmt->execute();

    $agents = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Return Agents
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Agents fetched successfully",
        "count" => count($agents),
        "data" => $agents
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch agents",
        "error" => $e->getMessage()
    ]);
}

?>