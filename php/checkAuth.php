<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);


/*
 * =====================================================
 * CHECK AUTHENTICATION
 * =====================================================
 */

if (
    !isset($_SESSION['entra_authenticated']) ||
    $_SESSION['entra_authenticated'] !== true
) {

    echo json_encode([
        "authenticated" => false
    ]);

    exit;
}


/*
 * =====================================================
 * RETURN APPLICATION USER DATA
 * =====================================================
 */

echo json_encode([
    "authenticated" =>
        true,

    "role" =>
        $_SESSION['role']
        ?? "",

    "department" =>
        $_SESSION['department']
        ?? "",

    "email" =>
        $_SESSION['email']
        ?? "",

    "username" =>
        $_SESSION['username']
        ?? ""
]);

exit;