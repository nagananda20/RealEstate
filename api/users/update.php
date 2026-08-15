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
| Get User ID
|--------------------------------------------------------------------------
*/

$user_id = isset($data["id"])
    ? (int)$data["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validate User ID
|--------------------------------------------------------------------------
*/

if ($user_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid user ID is required"
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Check User Exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $user_id
    ]);

    if (!$check->fetch()) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found"
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
        empty($email)
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Name and email are required"
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
    | Check Email Belongs To Another User
    |--------------------------------------------------------------------------
    */

    $emailCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND id != ?
        LIMIT 1
    ");

    $emailCheck->execute([
        $email,
        $user_id
    ]);

    if ($emailCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Email address already belongs to another user"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Password If Provided
    |--------------------------------------------------------------------------
    */

    if (
        !empty($password) &&
        strlen($password) < 6
    ) {

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
    | Update With Password
    |--------------------------------------------------------------------------
    */

    if (!empty($password)) {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users

            SET
                name = ?,
                email = ?,
                phone = ?,
                password = ?,
                role = ?,
                profile_image = ?,
                status = ?

            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $email,
            $phone,
            $hashed_password,
            $role,
            $profile_image,
            $status,
            $user_id
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Update Without Changing Password
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE users

            SET
                name = ?,
                email = ?,
                phone = ?,
                role = ?,
                profile_image = ?,
                status = ?

            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $email,
            $phone,
            $role,
            $profile_image,
            $status,
            $user_id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "User updated successfully",
        "user_id" => $user_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update user",
        "error" => $e->getMessage()
    ]);
}

?>