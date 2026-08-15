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
| Get enquiry ID
|--------------------------------------------------------------------------
*/

$enquiry_id = isset($data["id"])
    ? (int)$data["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate ID
|--------------------------------------------------------------------------
*/

if ($enquiry_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid enquiry ID is required"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check enquiry exists
|--------------------------------------------------------------------------
*/

try {

    $check = $pdo->prepare("
        SELECT id
        FROM enquiries
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $enquiry_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Enquiry not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get values
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

    $message = trim(
        $data["message"] ?? ""
    );

    $status = trim(
        $data["status"] ?? "new"
    );


    /*
    |--------------------------------------------------------------------------
    | Validate required fields
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
            "message" =>
                "Name, email and message are required"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate email
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
    | Validate status
    |--------------------------------------------------------------------------
    */

    $allowed_status = [
        "new",
        "contacted",
        "closed"
    ];

    if (!in_array(
        $status,
        $allowed_status,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid enquiry status"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update enquiry
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE enquiries

        SET
            name = ?,
            email = ?,
            phone = ?,
            message = ?,
            status = ?

        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $message,
        $status,
        $enquiry_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Enquiry updated successfully",
        "enquiry_id" => $enquiry_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update enquiry",
        "error" => $e->getMessage()
    ]);
}

?>