<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/helpers/cart_helpers.php';
  include_once '../includes/auth/user_login.php';
  include_once '../includes/auth/employee_login.php';
  include_once '../includes/auth/register.php';
  include_once '../includes/fetch/fetch_client_orders.php';
  include_once '../includes/fetch/fetch_order_details.php';

  // Stałe dla statusów zamówień
  $ORDER_STATUSES = [
    'w przygotowaniu' => ['class' => 'status-badge warning'],
    'wysłane' => ['class' => 'status-badge info'],
    'dostarczone' => ['class' => 'status-badge success'],
    'anulowane' => ['class' => 'status-badge danger']
  ];

  $errors = [
    'email' => '',
    'password' => '',
    'employee' => '',
    'employee_password' => '',
    'register_email' => '',
    'register_password' => '',
    'register_username' => '',
    'profile_update' => '',
  ];

  $values = [
    'email' => '',
    'employee_id' => '',
    'register_email' => '',
    'register_username' => '',
  ];

  // Sprawdzenie czy użytkownik jest zalogowany
  $isLoggedIn = isset($_SESSION['user_id']);
  $userData = null;
  $userOrders = [];
  $userReviews = [];

  if ($isLoggedIn) {
    // Pobierz dane użytkownika
    $userId = $_SESSION['user_id'];
    $query = "SELECT u.*, k.id as klient_id FROM uzytkownicy u 
              LEFT JOIN klienci k ON u.id = k.uzytkownik_id 
              WHERE u.id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userData = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    // Pobierz zamówienia użytkownika
    if ($userData['klient_id']) {
      $userOrders = fetchClientOrders($userData['klient_id'], $connection);
    }

    // Pobierz opinie użytkownika
    $query = "SELECT io.*, i.nazwa as nazwa_instrumentu, i.kod_produktu 
              FROM instrument_oceny io 
              JOIN instrumenty i ON io.instrument_id = i.id 
              WHERE io.user_id = ? 
              ORDER BY io.data_oceny DESC";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($review = mysqli_fetch_assoc($result)) {
      $userReviews[] = $review;
    }
    mysqli_free_result($result);

    // Sprawdź czy mamy podgląd szczegółów zamówienia
    if (isset($_GET['order_details']) && is_numeric($_GET['order_details'])) {
      $orderDetails = fetchOrderDetails($_GET['order_details'], $connection);
      if ($orderDetails && $orderDetails['order']['klient_id'] == $userData['klient_id']) {
        $viewingOrderDetails = true;
      }
    }

    // Obsługa wylogowania
    if (isset($_POST['logout'])) {
      session_destroy();
      header('Location: profile.php');
      exit();
    }

    // Obsługa aktualizacji profilu
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
      $newUsername = trim($_POST['username']);
      $newEmail = trim($_POST['email']);
      $newPassword = trim($_POST['new_password']);
      $confirmPassword = trim($_POST['confirm_password']);

      $errors['profile_update'] = '';

      // Sprawdź czy email jest już zajęty przez innego użytkownika
      $query = "SELECT id FROM uzytkownicy WHERE email = ? AND id != ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 'si', $newEmail, $userId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      if (mysqli_num_rows($result) > 0) {
        $errors['profile_update'] = "Ten adres email jest już zajęty";
      } else {
        $updateQuery = "UPDATE uzytkownicy SET nazwa_uzytkownika = ?, email = ?";
        $params = [$newUsername, $newEmail];
        $types = 'ss';

        if (!empty($newPassword)) {
          if ($newPassword === $confirmPassword) {
            $updateQuery .= ", haslo = ?";
            $params[] = $newPassword;
            $types .= 's';
          } else {
            $errors['profile_update'] = "Nowe hasła nie są identyczne";
          }
        }

        $updateQuery .= " WHERE id = ?";
        $params[] = $userId;
        $types .= 'i';

        if (empty($errors['profile_update'])) {
          $stmt = mysqli_prepare($connection, $updateQuery);
          mysqli_stmt_bind_param($stmt, $types, ...$params);
          
          if (mysqli_stmt_execute($stmt)) {
            $userData['nazwa_uzytkownika'] = $newUsername;
            $userData['email'] = $newEmail;
            $_SESSION['success_message'] = "Profil został zaktualizowany pomyślnie";
          } else {
            $errors['profile_update'] = "Wystąpił błąd podczas aktualizacji profilu";
          }
        }
      }
    }

    // Obsługa anulowania zamówienia
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_order'])) {
      $order_id = (int)$_POST['order_id'];
      
      // Sprawdź czy zamówienie należy do użytkownika i czy można je anulować
      $check_sql = "SELECT z.* FROM zamowienia z 
                    JOIN klienci k ON z.klient_id = k.id 
                    WHERE z.id = ? AND k.uzytkownik_id = ? 
                    AND z.status != 'dostarczone' AND z.status != 'anulowane'";
      
      $stmt = mysqli_prepare($connection, $check_sql);
      mysqli_stmt_bind_param($stmt, "ii", $order_id, $userId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      if ($order = mysqli_fetch_assoc($result)) {
        // Aktualizuj status zamówienia
        $update_sql = "UPDATE zamowienia SET status = 'anulowane' WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
          $_SESSION['success_message'] = "Zamówienie zostało anulowane.";
          header("Location: profile.php");
          exit();
        } else {
          $errors['profile_update'] = "Wystąpił błąd podczas anulowania zamówienia.";
        }
      }
    }
  } else {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
      handleLogin($connection, $errors, $values);
      handleEmployeeLogin($connection, $errors, $values);
      handleRegistration($connection, $errors, $values);
    }
  }

  $active_form = !empty($values['employee_id']) ? 'employee' : 
  (!empty($values['register_email']) ? 'register' : 'login');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="logowanie, rejestracja, sklep muzyczny" name="keywords">
  <meta content="Panel logowania do sklepu muzycznego" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/profile.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <script src="../assets/js/profile.js" defer></script>
  <title><?= $isLoggedIn ? 'Mój Profil' : 'Logowanie' ?> - Sklep Muzyczny</title>
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="fade-in">
  <?php if ($isLoggedIn): ?>
    <?php if (isset($viewingOrderDetails) && $orderDetails): ?>
      <div class="order-details-container">
        <div class="order-details-header">
          <h2>Szczegóły zamówienia #<?= $orderDetails['order']['id'] ?></h2>
          <div class="button-group">
            <?php if ($orderDetails['order']['status'] !== 'dostarczone' && $orderDetails['order']['status'] !== 'anulowane'): ?>
              <form method="POST" class="cancel-order-form" onsubmit="return confirm('Czy na pewno chcesz anulować to zamówienie?');">
                <input type="hidden" name="order_id" value="<?= $orderDetails['order']['id'] ?>">
                <button type="submit" name="cancel_order" class="form-button btn-danger">
                  <i class="fas fa-times"></i> Anuluj zamówienie
                </button>
              </form>
            <?php endif; ?>
            <a href="profile.php" class="form-button">Powrót do profilu</a>
          </div>
        </div>

        <div class="order-info">
          <div class="info-group">
            <h3>Informacje o zamówieniu</h3>
            <p><strong>ID zamówienia:</strong> <?= $orderDetails['order']['id'] ?></p>
            <p><strong>Data złożenia:</strong> <?= $orderDetails['order']['data_zamowienia'] ?></p>
            <p><strong>Status:</strong> 
              <span class="status-badge <?= $ORDER_STATUSES[$orderDetails['order']['status']]['class'] ?>">
                <?= $orderDetails['order']['status'] ?>
              </span>
            </p>
            <p><strong>Wartość całkowita:</strong> <?= $orderDetails['summary']['total'] ?> zł</p>
          </div>

          <?php if ($orderDetails['summary']['discount']): ?>
          <div class="info-group">
            <h3>Informacje o zniżce</h3>
            <p><strong>Wysokość zniżki:</strong> <?= $orderDetails['summary']['discount_percent'] ?>%</p>
            <p><strong>Kwota zniżki:</strong> <?= $orderDetails['summary']['discount'] ?> zł</p>
          </div>
          <?php endif; ?>
        </div>

        <h3>Zamówione produkty</h3>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Produkt</th>
                <th>Kod produktu</th>
                <th>Ilość</th>
                <th>Cena</th>
                <th>Suma</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orderDetails['items'] as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item['nazwa']) ?></td>
                  <td><?= htmlspecialchars($item['kod_produktu']) ?></td>
                  <td><?= $item['ilosc'] ?></td>
                  <td><?= number_format($item['cena'], 2) ?> zł</td>
                  <td><?= number_format($item['cena'] * $item['ilosc'], 2) ?> zł</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4" class="text-right"><strong>Suma częściowa:</strong></td>
                <td><?= $orderDetails['summary']['subtotal'] ?> zł</td>
              </tr>
              <?php if ($orderDetails['summary']['discount']): ?>
              <tr>
                <td colspan="4" class="text-right"><strong>Zniżka (<?= $orderDetails['summary']['discount_percent'] ?>%):</strong></td>
                <td>-<?= $orderDetails['summary']['discount'] ?> zł</td>
              </tr>
              <?php endif; ?>
              <tr>
                <td colspan="4" class="text-right"><strong>Razem:</strong></td>
                <td><strong><?= $orderDetails['summary']['total'] ?> zł</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="profile-container">
        <div class="profile-header">
          <h1>Mój Profil</h1>
          <form method="POST" class="logout-form">
            <button type="submit" name="logout" class="form-button btn-danger">Wyloguj się</button>
          </form>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="success-message">
            <?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($errors['profile_update'])): ?>
          <div class="error-message">
            <?= $errors['profile_update'] ?>
          </div>
        <?php endif; ?>

        <div class="profile-tabs">
          <button class="profile-tab active" data-tab="profile">Dane profilu</button>
          <button class="profile-tab" data-tab="orders">Zamówienia</button>
          <button class="profile-tab" data-tab="reviews">Opinie</button>
        </div>

        <div class="profile-content active" id="profile">
          <form method="POST" class="profile-form">
            <div class="form-group">
              <label class="form-label" for="username">Nazwa użytkownika</label>
              <input class="form-input" type="text" id="username" name="username" 
                     value="<?= htmlspecialchars($userData['nazwa_uzytkownika']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">Email</label>
              <input class="form-input" type="email" id="email" name="email" 
                     value="<?= htmlspecialchars($userData['email']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="new_password">Nowe hasło (opcjonalne)</label>
              <input class="form-input" type="password" id="new_password" name="new_password">
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Potwierdź nowe hasło</label>
              <input class="form-input" type="password" id="confirm_password" name="confirm_password">
            </div>

            <div class="button-group">
              <button type="submit" name="update_profile" class="form-button">Aktualizuj profil</button>
            </div>
          </form>
        </div>

        <?php if (!isset($viewingOrderDetails)): ?>
        <div class="profile-content" id="orders">
          <h2>Twoje zamówienia</h2>
          <?php if (empty($userOrders)): ?>
            <p class="no-data">Nie masz jeszcze żadnych zamówień</p>
          <?php else: ?>
            <div class="orders-list">
              <?php foreach ($userOrders as $order): ?>
                <div class="order-item">
                  <div class="order-header">
                    <span class="order-number">Zamówienie #<?= $order['id'] ?></span>
                    <span class="order-date"><?= $order['data_zamowienia_format'] ?></span>
                    <span class="status-badge <?= $order['statusClass'] ?>"><?= $order['status'] ?></span>
                  </div>
                  <div class="order-details">
                    <span class="order-products">Liczba produktów: <?= $order['liczba_produktow'] ?></span>
                    <span class="order-value">Wartość: <?= number_format($order['wartosc_calkowita'], 2) ?> zł</span>
                  </div>
                  <div class="order-actions">
                    <a href="?order_details=<?= $order['id'] ?>" class="form-button">Zobacz szczegóły</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="profile-content" id="reviews">
          <h2>Twoje opinie</h2>
          <?php if (empty($userReviews)): ?>
            <p class="no-data">Nie dodałeś jeszcze żadnych opinii</p>
          <?php else: ?>
            <div class="reviews-list">
              <?php foreach ($userReviews as $review): ?>
                <div class="review-item">
                  <div class="review-header">
                    <span class="product-name"><?= htmlspecialchars($review['nazwa_instrumentu']) ?></span>
                    <span class="review-date"><?= date('d.m.Y H:i', strtotime($review['data_oceny'])) ?></span>
                  </div>
                  <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fas fa-star<?= $i <= $review['ocena'] ? ' active' : '' ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <p class="review-comment"><?= htmlspecialchars($review['komentarz']) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="login-container">
      <div class="login-tabs">
        <button class="login-tab <?= $active_form === 'login' ? 'active' : '' ?>" data-tab="login">Logowanie</button>
        <button class="login-tab <?= $active_form === 'register' ? 'active' : '' ?>" data-tab="register">Rejestracja
        </button>
        <button class="login-tab <?= $active_form === 'employee' ? 'active' : '' ?>" data-tab="employee">Panel
          Pracownika
        </button>
      </div>

      <form class="login-form <?= $active_form === 'login' ? 'active' : '' ?>" id="loginForm" method="POST"
            action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <div class="form-group">
          <label class="form-label" for="loginEmail">Email</label>
          <input class="form-input" id="loginEmail" name="loginEmail" required type="email"
                 value="<?= $values['email'] ?>">
          <?php if (!empty($errors['email'])): ?>
            <p class="form-error"><?= $errors['email'] ?></p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="loginPassword">Hasło</label>
          <input class="form-input" id="loginPassword" name="loginPassword" required type="password">
          <?php if (!empty($errors['password'])): ?>
            <p class="form-error"><?= $errors['password'] ?></p>
          <?php endif; ?>
        </div>
        <button class="form-button" type="submit">Zaloguj się</button>
        <a class="form-link" href="#">Zapomniałeś hasła?</a>
      </form>

      <form class="login-form <?= $active_form === 'register' ? 'active' : '' ?>" id="registerForm" method="POST"
            action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <div class="form-group">
          <label class="form-label" for="registerName">Nazwa użytkownika</label>
          <input class="form-input" id="registerName" name="username" required type="text"
                 value="<?= $values['register_username'] ?>">
          <?php if (!empty($errors['register_username'])): ?>
            <p class="form-error"><?= $errors['register_username'] ?></p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="registerEmail">Email</label>
          <input class="form-input" id="registerEmail" name="email" required type="email"
                 value="<?= $values['register_email'] ?>">
          <?php if (!empty($errors['register_email'])): ?>
            <p class="form-error"><?= $errors['register_email'] ?></p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="registerPassword">Hasło</label>
          <input class="form-input" id="registerPassword" name="password" required type="password">
        </div>
        <div class="form-group">
          <label class="form-label" for="registerPasswordConfirm">Potwierdź hasło</label>
          <input class="form-input" id="registerPasswordConfirm" name="passwordConfirm" required type="password">
          <?php if (!empty($errors['register_password'])): ?>
            <p class="form-error"><?= $errors['register_password'] ?></p>
          <?php endif; ?>
        </div>
        <button class="form-button" type="submit">Zarejestruj się</button>
      </form>

      <form class="login-form <?= $active_form === 'employee' ? 'active' : '' ?>" id="employeeForm" method="POST"
            action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <div class="form-group">
          <label class="form-label" for="employeeId">ID Pracownika</label>
          <input class="form-input" id="employeeId" name="employeeId" required type="text"
                 value="<?= $values['employee_id'] ?>">
          <?php if (!empty($errors['employee'])): ?>
            <p class="form-error"><?= $errors['employee'] ?></p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="employeePassword">Hasło</label>
          <input class="form-input" id="employeePassword" name="employeePassword" required type="password">
          <?php if (!empty($errors['employee_password'])): ?>
            <p class="form-error"><?= $errors['employee_password'] ?></p>
          <?php endif; ?>
        </div>
        <button class="form-button" type="submit">Zaloguj jako pracownik</button>
      </form>
    </div>
  <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.querySelectorAll('.profile-tab');
  const contents = document.querySelectorAll('.profile-content');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;

      // Usuń klasę active ze wszystkich zakładek i zawartości
      tabs.forEach(t => t.classList.remove('active'));
      contents.forEach(c => c.classList.remove('active'));

      // Dodaj klasę active do klikniętej zakładki i odpowiedniej zawartości
      tab.classList.add('active');
      document.getElementById(target).classList.add('active');
    });
  });
});
</script>

<?php mysqli_close($connection); ?>
</body>
</html>
