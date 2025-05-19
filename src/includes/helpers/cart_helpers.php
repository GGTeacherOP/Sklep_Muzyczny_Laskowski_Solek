<?php
  function initializeCart(): void {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'buy' => [],
            'rent' => [],
        ];
    }
  }

  function loadUserCart(mysqli $connection, int $userId): void {
    initializeCart();

    $query = "SELECT id FROM koszyk WHERE klient_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $cartId = mysqli_fetch_assoc($result)['id'];
        mysqli_free_result($result);

        $query = "
            SELECT ks.instrument_id, ks.typ, ks.ilosc, i.cena_sprzedazy
            FROM koszyk_szczegoly ks
            JOIN instrumenty i ON ks.instrument_id = i.id
            WHERE ks.koszyk_id = ?
        ";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, 'i', $cartId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $productId = intval($row['instrument_id']);
                $type = $row['typ'];
                $quantity = intval($row['ilosc']);

                $_SESSION['cart'][$type][$productId] = [
                    'quantity' => $quantity,
                ];
            }
        }
        mysqli_free_result($result);
    }
  } 

  function addToCart(int $productId, string $productType, int $quantity = 1) : void
  {
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
  }

  function syncCartWithDatabase(mysqli $connection, int $userId, array &$cartItems) : void
  {
    // Najpierw sprawdź czy użytkownik ma rekord w tabeli klienci
    $checkClientQuery = "SELECT id FROM klienci WHERE uzytkownik_id = ?";
    $stmt = mysqli_prepare($connection, $checkClientQuery);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
      // Jeśli nie ma rekordu w tabeli klienci, utwórz go
      $insertClientQuery = "INSERT INTO klienci (uzytkownik_id) VALUES (?)";
      $stmt = mysqli_prepare($connection, $insertClientQuery);
      mysqli_stmt_bind_param($stmt, 'i', $userId);
      mysqli_stmt_execute($stmt);
      $clientId = mysqli_insert_id($connection);
    } else {
      $clientId = mysqli_fetch_assoc($result)['id'];
    }
    mysqli_free_result($result);

    // Teraz sprawdź czy klient ma koszyk
    $query = "SELECT id FROM koszyk WHERE klient_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
      $cartId = mysqli_fetch_assoc($result)['id'];
      mysqli_free_result($result);
    } else {
      // Utwórz nowy koszyk dla klienta
      $insertCartQuery = "INSERT INTO koszyk (klient_id) VALUES (?)";
      $stmt = mysqli_prepare($connection, $insertCartQuery);
      mysqli_stmt_bind_param($stmt, 'i', $clientId);
      mysqli_stmt_execute($stmt);
      $cartId = mysqli_insert_id($connection);
    }

    // Usuń stare pozycje z koszyka
    $deleteQuery = "DELETE FROM koszyk_szczegoly WHERE koszyk_id = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, 'i', $cartId);
    mysqli_stmt_execute($stmt);

    // Dodaj nowe pozycje do koszyka
    foreach (['buy', 'rent'] as $type) {
      foreach ($cartItems[$type] as $productId => $product) {
        $quantity = intval($product['quantity']);
        $price = floatval($product['cena_sprzedazy']);

        $insertQuery = "INSERT INTO koszyk_szczegoly (koszyk_id, instrument_id, typ, ilosc, cena) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $insertQuery);
        mysqli_stmt_bind_param($stmt, 'iisid', $cartId, $productId, $type, $quantity, $price);
        mysqli_stmt_execute($stmt);
      }
    }
  }

  function getTotalItemsInCart() : int
  {
    $totalItems = 0;
    if (isset($_SESSION['cart'])) {
      foreach ($_SESSION['cart'] as $productType => $products) {
        $totalItems += count($products);
      }
    }
    return $totalItems;
  }

  function getPromoDiscount(mysqli $connection, string $promoCode) : float
  {
    $currentDate = date('Y-m-d H:i:s');
    $query = "
        SELECT znizka FROM kody_promocyjne
        WHERE kod = '$promoCode' 
          AND data_rozpoczecia <= '$currentDate'
          AND data_zakonczenia >= '$currentDate'
    ";
    $result = mysqli_query($connection, $query);
    $discount = 0;
    if ($result && mysqli_num_rows($result) > 0) {
      $promo = mysqli_fetch_assoc($result);
      $discount = $promo['znizka'];
    }
    mysqli_free_result($result);
    return $discount;
  }

  function removeFromCart(int $productId, string $type) : void
  {
    unset($_SESSION['cart'][$type][$productId]);
  }

  function updateCartQuantity(int $productId, string $type, int $quantity) : void
  {
    $_SESSION['cart'][$type][$productId]['quantity'] = max(1, $quantity);
  }

  function calculateTotalAmount($totalBuy, $totalRent, $discount) {
    $totalPriceForItems = $totalBuy + $totalRent;
    $discountAmount = $totalPriceForItems * ($discount / 100);
    $delivery = min($totalPriceForItems / 100, 20);
    $vatTax = round($totalPriceForItems * 0.23, 2);
    $totalAmount = $totalPriceForItems - $discountAmount + $delivery + $vatTax;
  
    return [
      'totalPriceForItems' => $totalPriceForItems,
      'discountAmount' => $discountAmount,
      'delivery' => $delivery,
      'vatTax' => $vatTax,
      'totalAmount' => $totalAmount
    ];
  }
  
  function getCartItemsFromDatabase(mysqli $connection, array $productIds, array &$cartItems) : void
  {
      if (empty($productIds)) {
          return;
      }

      $idList = implode(",", array_map('intval', $productIds));
      $sql = "
          SELECT instrumenty.*, instrument_zdjecia.url, instrument_zdjecia.alt_text, kategorie_instrumentow.nazwa as 'nazwa_kategorii'
          FROM instrumenty
          JOIN instrument_zdjecia ON instrumenty.id = instrument_zdjecia.instrument_id
          JOIN kategorie_instrumentow ON instrumenty.kategoria_id = kategorie_instrumentow.id
          WHERE instrumenty.id IN ($idList)
      ";

      $result = mysqli_query($connection, $sql);

      while ($row = mysqli_fetch_assoc($result)) {
          $productId = $row['id'];
          foreach (['buy', 'rent'] as $type) {
              if (isset($_SESSION['cart'][$type][$productId])) {
                  $row['quantity'] = $_SESSION['cart'][$type][$productId]['quantity'];
                  $cartItems[$type][$productId] = $row;
              }
          }
      }

      mysqli_free_result($result);
  }
?>