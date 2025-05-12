<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';

  if (!isset($_SESSION['employee_id'])) {
    header('Location: home.php');
    exit();
  }

  $employee_id = $_SESSION['employee_id'];

  $sql = "SELECT * FROM pracownicy JOIN uzytkownicy ON pracownicy.uzytkownik_id = uzytkownicy.id WHERE identyfikator LIKE '$employee_id';";
  $result = mysqli_query($connection, $sql);
  $employee = mysqli_fetch_assoc($result);
  mysqli_free_result($result);

  $role = $employee['stanowisko'];
  $username = $employee['nazwa_uzytkownika'];

  $pages = [
    'products' => ['pracownik', 'manager', 'wlasciciel'],
    'categories' => ['pracownik', 'manager', 'wlasciciel'],
    'brands' => ['pracownik', 'manager', 'wlasciciel'],
    'reviews' => ['pracownik', 'manager', 'wlasciciel'],
    'orders' => ['manager', 'wlasciciel'],
    'promotions' => ['manager', 'wlasciciel'],
    'employees' => ['wlasciciel'],
    'clients' => ['wlasciciel']
  ];

  $page = $_GET['view'] ?? 'products';

  if (!isset($pages[$page]) || !in_array($role, $pages[$page])) {
    header('Location: panel.php?view=products');
    exit();
  }

  $page_file = __DIR__ . "\\pannel\\$page.php";
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="instrumenty muzyczne, sklep muzyczny, gitary, basy, instrumenty strunowe, perkusje, sprzęt muzyczny"
        name="keywords">
  <meta
    content="Sklep muzyczny online oferujący szeroki wybór instrumentów: gitary, basy, perkusje i więcej. Znajdź idealny sprzęt dla siebie."
    name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/panel.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <title>Panel Administratora - <?php echo ucfirst($page); ?></title>
</head>
<body>
<main class="fade-in">
  <?php include '../components/header.php'; ?>

  <h1>Witaj, <?php echo htmlspecialchars($username); ?>!</h1>
  <h2>Panel Administratora (<?php echo htmlspecialchars($role); ?>)</h2>

  <nav>
    <ul>
      <?php foreach ($pages as $key => $roles) : ?>
        <?php if (in_array($role, $roles)) : ?>
          <li><a href="?view=<?php echo $key; ?>"><?php echo ucfirst($key); ?></a></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>

  <section>
    <?php
      if (file_exists($page_file)) {
        include $page_file;
      } else {
        echo '<p>Nie znaleziono strony.</p>';
        echo $page_file;
      }
    ?>
  </section>
</main>
<?php mysqli_close($connection); ?>
<?php include '../components/footer.php'; ?>
</body>
</html>