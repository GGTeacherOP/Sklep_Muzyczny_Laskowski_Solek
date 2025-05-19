<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/fetch/fetch_popular_products.php';
  include_once '../includes/fetch/fetch_product_categories.php';
  include_once '../includes/render/render_product_card.php';
  include_once '../includes/render/render_category_card.php';
  include_once '../includes/helpers/cart_helpers.php';

  if (isset($_POST['add_to_cart'])) {
    addToCart($_POST['product_id'], $_POST['product_type']);
    header("Location: home.php");
    exit();
  }

  $popularBuyProducts = getPopularProducts($connection, 'buy');
  $popularRentProducts = getPopularProducts($connection, 'rent');
  $productCategories = getProductCategories($connection);
  
  // Pobieranie producentów
  $producers_query = "SELECT id, nazwa FROM producenci ORDER BY nazwa";
  $producers = mysqli_query($connection, $producers_query);
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
<?php include '../components/header.php'; ?>
<main class="fade-in">
  <section class="hero-section fade-in">
    <div class="hero-content">
      <h1 class="hero-title">Odkryj świat muzyki</h1>
      <p class="hero-subtitle">Najlepsze instrumenty do kupienia i wypożyczenia – tylko u nas!</p>
      <a href="#popular-products" class="hero-button">Zobacz produkty</a>
    </div>
  </section>

  <section class="instrument-types fade-in">
    <div class="instrument-types-header">
      <h4 class="instrument-types-title">Typy produktów</h4>
      <div class="instrument-types-controls">
        <a href="katalog.php" class="view-all-button"><strong>Wyświetl wszystko</strong></a>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-left"></i></button>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-right"></i></button>
      </div>
    </div>
    <div class="instrument-types-list fade-in">
      <?php while ($category = mysqli_fetch_assoc($productCategories)) {
        echo renderCategoryCard($category);
      } ?>
    </div>
  </section>

  <section class="producers-section fade-in">
    <div class="producers-header">
      <h4 class="producers-title">Producenci</h4>
    </div>
    <div class="producers-list fade-in">
      <?php while ($producer = mysqli_fetch_assoc($producers)) : ?>
        <a href="katalog.php?producer_id=<?php echo $producer['id']; ?>" class="producer-card fade-in">
          <span class="producer-name"><?php echo htmlspecialchars($producer['nazwa']); ?></span>
        </a>
      <?php endwhile; ?>
    </div>
  </section>

  <section class="popular-products fade-in" id="popular-products">
    <div class="popular-section">
      <h2 class="section-title">Najczęściej Kupowane</h2>
      <div class="products-grid">
        <?php while ($product = mysqli_fetch_assoc($popularBuyProducts)) {
          echo renderProductCard($product, 'buy');
        } ?>
      </div>
    </div>

    <div class="popular-section">
      <h2 class="section-title">Najczęściej Wypożyczane</h2>
      <div class="products-grid">
        <?php while ($product = mysqli_fetch_assoc($popularRentProducts)) {
          echo renderProductCard($product, 'rent');
        } ?>
      </div>
    </div>
  </section>
</main>
<?php mysqli_close($connection); ?>
<?php include '../components/footer.php'; ?>
</body>
</html>