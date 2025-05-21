<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/helpers/cart_helpers.php';
  include_once '../includes/helpers/format_helpers.php';

  // Sprawdź czy użytkownik jest zalogowany
  if (!isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
  }

  $userId = $_SESSION['user_id'];
  $promoCode = $_SESSION['promo_code'] ?? NULL;
  $discount = 0;

  if (!empty($promoCode)) {
    $discount = getPromoDiscount($connection, $promoCode);
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

  $calculations = calculateTotalAmount($totalBuy, $totalRent, $discount);
  $totalAmount = $calculations['totalAmount'];

  // Obsługa formularza
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address']);
    
    if (empty($address)) {
      $error = "Adres wysyłki jest wymagany";
    } else {
      // Pobierz ID klienta
      $query = "SELECT id FROM klienci WHERE uzytkownik_id = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 'i', $userId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      if ($result && mysqli_num_rows($result) > 0) {
        $clientId = mysqli_fetch_assoc($result)['id'];
        mysqli_free_result($result);

        // Utwórz zamówienie
        $query = "INSERT INTO zamowienia (klient_id, adres_wysylki, kod_promocyjny_id) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        $promoCodeId = !empty($promoCode) ? getPromoCodeId($connection, $promoCode) : NULL;
        mysqli_stmt_bind_param($stmt, 'isi', $clientId, $address, $promoCodeId);
        
        if (mysqli_stmt_execute($stmt)) {
          $orderId = mysqli_insert_id($connection);
          
          // Dodaj produkty do zamówienia
          foreach (['buy', 'rent'] as $type) {
            foreach ($cartItems[$type] as $product) {
              $query = "INSERT INTO zamowienie_szczegoly (zamowienie_id, instrument_id, ilosc, cena) VALUES (?, ?, ?, ?)";
              $stmt = mysqli_prepare($connection, $query);
              mysqli_stmt_bind_param($stmt, 'iiid', $orderId, $product['id'], $product['quantity'], $product['cena_sprzedazy']);
              mysqli_stmt_execute($stmt);
            }
          }
          
          // Wyczyść koszyk
          $_SESSION['cart'] = ['buy' => [], 'rent' => []];
          unset($_SESSION['promo_code']);
          
          // Wyczyść koszyk w bazie danych
          if (isset($_SESSION['user_id'])) {
            $query = "SELECT k.id FROM klienci k JOIN uzytkownicy u ON k.uzytkownik_id = u.id WHERE u.id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($client = mysqli_fetch_assoc($result)) {
              $query = "DELETE FROM koszyk_szczegoly WHERE koszyk_id IN (SELECT id FROM koszyk WHERE klient_id = ?)";
              $stmt = mysqli_prepare($connection, $query);
              mysqli_stmt_bind_param($stmt, 'i', $client['id']);
              mysqli_stmt_execute($stmt);
            }
          }
          
          // Przekieruj do strony potwierdzenia
          header("Location: order_confirmation.php?order_id=" . $orderId);
          exit();
        }
      }
    }
  }
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="zamówienie, sklep muzyczny" name="keywords">
  <meta content="Złóż zamówienie w sklepie muzycznym" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/checkout.css" rel="stylesheet">
  <script src="../assets/js/header.js" type="module"></script>
  <title>Zamówienie - Sklep Muzyczny</title>
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="fade-in">
  <div class="checkout-container">
    <h1>Złóż zamówienie</h1>
    
    <?php if (isset($error)): ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <div class="checkout-content">
      <form method="POST" class="checkout-form">
        <div class="form-group">
          <label for="address">Adres wysyłki</label>
          <textarea id="address" name="address" required rows="4" placeholder="Wprowadź pełny adres wysyłki..."></textarea>
        </div>

        <div class="order-summary">
          <h2>Podsumowanie zamówienia</h2>
          <div class="summary-item">
            <span>Kupno:</span>
            <span><?= formatPrice($totalBuy) ?></span>
          </div>
          <div class="summary-item">
            <span>Wypożyczenie:</span>
            <span><?= formatPrice($totalRent) ?></span>
          </div>
          <?php if ($discount > 0): ?>
            <div class="summary-item">
              <span>Zniżka (<?= $discount ?>%):</span>
              <span>-<?= formatPrice($calculations['discountAmount']) ?></span>
            </div>
          <?php endif; ?>
          <div class="summary-item">
            <span>Dostawa:</span>
            <span><?= formatPrice($calculations['delivery']) ?></span>
          </div>
          <div class="summary-item">
            <span>Podatek VAT:</span>
            <span><?= formatPrice($calculations['vatTax']) ?></span>
          </div>
          <div class="summary-item total">
            <span>Łączna kwota:</span>
            <span><?= formatPrice($totalAmount) ?></span>
          </div>
        </div>

        <div class="form-actions">
          <a href="cart.php" class="back-button">Powrót do koszyka</a>
          <button type="submit" name="place_order" class="submit-button">Złóż zamówienie</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php mysqli_close($connection); ?>
</body>
</html> 