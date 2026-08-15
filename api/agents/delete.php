<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only DELETE / POST allowed
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] !== "DELETE" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only DELETE or POST method is allowed"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Agent ID
|--------------------------------------------------------------------------
*/

$agent_id = 0;

if (isset($_GET["id"])) {

    $agent_id = (int)$_GET["id"];

} else {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        is_array($data) &&
        isset($data["id"])
    ) {

        $agent_id = (int)$data["id"];
    }
}


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


try {

    /*
    |--------------------------------------------------------------------------
    | Check Agent Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM agents
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $agent_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Agent not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Agent
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM agents
        WHERE id = ?
    ");

    $stmt->execute([
        $agent_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Agent deleted successfully",
        "agent_id" => $agent_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete agent",
        "error" => $e->getMessage()
    ]);
}

?>