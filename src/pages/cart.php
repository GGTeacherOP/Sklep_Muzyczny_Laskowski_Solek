<?php
  /** @var mysqli $connection */
  include_once '../includes/db_config.php';
  include_once '../includes/session_config.php';
  include_once '../includes/render_cart_item.php';
  include_once '../includes/cart_actions.php';
  include_once '../includes/format_price.php';

  $userId = $_SESSION['user_id'] ?? NULL;
  $promoCode = $_SESSION['promo_code'] ?? NULL;
  $currentDate = date('Y-m-d H:i:s');

  $discount = 0;
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promoCode = mysqli_real_escape_string($connection, $_POST['promo_code']);
    $_SESSION['promo_code'] = $promoCode;
  }

  if (!empty($promoCode)) {
    $query = "
        SELECT znizka FROM kody_promocyjne
        WHERE kod = '$promoCode' 
          AND aktywna = 1
          AND data_rozpoczecia <= '$currentDate'
          AND data_zakonczenia >= '$currentDate'
    ";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
      $promo = mysqli_fetch_assoc($result);
      $discount = $promo['znizka'];
    }
    mysqli_free_result($result);
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
<main class="fade-in">
  <?php include '../components/header.php'; ?>

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
            $totalPriceForItems = $totalBuy + $totalRent;
            $discountAmount = $totalPriceForItems * ($discount / 100);
            $delivery = min($totalPriceForItems / 100, 20);
            $vatTax = round($totalPriceForItems * 0.23, 2);
            $totalAmount = $totalPriceForItems - $discountAmount + $delivery + $vatTax;
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
        <button class="checkout-button">Przejdź do kasy</button>
      </aside>
    </section>
  </section>
</main>
<?php mysqli_close($connection); ?>
</body>
</html>
