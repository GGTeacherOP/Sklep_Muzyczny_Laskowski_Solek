<?php
  function getProductCategories(mysqli $connection) : mysqli_result
  {
    $sql = "SELECT id, nazwa FROM kategorie_instrumentow ORDER BY nazwa;";

    return mysqli_query($connection, $sql);
  }

?>