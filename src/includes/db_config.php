<?php
  const DB_SERVER = 'localhost';
  const DB_USERNAME = 'root';
  const DB_PASSWORD = '';
  const DB_DATABASE = 'sm';

  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

  if (!$connection) {
    error_log("Connection failed: " . mysqli_connect_error());
    die("Connection failed: Unable to connect to the database.");
  }
?>
