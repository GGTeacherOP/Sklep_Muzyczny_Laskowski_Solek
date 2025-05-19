<?php
  /** @var mysqli $connection */
  include_once '../includes/config/db_config.php';
  include_once '../includes/config/session_config.php';
  include_once '../includes/helpers/cart_helpers.php';
  include_once '../includes/helpers/format_helpers.php';
  
  // Sprawdzanie czy ID produktu zostało przekazane
  if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
  }
  
  $product_id = (int)$_GET['id'];
  $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
  $user_rating = null;
  
  // Obsługa dodawania do koszyka
  if (isset($_POST['add_to_cart'])) {
    addToCart($_POST['product_id'], $_POST['product_type']);
    header("Location: produkt.php?id=" . $product_id . "&dodano=true");
    exit();
  }
  
  // Obsługa dodawania opinii
  if (isset($_POST['add_rating']) && $user_id) {
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
      $checkQuery = "SELECT id FROM instrument_oceny WHERE instrument_id = ? AND user_id = ?";
      $stmt = $connection->prepare($checkQuery);
      $stmt->bind_param("ii", $product_id, $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows === 0) {
        // Dodaj nową opinię
        $addQuery = "INSERT INTO instrument_oceny (instrument_id, user_id, ocena, komentarz, data_oceny) 
                    VALUES (?, ?, ?, ?, NOW())";
        $stmt = $connection->prepare($addQuery);
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
        $stmt->execute();
        header("Location: produkt.php?id=" . $product_id);
        exit();
      }
    }
  }
  
  // Obsługa edycji opinii
  if (isset($_POST['edit_rating']) && $user_id) {
    $rating_id = (int)$_POST['rating_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
      $updateQuery = "UPDATE instrument_oceny 
                     SET ocena = ?, komentarz = ?, czy_edytowana = 1, data_edycji = NOW() 
                     WHERE id = ? AND user_id = ?";
      $stmt = $connection->prepare($updateQuery);
      $stmt->bind_param("isii", $rating, $comment, $rating_id, $user_id);
      $stmt->execute();
      header("Location: produkt.php?id=" . $product_id);
      exit();
    }
  }
  
  // Obsługa usuwania opinii
  if (isset($_POST['delete_rating']) && $user_id) {
    $rating_id = (int)$_POST['rating_id'];
    
    $deleteQuery = "DELETE FROM instrument_oceny WHERE id = ? AND user_id = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param("ii", $rating_id, $user_id);
    $stmt->execute();
    header("Location: produkt.php?id=" . $product_id);
    exit();
  }
  
  // Pobranie szczegółów produktu
  $query = "
    SELECT i.*, k.nazwa AS nazwa_kategorii, p.nazwa AS nazwa_producenta, 
           z.url, z.alt_text 
    FROM instrumenty i 
    LEFT JOIN kategorie_instrumentow k ON i.kategoria_id = k.id 
    LEFT JOIN producenci p ON i.producent_id = p.id 
    LEFT JOIN instrument_zdjecia z ON i.id = z.instrument_id 
    WHERE i.id = ?
    LIMIT 1
  ";
  
  $stmt = $connection->prepare($query);
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    header("Location: home.php");
    exit();
  }
  
  $product = $result->fetch_assoc();
  
  // Sprawdzenie czy użytkownik już wystawił ocenę
  if ($user_id) {
    $userRatingQuery = "
      SELECT * FROM instrument_oceny 
      WHERE instrument_id = ? AND user_id = ?
    ";
    
    $stmt = $connection->prepare($userRatingQuery);
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $userRatingResult = $stmt->get_result();
    
    if ($userRatingResult->num_rows > 0) {
      $user_rating = $userRatingResult->fetch_assoc();
    }
  }
  
  // Pobranie ocen produktu (bez oceny zalogowanego użytkownika)
  $query_ratings = "
    SELECT o.*, u.nazwa_uzytkownika 
    FROM instrument_oceny o
    JOIN uzytkownicy u ON o.user_id = u.id
    WHERE o.instrument_id = ? " . 
    ($user_id ? "AND o.user_id != ?" : "") . "
    ORDER BY o.data_oceny DESC
  ";
  
  $stmt_ratings = $connection->prepare($query_ratings);
  
  if ($user_id) {
    $stmt_ratings->bind_param("ii", $product_id, $user_id);
  } else {
    $stmt_ratings->bind_param("i", $product_id);
  }
  
  $stmt_ratings->execute();
  $ratings = $stmt_ratings->get_result();
  
  // Obliczenie średniej oceny
  $query_avg_rating = "
    SELECT AVG(ocena) AS srednia_ocena, COUNT(*) AS liczba_ocen 
    FROM instrument_oceny 
    WHERE instrument_id = ?
  ";
  
  $stmt_avg = $connection->prepare($query_avg_rating);
  $stmt_avg->bind_param("i", $product_id);
  $stmt_avg->execute();
  $avg_result = $stmt_avg->get_result()->fetch_assoc();
  
  $avg_rating = $avg_result['srednia_ocena'] ? round($avg_result['srednia_ocena'], 1) : 0;
  $ratings_count = $avg_result['liczba_ocen'];
  
  // Pobranie wszystkich zdjęć produktu
  $query_images = "
    SELECT * FROM instrument_zdjecia 
    WHERE instrument_id = ? 
    ORDER BY kolejnosc ASC
  ";
  
  $stmt_images = $connection->prepare($query_images);
  $stmt_images->bind_param("i", $product_id);
  $stmt_images->execute();
  $images = $stmt_images->get_result();
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="<?php echo htmlspecialchars($product['nazwa']); ?>, <?php echo htmlspecialchars($product['nazwa_kategorii']); ?>, sklep muzyczny" name="keywords">
  <meta content="<?php echo htmlspecialchars($product['opis']); ?>" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="../assets/css/product.css" rel="stylesheet">
  <script type="module" src="../assets/js/header.js"></script>
  <title><?php echo htmlspecialchars($product['nazwa']); ?> | Sklep Muzyczny</title>
