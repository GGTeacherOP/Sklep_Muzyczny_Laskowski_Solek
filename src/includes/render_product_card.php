<?php
  function renderProductCard($product, $type)
  {
    return "
    <article class=\"product-card\">
      <div class=\"product-image\">
        <img alt=\"{$product['alt_text']}\" src=\"{$product['url']}\">
        <span class=\"category-badge\">{$product['nazwa_kategorii']}</span>
      </div>
      <div class=\"product-info\">
        <h3 class=\"product-name\">{$product['nazwa']}</h3>
        <p class=\"product-price\">{$product['cena']} PLN</p>
        <form method=\"post\" action=\"home.php\">
          <input type=\"hidden\" name=\"product_id\" value=\"{$product['id']}\">
          <input type=\"hidden\" name=\"product_type\" value=\"{$type}\">
          <button type=\"submit\" name=\"add_to_cart\" class=\"product-action-btn buy-product-btn\">
            Kup <i class=\"fa-solid fa-cart-plus\"></i>
          </button>
        </form>
      </div>
    </article>
    ";
  }

?>