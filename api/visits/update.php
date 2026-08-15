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
| Get Visit ID
|--------------------------------------------------------------------------
*/

$visit_id = isset($data["id"])
    ? (int)$data["id"]
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


try {

    /*
    |--------------------------------------------------------------------------
    | Check Visit Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT
            id,
            property_id
        FROM visits
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $visit_id
    ]);

    $existingVisit = $check->fetch();

    if (!$existingVisit) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Visit not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Data
    |--------------------------------------------------------------------------
    */

    $property_id = !empty($data["property_id"])
        ? (int)$data["property_id"]
        : (int)$existingVisit["property_id"];

    $agent_id = !empty($data["agent_id"])
        ? (int)$data["agent_id"]
        : null;

    $visit_date = trim(
        $data["visit_date"] ?? ""
    );

    $visit_time = trim(
        $data["visit_time"] ?? ""
    );

    $message = trim(
        $data["message"] ?? ""
    );

    $status = trim(
        $data["status"] ?? "pending"
    );


    /*
    |--------------------------------------------------------------------------
    | Required Fields
    |--------------------------------------------------------------------------
    */

    if (
        empty($visit_date) ||
        empty($visit_time)
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Visit date and time are required"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Date
    |--------------------------------------------------------------------------
    */

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $visit_date
    );

    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $visit_date
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid visit date. Use YYYY-MM-DD"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Time
    |--------------------------------------------------------------------------
    */

    $timeObject = DateTime::createFromFormat(
        "H:i",
        $visit_time
    );

    if (
        !$timeObject ||
        $timeObject->format("H:i") !== $visit_time
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid visit time. Use HH:MM"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Status
    |--------------------------------------------------------------------------
    */

    $allowed_status = [
        "pending",
        "approved",
        "completed",
        "cancelled"
    ];

    if (!in_array(
        $status,
        $allowed_status,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid visit status"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Property
    |--------------------------------------------------------------------------
    */

    $propertyCheck = $pdo->prepare("
        SELECT id, status
        FROM properties
        WHERE id = ?
        LIMIT 1
    ");

    $propertyCheck->execute([
        $property_id
    ]);

    $property = $propertyCheck->fetch();

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
    | Check Agent
    |--------------------------------------------------------------------------
    */

    if ($agent_id !== null) {

        $agentCheck = $pdo->prepare("
            SELECT id, status
            FROM agents
            WHERE id = ?
            LIMIT 1
        ");

        $agentCheck->execute([
            $agent_id
        ]);

        $agent = $agentCheck->fetch();

        if (!$agent) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Agent not found"
            ]);

            exit;
        }

        if ($agent["status"] !== "active") {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Agent is not active"
            ]);

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Booking
    |--------------------------------------------------------------------------
    */

    $duplicateCheck = $pdo->prepare("
        SELECT id
        FROM visits

        WHERE property_id = ?
        AND visit_date = ?
        AND visit_time = ?

        AND status IN (
            'pending',
            'approved'
        )

        AND id != ?

        LIMIT 1
    ");

    $duplicateCheck->execute([
        $property_id,
        $visit_date,
        $visit_time,
        $visit_id
    ]);

    if ($duplicateCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "This visit time is already booked"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Visit
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE visits

        SET
            property_id = ?,
            agent_id = ?,
            visit_date = ?,
            visit_time = ?,
            message = ?,
            status = ?

        WHERE id = ?
    ");

    $stmt->execute([
        $property_id,
        $agent_id,
        $visit_date,
        $visit_time,
        $message,
        $status,
        $visit_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Visit updated successfully",
        "visit_id" => $visit_id,
        "status" => $status
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update visit",
        "error" => $e->getMessage()
    ]);
}

?>