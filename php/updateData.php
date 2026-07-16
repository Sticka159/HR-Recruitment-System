<?php

require 'db.php';

session_start();

header('Content-Type: application/json');

$state = $_POST['state'] ?? '';

switch ($state) {

    case 'UPDATE_REQUEST':

        $status =
            $_POST['status'] ?? 'NEW';

        $closedAtSql =
            in_array(
                $status,
                [
                    'COMPLETED',
                    'CANCELLED'
                ]
            )
                ? 'GETDATE()'
                : 'NULL';

        $sql = "
            UPDATE RecruitmentRequests
            SET
                WorkerType = ?,
                PositionName = ?,
                ShiftType = ?,
                NeededDate = ?,
                CandidateName = ?,
                ExpectedStartDate = ?,
                Status = ?,
                Note = ?,
                ClosedAt = $closedAtSql
            WHERE Id = ?
        ";

        $params = [

            $_POST['workerType'] ?? '',

            $_POST['positionName'] ?? '',

            $_POST['shiftType'] ?? '',

            $_POST['neededDate'] ?? null,

            ($_POST['candidateName'] ?? '') === ''
                ? null
                : $_POST['candidateName'],

            ($_POST['expectedStartDate'] ?? '') === ''
                ? null
                : $_POST['expectedStartDate'],

            $status,

            $_POST['note'] ?? '',

            $_POST['id'] ?? 0
        ];

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