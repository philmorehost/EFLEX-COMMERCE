<?php
/*
 * Database Connection Settings
 *
 * This file will contain the settings for connecting to the database.
 * We will use MySQLi for the connection.
 *
 * Host: 'localhost'
 * User: 'root'
 * Pass: ''
 * DB Name: 'ecommerce_db'
 */

// These are placeholder credentials. We will replace them later.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ecommerce_db');

/* Attempt to connect to MySQL database */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Include and run the database setup script to ensure tables exist
require_once 'db_setup.php';
setup_database_tables($mysqli);
?>
