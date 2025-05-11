<?php
  session_start();

  $totalItems = 0;
  if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productType => $products) {
      $totalItems += count($products);
    }
  }

  $server_name = "localhost";
  $user_name = "root";
  $password = "";
  $database_name = "sm";

  $errors = array(
    'email' => '',
    'password' => '',
    'employee' => '',
    'employee_password' => '',
    'register_email' => '',
    'register_password' => '',
    'register_username' => '',
  );

  $values = array(
    'email' => '',
    'employee_id' => '',
    'register_email' => '',
    'register_username' => '',
  );

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $connection = mysqli_connect($server_name, $user_name, $password, $database_name);

    if (!$connection) {
      die("Połączenie nieudane: " . mysqli_connect_error());
    }

    if (!empty($_POST['loginEmail'])) {
      $email = $_POST['loginEmail'];
      $password = $_POST['loginPassword'];
      $values['email'] = htmlspecialchars($email);

      $query = "SELECT id, haslo FROM uzytkownicy WHERE email = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($user = mysqli_fetch_assoc($result)) {
        if ($password === $user['haslo']) {
          $_SESSION['user_id'] = $user['id'];
          loadUserCart($connection, $user['id']);
          header("Location: home.php");
          exit();
        } else {
          $errors['password'] = "Nieprawidłowe hasło.";
        }
      } else {
        $errors['email'] = "Nie znaleziono konta z tym adresem email.";
      }

      mysqli_stmt_close($stmt);
    }

    if (!empty($_POST['employeeId'])) {
      $employee_id = $_POST['employeeId'];
      $employee_password = $_POST['employeePassword'];
      $values['employee_id'] = htmlspecialchars($employee_id);

      $query = "
        SELECT pracownicy.*, uzytkownicy.haslo 
        FROM pracownicy 
        JOIN uzytkownicy ON pracownicy.uzytkownik_id = uzytkownicy.id 
        WHERE pracownicy.identyfikator = ?
      ";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 's', $employee_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($employee = mysqli_fetch_assoc($result)) {
        if ($employee_password === $employee['haslo']) {
          session_start();
          $_SESSION['user_id'] = $employee['uzytkownik_id'];
          $_SESSION['employee_id'] = $employee['id'];
          loadUserCart($connection, $employee['uzytkownik_id']);
          header("Location: home.php");
          exit();
        } else {
          $errors['employee_password'] = "Nieprawidłowe hasło.";
        }
      } else {
        $errors['employee'] = "Nie znaleziono pracownika z tym ID.";
      }

      mysqli_stmt_close($stmt);
    }

    if (!empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {
      $username = $_POST['username'];
      $email = $_POST['email'];
      $password = $_POST['password'];
      $password_confirm = $_POST['passwordConfirm'];
      $values['register_email'] = htmlspecialchars($email);
      $values['register_username'] = htmlspecialchars($username);

      if ($password !== $password_confirm) {
        $errors['register_password'] = "Hasła nie są zgodne.";
      } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{2,32}$/', $password)) {
        $errors['register_password'] = "Hasło musi mieć od 2 do 32 znaków, w tym małą literę, dużą literę, cyfrę i znak specjalny.";
      }

      $query = "SELECT id FROM uzytkownicy WHERE email = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if (mysqli_fetch_assoc($result)) {
        $errors['register_email'] = "Ten adres email jest już zarejestrowany.";
      }

      mysqli_stmt_close($stmt);

      if (empty($errors['register_email']) && empty($errors['register_password'])) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $user_query = "INSERT INTO uzytkownicy (nazwa_uzytkownika, email, haslo) VALUES (?, ?, ?)";
        $user_stmt = mysqli_prepare($connection, $user_query);
        mysqli_stmt_bind_param($user_stmt, 'sss', $username, $email, $hashed_password);

        if (mysqli_stmt_execute($user_stmt)) {
          $user_id = mysqli_insert_id($connection);

          $client_query = "INSERT INTO klienci (uzytkownik_id) VALUES (?)";
          $client_stmt = mysqli_prepare($connection, $client_query);
          mysqli_stmt_bind_param($client_stmt, 'i', $user_id);

          if (mysqli_stmt_execute($client_stmt)) {
            session_start();
            $_SESSION['user_id'] = $user_id;
            header("Location: home.php");
            exit();
          } else {
            $errors['register'] = "Wystąpił problem podczas tworzenia konta klienta. Spróbuj ponownie.";
          }

          mysqli_stmt_close($client_stmt);
        } else {
          $errors['register_username'] = "Wystąpił problem podczas rejestracji użytkownika. Spróbuj ponownie.";
        }

        mysqli_stmt_close($user_stmt);
      }
    }

    mysqli_close($connection);
  }

  function loadUserCart(mysqli $connection, int $userId) : void
  {
    $_SESSION['cart'] = $_SESSION['cart'] ?? ["buy" => [], "rent" => []];

    $query = "
        SELECT ks.instrument_id, ks.typ, ks.ilosc, i.cena
        FROM koszyk_szczegoly ks
        JOIN instrumenty i ON ks.instrument_id = i.id
        WHERE ks.koszyk_id = (SELECT id FROM koszyk WHERE klient_id = $userId)
    ";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $productId = intval($row['instrument_id']);
        $type = $row['typ'];
        $quantity = intval($row['ilosc']);

        $_SESSION['cart'][$type][$productId] = [
          'quantity' => $quantity,
        ];
      }
      mysqli_free_result($result);
    }
  }


  $active_form = !empty($values['employee_id']) ? 'employee' : (!empty($values['register_email']) ? 'register' : 'login');
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
  <link href="profile.css" rel="stylesheet">
  <script type="module" src="_header.js"></script>
  <script src="profile.js" defer></script>
  <title>Logowanie - Sklep Muzyczny</title>
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
      <button aria-label="Koszyk" class="tray-item" title="Przejdź do koszyka" type="button">
        <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
        <span>Koszyk (<?= $totalItems ?>)</span>
      </button>
      <button aria-label="Profil użytkownika - aktualnie wyświetlana podstrona" class="tray-item active_subpage"
              title="Przejdź do swojego profilu" type="button">
        <i aria-hidden="true" class="fa-solid fa-user"></i>
        <span>Profil</span>
      </button>
      <button aria-label="Strona główna" class="tray-item" title="Przejdź do strony głównej" type="button">
        <i aria-hidden="true" class="fa-solid fa-home"></i>
        <span>Główna</span>
      </button>
    </nav>
  </header>

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

</main>
</body>
</html>
