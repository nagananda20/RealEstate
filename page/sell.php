<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
$role=$_SESSION["user_role"] ?? "user";
if ($role !== "admin") {
    header("Location: properties.php");
    exit;
}
header("Location: ../admin/property-add.php");
exit;
?>
