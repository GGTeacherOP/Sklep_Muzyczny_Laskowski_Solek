<?php
  function renderBrandCard(array $producer) : string
  {
    /** @var mysqli $connection */
    global $connection;

    // Pobierz najpopularniejszy produkt od danego producenta
    $sql = "
      SELECT 
        iz.url,
        iz.alt_text
      FROM instrumenty i
      JOIN instrument_zdjecia iz ON i.id = iz.instrument_id AND iz.kolejnosc = 1
      LEFT JOIN zamowienie_szczegoly zs ON i.id = zs.instrument_id
      WHERE i.producent_id = {$producer['id']}
      GROUP BY i.id, iz.url, iz.alt_text
      ORDER BY COUNT(zs.id) DESC
      LIMIT 1
    ";

    $result = mysqli_query($connection, $sql);
    $imageHtml = '<div aria-hidden="true" class="brand-icon"></div>';
    
    if ($result && $image = mysqli_fetch_assoc($result)) {
      $imageUrl = "../assets/images/" . $image['url'];
      $imageHtml = "<div aria-hidden=\"true\" class=\"brand-icon\">
        <img src=\"{$imageUrl}\" alt=\"{$image['alt_text']}\" />
      </div>";
    }

    return "
    <a href=\"katalog.php?producer_id={$producer['id']}\" class=\"brand-card fade-in\" role=\"button\" tabindex=\"1\">
      {$imageHtml}
      <span class=\"brand-name\">{$producer['nazwa']}</span>
    </a>
    ";
  }
?> 