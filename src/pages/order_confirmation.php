<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/helpers/format_helpers.php';

  // Sprawdź czy użytkownik jest zalogowany
  if (!isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
  }

  // Sprawdź czy przekazano ID zamówienia
  if (!isset($_GET['order_id'])) {
    header('Location: profile.php');
    exit();
  }

  $orderId = intval($_GET['order_id']);
  $userId = $_SESSION['user_id'];

  // Pobierz dane zamówienia
  $query = "SELECT z.*, k.uzytkownik_id 
            FROM zamowienia z 
            JOIN klienci k ON z.klient_id = k.id 
            WHERE z.id = ? AND k.uzytkownik_id = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, 'ii', $orderId, $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $order = mysqli_fetch_assoc($result);
  mysqli_free_result($result);

  if (!$order) {
    header('Location: profile.php');
    exit();
  }

  // Pobierz szczegóły zamówienia
  $query = "SELECT zs.*, i.nazwa, i.kod_produktu 
            FROM zamowienie_szczegoly zs 
            JOIN instrumenty i ON zs.instrument_id = i.id 
            WHERE zs.zamowienie_id = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, 'i', $orderId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $orderItems = [];
  while ($item = mysqli_fetch_assoc($result)) {
    $orderItems[] = $item;
  }
  mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="potwierdzenie zamówienia, sklep muzyczny" name="keywords">
  <meta content="Potwierdzenie zamówienia w sklepie muzycznym" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/order_confirmation.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <title>Potwierdzenie zamówienia - Sklep Muzyczny</title>
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="fade-in">
  <div class="confirmation-container">
    <div class="confirmation-header">
      <i class="fas fa-check-circle"></i>
      <h1>Dziękujemy za zamówienie!</h1>
      <p>Twoje zamówienie zostało przyjęte do realizacji.</p>
    </div>

    <div class="order-details">
      <h2>Szczegóły zamówienia #<?= $order['id'] ?></h2>
      
      <div class="order-info">
        <div class="info-group">
          <h3>Informacje o zamówieniu</h3>
          <p><strong>Data złożenia:</strong> <?= date('d.m.Y H:i', strtotime($order['data_zamowienia'])) ?></p>
          <p><strong>Status:</strong> <?= $order['status'] ?></p>
          <p><strong>Adres wysyłki:</strong> <?= nl2br(htmlspecialchars($order['adres_wysylki'])) ?></p>
        </div>
      </div>

      <h3>Zamówione produkty</h3>
      <div class="order-items">
        <?php foreach ($orderItems as $item): ?>
          <div class="order-item">
            <div class="item-details">
              <span class="item-name"><?= htmlspecialchars($item['nazwa']) ?></span>
              <span class="item-code"><?= htmlspecialchars($item['kod_produktu']) ?></span>
            </div>
            <div class="item-quantity">
              <span>Ilość: <?= $item['ilosc'] ?></span>
            </div>
            <div class="item-price">
              <span><?= formatPrice($item['cena']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="confirmation-actions">
      <a href="home.php" class="action-button home-button">
        <i class="fas fa-home"></i> Powrót do strony głównej
      </a>
      <a href="profile.php" class="action-button profile-button">
        <i class="fas fa-user"></i> Przejdź do profilu
      </a>
    </div>
  </div>
</main>
<?php mysqli_close($connection); ?>
</body>
</html> 