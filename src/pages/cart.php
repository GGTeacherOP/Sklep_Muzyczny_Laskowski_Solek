<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/render/render_cart_item.php';
  include_once '../includes/helpers/cart_helpers.php';
  include_once '../includes/helpers/format_helpers.php';

  $userId = $_SESSION['user_id'] ?? NULL;
  $promoCode = $_SESSION['promo_code'] ?? NULL;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promoCode = mysqli_real_escape_string($connection, $_POST['promo_code']);
    $_SESSION['promo_code'] = $promoCode;
  }

  $discount = 0;
  if (!empty($promoCode)) {
    $discount = getPromoDiscount($connection, $promoCode);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $productId = intval($_POST['product_id']);
    $type = $_POST['type'];
    removeFromCart($productId, $type);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $productId = intval($_POST['product_id']);
    $type = $_POST['type'];
    $quantity = max(1, intval($_POST['quantity']));
    updateCartQuantity($productId, $type, $quantity);
  }

  // Inicjalizacja koszyka
  initializeCart();
  
  // Pobierz wszystkie ID produktów z koszyka sesji
  $productIds = array_unique(array_merge(
    array_keys($_SESSION['cart']['buy'] ?? []),
    array_keys($_SESSION['cart']['rent'] ?? [])
  ));

  $cartItems = ["buy" => [], "rent" => []];
  $totalItems = 0;

  if (!empty($productIds)) {
    $totalItems = count($_SESSION['cart']['buy']) + count($_SESSION['cart']['rent']);
    getCartItemsFromDatabase($connection, $productIds, $cartItems);
  }

  $totalBuy = 0;
  $totalRent = 0;

  foreach ($cartItems['buy'] as $item) {
    $totalBuy += $item['cena_sprzedazy'] * $item['quantity'];
  }

  foreach ($cartItems['rent'] as $item) {
    $totalRent += $item['cena_sprzedazy'] * $item['quantity'];
  }

  // Synchronizuj koszyk z bazą danych jeśli użytkownik jest zalogowany
  if ($userId) {
    syncCartWithDatabase($connection, $userId, $cartItems);
  }
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
  <link href="../assets/css/cart.css" rel="stylesheet">
  <script src="../assets/js/header.js" type="module"></script>
  <title>Koszyk - Sklep Muzyczny</title>
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="fade-in">
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

          <form method="POST" action="cart.php">
            <div class="promo-code-container">
              <input class="promo-code-input" name="promo_code" id="promo-code" placeholder="Kod promocyjny" type="text"
                     maxlength="16" value="<?= htmlspecialchars($promoCode) ?>">
              <button class="promo-code-apply" type="submit">
                <i class="fa-solid fa-check"></i>
              </button>
            </div>
          </form>

          <div class="cart-summary-section">
            <p>Kupno: <span id="total-buy"><?= formatPrice($totalBuy) ?></span></p>
            <p>Wypożyczenie: <span id="total-rent"><?= formatPrice($totalRent) ?></span></p>
          </div>

          <hr>

          <?php
            $calculations = calculateTotalAmount($totalBuy, $totalRent, $discount);

            $totalPriceForItems = $calculations['totalPriceForItems'];
            $discountAmount = $calculations['discountAmount'];
            $delivery = $calculations['delivery'];
            $vatTax = $calculations['vatTax'];
            $totalAmount = $calculations['totalAmount'];
          ?>

          <div class="cart-summary-section">
            <p>Koszyk: <span id="subtotal"><?= formatPrice($totalPriceForItems) ?></span></p>
            <p>Zniżka: <span id="discount"><?= formatPrice($discountAmount) ?></span></p>
            <p>Dostawa: <span id="delivery"><?= formatPrice($delivery) ?></span></p>
            <p>Podatek: <span id="tax"><?= formatPrice($vatTax) ?></span></p>
          </div>

          <hr>

          <div class="cart-summary-section">
            <p>Łączna kwota: <span id="total-amount"><?= formatPrice($totalAmount) ?></span></p>
          </div>

        </div>
        <div class="cart-actions">
            <a href="home.php" class="continue-shopping">Kontynuuj zakupy</a>
            <?php if (!empty($cartItems)): ?>
                <a href="checkout.php" class="checkout-button">Przejdź do kasy</a>
            <?php endif; ?>
        </div>
      </aside>
    </section>
  </section>
</main>
<?php mysqli_close($connection); ?>
</body>
</html>
