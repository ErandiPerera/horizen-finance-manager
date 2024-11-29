<?php

error_reporting(E_ALL);  // Enable error reporting for debugging
ini_set('display_errors', '1');

// Database credentials
$dbuser = "root"; // Database username
$dbpassword = ""; // Database password
$dbname = "dbmoney"; // Database name
$dbhost = "localhost"; // Database host

// Create connection
$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check connection
if ($mysqli->connect_errno) {
    printf("MySQLi connection failed: %s\n", $mysqli->connect_error);
    exit();
}

// Change character set to utf8
if (!$mysqli->set_charset('utf8')) {
    printf('Error loading character set utf8: %s\n', $mysqli->error);
} else {
    echo "Database connected and character set set to UTF-8.";
}

?>
