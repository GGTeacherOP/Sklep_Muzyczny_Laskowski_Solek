<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/fetch/fetch_popular_products.php';
  include_once '../includes/fetch/fetch_product_categories.php';
  include_once '../includes/render/render_product_card.php';
  include_once '../includes/render/render_category_card.php';
  include_once '../includes/render/render_brand_card.php';
  include_once '../includes/helpers/cart_helpers.php';

  if (isset($_POST['add_to_cart'])) {
    addToCart($connection, $_POST['product_id'], $_POST['product_type']);
    header("Location: home.php");
    exit();
  }

  // Obsługa formularza kontaktowego
  $contact_message = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $email = isset($_SESSION['user_id']) ?
      mysqli_real_escape_string($connection, $_POST['contact_email']) :
      mysqli_real_escape_string($connection, $_POST['contact_email']);
    $subject = mysqli_real_escape_string($connection, $_POST['contact_subject']);
    $message = mysqli_real_escape_string($connection, $_POST['contact_message']);

    if (!empty($email) && !empty($subject) && !empty($message)) {
      $query = "INSERT INTO wiadomosci (email, temat, tresc) VALUES ('$email', '$subject', '$message')";
      if (mysqli_query($connection, $query)) {
        $contact_message = '<p class="success-message">Wiadomość została wysłana. Dziękujemy!</p>';
      } else {
        $contact_message = '<p class="error-message">Wystąpił błąd podczas wysyłania wiadomości.</p>';
      }
    } else {
      $contact_message = '<p class="error-message">Proszę wypełnić wszystkie pola formularza.</p>';
    }
    header("Location: home.php#contact");
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
      <p class="hero-subtitle">Najlepsze instrumenty do kupienia - tylko u nas!</p>
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

  <section class="instrument-brands fade-in">
    <div class="instrument-brands-header">
      <h4 class="instrument-brands-title">Producenci</h4>
      <div class="instrument-brands-controls">
        <a href="katalog.php" class="view-all-button"><strong>Wyświetl wszystko</strong></a>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-left"></i></button>
        <button class="scroll-button" type="button"><i class="fa-solid fa-caret-right"></i></button>
      </div>
    </div>
    <div class="instrument-brands-list fade-in">
      <?php while ($producer = mysqli_fetch_assoc($producers)) : ?><?php echo renderBrandCard($producer); ?><?php endwhile; ?>
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
  </section>

  <section class="contact-section fade-in" id="contact">
    <div class="contact-container">
      <div class="contact-info">
        <h2 class="section-title">Skontaktuj się z nami</h2>
        <p class="contact-subtitle">
          Jeśli masz pytania dotyczące naszych produktów lub potrzebujesz pomocy w dokonaniu wyboru, nie wahaj się z
          nami skontaktować. Nasz zespół z przyjemnością odpowie na Twoje pytania i pomoże Ci znaleźć idealne
          rozwiązanie muzyczne. </p>
        <p class="contact-details">
          <strong>Email:</strong> kontakt@sklepmuzyczny.pl<br>
          <strong>Telefon:</strong> +48 123 456 789<br>
          <strong>Godziny otwarcia:</strong> Pon-Pt: 9:00 - 17:00 </p>
      </div>

      <div class="contact-form-wrapper">
        <?php echo $contact_message; ?>

        <form class="contact-form" method="POST" action="">
          <form class="contact-form" method="POST" action="">
            <?php if (isset($_SESSION['user_id'])):
              $user_id = $_SESSION['user_id'];
              $user_query = "SELECT email FROM uzytkownicy WHERE id = $user_id";
              $user_result = mysqli_query($connection, $user_query);
              $user_data = mysqli_fetch_assoc($user_result);
              $user_email = $user_data['email'];
              ?>
              <div class="form-group">
                <label for="contact_email">Twój email (zalogowany jako <?php echo $user_email; ?>)</label>
                <input type="email" id="contact_email" name="contact_email" value="<?php echo $user_email; ?>" required>
                <small>Możesz zmienić email, jeśli chcesz otrzymać odpowiedź na inny adres</small>
              </div>
            <?php else: ?>
              <div class="form-group">
                <label for="contact_email">Twój email</label>
                <input type="email" id="contact_email" name="contact_email" required>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label for="contact_subject">Temat</label>
              <input type="text" id="contact_subject" name="contact_subject" required>
            </div>

            <div class="form-group">
              <label for="contact_message">Wiadomość</label>
              <textarea id="contact_message" name="contact_message" rows="5" required></textarea>
            </div>

            <button type="submit" name="submit_contact" class="submit-button">Wyślij wiadomość</button>
          </form>
        </form>
      </div>
    </div>
  </section>

</main>
<?php mysqli_close($connection); ?>
<?php include '../components/footer.php'; ?>
</body>
</html>