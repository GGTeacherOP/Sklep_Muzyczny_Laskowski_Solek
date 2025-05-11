<?php
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
    $query = "SELECT id FROM koszyk WHERE klient_id = $userId";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
      $cartId = mysqli_fetch_assoc($result)['id'];
      mysqli_free_result($result);
    } else {
      $query = "INSERT INTO koszyk (klient_id) VALUES ($userId)";
      mysqli_query($connection, $query);
      $cartId = mysqli_insert_id($connection);
    }

    mysqli_query($connection, "DELETE FROM koszyk_szczegoly WHERE koszyk_id = $cartId");

    foreach (['buy', 'rent'] as $type) {
      foreach ($cartItems[$type] as $productId => $product) {
        $quantity = intval($product['quantity']);
        $price = floatval($product['cena']);

        $query = "
                INSERT INTO koszyk_szczegoly (koszyk_id, instrument_id, typ, ilosc, cena)
                VALUES ($cartId, $productId, '$type', $quantity, $price)
            ";
        mysqli_query($connection, $query);
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
?>