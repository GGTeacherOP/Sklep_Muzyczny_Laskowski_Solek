<?php
  $server_name = "localhost";
  $user_name = "root";
  $password = "";
  $databse_name = "sm";

  $email_error = "";
  $password_error = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);
    if (!$connection) {
      die("Połączenie nieudane: " . mysqli_connect_error());
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sprawdzenie, czy email istnieje
    $email_check_sql = "
SELECT id, haslo
FROM uzytkownicy
WHERE email = '$email';
";
    $email_check_result = mysqli_query($connection, $email_check_sql);

    if (mysqli_num_rows($email_check_result) === 0) {
      $email_error = "Nie znaleziono konta z tym adresem email.";
    } else {
      $user = mysqli_fetch_assoc($email_check_result);

      // Sprawdzenie hasła
      if ($user['haslo'] === $password) {
        session_start();
        $_SESSION['uzytkownik_id'] = $user['id'];
        header("Location: home.php");
        exit();
      } else {
        $password_error = "Nieprawidłowe hasło!";
      }
    }

    mysqli_close($connection);
  }
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
  <style>
    .error {
      color: red;
      font-size: 14px;
      margin: 5px 0 0;
    }
  </style>
</head>
<body>
<main class="fade-in">
  <header class="header">
    <div class="logo">
      <img alt="Logo Sklepu Muzycznego" src="assets/images/logo_sklepu.png">
    </div>
    <form class="search-bar" role="search">
      <input aria-label="Wyszukiwarka instrumentów" class="search-input" placeholder="Szukaj instrumentów..." type="text">
      <button aria-label="Wyszukaj" class="search-button" type="button">
        <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>
    <nav class="tray">
      <button aria-label="Koszyk" class="tray-item" title="Przejdź do koszyka" type="button">
        <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
        <span>Koszyk</span>
      </button>
      <button aria-label="Profil użytkownika - aktualnie wyświetlana podstrona" class="tray-item active_subpage" title="Przejdź do swojego profilu" type="button">
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
      <button class="login-tab active" data-tab="login">Logowanie</button>
      <button class="login-tab" data-tab="register">Rejestracja</button>
      <button class="login-tab" data-tab="employee">Panel Pracownika</button>
    </div>

    <form class="login-form" id="loginForm" method="POST" action="profile.php">
      <div class="form-group">
        <label class="form-label" for="loginEmail">Email</label>
        <input class="form-input" id="loginEmail" name="email" required type="email">
        <?php if (!empty($email_error)): ?>
          <p class="error"><?= $email_error ?></p>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label" for="loginPassword">Hasło</label>
        <input class="form-input" id="loginPassword" name="password" required type="password">
        <?php if (!empty($password_error)): ?>
          <p class="error"><?= $password_error ?></p>
        <?php endif; ?>
      </div>
      <button class="form-button" type="submit">Zaloguj się</button>
      <a class="form-link" href="#">Zapomniałeś hasła?</a>
    </form>

    <form class="login-form" id="registerForm" style="display: none;" method="POST" action="profile.php">
      <div class="form-group">
        <label class="form-label" for="registerName">Nazwa użytkownika</label>
        <input class="form-input" id="registerName" name="username" required type="text">
      </div>
      <div class="form-group">
        <label class="form-label" for="registerEmail">Email</label>
        <input class="form-input" id="registerEmail" name="email" required type="email">
      </div>
      <div class="form-group">
        <label class="form-label" for="registerPassword">Hasło</label>
        <input class="form-input" id="registerPassword" name="password" required type="password">
      </div>
      <div class="form-group">
        <label class="form-label" for="registerPasswordConfirm">Potwierdź hasło</label>
        <input class="form-input" id="registerPasswordConfirm" name="passwordConfirm" required type="password">
      </div>
      <button class="form-button" type="submit">Zarejestruj się</button>
    </form>

    <form class="login-form" id="employeeForm" style="display: none;" method="POST" action="profile.php">
      <div class="form-group">
        <label class="form-label" for="employeeId">ID Pracownika</label>
        <input class="form-input" id="employeeId" name="employeeId" required type="text">
      </div>
      <div class="form-group">
        <label class="form-label" for="employeePassword">Hasło</label>
        <input class="form-input" id="employeePassword" name="password" required type="password">
      </div>
      <button class="form-button" type="submit">Zaloguj jako pracownik</button>
    </form>
  </div>
</main>
</body>
</html>
