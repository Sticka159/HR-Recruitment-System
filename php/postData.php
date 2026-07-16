<?php

require 'db.php';

session_start();

header('Content-Type: application/json');

$state = $_POST['state'] ?? '';

switch ($state) {

    case 'CREATE_REQUEST':

        $sql = "
            INSERT INTO RecruitmentRequests
            (
                CreatedAt,
                CreatedBy,
                WorkerType,
                Department,
                PositionName,
                Quantity,
                ShiftType,
                NeededDate,
                CandidateName,
                ExpectedStartDate,
                Priority,
                Status,
                Note
            )
            VALUES
            (
                GETDATE(),
                ?,
                ?,
                ?,
                ?,
                1,
                '',
                ?,
                ?,
                ?,
                'MEDIUM',
                ?,
                ?
            )
        ";

        $positionsCount =
            max(
                1,
                intval(
                    $_POST['positionsCount'] ?? 1
                )
            );

        $params = [

            $_SESSION['user_id'],

            $_POST['workerType'] ?? '',

            $_SESSION['department'],

            $_POST['positionName'] ?? '',

            $_POST['neededDate'] ?? null,

            ($_POST['candidateName'] ?? '') === ''
                ? null
                : $_POST['candidateName'],

            ($_POST['expectedStartDate'] ?? '') === ''
                ? null
                : $_POST['expectedStartDate'],

            $_POST['status'] ?? 'NEW',

            $_POST['note'] ?? ''
        ];

        for (
            $i = 0;
            $i < $positionsCount;
            $i++
        ) {

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
        }

        echo json_encode([
            "success" => true
        ]);

        break;

    default:

        echo json_encode([
            "success" => false,
            "message" => "Unknown state"
        ]);
}