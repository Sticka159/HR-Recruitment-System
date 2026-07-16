<?php

require 'db.php';

session_start();

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "
    SELECT *
    FROM Users
    WHERE Username = ?
";

$stmt = sqlsrv_query(
    $conn,
    $sql,
    [$username]
);

$user = sqlsrv_fetch_array(
    $stmt,
    SQLSRV_FETCH_ASSOC
);

if (
    $user &&
    $user['PasswordHash'] === $password
) {

    $_SESSION['user_id'] =
        $user['Id'];

    $_SESSION['role'] =
        $user['Role'];

    $_SESSION['department'] =
        $user['Department'];

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false
    ]);
}