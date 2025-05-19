<?php
  function renderCategoryCard(array $category) : string
  {
    /** @var mysqli $connection */
    global $connection;

    // Pobierz najpopularniejszy produkt z danej kategorii
    $sql = "
      SELECT 
        iz.url,
        iz.alt_text
      FROM instrumenty i
      JOIN instrument_zdjecia iz ON i.id = iz.instrument_id AND iz.kolejnosc = 1
      LEFT JOIN zamowienie_szczegoly zs ON i.id = zs.instrument_id
      WHERE i.kategoria_id = {$category['id']}
      GROUP BY i.id, iz.url, iz.alt_text
      ORDER BY COUNT(zs.id) DESC
      LIMIT 1
    ";

    $result = mysqli_query($connection, $sql);
    $imageHtml = '<div aria-hidden="true" class="instrument-icon"></div>';
    
    if ($result && $image = mysqli_fetch_assoc($result)) {
      $imageUrl = "../assets/images/" . $image['url'];
      $imageHtml = "<div aria-hidden=\"true\" class=\"instrument-icon\">
        <img src=\"{$imageUrl}\" alt=\"{$image['alt_text']}\" />
      </div>";
    }

    return "
    <a href=\"katalog.php?category_id={$category['id']}\" class=\"instrument-card fade-in\" role=\"button\" tabindex=\"1\">
      {$imageHtml}
      <span class=\"instrument-name\">{$category['nazwa']}</span>
    </a>
    ";
  }

?>