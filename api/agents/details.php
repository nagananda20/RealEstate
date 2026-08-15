<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get Agent ID
|--------------------------------------------------------------------------
*/

$agent_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate Agent ID
|--------------------------------------------------------------------------
*/

if ($agent_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid agent ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Agent Details
|--------------------------------------------------------------------------
*/

try {

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

        WHERE id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $agent_id
    ]);

    $agent = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Agent Not Found
    |--------------------------------------------------------------------------
    */

    if (!$agent) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Agent not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Return Agent
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Agent details fetched successfully",
        "data" => $agent
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch agent details",
        "error" => $e->getMessage()
    ]);
}

?>
