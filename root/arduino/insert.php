<?php

require_once('../include/database.php');

define('KEY', '{server_insert_key}');

function getInputData() {

    $inputData = array();

    if (!isset($_REQUEST['key']))
        return false;
    $key = $_REQUEST['key'];
    if (!isset($_REQUEST['temperature']))
        return false;
    $temperature = $_REQUEST['temperature'];
    if (!isset($_REQUEST['humidity']))
        return false;
    $humidity = $_REQUEST['humidity'];
    if (!isset($_REQUEST['pressure']))
        return false;
    $pressure = $_REQUEST['pressure'];

    return array(
        'key' => $key,
        'temperature' => (double) $temperature,
        'humidity' => (double) $humidity,
        'pressure' => (double) $pressure
    );
}

function insert($inputData) {

    return database("INSERT INTO `mlp_current_data` (`dateTime`, `temperature`, `humidity`, `pressure`) VALUES (NOW(), :temperature, :humidity, :pressure)", array('temperature' => $inputData['temperature'], 'humidity' => $inputData['humidity'], 'pressure' => $inputData['pressure']));
}

$inputData = getInputData();
if ($inputData === false) {
    http_response_code(500);
    echo 'Bad input';
    exit();
}

if ($inputData['key'] !== KEY) {
    http_response_code(403);
    echo 'Wrong key';
    exit();
}

$status = insert($inputData);
if ($status === false) {
    http_response_code(500);
    echo 'Failed';
    exit();
}

database("CALL mlp_commit()");

echo 'Success';
