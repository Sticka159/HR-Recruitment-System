<?php

require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect(
    $serverName,
    $connectionOptions
);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}