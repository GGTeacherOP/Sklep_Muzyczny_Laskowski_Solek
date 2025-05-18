<?php
/**
 * Pobiera szczegóły zamówienia na podstawie ID
 * 
 * @param int $order_id ID zamówienia
 * @param mysqli $connection Połączenie z bazą danych
 * @return array|null Szczegóły zamówienia lub null jeśli nie znaleziono
 */
function fetchOrderDetails($order_id, $connection) {
  // Pobieranie informacji o zamówieniu
  $order_id = mysqli_real_escape_string($connection, $order_id);
  
  $sql = "SELECT z.*, u.nazwa_uzytkownika as nazwa_klienta, k.id as klient_id
          FROM zamowienia z
          JOIN klienci k ON z.klient_id = k.id
          JOIN uzytkownicy u ON k.uzytkownik_id = u.id
          WHERE z.id = '$order_id'";
  $result = mysqli_query($connection, $sql);
  $order = mysqli_fetch_assoc($result);

  if (!$order) {
    return null;
  }

  // Pobieranie szczegółów zamówienia
  $sql = "SELECT zs.*, i.nazwa, i.kod_produktu
          FROM zamowienie_szczegoly zs
          JOIN instrumenty i ON zs.instrument_id = i.id
          WHERE zs.zamowienie_id = '$order_id'";
  $result = mysqli_query($connection, $sql);
  $items = [];
  $subtotal = 0;

  while ($item = mysqli_fetch_assoc($result)) {
    $items[] = $item;
    $subtotal += $item['cena'] * $item['ilosc'];
  }

  // Pobieranie informacji o kodzie promocyjnym, jeśli istnieje
  $discount_percent = 0;
  if (!empty($order['kod_promocyjny_id'])) {
    $kod_promocyjny_id = $order['kod_promocyjny_id'];
    $sql = "SELECT znizka FROM kody_promocyjne WHERE id = '$kod_promocyjny_id'";
    $result = mysqli_query($connection, $sql);
    if ($promocja = mysqli_fetch_assoc($result)) {
      $discount_percent = $promocja['znizka'];
    }
  }

  // Obliczanie podsumowania
  $discount_amount = $discount_percent > 0 ? $subtotal * ($discount_percent / 100) : 0;
  $total = $subtotal - $discount_amount;

  $summary = [
    'subtotal' => number_format($subtotal, 2),
    'discount' => $discount_percent > 0 ? number_format($discount_amount, 2) : null,
    'discount_percent' => $discount_percent > 0 ? $discount_percent : null,
    'total' => number_format($total, 2)
  ];

  // Formatowanie daty
  $order['data_zamowienia'] = date('d.m.Y H:i', strtotime($order['data_zamowienia']));

  // Tabela adresy nie istnieje w bazie danych, więc ustawiamy adres na null
  $order['adres'] = null;

  return [
    'order' => $order,
    'items' => $items,
    'summary' => $summary
  ];
} 