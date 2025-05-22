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

    // Pobierz ID klienta
    $query = "SELECT k.id FROM klienci k WHERE k.uzytkownik_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $clientId = mysqli_fetch_assoc($result)['id'];
        mysqli_free_result($result);

        // Pobierz ID koszyka
        $query = "SELECT id FROM koszyk WHERE klient_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, 'i', $clientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $cartId = mysqli_fetch_assoc($result)['id'];
            mysqli_free_result($result);

            // Pobierz szczegóły koszyka z pełnymi informacjami o produktach
            $query = "
                SELECT ks.instrument_id, ks.typ, ks.ilosc, i.cena_sprzedazy,
                       i.nazwa, i.kod_produktu, k.nazwa as nazwa_kategorii,
                       iz.url, iz.alt_text
                FROM koszyk_szczegoly ks
                JOIN instrumenty i ON ks.instrument_id = i.id
                JOIN kategorie_instrumentow k ON i.kategoria_id = k.id
                JOIN instrument_zdjecia iz ON i.id = iz.instrument_id
                WHERE ks.koszyk_id = ? AND ks.typ IN ('buy', 'rent')
            ";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, 'i', $cartId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $productId = intval($row['instrument_id']);
                    $type = $row['typ'] ?: 'buy'; // Domyślnie 'buy' jeśli typ jest pusty
                    $quantity = intval($row['ilosc']);

                    // Zawsze aktualizuj ilość produktu w koszyku sesji
                    $_SESSION['cart'][$type][$productId] = [
                        'id' => $productId,
                        'quantity' => $quantity,
                        'cena_sprzedazy' => $row['cena_sprzedazy'],
                        'nazwa' => $row['nazwa'],
                        'kod_produktu' => $row['kod_produktu'],
                        'nazwa_kategorii' => $row['nazwa_kategorii'],
                        'url' => $row['url'],
                        'alt_text' => $row['alt_text']
                    ];
                }
            }
            mysqli_free_result($result);
        }
    }
  }

  function addToCart(mysqli $connection, int $productId, string $productType, int $quantity = 1) : void
  {
    if (!isset($_SESSION['cart'])) {
      $_SESSION['cart'] = [
        'buy' => [],
        'rent' => [],
      ];
    }

    // Pobierz stan magazynowy produktu
    $query = "SELECT stan_magazynowy FROM instrumenty WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
      $product = mysqli_fetch_assoc($result);
      $stock = intval($product['stan_magazynowy']);

      // Oblicz aktualną ilość w koszyku
      $currentQuantity = isset($_SESSION['cart'][$productType][$productId])
        ? $_SESSION['cart'][$productType][$productId]['quantity']
        : 0;

      // Sprawdź czy nowa ilość nie przekracza stanu magazynowego
      if (($currentQuantity + $quantity) > $stock) {
        // Możesz rzucić wyjątek lub ustawić ilość na maksymalną możliwą
        $quantity = max(0, $stock - $currentQuantity);

        if ($quantity <= 0) {
          // Nie ma już dostępnych sztuk
          return;
        }
      }
    }
    mysqli_free_result($result);

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

    // Pobierz aktualne pozycje z koszyka
    $currentItemsQuery = "SELECT instrument_id, typ, ilosc FROM koszyk_szczegoly WHERE koszyk_id = ?";
    $stmt = mysqli_prepare($connection, $currentItemsQuery);
    mysqli_stmt_bind_param($stmt, 'i', $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $currentItems = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $currentItems[$row['typ']][$row['instrument_id']] = $row['ilosc'];
    }
    mysqli_free_result($result);

    // Aktualizuj lub dodaj nowe pozycje do koszyka
    foreach (['buy', 'rent'] as $type) {
      foreach ($cartItems[$type] as $productId => $product) {
        $quantity = intval($product['quantity']);
        $price = floatval($product['cena_sprzedazy']);

        if (isset($currentItems[$type][$productId])) {
          // Aktualizuj istniejącą pozycję
          $updateQuery = "UPDATE koszyk_szczegoly SET ilosc = ?, cena = ? WHERE koszyk_id = ? AND instrument_id = ? AND typ = ?";
          $stmt = mysqli_prepare($connection, $updateQuery);
          mysqli_stmt_bind_param($stmt, 'idiis', $quantity, $price, $cartId, $productId, $type);
          mysqli_stmt_execute($stmt);
        } else {
          // Dodaj nową pozycję
          $insertQuery = "INSERT INTO koszyk_szczegoly (koszyk_id, instrument_id, typ, ilosc, cena) VALUES (?, ?, ?, ?, ?)";
          $stmt = mysqli_prepare($connection, $insertQuery);
          mysqli_stmt_bind_param($stmt, 'iisid', $cartId, $productId, $type, $quantity, $price);
          mysqli_stmt_execute($stmt);
        }
      }
    }

    // Usuń pozycje, które nie są już w koszyku sesji
    $deleteQuery = "DELETE FROM koszyk_szczegoly WHERE koszyk_id = ? AND (instrument_id, typ) NOT IN (";
    $params = [$cartId];
    $types = 'i';
    $placeholders = [];
    
    foreach (['buy', 'rent'] as $type) {
      foreach ($cartItems[$type] as $productId => $product) {
        $placeholders[] = "(?, ?)";
        $params[] = $productId;
        $params[] = $type;
        $types .= 'is';
      }
    }
    
    if (!empty($placeholders)) {
      $deleteQuery .= implode(',', $placeholders) . ")";
      $stmt = mysqli_prepare($connection, $deleteQuery);
      mysqli_stmt_bind_param($stmt, $types, ...$params);
      mysqli_stmt_execute($stmt);
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
          AND instrument_zdjecia.kolejnosc = 1
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

  function getPromoCodeId(mysqli $connection, string $promoCode) : ?int {
    $query = "SELECT id FROM kody_promocyjne WHERE kod = ? AND aktywna = 1 AND data_rozpoczecia <= NOW() AND data_zakonczenia >= NOW()";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 's', $promoCode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['id']);
    }
    
    return null;
  }
?>