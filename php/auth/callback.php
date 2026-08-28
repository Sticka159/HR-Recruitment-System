<?php

session_start();

if (
    !isset($_GET['code']) ||
    !isset($_GET['state'])
) {

    http_response_code(400);

    echo "Invalid authentication response.";

    exit;
}

if (
    !isset($_SESSION['entra_state']) ||
    !hash_equals(
        $_SESSION['entra_state'],
        $_GET['state']
    )
) {

    http_response_code(400);

    echo "Invalid authentication state.";

    exit;
}

unset(
    $_SESSION['entra_state']
);

$code =
    $_GET['code'];

$tenantId =
    getenv('ENTRA_TENANT_ID');

$clientId =
    getenv('ENTRA_CLIENT_ID');

$clientSecret =
    getenv('ENTRA_CLIENT_SECRET');

$redirectUri =
    getenv('ENTRA_REDIRECT_URI');

if (
    !$tenantId ||
    !$clientId ||
    !$clientSecret ||
    !$redirectUri
) {

    http_response_code(500);

    echo "Entra ID configuration is missing.";

    exit;
}


/*
 * =====================================================
 * REQUEST TOKEN
 * =====================================================
 */

$tokenUrl =
    'https://login.microsoftonline.com/'
    . rawurlencode($tenantId)
    . '/oauth2/v2.0/token';

$postData = [

    'client_id' =>
        $clientId,

    'client_secret' =>
        $clientSecret,

    'grant_type' =>
        'authorization_code',

    'code' =>
        $code,

    'redirect_uri' =>
        $redirectUri,

    'scope' =>
        'openid profile email'
];

$ch =
    curl_init(
        $tokenUrl
    );

curl_setopt_array(
    $ch,
    [

        CURLOPT_POST =>
            true,

        CURLOPT_POSTFIELDS =>
            http_build_query(
                $postData
            ),

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_HTTPHEADER =>
            [
                'Content-Type: application/x-www-form-urlencoded'
            ],

        CURLOPT_TIMEOUT =>
            15

    ]
);

$response =
    curl_exec($ch);

$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

$curlError =
    curl_error($ch);

curl_close($ch);

if (
    $response === false ||
    $curlError
) {

    http_response_code(500);

    echo "Token request failed.";

    exit;
}

$tokenData =
    json_decode(
        $response,
        true
    );

if (
    $httpCode !== 200 ||
    !is_array($tokenData) ||
    empty($tokenData['id_token'])
) {

    http_response_code(500);

    echo "Authentication failed.";

    exit;
}

$idToken =
    $tokenData['id_token'];


/*
 * =====================================================
 * PARSE ID TOKEN
 * =====================================================
 */

$parts =
    explode(
        '.',
        $idToken
    );

if (
    count($parts) !== 3
) {

    http_response_code(500);

    echo "Invalid ID token.";

    exit;
}

$payload =
    $parts[1];

$payload .=
    str_repeat(
        '=',
        (4 - strlen($payload) % 4) % 4
    );

$payload =
    base64_decode(
        strtr(
            $payload,
            '-_',
            '+/'
        )
    );

$claims =
    json_decode(
        $payload,
        true
    );

if (
    !is_array($claims)
) {

    http_response_code(500);

    echo "Invalid identity information.";

    exit;
}


/*
 * =====================================================
 * GET USER EMAIL
 * =====================================================
 */

$email =
    $claims['preferred_username']
    ?? $claims['email']
    ?? '';

$email =
    strtolower(
        trim($email)
    );

if ($email === '') {

    http_response_code(403);

    echo "No email address was provided by Entra ID.";

    exit;
}


/*
 * =====================================================
 * DATABASE CONNECTION
 * =====================================================
 */

require_once __DIR__ . '/../db.php';

if ($conn === false) {

    http_response_code(500);

    echo "Database connection failed.";

    exit;
}


/*
 * =====================================================
 * FIND USER
 * =====================================================
 */

$sql = "
    SELECT
        Id,
        Username,
        Role,
        Department,
        Email
    FROM dbo.Users
    WHERE LOWER(Email) = ?
";

$params = [
    $email
];

$stmt =
    sqlsrv_query(
        $conn,
        $sql,
        $params
    );

if ($stmt === false) {

    http_response_code(500);

    echo "Database query failed.";

    exit;
}

$user =
    sqlsrv_fetch_array(
        $stmt,
        SQLSRV_FETCH_ASSOC
    );

if (!$user) {

    http_response_code(403);

    echo "User is not authorized to access this application.";

    exit;
}


/*
 * =====================================================
 * CREATE APPLICATION SESSION
 * =====================================================
 */

$_SESSION['user_id'] =
    $user['Id'];

$_SESSION['username'] =
    $user['Username'];

$_SESSION['role'] =
    $user['Role'];

$_SESSION['department'] =
    $user['Department'];

$_SESSION['email'] =
    $user['Email'];

$_SESSION['entra_claims'] =
    $claims;

$_SESSION['entra_authenticated'] =
    true;


/*
 * =====================================================
 * LOGIN SUCCESS
 * =====================================================
 */

header(
    'Location: ../../app.html'
);

exit;