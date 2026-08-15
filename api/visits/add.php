<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Only POST method allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST method is allowed"
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
| Get data
|--------------------------------------------------------------------------
*/

$user_id = !empty($data["user_id"])
    ? (int)$data["user_id"]
    : null;

$property_id = !empty($data["property_id"])
    ? (int)$data["property_id"]
    : null;

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


/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if (
    $property_id === null ||
    empty($visit_date) ||
    empty($visit_time)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Property ID, visit date and visit time are required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Date
|--------------------------------------------------------------------------
*/

$date_object = DateTime::createFromFormat(
    "Y-m-d",
    $visit_date
);

if (
    !$date_object ||
    $date_object->format("Y-m-d") !== $visit_date
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

$time_object = DateTime::createFromFormat(
    "H:i",
    $visit_time
);

if (
    !$time_object ||
    $time_object->format("H:i") !== $visit_time
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid visit time. Use HH:MM"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check property
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
    | Check property availability
    |--------------------------------------------------------------------------
    */

    if (
        $property["status"] === "sold" ||
        $property["status"] === "rented"
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Visits cannot be booked for this property"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check user
    |--------------------------------------------------------------------------
    */

    if ($user_id !== null) {

        $userCheck = $pdo->prepare("
            SELECT id
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $userCheck->execute([
            $user_id
        ]);

        if (!$userCheck->fetch()) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check agent
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
    | Check duplicate visit
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

        LIMIT 1
    ");

    $duplicateCheck->execute([
        $property_id,
        $visit_date,
        $visit_time
    ]);

    if ($duplicateCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "This visit time is already booked"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Add Visit
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO visits
        (
            user_id,
            property_id,
            agent_id,
            visit_date,
            visit_time,
            message,
            status
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'pending'
        )
    ");

    $stmt->execute([
        $user_id,
        $property_id,
        $agent_id,
        $visit_date,
        $visit_time,
        $message
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get Visit ID
    |--------------------------------------------------------------------------
    */

    $visit_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Visit booked successfully",
        "visit_id" => $visit_id,
        "status" => "pending"
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to book visit",
        "error" => $e->getMessage()
    ]);
}

?>