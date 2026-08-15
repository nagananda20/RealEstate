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
| Get User Data
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

$password = $data["password"] ?? "";

$role = trim(
    $data["role"] ?? "user"
);

$profile_image = trim(
    $data["profile_image"] ?? ""
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
    empty($password)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Name, email and password are required"
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
| Validate Password
|--------------------------------------------------------------------------
*/

if (strlen($password) < 6) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Password must contain at least 6 characters"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Allowed Roles
|--------------------------------------------------------------------------
*/

$allowed_roles = [
    "user",
    "admin",
    "agent"
];

if (!in_array(
    $role,
    $allowed_roles,
    true
)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid user role"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Allowed Status
|--------------------------------------------------------------------------
*/

$allowed_status = [
    "active",
    "inactive",
    "blocked"
];

if (!in_array(
    $status,
    $allowed_status,
    true
)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid user status"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Database Operations
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Check Email Already Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM users
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
            "message" => "Email address already exists"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Hash Password
    |--------------------------------------------------------------------------
    */

    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /*
    |--------------------------------------------------------------------------
    | Insert User
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            name,
            email,
            phone,
            password,
            role,
            profile_image,
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
            ?
        )
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $hashed_password,
        $role,
        $profile_image,
        $status
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get New User ID
    |--------------------------------------------------------------------------
    */

    $user_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "User added successfully",
        "user_id" => $user_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add user",
        "error" => $e->getMessage()
    ]);
}

?>