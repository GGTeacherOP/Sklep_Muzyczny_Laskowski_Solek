<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';

  if (!isset($_SESSION['employee_id'])) {
    header('Location: home.php');
    exit();
  }

  $employee_id = $_SESSION['employee_id'];

// Zabezpieczenie przed SQL Injection
$stmt = $connection->prepare("SELECT * FROM pracownicy JOIN uzytkownicy ON pracownicy.uzytkownik_id = uzytkownicy.id WHERE identyfikator = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    session_destroy();
    header('Location: home.php?error=invalid_employee');
    exit();
}

  // Pobierz nazwę stanowiska
$stmt = $connection->prepare("SELECT s.nazwa FROM stanowiska s JOIN pracownicy p ON s.id = p.stanowisko_id WHERE p.id = ?");
$stmt->bind_param("i", $employee['id']);
$stmt->execute();
$stanowisko_result = $stmt->get_result();
$stanowisko = $stanowisko_result->fetch_assoc();
$role = $stanowisko['nazwa'];
$stmt->close();
  $username = $employee['nazwa_uzytkownika'];

  $pages = [
  'products' => [
    'roles' => ['pracownik', 'manager', 'właściciel', 'informatyk'],
    'icon' => 'fas fa-box',
    'title' => 'Produkty'
  ],
  'categories' => [
    'roles' => ['pracownik', 'manager', 'właściciel', 'informatyk'],
    'icon' => 'fas fa-tags',
    'title' => 'Kategorie produktów'
  ],
  'brands' => [
    'roles' => ['pracownik', 'manager', 'właściciel', 'informatyk'],
    'icon' => 'fas fa-industry',
    'title' => 'Producenci'
  ],
  'reviews' => [
    'roles' => ['manager', 'właściciel'],
    'icon' => 'fas fa-star',
    'title' => 'Oceny produktów'
  ],
  'orders' => [
    'roles' => ['pracownik', 'manager', 'właściciel'],
    'icon' => 'fas fa-shopping-cart',
    'title' => 'Zamówienia'
  ],
  'promotions' => [
    'roles' => ['właściciel', 'informatyk'],
    'icon' => 'fas fa-percent',
    'title' => 'Kody promocyjne'
  ],
  'employees' => [
    'roles' => ['manager', 'właściciel'],
    'icon' => 'fas fa-users',
    'title' => 'Pracownicy'
  ],
  'customers' => [
    'roles' => ['właściciel', 'informatyk'],
    'icon' => 'fas fa-user-friends',
    'title' => 'Klienci'
  ],
  'messages' => [
    'roles' => ['sekretarka', 'właściciel'],
    'icon' => 'fas fa-envelope',
    'title' => 'Wiadomości'
  ],
  'deliveries' => [
    'roles' => ['pracownik', 'manager', 'właściciel'],
    'icon' => 'fas fa-truck',
    'title' => 'Dostawy'
  ],
  'finances' => [
    'roles' => ['właściciel'],
    'icon' => 'fas fa-chart-line',
    'title' => 'Finanse'
  ],
  'positions' => [
    'roles' => ['właściciel'],
    'icon' => 'fas fa-user-tie',
    'title' => 'Stanowiska'
  ]
  ];

$page = $_GET['view'] ?? '';

// Sprawdzanie dostępu do wybranej strony
if (!empty($page)) {
  if (!isset($pages[$page]) || !in_array($role, $pages[$page]['roles'])) {
    header('Location: panel.php');
    exit();
  }
  $page_file = __DIR__ . "\\panel\\$page.php";
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="instrumenty muzyczne, sklep muzyczny, gitary, basy, instrumenty strunowe, perkusje, sprzęt muzyczny"
        name="keywords">
  <meta content="Panel administratora sklepu muzycznego - zarządzaj produktami, zamówieniami i więcej."
    name="description">
  <meta content="noindex, nofollow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/panel.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <script src="../assets/js/panel.js"></script>
  <title>Panel Administratora<?php echo $page ? ' - ' . $pages[$page]['title'] : ''; ?></title>
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="fade-in">
  <div class="admin-panel">
    <?php if (empty($page)): ?>
      <div class="admin-header">
        <div class="admin-header-top">
        <div class="welcome-message">
          Witaj, <?php echo htmlspecialchars($username); ?>
        </div>
        <div class="current-date">
          <?php setlocale(LC_TIME, "pl_PL.UTF-8"); ?>
          <?= strftime('%e %B, %Y') ?>
        </div>
        </div>
        <div class="info-message">
          Panel administratora Sklepu Muzycznego.
        </div>
      </div>

      <div class="admin-nav-grid">
        <?php foreach ($pages as $key => $data): ?>
          <?php if (in_array($role, $data['roles'])): ?>
            <a href="?view=<?php echo $key; ?>" class="admin-nav-card">
              <div class="admin-nav-card-icon">
                <i class="<?php echo $data['icon']; ?>"></i>
              </div>
              <div class="admin-nav-card-content">
                <h3 class="admin-nav-card-title"><?php echo $data['title']; ?></h3>
              </div>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Widok konkretnej sekcji -->
      <div class="admin-section-header">
        <?php if ($page === 'orders' && isset($_GET['view_details'])): ?>
          <h2 class="admin-section-title">Informacje o zamówieniu #<?php echo htmlspecialchars($_GET['view_details']); ?></h2>
          <a href="panel.php?view=orders" class="admin-back-button">
            <i class="fas fa-arrow-left"></i>
            Powrót do listy
          </a>
        <?php else: ?>
          <h2 class="admin-section-title"><?php echo $pages[$page]['title']; ?></h2>
          <a href="panel.php" class="admin-back-button">
            <i class="fas fa-arrow-left"></i>
            Powrót do menu
          </a>
        <?php endif; ?>
      </div>

      <div class="admin-content">
    <?php
      if (file_exists($page_file)) {
        include $page_file;
      } else {
        echo '<p>Nie znaleziono strony.</p>';
      }
    ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php mysqli_close($connection); ?>
</body>
</html>