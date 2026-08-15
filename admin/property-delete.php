<?php

session_start();

require_once "../config/database.php";

/* =========================
   ADMIN AUTHENTICATION
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================
   PROPERTY ID
========================= */

$propertyId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$propertyId || $propertyId <= 0) {
    header("Location: properties.php?error=invalid_id");
    exit;
}


/* =========================
   CHECK PROPERTY
========================= */

$sql = "
    SELECT id, title
    FROM properties
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: properties.php?error=database");
    exit;
}

$stmt->bind_param(
    "i",
    $propertyId
);

$stmt->execute();

$result = $stmt->get_result();

$property = $result->fetch_assoc();

$stmt->close();


if (!$property) {

    header(
        "Location: properties.php?error=not_found"
    );

    exit;
}


/* =========================
   DELETE RELATED DATA
========================= */

/*
 * If your project later contains:
 *
 * property_images
 * enquiries
 * visits
 * favorites
 *
 * they should also be removed here.
 *
 * The checks below only execute if
 * the corresponding tables exist.
 */


/* =========================
   DELETE PROPERTY IMAGES
========================= */

$imageFiles = [];

$imageTableCheck = $conn->query(
    "SHOW TABLES LIKE 'property_images'"
);

if (
    $imageTableCheck &&
    $imageTableCheck->num_rows > 0
) {

    $imageSQL = "
        SELECT image_path
        FROM property_images
        WHERE property_id = ?
    ";

    $imageStmt =
        $conn->prepare($imageSQL);

    if ($imageStmt) {

        $imageStmt->bind_param(
            "i",
            $propertyId
        );

        $imageStmt->execute();

        $imageResult =
            $imageStmt->get_result();

        while (
            $imageRow =
            $imageResult->fetch_assoc()
        ) {

            if (
                !empty(
                    $imageRow["image_path"]
                )
            ) {

                $imageFiles[] =
                    $imageRow["image_path"];
            }
        }

        $imageStmt->close();
    }
}


/* =========================
   TRANSACTION
========================= */

$conn->begin_transaction();

try {


    /* =========================
       DELETE PROPERTY IMAGES
    ========================= */

    if (
        $imageTableCheck &&
        $imageTableCheck->num_rows > 0
    ) {

        $deleteImagesSQL = "
            DELETE FROM property_images
            WHERE property_id = ?
        ";

        $deleteImagesStmt =
            $conn->prepare(
                $deleteImagesSQL
            );

        if (!$deleteImagesStmt) {
            throw new Exception(
                "Unable to delete property images."
            );
        }

        $deleteImagesStmt->bind_param(
            "i",
            $propertyId
        );

        if (
            !$deleteImagesStmt->execute()
        ) {

            throw new Exception(
                "Unable to remove property images."
            );
        }

        $deleteImagesStmt->close();
    }


    /* =========================
       DELETE ENQUIRIES
    ========================= */

    $enquiryTableCheck = $conn->query(
        "SHOW TABLES LIKE 'enquiries'"
    );

    if (
        $enquiryTableCheck &&
        $enquiryTableCheck->num_rows > 0
    ) {

        $sql = "
            DELETE FROM enquiries
            WHERE property_id = ?
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare enquiries deletion."
            );
        }

        $stmt->bind_param(
            "i",
            $propertyId
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Unable to delete enquiries."
            );
        }

        $stmt->close();
    }


    /* =========================
       DELETE VISITS
    ========================= */

    $visitTableCheck = $conn->query(
        "SHOW TABLES LIKE 'visits'"
    );

    if (
        $visitTableCheck &&
        $visitTableCheck->num_rows > 0
    ) {

        $sql = "
            DELETE FROM visits
            WHERE property_id = ?
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare visits deletion."
            );
        }

        $stmt->bind_param(
            "i",
            $propertyId
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Unable to delete visits."
            );
        }

        $stmt->close();
    }


    /* =========================
       DELETE FAVORITES
    ========================= */

    $favoriteTableCheck = $conn->query(
        "SHOW TABLES LIKE 'favorites'"
    );

    if (
        $favoriteTableCheck &&
        $favoriteTableCheck->num_rows > 0
    ) {

        $sql = "
            DELETE FROM favorites
            WHERE property_id = ?
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare favorites deletion."
            );
        }

        $stmt->bind_param(
            "i",
            $propertyId
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Unable to delete favorites."
            );
        }

        $stmt->close();
    }


    /* =========================
       DELETE PROPERTY
    ========================= */

    $deleteSQL = "
        DELETE FROM properties
        WHERE id = ?
    ";

    $deleteStmt =
        $conn->prepare($deleteSQL);

    if (!$deleteStmt) {

        throw new Exception(
            "Unable to prepare property deletion."
        );
    }


    $deleteStmt->bind_param(
        "i",
        $propertyId
    );


    if (!$deleteStmt->execute()) {

        throw new Exception(
            "Unable to delete property."
        );
    }


    if (
        $deleteStmt->affected_rows !== 1
    ) {

        throw new Exception(
            "Property was not deleted."
        );
    }


    $deleteStmt->close();


    /* =========================
       COMMIT
    ========================= */

    $conn->commit();


    /* =========================
       DELETE PHYSICAL IMAGES
    ========================= */

    foreach (
        $imageFiles
        as $imagePath
    ) {

        /*
         * Only delete files inside
         * the project's upload directory.
         */

        $uploadRoot =
            realpath(
                __DIR__ .
                "/../uploads/properties"
            );

        if (!$uploadRoot) {
            continue;
        }


        $fileName =
            basename($imagePath);

        $fullPath =
            $uploadRoot .
            DIRECTORY_SEPARATOR .
            $fileName;


        if (
            is_file($fullPath)
        ) {

            @unlink($fullPath);
        }
    }


    /* =========================
       SUCCESS
    ========================= */

    header(
        "Location: properties.php?deleted=1"
    );

    exit;


} catch (Throwable $e) {


    /* =========================
       ROLLBACK
    ========================= */

    $conn->rollback();


    header(
        "Location: properties.php?error=delete_failed"
    );

    exit;
}

?>