<?php
  $server_name = "localhost";
  $user_name = "root";
  $password = "";
  $databse_name = "sm";

  $instrument_type_id = $_GET["instrument"];
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
  <link href="instruments.css" rel="stylesheet">
  <script type="module" src="_header.js"></script>
  <script type="module" src="home.js"></script>
  <title>Sklep Muzyczny</title>
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
      <button aria-label="Profil użytkownika" class="tray-item" title="Przejdź do swojego profilu" type="button">
        <i aria-hidden="true" class="fa-solid fa-user"></i>
        <span>Profil</span>
      </button>
      <button aria-label="Strona główna - aktualnie wyświetlana podstrona" class="tray-item active_subpage"
              title="Przejdź do strony głównej" type="button">
        <i aria-hidden="true" class="fa-solid fa-home"></i>
        <span>Główna</span>
      </button>
    </nav>
  </header>

  <section class="instrument-types fade-in">
    <div class="instrument-types-list fade-in">
      <?php
        $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);
        if (!$connection) {
          die("Połączenie nieudane: " . mysqli_connect_error());
        }

        $sql = "
SELECT kategorie_instrumentow.nazwa, kategorie_instrumentow.id
FROM kategorie_instrumentow
ORDER BY kategorie_instrumentow.id;
";
        $result = mysqli_query($connection, $sql);

        while ($row = mysqli_fetch_array($result)) {
          echo "
            <a href=\"instruments.php?instrument={$row['id']}\" aria-label=\"Wybierz typ {$row['nazwa']}\" class=\"fade-in\" role=\"button\" tabindex=\"1\">
              <span class=\"instrument-name\">{$row['nazwa']}</span>
            </a>";
        }
        mysqli_close($connection);
      ?>
    </div>
  </section>
  <section class="category-banner fade-in">
    <?php
      $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);

      if (!$connection) {
        die("Połączenie nieudane: " . mysqli_connect_error());
      }

      $sql = "
SELECT kategorie_instrumentow.nazwa
FROM kategorie_instrumentow
WHERE kategorie_instrumentow.id = $instrument_type_id";
      $result = mysqli_query($connection, $sql);

      $row = mysqli_fetch_array($result);
    ?>
    <div class="breadcrumbs">
      <a href="home.php"><span class="category-pill"><i class="far fa-folder"></i> Wszystkie kategorie</span></a>
      <span class="breadcrumb-arrow"><i class="fas fa-chevron-right"></i></span>
      <?php
        echo "<span class=\"breadcrumb-current\">{$row['nazwa']}</span>";
      ?>
    </div>
    <div class="banner-title-container">
      
      <?php
        echo "<h1 class='banner-title'>{$row['nazwa']}</h1>";

        mysqli_close($connection);
      ?>
    </div>
    
    <div class="banner-bottom">
      <span class="banner-bottom-el">SKLEP MUZIK</span>
    </div>
  </section>

  <section class="products-section fade-in">
    
    <section class="product-header">
      <h1 class="product-title">Dostępna ilość</h1>
      <?php
      $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);

      if (!$connection) {
        die("Połączenie nieudane: " . mysqli_connect_error());
      }

      $sql = "
