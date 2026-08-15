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


try {

    /*
    |--------------------------------------------------------------------------
    | Check Email
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM agents
        WHERE email = ?
        LIMIT 1
    ");

    $check->execute([
        $email
    ]);

    if ($check->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Agent email already exists"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Agent
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO agents
        (
            name,
            email,
            phone,
            photo,
            specialization,
            experience,
            bio,
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
            ?,
            ?
        )
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $photo,
        $specialization,
        $experience,
        $bio,
        $status
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get New Agent ID
    |--------------------------------------------------------------------------
    */

    $agent_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Agent added successfully",
        "agent_id" => $agent_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add agent",
        "error" => $e->getMessage()
    ]);
}

?>