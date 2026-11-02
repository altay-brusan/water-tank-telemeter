<?php
/*
 * Portal configuration template.
 * Copy this file to config.php and fill in your own values.
 * config.php is git-ignored and must never be committed.
 */

$sql_details = array(
	'user' => "YOUR_DB_USER",
	'pass' => "YOUR_DB_PASSWORD",
	'db'   => "YOUR_DB_NAME",
	'host' => "YOUR_DB_HOST"
);
define('MQTT_CLIENT', 'YOUR_MQTT_CLIENT_ID');
define('MQTT_USER', 'YOUR_MQTT_USER');
define('MQTT_PASS', 'YOUR_MQTT_PASSWORD');

include(__DIR__ . "/functions.php");

$mysqli =  new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);

// Check connection
if($mysqli === false){
    die("Oops! Something went wrong. " . mysqli_connect_error());
}

?>
