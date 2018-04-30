<?php

function celsiusToFahrenheit($TC) {
    return $TC * 1.8 + 32;
}

function fahrenheitToCelsius($TF) {
    return ($TF - 32) / 1.8;
}

function hectopascalToTorr($hPa) {
    return $hPa / 1.33322;
}

function torrToHectopascal($torr) {
    return $torr * 1.33322;
}

function dewPoint($TC, $RH) {
    $T = (17.271 * $TC) / (237.7 + $TC) + log($RH * 0.01);
    $Td = (237.7 * $T) / (17.271 - $T);
    return $Td;
}

function heatIndex($TF, $RH) {
    if ($TF < 80)
        return false;
    if ($RH < 40)
        return false;
    return -42.379
            + 2.04901523 * $TF
            + 10.14333127 * $RH
            - .22475541 * $TF * $RH
            - .00683783 * $TF * $TF
            - .05481717 * $RH * $RH
            + .00122874 * $TF * $TF * $RH
            + .00085282 * $TF * $RH * $RH
            - .00000199 * $TF * $TF * $RH * $RH;
}
