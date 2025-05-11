<header class="header">
  <div class="logo">
    <img alt="Logo Sklepu Muzycznego" src="src/assets/images/logo_sklepu.png">
  </div>
  <form class="search-bar" role="search">
    <input aria-label="Wyszukiwarka instrumentów" class="search-input" placeholder="Szukaj instrumentów..." type="text">
    <button aria-label="Wyszukaj" class="search-button" type="button">
      <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
    </button>
  </form>
  <nav class="tray">
    <?php
      $current_page = basename($_SERVER['PHP_SELF']);

      $home_page = 'home.php';
      $cart_page = 'cart.php';
      $profile_page = 'profile.php';

      $active_class = function ($page) use ($current_page) {
        return $current_page === $page ? 'active_subpage' : '';
      };
    ?>

    <button aria-label="Strona główna" class="tray-item <?= $active_class($home_page) ?>"
            title="Przejdź do strony głównej" type="button">
      <i aria-hidden="true" class="fa-solid fa-home"></i>
      <span>Główna</span>
    </button>
    <?php
      $totalItems = 0;
      if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productType => $products) {
          $totalItems += count($products);
        }
      }
    ?>
    <button aria-label="Koszyk" class="tray-item <?= $active_class($cart_page) ?>" title="Przejdź do koszyka"
            type="button">
      <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
      <span>Koszyk (<?= $totalItems ?>)</span>
    </button>
    <button aria-label="Profil użytkownika" class="tray-item <?= $active_class($profile_page) ?>"
            title="Przejdź do swojego profilu" type="button">
      <i aria-hidden="true" class="fa-solid fa-user"></i>
      <span>Profil</span>
    </button>
  </nav>
</header>
<?php
