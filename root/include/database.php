<?php

define('DATABASE_HOST', '127.0.0.1');
define('DATABASE_PORT', 3306);
define('DATABASE_NAME', 'meteo');
define('DATABASE_USERNAME', 'root');
define('DATABASE_PASSWORD', '');

function database($statement = false, $input_parameters = null) {
    static $dbc = false;

    if ($dbc === false) {
        try {
            $dbc = new PDO('mysql:host=' . DATABASE_HOST . ';port=' . DATABASE_PORT . ';dbname=' . DATABASE_NAME, DATABASE_USERNAME, DATABASE_PASSWORD);
        } catch (PDOException $ex) {
            return false;
        }
    }

    if ($statement === false) {
        return $dbc;
    }

    $query = $dbc->prepare($statement);
    $query->execute($input_parameters);
    return $query;
}
