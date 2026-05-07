<?php
$servername = "localhost";
$usern = "root";
$password = "";
$database = "framewise";
$conn = mysqli_connect($servername, $usern, $password, $database);

if (!$conn){
    die("Connection failed : " .mysqli_connect_error());
}   