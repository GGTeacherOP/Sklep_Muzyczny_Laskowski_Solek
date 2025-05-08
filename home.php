<?php
$server_name = "localhost";
$user_name = "root";
$password = "";
$databse_name = "sm";
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
  <link href="home.css" rel="stylesheet">
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
    <div class="instrument-types-header">
      <h4 class="instrument-types-title">Typy produktów</h4>
      <div class="instrument-types-controls">
        <button class="view-all-button" type="button"><strong>Wyświetl wszystko</strong></button>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-left"></i></button>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-right"></i></button>
      </div>
    </div>
    <div class="instrument-types-list fade-in">
        <?php
        $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);
        if (!$connection) {
            die("Połączenie nieudane: " . mysqli_connect_error());
        }

        $sql = "
SELECT kategorie_instrumentow.nazwa 
FROM kategorie_instrumentow;
";
        $result = mysqli_query($connection, $sql);

        while ($row = mysqli_fetch_array($result)) {
            echo "
            <div aria-label=\"Wybierz typ {$row['nazwa']}\" class=\"instrument-card fade-in\" role=\"button\" tabindex=\"1\">
              <div aria-hidden=\"true\" class=\"instrument-icon\"></div>
              <span class=\"instrument-name\">{$row['nazwa']}</span>
            </div>";
        }

        mysqli_close($connection);
        ?>
    </div>
  </section>
  <section class="popular-products fade-in">
    <div class="popular-section">
      <h2 class="section-title">Najczęściej Kupowane</h2>
      <div class="products-grid">
          <?php
          $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);
          if (!$connection) {
              die("Połączenie nieudane: " . mysqli_connect_error());
          }

          $sql = "
SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa as 'nazwa_kategorii'
FROM instrumenty
JOIN zamowienie_szczegoly
ON instrumenty.id = zamowienie_szczegoly.instrument_id
JOIN instrument_zdjecia
ON instrumenty.id = instrument_zdjecia.instrument_id AND instrument_zdjecia.kolejnosc = 1
JOIN kategorie_instrumentow
ON instrumenty.kategoria_id = kategorie_instrumentow.id
JOIN zamowienia
ON zamowienie_szczegoly.zamowienie_id = zamowienia.id AND zamowienia.status NOT LIKE 'anulowane'
GROUP BY zamowienie_szczegoly.instrument_id
ORDER BY COUNT(zamowienie_szczegoly.instrument_id) DESC
LIMIT 10;
";
          $result = mysqli_query($connection, $sql);

          while ($row = mysqli_fetch_array($result)) {
              echo "
              <article class=\"product-card\">
                <div class=\"product-image\">
                  <img alt=\"$row[alt_text]\" src=\"$row[nazwa]\">
                  <span class=\"category-badge\">$row[nazwa_kategorii]</span>\
                </div>
                <div class=\"product-info\">
                  <h3 class=\"product-name\">$row[nazwa]</h3>
                  <p class=\"product-price\">$row[cena]</p>
                  <div class=\"product-actions\">
                    <button class=\"product-action-btn more-info-btn\">Więcej <i class=\"fas fa-arrow-right\"></i></button>
                    <button class=\"product-action-btn buy-product-btn\">Kup <i class=\"fas fa-shopping-cart\"></i></button>
                  </div>
                </div>
              </article>
              ";
          }

          mysqli_close($connection);
          ?>
      </div>
    </div>

    <div class="popular-section">
      <h2 class="section-title">Najczęściej Wypożyczane</h2>
      <div class="products-grid">
          <?php
          $connection = mysqli_connect($server_name, $user_name, $password, $databse_name);
          if (!$connection) {
              die("Połączenie nieudane: " . mysqli_connect_error());
          }

          $sql = "
SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa as 'nazwa_kategorii'
FROM instrumenty
JOIN wypozyczenia
ON instrumenty.id = wypozyczenia.instrument_id AND wypozyczenia.status NOT IN ('anulowane', 'uszkodzone')
JOIN instrument_zdjecia
ON instrumenty.id = instrument_zdjecia.instrument_id AND instrument_zdjecia.kolejnosc = 1
JOIN kategorie_instrumentow
ON instrumenty.kategoria_id = kategorie_instrumentow.id
GROUP BY wypozyczenia.instrument_id
ORDER BY COUNT(wypozyczenia.instrument_id) DESC
LIMIT 10;
";
          $result = mysqli_query($connection, $sql);

          while ($row = mysqli_fetch_array($result)) {
              echo "
              <article class=\"product-card\">
                <div class=\"product-image\">
                  <img alt=\"$row[alt_text]\" src=\"$row[nazwa]\">
                  <span class=\"category-badge\">$row[nazwa_kategorii]</span>\
                </div>
                <div class=\"product-info\">
                  <h3 class=\"product-name\">$row[nazwa]</h3>
                  <p class=\"product-price\">$row[cena]</p>
                  <div class=\"product-actions\">
                    <button class=\"product-action-btn more-info-btn\">Więcej <i class=\"fas fa-arrow-right\"></i></button>
                    <button class=\"product-action-btn buy-product-btn\">Kup <i class=\"fas fa-shopping-cart\"></i></button>
                  </div>
                </div>
              </article>
              ";
          }

          mysqli_close($connection);
          ?>
      </div>
    </div>
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