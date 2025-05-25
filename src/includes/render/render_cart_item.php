<?php
  function renderCartItem(mysqli $connection, array $product, string $type = 'buy') : string
  {
    $productId = $product['id'];
    $name = htmlspecialchars($product['nazwa']);
    $category = htmlspecialchars($product['nazwa_kategorii']);
    $imageUrl = "../assets/images/" . htmlspecialchars($product['url']);
    $altText = htmlspecialchars($product['alt_text']);
    $quantity = intval($product['quantity']);
    $price = formatPrice($product['cena_sprzedazy'], $quantity);

    // Pobierz stan magazynowy produktu
    $stock = 0;
    $query = "SELECT stan_magazynowy FROM instrumenty WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
      $productData = mysqli_fetch_assoc($result);
      $stock = intval($productData['stan_magazynowy']);
    }
    mysqli_free_result($result);

    // Przygotuj atrybuty dla inputa quantity
    $maxQuantity = max(1, $stock); // Nigdy nie mniej niż 1
    $maxAttr = $quantity >= $stock ? 'max="'.$quantity.'"' : 'max="'.$stock.'"';
    $plusButtonDisabled = $quantity >= $stock ? 'disabled' : '';

    return "
    <li class=\"cart-item\">
      <img alt=\"{$altText}\" src=\"{$imageUrl}\">
      <div class=\"cart-item-details\">
        <div class=\"cart-item-product-details\">
          <div class=\"cart-item-text\">
            <div class=\"cart-item-name\">{$name}</div>
            <div class=\"cart-item-category\">{$category}</div>
            <div class=\"cart-item-category stock-info\">Dostępnych: {$stock}</div>
          </div>
          
          <form method=\"POST\" action=\"cart.php\" class=\"quantity-form\">
            <input type=\"hidden\" name=\"product_id\" value=\"{$productId}\">
            <input type=\"hidden\" name=\"type\" value=\"{$type}\">
            <div class=\"cart-item-quantity\">
              <button type=\"submit\" name=\"update_quantity\" value=\"{$quantity}\" class=\"quantity-button\" onclick=\"this.form.quantity.value = Math.max(1, this.form.quantity.value - 1)\">
                <i class=\"fa-solid fa-minus\"></i>
              </button>
              <input class=\"quantity-input\" name=\"quantity\" min=\"1\" {$maxAttr} type=\"number\" value=\"{$quantity}\">
              <button type=\"submit\" name=\"update_quantity\" value=\"{$quantity}\" class=\"quantity-button\" {$plusButtonDisabled} onclick=\"if(this.form.quantity.value < {$stock}) this.form.quantity.value = parseInt(this.form.quantity.value) + 1\">
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