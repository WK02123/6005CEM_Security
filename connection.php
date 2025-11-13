<?php
$servername = "127.0.0.1"; // or "localhost"
$username   = "root";
$password   = "";          // use your actual MySQL password if set
$dbname     = "edoc";
$port       = 3306;

$database = new mysqli($servername, $username, $password, $dbname, $port);



if ($database->connect_error) {
    die("Échec de la connexion : " . $database->connect_error);
}
?>