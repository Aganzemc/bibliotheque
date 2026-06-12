<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "bibliotheque";

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn){
    die("Erreur de connexion");
}

?>