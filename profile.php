<?php
  $server_name = "localhost";
  $user_name = "root";
  $password = "";
  $database_name = "sm";

  $errors = array(
    'email' => '',
    'password' => '',
    'employee' => '',
    'employee_password' => '',
  );

  $values = array(
    'email' => '',
    'employee_id' => '',
  );

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $connection = mysqli_connect($server_name, $user_name, $password, $database_name);

    if (!$connection) {
      die("Połączenie nieudane: " . mysqli_connect_error());
    }

    if (!empty($_POST['email'])) {
      $email = $_POST['email'];
      $password = $_POST['password'];
      $values['email'] = htmlspecialchars($email);

      $query = "SELECT id, haslo FROM uzytkownicy WHERE email = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($user = mysqli_fetch_assoc($result)) {
        if ($password === $user['haslo']) {
          session_start();
          $_SESSION['user_id'] = $user['id'];
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

    mysqli_close($connection);
  }

  $active_form = !empty($values['employee_id']) ? 'employee' : 'login';
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
        <span>Koszyk</span>
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
      <button class="login-tab" data-tab="register">Rejestracja</button>
      <button class="login-tab <?= $active_form === 'employee' ? 'active' : '' ?>" data-tab="employee">Panel
        Pracownika
      </button>
    </div>

    <form class="login-form <?= $active_form === 'login' ? 'active' : '' ?>" id="loginForm" method="POST"
          action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
      <div class="form-group">
        <label class="form-label" for="loginEmail">Email</label>
        <input class="form-input" id="loginEmail" name="email" required type="email" value="<?= $values['email'] ?>">
        <?php if (!empty($errors['email'])): ?>
          <p class="form-error"><?= $errors['email'] ?></p>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label" for="loginPassword">Hasło</label>
        <input class="form-input" id="loginPassword" name="password" required type="password">
        <?php if (!empty($errors['password'])): ?>: ?><p class="form-error"><?= $errors['password'] ?></p>
        <?php endif; ?>
      </div>
      <button class="form-button" type="submit">Zaloguj się</button>
      <a class="form-link" href="#">Zapomniałeś hasła?</a>
    </form>

    <form class="login-form" style="display: none" id="registerForm" method="POST"
          action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
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

    <form class="login-form <?= $active_form === 'employee' ? 'active' : '' ?>" id="employeeForm" method="POST"
          action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
      <div class="form-group">
        <label class="form-label" for="employeeId">ID Pracownika</label>
        <input class="form-input" id="employeeId" name="employeeId" required type="text"
               value="<?= $values['employee_id'] ?>">
        <?php if (!empty($errors['employee'])): ?><p class="form-error"><?= $errors['employee'] ?></p>
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
