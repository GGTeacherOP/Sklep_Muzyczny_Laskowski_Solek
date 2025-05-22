<?php
  include_once '../includes/helpers/format_helpers.php';

  function renderProductCard(array $product, string $type) : string
  {
    $price = formatPrice($product['cena_sprzedazy']);
    $url = "../assets/images/" . $product['url'];
    $rating = isset($product['srednia_ocena']) ? floatval($product['srednia_ocena']) : 0;
    $ratingCount = isset($product['liczba_ocen']) ? intval($product['liczba_ocen']) : 0;
    $stock = intval($product['stan_magazynowy']);

    // Generowanie gwiazdek oceny
    $ratingStars = '';
    for ($i = 1; $i <= 5; $i++) {
      if ($i <= $rating) {
        $ratingStars .= '<i class="fa-solid fa-star"></i>';
      } elseif ($i - 0.5 <= $rating) {
        $ratingStars .= '<i class="fa-solid fa-star-half-stroke"></i>';
      } else {
        $ratingStars .= '<i class="fa-regular fa-star"></i>';
      }
    }

    // Przygotowanie informacji o dostępności
    $availabilityClass = $stock > 0 ? 'in-stock' : 'out-of-stock';
    $availabilityText = $stock > 0 ? "Dostępny ($stock szt.)" : 'Niedostępny';
    $availabilityIcon = $stock > 0 ? 'fa-check-circle' : 'fa-times-circle';

    $availabilityHtml = "
        <div class=\"product-availability {$availabilityClass}\">
            <i class=\"fa-solid {$availabilityIcon}\"></i>
            <span>{$availabilityText}</span>
        </div>
    ";

    $ratingHtml = "
        <div class=\"product-rating\">
            <div class=\"stars\">{$ratingStars}</div>
        </div>
    ";

    return "
    <article class=\"product-card\">
        <div class=\"product-image\">
            <img alt=\"{$product['alt_text']}\" src=\"{$url}\">
            <span class=\"category-badge\">{$product['nazwa_kategorii']}</span>
        </div>
        <div class=\"product-info\">
            <h3 class=\"product-name\">{$product['nazwa']}</h3>
            {$ratingHtml}
            <p class=\"product-price\">{$price}</p>
            {$availabilityHtml}
            <div class=\"product-actions\">
                <form method=\"post\" action=\"home.php\">
                    <input type=\"hidden\" name=\"product_id\" value=\"{$product['id']}\">
                    <input type=\"hidden\" name=\"product_type\" value=\"{$type}\">
                </form>
                <a href=\"produkt.php?id={$product['id']}\" class=\"product-action-btn view-details-btn\">
                    <i class=\"fa-solid fa-eye\"></i> Wyświetl informacje
                </a>
            </div>
        </div>
    </article>
    ";
  }
?>