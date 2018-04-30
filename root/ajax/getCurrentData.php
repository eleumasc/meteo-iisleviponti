<?php

require_once('../include/database.php');
require_once('../include/functions.php');

function getCurrentData() {

    $result = database("SELECT UNIX_TIMESTAMP(`dateTime`) `timestamp`, `temperature`, `humidity`, `pressure` FROM `mlp_last_data` AS `ld1` WHERE NOT EXISTS ( SELECT * FROM `mlp_last_data` AS `ld2` WHERE `ld2`.`dateTime` > `ld1`.`dateTime` )");
    if ($result === false)
        return false;

    if ($result->rowCount() < 1)
        return false;

    $rr = $result->fetch(PDO::FETCH_ASSOC);

    $TC = $rr['temperature'];
    $TF = celsiusToFahrenheit($TC);
    $RH = $rr['humidity'];
    $hPa = $rr['pressure'];
    $torr = hectopascalToTorr($hPa);
    $TdC = dewPoint($TC, $RH);
    $TdF = celsiusToFahrenheit($TdC);
    $HIF = heatIndex($TF, $RH);
    $HIC = fahrenheitToCelsius($HIF);

    return array(
        'timestamp' => (int) $rr['timestamp'],
        'temperature' => (int) $TC . '&deg;C',
        'humidity' => (int) $RH . '%',
        'pressure' => (int) $hPa . ' hPa',
        'temperature1' => (int) $TC . '&deg;C',
        'temperature2' => (int) $TF . '&deg;F',
        'humidity1' => (int) $RH . '%',
        'pressure1' => (int) $hPa . ' hPa',
        'pressure2' => (int) $torr . ' torr',
        'dewPoint1' => (int) $TdC . '&deg;C',
        'dewPoint2' => (int) $TdF . '&deg;F',
        'heatIndex1' => ($HIF !== false ? (int) $HIC . '&deg;C' : null),
        'heatIndex2' => ($HIF !== false ? (int) $HIF . '&deg;F' : null)
    );
}

$currentData = getCurrentData();
if ($currentData === false) {
    http_response_code(500);
    exit();
}

header('Content-Type: application/json');
echo json_encode($currentData);
