<?php
  session_start();

  $server_name = "localhost";
  $user_name = "root";
  $password = "";
  $database_name = "sm";

  $connection = mysqli_connect($server_name, $user_name, $password, $database_name);
  if (!$connection) {
    die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $productId = intval($_POST['product_id']);
    $type = $_POST['type'];
    unset($_SESSION['cart'][$type][$productId]);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $productId = intval($_POST['product_id']);
    $type = $_POST['type'];
    $quantity = max(1, intval($_POST['quantity']));
    $_SESSION['cart'][$type][$productId]['quantity'] = $quantity;
  }

  $cartItems = ["buy" => [], "rent" => []];
  $totalItems = 0;

  $productIds = array_unique(array_merge(
    array_keys($_SESSION['cart']['buy'] ?? []),
    array_keys($_SESSION['cart']['rent'] ?? [])
  ));

  if (!empty($productIds)) {
    $totalItems = count($_SESSION['cart']['buy']) + count($_SESSION['cart']['rent']);

    $idList = implode(",", array_map('intval', $productIds));
    $sql = "
SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa as 'nazwa_kategorii'
FROM instrumenty
JOIN instrument_zdjecia ON instrumenty.id = instrument_zdjecia.instrument_id
JOIN kategorie_instrumentow ON instrumenty.kategoria_id = kategorie_instrumentow.id
WHERE instrumenty.id IN ($idList)
";
    $result = mysqli_query($connection, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
      $productId = $row['id'];
      foreach (['buy', 'rent'] as $type) {
        if (isset($_SESSION['cart'][$type][$productId])) {
          $row['quantity'] = $_SESSION['cart'][$type][$productId]['quantity'];
          $cartItems[$type][$productId] = $row;
        }
      }
    }
    mysqli_free_result($result);
  }

  $totalBuy = 0;
  $totalRent = 0;

  foreach ($cartItems['buy'] as $item) {
    $totalBuy += $item['cena'] * $item['quantity'];
  }

  foreach ($cartItems['rent'] as $item) {
    $totalRent += $item['cena'] * $item['quantity'];
  }

  function formatPrice(float $price, int $quantity = 1): string {
    $total = $price * $quantity;
    return number_format($total, 2, ',', ' ') . ' zł';
  }

  function renderCartItem(array $product, string $type = 'buy'): string {
    $productId = $product['id'];
    $name = htmlspecialchars($product['nazwa']);
    $category = htmlspecialchars($product['nazwa_kategorii']);
    $imageUrl = htmlspecialchars($product['url']);
    $altText = htmlspecialchars($product['alt_text']);
    $quantity = intval($product['quantity']);
    $price = formatPrice($product['cena'], $quantity);

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

  mysqli_close($connection);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="koszyk, produkty, sklep muzyczny" name="keywords">
  <meta content="Koszyk użytkownika sklepu muzycznego" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="cart.css" rel="stylesheet">
  <script defer src="cart.js"></script>
  <script src="_header.js" type="module"></script>
  <title>Koszyk - Sklep Muzyczny</title>
</head>
<body>
<main class="fade-in">
  <header class="header">
    <div class="logo">
      <img alt="Logo Sklepu Muzycznego" src="assets/images/logo_sklepu.png">
    </div>
    <form class="search-bar" role="search">
      <input aria-label="Wyszukiwarka instrumentów" class="search-input" placeholder="Szukaj instrumentów..."
             type="text">
      <button aria-label="Wyszukaj" class="search-button" type="button">
        <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>
    <nav class="tray">
      <button aria-label="Koszyk - aktualnie wyświetlana podstrona" class="tray-item active_subpage"
              title="Przejdź do koszyka" type="button">
        <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
        <span>Koszyk (<?= $totalItems ?>)</span>
      </button>
      <button aria-label="Profil użytkownika" class="tray-item" title="Przejdź do swojego profilu" type="button">
        <i aria-hidden="true" class="fa-solid fa-user"></i>
        <span>Profil</span>
      </button>
      <button aria-label="Strona główna" class="tray-item" title="Przejdź do strony głównej" type="button">
        <i aria-hidden="true" class="fa-solid fa-home"></i>
        <span>Główna</span>
      </button>
    </nav>
  </header>

  <section class="cart-container-empty <?= $totalItems === 0 ? 'active' : '' ?>">
    <i class="fa-solid fa-box-open empty-cart-icon"></i>
    <h2>Twój koszyk jest pusty</h2>
    <button>Znajdź coś dla siebie <i class="fa-solid fa-arrow-right"></i></button>
  </section>

  <section class="cart-container-full <?= $totalItems > 0 ? 'active' : '' ?>">
    <section class="cart-container">
      <div class="cart-items">
        <h2>Koszyk</h2>

        <div class="cart-section <?= $totalBuy > 0 ? 'visible' : '' ?>" id="buy-section">
          <h3>Kupno</h3>
          <ul>
            <?php
              foreach ($cartItems['buy'] as $product) {
                echo renderCartItem($product);
              }
              unset($product);
            ?>

          </ul>
        </div>

        <div class="cart-section <?= $totalRent > 0 ? 'visible' : '' ?>" id="rent-section">
          <h3>Wypożyczenie</h3>
          <ul>
            <?php
              foreach ($cartItems['rent'] as $product) {
                echo renderCartItem($product, 'rent');
              }
              unset($product);
            ?>
          </ul>
        </div>

      </div>

      <aside class="cart-summary">
        <div class="cart-summary-inner">
          <h2>Podsumowanie</h2>

          <div class="promo-code-container">
            <input class="promo-code-input" id="promo-code" placeholder="Kod promocyjny" type="text" maxlength="16">
          </div>

          <div class="cart-summary-section">
            <p>Kupno: <span id="total-buy"><?= formatPrice($totalBuy) ?></span></p>
            <p>Wypożyczenie: <span id="total-rent"><?= formatPrice($totalRent) ?></span></p>
          </div>

          <hr>

          <?php
            $totalPriceForItems = $totalBuy + $totalRent;
            $discount = 0;
            $delivery = min($totalPriceForItems / 100, 20);
            $vatTax = round($totalPriceForItems * 0.23, 2);
            $totalAmount = $totalPriceForItems - $discount + $delivery + $vatTax;
          ?>

          <div class="cart-summary-section">
            <p>Koszyk: <span id="subtotal"><?= formatPrice($totalPriceForItems) ?></span></p>
            <p>Zniżka: <span id="discount"><?= formatPrice($discount) ?></span></p>
            <p>Dostawa: <span id="delivery"><?= formatPrice($delivery) ?></span></p>
            <p>Podatek: <span id="tax"><?= formatPrice($vatTax) ?></span></p>
          </div>

          <hr>

          <div class="cart-summary-section">
            <p>Łączna kwota: <span id="total-amount"><?= formatPrice($totalAmount) ?></span></p>
          </div>

        </div>
        <button class="checkout-button">Przejdź do kasy</button>
      </aside>
    </section>
  </section>
</main>
</body>
</html>
