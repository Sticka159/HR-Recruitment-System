<?php

session_start();

$tenantId =
    getenv('ENTRA_TENANT_ID');

$clientId =
    getenv('ENTRA_CLIENT_ID');

$redirectUri =
    getenv('ENTRA_REDIRECT_URI');

if (
    !$tenantId ||
    !$clientId ||
    !$redirectUri
) {

    http_response_code(500);

    echo "Entra ID configuration is missing.";

    exit;
}

$state =
    bin2hex(
        random_bytes(32)
    );

$nonce =
    bin2hex(
        random_bytes(32)
    );

$_SESSION['entra_state'] =
    $state;

$_SESSION['entra_nonce'] =
    $nonce;

$authorizeUrl =
    'https://login.microsoftonline.com/'
    . rawurlencode($tenantId)
    . '/oauth2/v2.0/authorize';

$params = [

    'client_id' =>
        $clientId,

    'response_type' =>
        'code',

    'redirect_uri' =>
        $redirectUri,

    'response_mode' =>
        'query',

    'scope' =>
        'openid profile email',

    'state' =>
        $state,

    'nonce' =>
        $nonce
];

header(
    'Location: '
    . $authorizeUrl
    . '?'
    . http_build_query(
        $params
    )
);

exit;