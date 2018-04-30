<?php

require_once('../include/class.smtp.php');

define('KEY', 'MkLbpQnUviS5O1b4UtVTxe2QTPUEL1fuTqFRWHc4BulR4TyP7Y');

define('SMTP_HOST', 'iisleviponti.it');
define('SMTP_PORT', 25);
define('SMTP_HELO', 'iisleviponti.it');
define('SMTP_FROM', 'meteo@iisleviponti.it');
define('SMTP_TO', 'meteo@iisleviponti.it');

function getInputData() {

    $inputData = array();

    if (!isset($_REQUEST['key']))
        return false;
    $key = $_REQUEST['key'];
    if (!isset($_REQUEST['statusId']))
        return false;
    $statusId = $_REQUEST['statusId'];
    $statusData = (isset($_REQUEST['statusData']) ? $_REQUEST['statusData'] : false);

    return array(
        'key' => $key,
        'statusId' => (int) $statusId,
        'statusData' => (int) $statusData
    );
}

function getMessage($statusId, $statusData) {

    $STATUSES = array(
        0 => array(
            'type' => 'INFO',
            'description' => 'Avvio del sistema'
        ),
        1 => array(
            'type' => 'ERRORE',
            'description' => 'Il componente DHT22 ha smesso di funzionare (codice {statusData})'
        ),
        2 => array(
            'type' => 'INFO',
            'description' => 'Il componente DHT22 ha ripreso a funzionare'
        ),
        3 => array(
            'type' => 'ERRORE',
            'description' => 'Il componente BMP180 ha smesso di funzionare (codice {statusData})'
        ),
        4 => array(
            'type' => 'INFO',
            'description' => 'Il componente BMP180 ha ripreso a funzionare'
        )
    );

    $Date = date(DATE_RFC822);
    $From = SMTP_FROM;
    $To = SMTP_TO;

    $statusType = (isset($STATUSES[$statusId]) ? $STATUSES[$statusId]['type'] : '<sconosciuto>');
    $statusDescription = str_replace('{statusData}', ($statusData !== false ? $statusData : '<sconosciuto>'), isset($STATUSES[$statusId]) ? $STATUSES[$statusId]['description'] : '<sconosciuto>');

    return <<<MESSAGE
Date: $Date
From: $From
To: $To
Subject: Notifica

METEO | IIS LEVI-PONTI
NOTIFICA DELLO STATO DI ARDUINO

Data: $Date
ID stato: $statusId
Tipo stato: $statusType

$statusDescription
MESSAGE;
}

function notify($inputData) {

    $SMTP = new SMTP;

    $SMTP->connect(SMTP_HOST, SMTP_PORT) and
            $SMTP->hello(SMTP_HELO) and
            $SMTP->mail(SMTP_FROM) and
            $SMTP->recipient(SMTP_TO) and
            $SMTP->data(getMessage($inputData['statusId'], $inputData['statusData'])) and
            $SMTP->quit() and
            $success = true;

    return isset($success);
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

$status = notify($inputData);
if ($status === false) {
    http_response_code(500);
    echo 'Failed';
    exit();
}

echo 'Success';
