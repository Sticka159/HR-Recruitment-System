<?php

session_start();


/*
 * =====================================================
 * ENTRA ID CONFIGURATION
 * =====================================================
 */

$tenantId =
    trim(
        getenv('ENTRA_TENANT_ID') ?: ''
    );

$clientId =
    trim(
        getenv('ENTRA_CLIENT_ID') ?: ''
    );

$redirectUri =
    trim(
        getenv('ENTRA_REDIRECT_URI') ?: ''
    );


/*
 * =====================================================
 * VALIDATE CONFIGURATION
 * =====================================================
 */

if (
    $tenantId === '' ||
    $clientId === '' ||
    $redirectUri === ''
) {

    http_response_code(500);

    echo "Entra ID configuration is missing.";

    exit;
}


/*
 * Redirect URI must be an absolute HTTPS URL.
 */

if (
    !filter_var(
        $redirectUri,
        FILTER_VALIDATE_URL
    ) ||
    !str_starts_with(
        strtolower($redirectUri),
        'https://'
    )
) {

    http_response_code(500);

    echo "ENTRA_REDIRECT_URI is not a valid HTTPS URL.";

    exit;
}


/*
 * =====================================================
 * SECURITY STATE
 * =====================================================
 */

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


/*
 * =====================================================
 * MICROSOFT AUTHORIZE URL
 * =====================================================
 */

$authorizeUrl =
    'https://login.microsoftonline.com/'
    . rawurlencode($tenantId)
    . '/oauth2/v2.0/authorize';


/*
 * =====================================================
 * PARAMETERS
 * =====================================================
 */

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


/*
 * =====================================================
 * REDIRECT TO MICROSOFT
 * =====================================================
 */

header(
    'Location: '
    . $authorizeUrl
    . '?'
    . http_build_query(
        $params
    )
);

exit;