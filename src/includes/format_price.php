<?php
  function formatPrice(float $price, int $quantity = 1) : string
  {
    $total = $price * $quantity;
    return number_format($total, 2, ',', ' ') . ' zł';
  }
?>