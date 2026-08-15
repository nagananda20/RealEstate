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
| Get Agent ID
|--------------------------------------------------------------------------
*/

$agent_id = isset($data["id"])
    ? (int)$data["id"]
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
    | Get Agent Data
    |--------------------------------------------------------------------------
    */

    $name = trim(
        $data["name"] ?? ""
    );

    $email = trim(
        $data["email"] ?? ""
    );

    $phone = trim(
        $data["phone"] ?? ""
    );

    $photo = trim(
        $data["photo"] ?? ""
    );

    $specialization = trim(
        $data["specialization"] ?? ""
    );

    $experience = isset($data["experience"])
        ? (int)$data["experience"]
        : 0;

    $bio = trim(
        $data["bio"] ?? ""
    );

    $status = trim(
        $data["status"] ?? "active"
    );


    /*
    |--------------------------------------------------------------------------
    | Validate Required Fields
    |--------------------------------------------------------------------------
    */

    if (
        empty($name) ||
        empty($email) ||
        empty($phone)
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Name, email and phone are required"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Email
    |--------------------------------------------------------------------------
    */

    if (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid email address"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Experience
    |--------------------------------------------------------------------------
    */

    if ($experience < 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Experience cannot be negative"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Status
    |--------------------------------------------------------------------------
    */

    $allowed_status = [
        "active",
        "inactive"
    ];

    if (!in_array(
        $status,
        $allowed_status,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid agent status"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Email Belongs To Another Agent
    |--------------------------------------------------------------------------
    */

    $emailCheck = $pdo->prepare("
        SELECT id
        FROM agents
        WHERE email = ?
        AND id != ?
        LIMIT 1
    ");

    $emailCheck->execute([
        $email,
        $agent_id
    ]);

    if ($emailCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Email already belongs to another agent"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Agent
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE agents

        SET
            name = ?,
            email = ?,
            phone = ?,
            photo = ?,
            specialization = ?,
            experience = ?,
            bio = ?,
            status = ?

        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $photo,
        $specialization,
        $experience,
        $bio,
        $status,
        $agent_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Agent updated successfully",
        "agent_id" => $agent_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update agent",
        "error" => $e->getMessage()
    ]);
}

?>