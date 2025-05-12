<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/cart_actions.php';
  include_once '../includes/auth/user_login.php';
  include_once '../includes/auth/employee_login.php';
  include_once '../includes/auth/register.php';

  $errors = [
    'email' => '',
    'password' => '',
    'employee' => '',
    'employee_password' => '',
    'register_email' => '',
    'register_password' => '',
    'register_username' => '',
  ];

  $values = [
    'email' => '',
    'employee_id' => '',
    'register_email' => '',
    'register_username' => '',
  ];

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleLogin($connection, $errors, $values);
    handleEmployeeLogin($connection, $errors, $values);
    handleRegistration($connection, $errors, $values);
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
  <title>Logowanie - Sklep Muzyczny</title>
</head>
<body>
<main class="fade-in">
  <?php include '../components/header.php'; ?>

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
<?php mysqli_close($connection); ?>
</body>
</html>
