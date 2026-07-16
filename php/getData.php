<?php

require 'db.php';

session_start();

header('Content-Type: application/json');

$state = $_GET['state'] ?? '';

switch ($state) {

    case 'GET_REQUESTS':

        $view =
            $_GET['view'] ?? 'active';

        $role =
            $_SESSION['role'] ?? '';

        $department =
            $_SESSION['department'] ?? '';

        $params = [];

        if ($view === 'history') {

            $where = "
                WHERE
                    r.ClosedAt IS NOT NULL
                AND
                    DATEDIFF(
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
                        OR
                        DATEDIFF(
                            HOUR,
                            r.ClosedAt,
                            GETDATE()
                        ) < 24
                    )
            ";
        }

        if (
            strtoupper($role) === 'TECHNOLOG'
        ) {

            $where .= "
                AND r.Department = ?
            ";

            $params[] =
                $department;
        }

        $sql = "
            SELECT
                r.*,
                u.Username AS CreatedByName
            FROM RecruitmentRequests r
            LEFT JOIN Users u
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

            echo json_encode([
                "success" => false,
                "error" => sqlsrv_errors()
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

            if (
                $row['CreatedAt']
                instanceof DateTime
            ) {

                $row['CreatedAt'] =
                    $row['CreatedAt']
                        ->format('c');
            }

            if (
                isset(
                    $row['ClosedAt']
                ) &&
                $row['ClosedAt']
                instanceof DateTime
            ) {

                $row['ClosedAt'] =
                    $row['ClosedAt']
                        ->format('c');
            }

            if (
                $row['NeededDate']
                instanceof DateTime
            ) {

                $row['NeededDate'] =
                    $row['NeededDate']
                        ->format('Y-m-d');
            }

            if (
                isset(
                    $row['ExpectedStartDate']
                ) &&
                $row['ExpectedStartDate']
                instanceof DateTime
            ) {

                $row['ExpectedStartDate'] =
                    $row['ExpectedStartDate']
                        ->format('Y-m-d');
            }

            $data[] = $row;
        }

        echo json_encode(
            $data
        );

        break;

    case 'GET_REQUEST':

        $id =
            $_GET['id'] ?? 0;

        $sql = "
            SELECT
                r.*,
                u.Username AS CreatedByName
            FROM RecruitmentRequests r
            LEFT JOIN Users u
                ON r.CreatedBy = u.Id
            WHERE r.Id = ?
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql,
            [$id]
        );

        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        );

        if (
            isset(
                $row['CreatedAt']
            ) &&
            $row['CreatedAt']
            instanceof DateTime
        ) {

            $row['CreatedAt'] =
                $row['CreatedAt']
                    ->format('c');
        }

        if (
            isset(
                $row['ClosedAt']
            ) &&
            $row['ClosedAt']
            instanceof DateTime
        ) {

            $row['ClosedAt'] =
                $row['ClosedAt']
                    ->format('c');
        }

        if (
            isset(
                $row['NeededDate']
            ) &&
            $row['NeededDate']
            instanceof DateTime
        ) {

            $row['NeededDate'] =
                $row['NeededDate']
                    ->format('Y-m-d');
        }

        if (
            isset(
                $row['ExpectedStartDate']
            ) &&
            $row['ExpectedStartDate']
            instanceof DateTime
        ) {

            $row['ExpectedStartDate'] =
                $row['ExpectedStartDate']
                    ->format('Y-m-d');
        }

        echo json_encode(
            $row
        );

        break;

    case 'GET_USERS':

        $sql = "
            SELECT
                Id,
                Username,
                Role,
                Department
            FROM Users
            ORDER BY Username
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql
        );

        $data = [];

        while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
        ) {

            $data[] = $row;
        }

        echo json_encode(
            $data
        );

        break;

    default:

        echo json_encode([
            "success" => false,
            "message" => "Unknown state"
        ]);
}