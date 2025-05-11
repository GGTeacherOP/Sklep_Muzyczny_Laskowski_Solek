<?php
  function addToCart($productId, $productType, $quantity = 1) : void
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