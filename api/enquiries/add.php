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
| Read JSON Data
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
| Get Form Data
|--------------------------------------------------------------------------
*/

$user_id = !empty($data["user_id"])
    ? (int)$data["user_id"]
    : null;

$property_id = !empty($data["property_id"])
    ? (int)$data["property_id"]
    : null;

$name = trim($data["name"] ?? "");

$email = trim($data["email"] ?? "");

$phone = trim($data["phone"] ?? "");

$message = trim($data["message"] ?? "");


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (
    empty($name) ||
    empty($email) ||
    empty($message)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Name, email and message are required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Email
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Property
|--------------------------------------------------------------------------
*/

try {

    if ($property_id !== null) {

        $propertyCheck = $pdo->prepare("
            SELECT id
            FROM properties
            WHERE id = ?
            LIMIT 1
        ");

        $propertyCheck->execute([
            $property_id
        ]);

        if (!$propertyCheck->fetch()) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Property not found"
            ]);

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check User
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
    | Insert Enquiry
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO enquiries
        (
            user_id,
            property_id,
            name,
            email,
            phone,
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
            'new'
        )
    ");

    $stmt->execute([
        $user_id,
        $property_id,
        $name,
        $email,
        $phone,
        $message
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get New Enquiry ID
    |--------------------------------------------------------------------------
    */

    $enquiry_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Enquiry added successfully",
        "enquiry_id" => $enquiry_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add enquiry",
        "error" => $e->getMessage()
    ]);
}

?>