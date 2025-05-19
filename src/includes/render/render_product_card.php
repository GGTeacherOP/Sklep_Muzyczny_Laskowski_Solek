<?php
  include_once '../includes/helpers/format_helpers.php';

  function renderProductCard(array $product, string $type) : string
  {
    $price = formatPrice($product['cena_sprzedazy']);

    return "
    <article class=\"product-card\">
      <div class=\"product-image\">
        <img alt=\"{$product['alt_text']}\" src=\"{$product['url']}\">
        <span class=\"category-badge\">{$product['nazwa_kategorii']}</span>
      </div>
      <div class=\"product-info\">
        <h3 class=\"product-name\">{$product['nazwa']}</h3>
        <p class=\"product-price\">{$price}</p>
        <div class=\"product-actions\">
          <form method=\"post\" action=\"home.php\">
            <input type=\"hidden\" name=\"product_id\" value=\"{$product['id']}\">
            <input type=\"hidden\" name=\"product_type\" value=\"{$type}\">
            <button type=\"submit\" name=\"add_to_cart\" class=\"product-action-btn buy-product-btn\">
              Kup <i class=\"fa-solid fa-cart-plus\"></i>
            </button>
          </form>
          <a href=\"produkt.php?id={$product['id']}\" class=\"product-action-btn view-details-btn\">
            Wyświetl informacje <i class=\"fa-solid fa-eye\"></i>
          </a>
        </div>
      </div>
    </article>
    ";
  }
?>