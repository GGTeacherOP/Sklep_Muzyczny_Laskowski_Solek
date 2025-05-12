<?php
  function getProductCategories(mysqli $connection) : mysqli_result
  {
    $sql = "SELECT kategorie_instrumentow.nazwa FROM kategorie_instrumentow;";

    return mysqli_query($connection, $sql);
  }

?>