SELECT COUNT(instrumenty.id)
FROM instrumenty
WHERE kategoria_id = $instrument_type_id
AND instrumenty.stan_magazynowy > 0;";
        $result = mysqli_query($connection, $sql);
        $row = mysqli_fetch_array($result);
        echo "<span class=\"product-count\">$row[0]</span>"
      ?>
    </section>

    <section class="product-list">
      <?php
        $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);

        if (!$connection) {
          die("Połączenie nieudane: " . mysqli_connect_error());
        }

        $sql = "
        SELECT instrumenty.nazwa, instrumenty.opis, instrumenty.cena, COUNT(instrument_oceny.ocena) 'ilosc_ocen', ROUND(AVG(instrument_oceny.ocena), 1) 'srednia_ocen', 
        instrument_zdjecia.url, instrument_zdjecia.alt_text 
        FROM instrumenty JOIN instrument_oceny ON instrumenty.id = instrument_oceny.instrument_id 
        JOIN instrument_zdjecia ON instrumenty.id = instrument_zdjecia.instrument_id 
        WHERE kategoria_id = $instrument_type_id 
        GROUP BY(instrumenty.id);";

        $result = mysqli_query($connection, $sql);

        while($row = mysqli_fetch_array($result)){
          echo "
            <div class=\"product-el\">

            <div class=\"product-img\">
              <img src=\"{$row['url']}\" alt=\"{$row['alt_text']}\">
            </div>

            <div class=\"product-info\">
              <h2 class=\"product-name\">{$row['nazwa']}</h2>
              <span class=\"product-desc\">{$row['opis']}</span>

              <div class=\"product-rating\">
                <span class=\"rating\">{$row['srednia_ocen']}</span>
                <span class=\"product-rating-count\">{$row['ilosc_ocen']}</span>
              </div>

              <span class=\"product-price\">{$row['cena']}</span>

              <div class=\"product-action\">
                <button class=\"buy-product-btn\"><i class=\"fas fa-shopping-cart\"></i></button>
                <button class=\"follow-product-btn\"><i class=\"far fa-heart\"></i></button>
              </div>

            </div>
          </div>
          ";
        }

        mysqli_close($connection);
      ?>

      <!-- <div class="product-el">

        <div class="product-img">
          <img src="" alt="nazwa gitary">
        </div>

        <div class="product-info">
          <h2 class="product-name">Fender Strat</h2>
          <span class="product-desc">Opis gitary</span>

          <div class="product-rating">
            <span class="rating">5</span>
            <span class="product-rating-count">156</span>
          </div>

          <span class="product-price">1000</span>

          <div class="product-action">
            <button class="buy-product-btn"><i class="fas fa-shopping-cart"></i></button>
            <button class="follow-product-btn"><i class="far fa-heart"></i></button>
          </div>

        </div>
      </div> -->
      
    </section>
  </section>

</main>
<footer class="fade-in">
  <div class="footer-container">
    <div class="footer-section">
      <h3 class="footer-section-title"><i class="fas fa-store"></i> O Nas</h3>
      <p>Sklep muzyczny z instrumentami, akcesoriami i sprzętem nagłośnieniowym. Profesjonalne doradztwo!</p>
    </div>

    <div class="footer-section">
      <h3 class="footer-section-title"><i class="fas fa-link"></i> Linki</h3>
      <ul class="footer-list">
        <li><a class="footer-list-el" href="home.php"><i class="fas fa-home"></i> Strona główna</a></li>
        <li><a class="footer-list-el" href="#"><i class="fas fa-guitar"></i> Instrumenty</a></li>
        <li><a class="footer-list-el" href="#"><i class="fas fa-volume-up"></i> Nagłośnienie</a></li>
        <li><a class="footer-list-el" href="mailto:sklepmuzyczny@example.com"><i class="fas fa-envelope"></i>
            Kontakt</a></li>
      </ul>
    </div>

    <div class="footer-section">
      <h3 class="footer-section-title"><i class="fas fa-address-card"></i> Kontakt</h3>
      <ul class="footer-list">
        <li><i class="fas fa-envelope"></i> sklepmuzyczny@example.com</li>
        <li><i class="fas fa-phone"></i> +48 111 222 333</li>
        <li><i class="fas fa-map-marker-alt"></i> Kazimierza Jagiellończyka 3 Mielec</li>
      </ul>
    </div>

    <div class="footer-section">
      <h3 class="footer-section-title"><i class="fas fa-share-alt"></i> Social Media</h3>
      <ul class="footer-list">
        <li><a href="https://facebook.com" target="_blank"><i class="fab fa-facebook"></i> Facebook</a></li>
        <li><a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i> Instagram</a></li>
        <li><a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i> Twitter</a></li>
        <li><a href="https://youtube.com" target="_blank"><i class="fab fa-youtube"></i> YouTube</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <i class="far fa-copyright"></i> 2025 SklepMuzik
  </div>
</footer>
</body>
</html>