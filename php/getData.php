<?php

require_once __DIR__ . '/db.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

/*
 * =====================================================
 * PŘEVOD DATUMŮ
 * =====================================================
 */

function formatRequestDates(&$row)
{
    $dateFields = [
        'CreatedAt' => 'c',
        'ClosedAt' => 'c',
        'NeededDate' => 'Y-m-d',
        'ExpectedStartDate' => 'Y-m-d'
    ];

    foreach ($dateFields as $field => $format) {
        if (
            isset($row[$field]) &&
            $row[$field] instanceof DateTimeInterface
        ) {
            $row[$field] = $row[$field]->format($format);
        }
    }
}

/*
 * =====================================================
 * VÝSTUP JSON
 * =====================================================
 */

function sendJson($data)
{
    echo json_encode(
        $data,
        JSON_INVALID_UTF8_SUBSTITUTE
    );
}

/*
 * =====================================================
 * ROUTER
 * =====================================================
 */

$state = $_GET['state'] ?? '';

switch ($state) {

    case 'GET_REQUESTS':

        $view = $_GET['view'] ?? 'active';

        $role = $_SESSION['role'] ?? '';
        $department = $_SESSION['department'] ?? '';

        $params = [];

        if ($view === 'history') {

            $where = "
                WHERE
                    r.ClosedAt IS NOT NULL
                    AND DATEDIFF(
                        HOUR,
                        r.ClosedAt,
                        GETDATE()
                    ) >= 24
            ";

        } else {

            $where = "
                WHERE
                    (
                        r.ClosedAt IS NULL
                        OR DATEDIFF(
                            HOUR,
                            r.ClosedAt,
                            GETDATE()
                        ) < 24
                    )
            ";
        }

        if (strtoupper($role) === 'TECHNOLOG') {

            $where .= "
                AND r.Department = ?
            ";

            $params[] = $department;
        }

        $sql = "
            SELECT
                r.Id,
                r.CreatedAt,
                r.CreatedBy,
                r.WorkerType,
                r.Department,
                r.PositionName,
                r.Quantity,
                r.ShiftType,
                r.NeededDate,
                r.CandidateName,
                r.ExpectedStartDate,
                r.Priority,
                r.Status,
                r.Note,
                r.ClosedAt,
                u.Username AS CreatedByName
            FROM dbo.RecruitmentRequests r
            LEFT JOIN dbo.Users u
                ON r.CreatedBy = u.Id
            $where
            ORDER BY r.CreatedAt DESC
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql,
            $params
        );

        if ($stmt === false) {

            http_response_code(500);

            sendJson([
                'success' => false,
                'error' => sqlsrv_errors()
            ]);

            exit;
        }

        $data = [];

        while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
        ) {

            formatRequestDates($row);

            $data[] = $row;
        }

        sendJson($data);

        break;


    case 'GET_REQUEST':

        $id = $_GET['id'] ?? 0;

        $sql = "
            SELECT
                r.Id,
                r.CreatedAt,
                r.CreatedBy,
                r.WorkerType,
                r.Department,
                r.PositionName,
                r.Quantity,
                r.ShiftType,
                r.NeededDate,
                r.CandidateName,
                r.ExpectedStartDate,
                r.Priority,
                r.Status,
                r.Note,
                r.ClosedAt,
                u.Username AS CreatedByName
            FROM dbo.RecruitmentRequests r
            LEFT JOIN dbo.Users u
                ON r.CreatedBy = u.Id
            WHERE r.Id = ?
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql,
            [$id]
        );

        if ($stmt === false) {

            http_response_code(500);

            sendJson([
                'success' => false,
                'error' => sqlsrv_errors()
            ]);

            exit;
        }

        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        );

        if ($row === false) {

            sendJson(null);

            break;
        }

        formatRequestDates($row);

        sendJson($row);

        break;


    case 'GET_USERS':

        $sql = "
            SELECT
                Id,
                Username,
                Role,
                Department
            FROM dbo.Users
            ORDER BY Username
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql
        );

        if ($stmt === false) {

            http_response_code(500);

            sendJson([
                'success' => false,
                'error' => sqlsrv_errors()
            ]);

            exit;
        }

        $data = [];

        while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
        ) {

            $data[] = $row;
        }

        sendJson($data);

        break;


    default:

        http_response_code(400);

        sendJson([
            'success' => false,
            'message' => 'Unknown state'
        ]);

        break;
}

?>