<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

if (
    !isset($_SESSION['entra_authenticated']) ||
    $_SESSION['entra_authenticated'] !== true
) {

    echo json_encode([
        "authenticated" => false
    ]);

    exit;
}

$claims =
    $_SESSION['entra_claims'] ?? [];

echo json_encode([
    "authenticated" => true,
    "role" =>
        $claims['role']
        ?? $claims['roles'][0]
            ?? "",
    "department" =>
        $claims['department']
        ?? ""
]);

exit;