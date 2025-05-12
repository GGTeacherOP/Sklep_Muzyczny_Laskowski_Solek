<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';

  if (!isset($_SESSION['employee_id'])) {
    header('Location: home.php');
    exit();
  }

  $employee_id = $_SESSION['employee_id'];

  $sql = "SELECT * FROM pracownicy WHERE identyfikator LIKE '$employee_id';";
  $result = mysqli_query($connection, $sql);
  $employee = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
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
  <link href="../assets/css/home.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <script type="module" src="../assets/js/home.js"></script>
  <title>Sklep Muzyczny - Panel</title>
</head>
<body>
<main class="fade-in">
  <?php include '../components/header.php'; ?>

  <h1>PANEL ADMINISTRATORA</h1>
</main>
<?php mysqli_close($connection); ?>
<?php include '../components/footer.php'; ?>
</body>
</html>