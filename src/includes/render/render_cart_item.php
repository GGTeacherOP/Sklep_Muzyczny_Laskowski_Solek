<?php
  function renderCartItem(array $product, string $type = 'buy') : string
  {
    $productId = $product['id'];
    $name = htmlspecialchars($product['nazwa']);
    $category = htmlspecialchars($product['nazwa_kategorii']);
    $imageUrl = htmlspecialchars($product['url']);
    $altText = htmlspecialchars($product['alt_text']);
    $quantity = intval($product['quantity']);
    $price = formatPrice($product['cena_sprzedazy'], $quantity);

    return "
    <li class=\"cart-item\">
      <img alt=\"{$altText}\" src=\"{$imageUrl}\">
      <div class=\"cart-item-details\">
        <div class=\"cart-item-product-details\">
          <div class=\"cart-item-text\">
            <div class=\"cart-item-name\">{$name}</div>
            <div class=\"cart-item-category\">{$category}</div>
          </div>
          
          <form method=\"POST\" action=\"cart.php\" class=\"quantity-form\">
            <input type=\"hidden\" name=\"product_id\" value=\"{$productId}\">
            <input type=\"hidden\" name=\"type\" value=\"{$type}\">
            <div class=\"cart-item-quantity\">
              <button type=\"submit\" name=\"update_quantity\" value=\"{$quantity}\" class=\"quantity-button\" onclick=\"this.form.quantity.value = Math.max(1, this.form.quantity.value - 1)\">
                <i class=\"fa-solid fa-minus\"></i>
              </button>
              <input class=\"quantity-input\" name=\"quantity\" min=\"1\" type=\"number\" value=\"{$quantity}\">
              <button type=\"submit\" name=\"update_quantity\" value=\"{$quantity}\" class=\"quantity-button\" onclick=\"this.form.quantity.value = parseInt(this.form.quantity.value) + 1\">
                <i class=\"fa-solid fa-plus\"></i>
              </button>
            </div>
          </form>
          
          <div class=\"cart-item-price\">{$price}</div>
        </div>

        <form method=\"POST\" action=\"cart.php\" class=\"remove-item-form\">
          <input type=\"hidden\" name=\"product_id\" value=\"{$productId}\">
          <input type=\"hidden\" name=\"type\" value=\"{$type}\">
          <button type=\"submit\" name=\"remove\" class=\"remove-button\">
            <i class=\"fa-solid fa-trash\"></i>
          </button>
        </form>
      </div>
    </li>
    ";
  }
?>