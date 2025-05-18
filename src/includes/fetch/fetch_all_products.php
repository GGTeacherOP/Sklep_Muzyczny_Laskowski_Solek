<?php
  function fetchAllProducts(mysqli $connection) : mysqli_result
  {
    $sql = "
      SELECT instrumenty.*, kategorie_instrumentow.nazwa as \"nazwa_kategorii\", producenci.nazwa as \"nazwa_producenta\"
      FROM instrumenty
      JOIN kategorie_instrumentow
      ON instrumenty.kategoria_id = kategorie_instrumentow.id
      JOIN producenci
      ON instrumenty.producent_id = producenci.id
      GROUP BY instrumenty.id
    ";

    return mysqli_query($connection, $sql);
  }
?>