</head>
<body>
<?php include '../components/header.php'; ?>

<main class="product-container fade-in">
  <div class="product-header">
    <a href="home.php" class="back-link">
      <i class="fa-solid fa-arrow-left"></i> Powrót do strony głównej
    </a>
    <h1 class="product-title"><?php echo htmlspecialchars($product['nazwa']); ?></h1>
  </div>

  <div class="product-details">
    <div class="product-images">
      <?php
        // Tworzymy tablicę zdjęć produktu
        $product_images = [];
        if ($images->num_rows > 0) {
          mysqli_data_seek($images, 0); // Reset wskaźnika wyników
          while($image = $images->fetch_assoc()) {
            $product_images[] = $image;
          }
        } else {
          // Dodaj główne zdjęcie, jeśli nie ma innych
          $product_images[] = [
            'url' => $product['url'],
            'alt_text' => $product['alt_text']
          ];
        }
      ?>
      <div class="main-image">
        <img src="<?php echo htmlspecialchars($product_images[0]['url']); ?>" alt="<?php echo htmlspecialchars($product_images[0]['alt_text']); ?>" id="mainImage">
      </div>
      
      <?php if (count($product_images) > 1): ?>
        <div class="carousel-controls">
          <button class="carousel-button prev-btn">
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          <button class="carousel-button next-btn">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
        <div class="image-thumbnails">
          <?php foreach($product_images as $index => $image): ?>
            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
              <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text']); ?>">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <div class="product-badge-container">
        <span class="product-badge category"><?php echo htmlspecialchars($product['nazwa_kategorii']); ?></span>
        <span class="product-badge producer"><?php echo htmlspecialchars($product['nazwa_producenta']); ?></span>
        <?php if ($product['stan_magazynowy'] > 0): ?>
          <span class="product-badge in-stock">Dostępny</span>
        <?php else: ?>
          <span class="product-badge out-of-stock">Niedostępny</span>
        <?php endif; ?>
      </div>

      <?php if ($ratings_count > 0): ?>
        <div class="product-ratings">
          <div class="stars">
            <?php 
              for ($i = 1; $i <= 5; $i++) {
                if ($i <= $avg_rating) {
                  echo '<i class="fa-solid fa-star"></i>';
                } elseif ($i - 0.5 <= $avg_rating) {
                  echo '<i class="fa-solid fa-star-half-stroke"></i>';
                } else {
                  echo '<i class="fa-regular fa-star"></i>';
                }
              }
            ?>
          </div>
          <span class="rating-count">(<?php echo $ratings_count; ?> ocen)</span>
        </div>
      <?php endif; ?>

      <div class="product-price-container">
        <div class="product-price">
          <span class="price-label">Cena:</span>
          <span class="price-value"><?php echo formatPrice($product['cena_sprzedazy']); ?></span>
        </div>
        
        <?php if ($product['cena_wypozyczenia_dzien'] > 0): ?>
          <div class="product-price">
            <span class="price-label">Cena wypożyczenia (dzień):</span>
            <span class="price-value"><?php echo formatPrice($product['cena_wypozyczenia_dzien']); ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="product-availability">
        <span class="availability-label">Dostępność:</span>
        <span><?php echo $product['stan_magazynowy']; ?> szt.</span>
      </div>

      <div class="product-code">
        <span class="code-label">Kod produktu:</span>
        <span><?php echo htmlspecialchars($product['kod_produktu']); ?></span>
      </div>

      <div class="product-actions">
        <form method="post">
          <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
          <input type="hidden" name="product_type" value="buy">
          <button type="submit" name="add_to_cart" class="product-action-btn buy-product-btn" <?php echo $product['stan_magazynowy'] <= 0 ? 'disabled' : ''; ?>>
            Kup teraz <i class="fa-solid fa-cart-plus"></i>
          </button>
        </form>
        
        <?php if ($product['cena_wypozyczenia_dzien'] > 0): ?>
          <form method="post">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="product_type" value="rent">
            <button type="submit" name="add_to_cart" class="product-action-btn rent-product-btn" <?php echo $product['stan_magazynowy'] <= 0 ? 'disabled' : ''; ?>>
              Wypożycz <i class="fa-solid fa-handshake"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="product-description">
    <h2>Opis produktu</h2>
    <p><?php echo nl2br(htmlspecialchars($product['opis'])); ?></p>
  </div>

  <div class="product-specifications">
    <h2>Specyfikacja</h2>
    <table class="specs-table">
      <tr>
        <th>Nazwa</th>
        <td><?php echo htmlspecialchars($product['nazwa']); ?></td>
      </tr>
      <tr>
        <th>Producent</th>
        <td><?php echo htmlspecialchars($product['nazwa_producenta']); ?></td>
      </tr>
      <tr>
        <th>Kategoria</th>
        <td><?php echo htmlspecialchars($product['nazwa_kategorii']); ?></td>
      </tr>
      <tr>
        <th>Kod produktu</th>
        <td><?php echo htmlspecialchars($product['kod_produktu']); ?></td>
      </tr>
    </table>
  </div>

  <div class="product-reviews">
    <h2>Opinie klientów <?php echo $ratings_count > 0 ? "(" . $ratings_count . ")" : ""; ?></h2>
    
    <?php if ($user_id): ?>
      <?php if ($user_rating): ?>
        <!-- Wyświetl ocenę użytkownika z opcją edycji/usunięcia -->
        <div class="user-review">
          <div class="review-item user-review-item">
            <div class="review-header">
              <div>
                <div class="review-stars">
                  <?php 
                    for ($i = 1; $i <= 5; $i++) {
                      if ($i <= $user_rating['ocena']) {
                        echo '<i class="fa-solid fa-star"></i>';
                      } else {
                        echo '<i class="fa-regular fa-star"></i>';
                      }
                    }
                  ?>
                </div>
                <span class="user-badge">Twoja opinia</span>
              </div>
              <span class="review-date">
                <?php echo date('d.m.Y', strtotime($user_rating['data_oceny'])); ?>
                <?php if ($user_rating['czy_edytowana']): ?>
                  <span class="edited-badge">(edytowano <?php echo date('d.m.Y', strtotime($user_rating['data_edycji'])); ?>)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="review-content">
              <?php echo nl2br(htmlspecialchars($user_rating['komentarz'])); ?>
            </div>
            <div class="review-actions">
              <button class="edit-review-btn" onclick="showEditForm()">Edytuj</button>
              <form method="post" class="delete-review-form">
                <input type="hidden" name="rating_id" value="<?php echo $user_rating['id']; ?>">
                <button type="submit" name="delete_rating" class="delete-review-btn">Usuń</button>
              </form>
            </div>
          </div>
          
          <!-- Formularz edycji opinii (ukryty domyślnie) -->
          <div class="review-edit-form" id="editReviewForm" style="display: none;">
            <h3>Edytuj swoją opinię</h3>
            <form method="post">
              <input type="hidden" name="rating_id" value="<?php echo $user_rating['id']; ?>">
              
              <div class="form-group">
                <div class="rating-input">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="edit-star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($user_rating['ocena'] == $i) ? 'checked' : ''; ?>>
                    <label for="edit-star<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                  <?php endfor; ?>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label" for="edit-comment">Komentarz:</label>
                <textarea id="edit-comment" name="comment" class="form-input" required><?php echo htmlspecialchars($user_rating['komentarz']); ?></textarea>
              </div>
              
              <div class="form-actions">
                <button type="submit" name="edit_rating" class="submit-review-btn">Zapisz zmiany</button>
                <button type="button" class="cancel-btn" onclick="hideEditForm()">Anuluj</button>
              </div>
            </form>
          </div>
        </div>
      <?php else: ?>
        <!-- Formularz dodawania opinii dla zalogowanego użytkownika bez opinii -->
        <div class="add-review-form">
          <h3>Dodaj swoją opinię</h3>
          <form method="post">
            <div class="form-group">
              <div class="rating-input">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                  <label for="star<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                <?php endfor; ?>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="comment">Komentarz:</label>
              <textarea id="comment" name="comment" class="form-input" required placeholder="Podziel się swoją opinią na temat tego produktu..."></textarea>
            </div>
            
            <button type="submit" name="add_rating" class="submit-review-btn">Dodaj opinię</button>
          </form>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <!-- Informacja dla niezalogowanych użytkowników -->
      <div class="login-to-review">
        <p>Zaloguj się, aby dodać opinię o tym produkcie.</p>
        <a href="login.php" class="login-btn">Zaloguj się</a>
      </div>
    <?php endif; ?>
    
    <?php if ($ratings->num_rows > 0 || $user_rating): ?>
      <div class="reviews-list">
        <?php while ($rating = $ratings->fetch_assoc()): ?>
          <div class="review-item">
            <div class="review-header">
              <div>
                <div class="review-stars">
                  <?php 
                    for ($i = 1; $i <= 5; $i++) {
                      if ($i <= $rating['ocena']) {
                        echo '<i class="fa-solid fa-star"></i>';
                      } else {
                        echo '<i class="fa-regular fa-star"></i>';
                      }
                    }
                  ?>
                </div>
                <span class="reviewer-name"><?php echo htmlspecialchars($rating['nazwa_uzytkownika']); ?></span>
              </div>
              <span class="review-date">
                <?php echo date('d.m.Y', strtotime($rating['data_oceny'])); ?>
                <?php if ($rating['czy_edytowana']): ?>
                  <span class="edited-badge">(edytowano <?php echo date('d.m.Y', strtotime($rating['data_edycji'])); ?>)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="review-content">
              <?php echo nl2br(htmlspecialchars($rating['komentarz'])); ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="no-reviews">
        <p>Ten produkt nie ma jeszcze opinii. Bądź pierwszy i podziel się swoją oceną!</p>
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    let currentIndex = 0;
    const imagesData = <?php echo json_encode($product_images); ?>;
    
    if (thumbnails.length > 0) {
      // Obsługa kliknięcia na miniaturę
      thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
          const index = parseInt(this.dataset.index);
          showImage(index);
        });
      });
      
      // Obsługa przycisków karuzeli
      if (nextBtn && prevBtn) {
        nextBtn.addEventListener('click', function() {
          showImage((currentIndex + 1) % imagesData.length);
        });
        
        prevBtn.addEventListener('click', function() {
          showImage((currentIndex - 1 + imagesData.length) % imagesData.length);
        });
      }
    }
    
    // Funkcja pokazująca wybrane zdjęcie
    function showImage(index) {
      if (index < 0 || index >= imagesData.length || index === currentIndex) return;
      
      currentIndex = index;
      mainImage.src = imagesData[index].url;
      mainImage.alt = imagesData[index].alt_text;
      
      // Aktualizacja klasy active dla miniatur
      thumbnails.forEach((thumb, i) => {
        if (i === index) {
          thumb.classList.add('active');
        } else {
          thumb.classList.remove('active');
        }
      });
    }
  });
  
  // Funkcje do obsługi formularza edycji
  function showEditForm() {
    document.getElementById('editReviewForm').style.display = 'block';
  }
  
  function hideEditForm() {
    document.getElementById('editReviewForm').style.display = 'none';
  }
</script>

<?php 
  mysqli_free_result($result);
  mysqli_free_result($ratings);
  mysqli_free_result($images);
  mysqli_close($connection); 
?>

<?php include '../components/footer.php'; ?>
</body>
</html> 