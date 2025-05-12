<?php
  function getPopularProducts(mysqli $connection, string $type = 'buy', int $limit = 10) : mysqli_result
  {
    if ($type === 'buy') {
      $sql = "
            SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa AS 'nazwa_kategorii'
            FROM instrumenty
            JOIN zamowienie_szczegoly
            ON instrumenty.id = zamowienie_szczegoly.instrument_id
            JOIN zamowienia
            ON zamowienie_szczegoly.zamowienie_id = zamowienia.id
            AND zamowienia.status NOT LIKE 'anulowane'
            JOIN instrument_zdjecia
            ON instrumenty.id = instrument_zdjecia.instrument_id
            AND instrument_zdjecia.kolejnosc = 1
            JOIN kategorie_instrumentow
            ON instrumenty.kategoria_id = kategorie_instrumentow.id
            GROUP BY zamowienie_szczegoly.instrument_id
            ORDER BY COUNT(zamowienie_szczegoly.instrument_id) DESC
            LIMIT $limit;
        ";
    } elseif ($type === 'rent') {
      $sql = "
            SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa AS 'nazwa_kategorii'
            FROM instrumenty
            JOIN wypozyczenia
            ON instrumenty.id = wypozyczenia.instrument_id
            AND wypozyczenia.status NOT IN ('anulowane', 'uszkodzone')
            JOIN instrument_zdjecia
            ON instrumenty.id = instrument_zdjecia.instrument_id
            AND instrument_zdjecia.kolejnosc = 1
            JOIN kategorie_instrumentow
            ON instrumenty.kategoria_id = kategorie_instrumentow.id
            GROUP BY wypozyczenia.instrument_id
            ORDER BY COUNT(wypozyczenia.instrument_id) DESC
            LIMIT $limit;
        ";
    } else {
      throw new InvalidArgumentException("Invalid product type: $type");
    }

    return mysqli_query($connection, $sql);
  }
?>
