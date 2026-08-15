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
| Get User ID
|--------------------------------------------------------------------------
*/

$user_id = 0;

if (isset($_GET["id"])) {

    $user_id = (int)$_GET["id"];

} else {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        is_array($data) &&
        isset($data["id"])
    ) {

        $user_id = (int)$data["id"];
    }
}


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
        SELECT id, role
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $user_id
    ]);

    $user = $check->fetch();

    if (!$user) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Deleting Admin
    |--------------------------------------------------------------------------
    */

    if ($user["role"] === "admin") {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Admin users cannot be deleted"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete User
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $stmt->execute([
        $user_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "User deleted successfully",
        "user_id" => $user_id
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete user",
        "error" => $e->getMessage()
    ]);
}

?>