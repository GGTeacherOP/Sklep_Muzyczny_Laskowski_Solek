<?php
/**
 * Pobiera zamówienia klienta
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Pobiera listę zamówień konkretnego klienta
 * 
 * @param int $client_id ID klienta
 * @param mysqli $connection Połączenie z bazą danych
 * @param string $sort_column Kolumna sortowania
 * @param string $sort_dir Kierunek sortowania (asc/desc)
 * @return array Lista zamówień klienta
 */
function fetchClientOrders($client_id, $connection, $sort_column = DEFAULT_SORT_COLUMN, $sort_dir = DEFAULT_SORT_DIRECTION) {
    // Zabezpieczenie przed SQL Injection
    $client_id = mysqli_real_escape_string($connection, $client_id);
    
    // Zabezpieczenie przed SQL Injection przy sortowaniu
    if (!in_array($sort_column, ORDER_SORT_COLUMNS)) {
        $sort_column = DEFAULT_SORT_COLUMN;
    }
    
    $sort_dir = strtolower($sort_dir);
    if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
        $sort_dir = DEFAULT_SORT_DIRECTION;
    }
    
    // Pobranie zamówień klienta
    $sql = "SELECT z.*, 
            (SELECT SUM(zs.ilosc * zs.cena) FROM zamowienie_szczegoly zs WHERE zs.zamowienie_id = z.id) as wartosc_calkowita
            FROM zamowienia z
            WHERE z.klient_id = '$client_id'";
    
    // Dodanie odpowiedniego sortowania
    if ($sort_column === 'wartosc_calkowita') {
        $sql .= " ORDER BY wartosc_calkowita $sort_dir";
    } else {
        $sql .= " ORDER BY z.$sort_column $sort_dir";
    }
    
    $result = mysqli_query($connection, $sql);
    $orders = [];
    
    while ($order = mysqli_fetch_assoc($result)) {
        // Pobierz liczbę produktów w zamówieniu
        $order_id = $order['id'];
        $items_sql = "SELECT COUNT(*) as count FROM zamowienie_szczegoly WHERE zamowienie_id = '$order_id'";
        $items_result = mysqli_query($connection, $items_sql);
        $items_count = mysqli_fetch_assoc($items_result);
        
        $order['liczba_produktow'] = $items_count['count'];
        $order['data_zamowienia_format'] = date('d.m.Y H:i', strtotime($order['data_zamowienia']));
        $order['statusClass'] = ORDER_STATUSES[$order['status']]['class'] ?? 'status-unknown';
        
        $orders[] = $order;
    }
    
    return $orders;
} 