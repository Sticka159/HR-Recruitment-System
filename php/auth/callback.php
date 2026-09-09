<?php

session_start();


/*
 * =====================================================
 * DEBUG - SESSION / STATE
 * =====================================================
 */

error_log(
    'ENTRA CALLBACK: session_id='
    . substr(
        hash(
            'sha256',
            session_id()
        ),
        0,
        12
    )
);

error_log(
    'ENTRA CALLBACK: method='
    . ($_SERVER['REQUEST_METHOD'] ?? 'unknown')
);

error_log(
    'ENTRA CALLBACK: post_code='
    . (isset($_POST['code']) ? 'YES' : 'NO')
);

error_log(
    'ENTRA CALLBACK: post_state='
    . (isset($_POST['state']) ? 'YES' : 'NO')
);

error_log(
    'ENTRA CALLBACK: session_state='
    . (isset($_SESSION['entra_state']) ? 'YES' : 'NO')
);

if (isset($_POST['state'])) {

    error_log(
        'ENTRA CALLBACK: received_state_hash='
        . substr(
            hash(
                'sha256',
                $_POST['state']
            ),
            0,
            12
        )
    );
}

if (isset($_SESSION['entra_state'])) {

    error_log(
        'ENTRA CALLBACK: stored_state_hash='
        . substr(
            hash(
                'sha256',
                $_SESSION['entra_state']
            ),
            0,
            12
        )
    );
}


/*
 * =====================================================
 * VALIDATE AUTHENTICATION RESPONSE
 * =====================================================
 */

if (
    !isset($_POST['code']) ||
    !isset($_POST['state'])
) {

    error_log(
        'ENTRA CALLBACK: missing code or state'
    );

    http_response_code(400);

    echo "Invalid authentication response.";

    exit;
}


/*
 * =====================================================
 * VALIDATE SECURITY STATE
 * =====================================================
 */

if (
    !isset($_SESSION['entra_state']) ||
    !hash_equals(
        $_SESSION['entra_state'],
        $_POST['state']
    )
) {

    error_log(
        'ENTRA CALLBACK: STATE VALIDATION FAILED'
    );

    http_response_code(400);

    echo "Invalid authentication state.";

    exit;
}

error_log(
    'ENTRA CALLBACK: STATE VALIDATION OK'
);


unset(
    $_SESSION['entra_state']
);


/*
 * =====================================================
 * GET AUTHORIZATION CODE
 * =====================================================
 */

$code =
    $_POST['code'];


/*
 * =====================================================
 * ENTRA ID CONFIGURATION
 * =====================================================
 */

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

    error_log(
        'ENTRA CALLBACK: Entra configuration missing'
    );

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

    error_log(
        'ENTRA CALLBACK: token request failed'
    );

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

    error_log(
        'ENTRA CALLBACK: token response invalid, HTTP '
        . $httpCode
    );

    http_response_code(500);

    echo "Authentication failed.";

    exit;
}

error_log(
    'ENTRA CALLBACK: token request OK'
);

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

    error_log(
        'ENTRA CALLBACK: invalid ID token'
    );

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

    error_log(
        'ENTRA CALLBACK: invalid identity information'
    );

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

    error_log(
        'ENTRA CALLBACK: no email in claims'
    );

    http_response_code(403);

    echo "No email address was provided by Entra ID.";

    exit;
}

error_log(
    'ENTRA CALLBACK: email received'
);


/*
 * =====================================================
 * DATABASE CONNECTION
 * =====================================================
 */

require_once __DIR__ . '/../db.php';

if ($conn === false) {

    error_log(
        'ENTRA CALLBACK: database connection failed'
    );

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

    error_log(
        'ENTRA CALLBACK: database query failed'
    );

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

    error_log(
        'ENTRA CALLBACK: user not found'
    );

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

error_log(
    'ENTRA CALLBACK: LOGIN SUCCESS'
);


/*
 * =====================================================
 * LOGIN SUCCESS
 * =====================================================
 */

header(
    'Location: ../../app.html'
);

exit;