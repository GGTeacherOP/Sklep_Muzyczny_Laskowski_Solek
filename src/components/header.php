<?php
  include_once '../includes/helpers/cart_helpers.php';

  $current_page = basename($_SERVER['PHP_SELF']);
  $home_page = 'home.php';
  $cart_page = 'cart.php';
  $profile_page = 'profile.php';
  $admin_page = 'panel.php';

  $active_class = function ($page) use ($current_page) {
    return $current_page === $page ? 'active_subpage' : '';
  };
?>
<header class="header">
  <div class="logo">
    <img alt="Logo Sklepu Muzycznego" src="../assets/images/Logo/logo_muzyczny.png">
  </div>
  <form class="search-bar" role="search" method="get" action="katalog.php">
    <input type="text" name="search" aria-label="Wyszukiwarka instrumentów" class="search-input" placeholder="Szukaj po nazwie, opisie lub kodzie produktu..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    <button aria-label="Wyszukaj" class="search-button" type="submit">
      <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
    </button>
  </form>
  <nav class="tray">
    <button aria-label="Strona główna" class="tray-item <?= $active_class($home_page) ?>"
            title="Przejdź do strony głównej" type="button">
      <i aria-hidden="true" class="fa-solid fa-home"></i>
      <span>Główna</span>
    </button>
    <button aria-label="Koszyk" class="tray-item <?= $active_class($cart_page) ?>" title="Przejdź do koszyka"
            type="button">
      <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
      <span>Koszyk (<?= getTotalItemsInCart() ?>)</span>
    </button>
    <button aria-label="Profil użytkownika" class="tray-item <?= $active_class($profile_page) ?>"
            title="Przejdź do swojego profilu" type="button">
      <i aria-hidden="true" class="fa-solid fa-user"></i>
      <span>Profil</span>
    </button>
    <?php
      if (isset($_SESSION['employee_id'])) {
        echo "
        <button aria-label=\"Panel admina\" class=\"tray-item {$active_class($admin_page)}\" title=\"Przejdź do panelu administratora\" type=\"button\">
          <i aria-hidden=\"true\" class=\"fa-solid fa-toolbox\"></i>
          <span>Panel</span>
        </button>
        ";
      }
    ?>
  </nav>
</header>
<?php
