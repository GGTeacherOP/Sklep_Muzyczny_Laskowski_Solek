<?php
  /** @var mysqli $connection */
  include '../includes/db_config.php';

  session_start();

  if (isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $productType = $_POST['product_type'];
    $quantity = 1;

    if (!isset($_SESSION['cart'])) {
      $_SESSION['cart'] = [
        'buy' => [],
        'rent' => [],
      ];
    }

    if (!isset($_SESSION['cart'][$productType][$productId])) {
      $_SESSION['cart'][$productType][$productId] = [
        'product_id' => $productId,
        'quantity' => 0,
      ];
    }

    $_SESSION['cart'][$productType][$productId]['quantity'] += $quantity;

    header("Location: home.php");
    exit();
  }

  $totalItems = 0;
  if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productType => $products) {
      $totalItems += count($products);
    }
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
  <meta
    content="Sklep muzyczny online oferujący szeroki wybór instrumentów: gitary, basy, perkusje i więcej. Znajdź idealny sprzęt dla siebie."
    name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/home.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <script type="module" src="../assets/js/home.js"></script>
  <title>Sklep Muzyczny</title>
</head>
<body>
<main class="fade-in">
  <?php include '../components/header.php'; ?>

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
      ?>
    </div>
  </section>
  <section class="popular-products fade-in">
    <div class="popular-section">
      <h2 class="section-title">Najczęściej Kupowane</h2>
      <div class="products-grid">
        <?php
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

          while ($row = mysqli_fetch_assoc($result)) {
            echo "
              <article class=\"product-card\">
                <div class=\"product-image\">
                  <img alt=\"{$row['alt_text']}\" src=\"{$row['url']}\">
                  <span class=\"category-badge\">{$row['nazwa_kategorii']}</span>
                </div>
                <div class=\"product-info\">
                  <h3 class=\"product-name\">{$row['nazwa']}</h3>
                  <p class=\"product-price\">{$row['cena']} PLN</p>
                  <form method=\"post\" action=\"home.php\">
                    <input type=\"hidden\" name=\"product_id\" value=\"{$row['id']}\">
                    <input type=\"hidden\" name=\"product_type\" value=\"buy\">
                    <button type=\"submit\" name=\"add_to_cart\" class=\"product-action-btn buy-product-btn\">
                      Kup <i class=\"fa-solid fa-cart-plus\"></i>
                    </button>
                  </form>
                </div>
              </article>
            ";
          }
        ?>
      </div>
    </div>

    <div class="popular-section">
      <h2 class="section-title">Najczęściej Wypożyczane</h2>
      <div class="products-grid">
        <?php
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

          while ($row = mysqli_fetch_assoc($result)) {
            echo "
              <article class=\"product-card\">
                <div class=\"product-image\">
                  <img alt=\"{$row['alt_text']}\" src=\"{$row['url']}\">
                  <span class=\"category-badge\">{$row['nazwa_kategorii']}</span>
                </div>
                <div class=\"product-info\">
                  <h3 class=\"product-name\">{$row['nazwa']}</h3>
                  <p class=\"product-price\">{$row['cena']} PLN</p>
                  <form method=\"post\" action=\"home.php\">
                    <input type=\"hidden\" name=\"product_id\" value=\"{$row['id']}\">
                    <input type=\"hidden\" name=\"product_type\" value=\"rent\">
                    <button type=\"submit\" name=\"add_to_cart\" class=\"product-action-btn buy-product-btn\">
                      Wypożycz <i class=\"fa-solid fa-cart-plus\"></i>
                    </button>
                    </form>
                </div>
              </article>
            ";
          }
        ?>
      </div>
    </div>
  </section>
</main>
<?php mysqli_close($connection); ?>
<?php include '../components/footer.php'; ?>
</body>
</html>