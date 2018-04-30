<?php

require_once('../include/database.php');

function getInputData() {

    if (!isset($_REQUEST['target']))
        return false;
    $target = $_REQUEST['target'];
    switch ($target) {
        case 'temperature':
        case 'humidity':
        case 'pressure':
            break;
        default:
            return false;
    }

    if (!isset($_REQUEST['startTimestamp']) or ! isset($_REQUEST['endTimestamp'])) {
        $result = database("SELECT UNIX_TIMESTAMP(DATE(`dateTime`)) `startTimestamp`, (UNIX_TIMESTAMP(DATE(`dateTime`)) + 86399) `endTimestamp` FROM `mlp_historical_data` WHERE `dateTime` = ( SELECT MAX(`dateTime`) FROM `mlp_historical_data` )");
        if ($result === false)
            return false;

        $rr = $result->fetch(PDO::FETCH_ASSOC);
    }

    if (isset($_REQUEST['startTimestamp'])) {
        $startTimestamp = $_REQUEST['startTimestamp'];
        if (!is_numeric($startTimestamp))
            return false;
    } else {
        $startTimestamp = $rr['startTimestamp'];
    }

    if (isset($_REQUEST['endTimestamp'])) {
        $endTimestamp = $_REQUEST['endTimestamp'];
        if (!is_numeric($endTimestamp))
            return false;
    } else {
        $endTimestamp = $rr['endTimestamp'];
    }

    if ($startTimestamp > $endTimestamp)
        return false;

    if (isset($_REQUEST['lastTimestamp'])) {
        $lastTimestamp = $_REQUEST['lastTimestamp'];
        if (!is_numeric($lastTimestamp))
            return false;
        if ($lastTimestamp < $startTimestamp)
            return false;
        if ($lastTimestamp > $endTimestamp)
            return false;
    }

    return array(
        'target' => $target,
        'startTimestamp' => (int) $startTimestamp,
        'endTimestamp' => (int) $endTimestamp,
        'lastTimestamp' => (isset($lastTimestamp) ? (int) $lastTimestamp : -1)
    );
}

function getHistoricalData($inputData) {

    $target = $inputData['target'];
    $startTimestamp = $inputData['startTimestamp'];
    $endTimestamp = $inputData['endTimestamp'];
    $lastTimestamp = $inputData['lastTimestamp'];

    $result1 = database("SELECT MIN(UNIX_TIMESTAMP(DATE(`dateTime`))) `minTimestamp`, (MAX(UNIX_TIMESTAMP(DATE(`dateTime`))) + 86399) `maxTimestamp`, MAX(UNIX_TIMESTAMP(DATE(`dateTime`))) `maxDateTimestamp` FROM `mlp_historical_data`");
    if ($result1 === false)
        return false;

    $rr1 = $result1->fetch(PDO::FETCH_ASSOC);
    if ($rr1['minTimestamp'] === null or $rr1['maxTimestamp'] === null)
        return false;
    $minTimestamp = $rr1['minTimestamp'];
    $maxTimestamp = $rr1['maxTimestamp'];

    $maxDateTimestamp = $rr1['maxDateTimestamp'];

    if ($startTimestamp < $minTimestamp) {
        $startTimestamp = $minTimestamp;
    }
    if ($endTimestamp > $maxTimestamp) {
        $endTimestamp = $maxTimestamp;
    }

    $result2 = database("SELECT *, UNIX_TIMESTAMP(`dateTime`) `timestamp` FROM `mlp_historical_data` WHERE UNIX_TIMESTAMP(`dateTime`) >= :startTimestamp AND UNIX_TIMESTAMP(`dateTime`) <= :endTimestamp AND UNIX_TIMESTAMP(`dateTime`) > :lastTimestamp", array('startTimestamp' => $startTimestamp, 'endTimestamp' => $endTimestamp, 'lastTimestamp' => $lastTimestamp));
    if ($result2 === false)
        return false;

    $dataPoints = array();

    while ($rr2 = $result2->fetch(PDO::FETCH_ASSOC)) {
        array_push($dataPoints, (object) array('x' => (int) $rr2['timestamp'], 'y' => (double) $rr2[$target]));
    }

    return array(
        'startTimestamp' => (int) $startTimestamp,
        'endTimestamp' => (int) $endTimestamp,
        'minTimestamp' => (int) $minTimestamp,
        'maxTimestamp' => (int) $maxTimestamp,
        'maxDateTimestamp' => (int) $maxDateTimestamp,
        'dataPoints' => $dataPoints
    );
}

$inputData = getInputData();
if ($inputData === false) {
    http_response_code(500);
    exit();
}

$historicalData = getHistoricalData($inputData);
if ($historicalData === false) {
    http_response_code(500);
    exit();
}

header('Content-Type: application/json');
echo json_encode($historicalData);
