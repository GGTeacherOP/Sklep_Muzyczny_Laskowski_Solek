<?php
/**
 * Pobiera listę zamówień z możliwością sortowania
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Pobiera listę zamówień z możliwością sortowania
 * 
 * @param mysqli $connection Połączenie z bazą danych
 * @param string $sort_column Kolumna sortowania
 * @param string $sort_dir Kierunek sortowania (asc/desc)
 * @return mysqli_result Wynik zapytania z zamówieniami
 */
function fetchOrders($connection, $sort_column = DEFAULT_SORT_COLUMN, $sort_dir = DEFAULT_SORT_DIRECTION) {
    // Zabezpieczenie przed SQL Injection przy sortowaniu
    if (!in_array($sort_column, ORDER_SORT_COLUMNS)) {
        $sort_column = DEFAULT_SORT_COLUMN;
    }
    
    $sort_dir = strtolower($sort_dir);
    if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
        $sort_dir = DEFAULT_SORT_DIRECTION;
    }
    
    // Pobranie zamówień z dodatkowymi informacjami
    $sql = "SELECT z.*, 
            u.nazwa_uzytkownika,
            (SELECT SUM(zs.ilosc * zs.cena) FROM zamowienie_szczegoly zs WHERE zs.zamowienie_id = z.id) as wartosc_calkowita
            FROM zamowienia z
            JOIN klienci k ON z.klient_id = k.id
            JOIN uzytkownicy u ON k.uzytkownik_id = u.id";
    
    // Dodanie odpowiedniego sortowania
    if ($sort_column === 'nazwa_uzytkownika') {
        $sql .= " ORDER BY u.$sort_column $sort_dir";
    } else if ($sort_column === 'wartosc_calkowita') {
        $sql .= " ORDER BY wartosc_calkowita $sort_dir";
    } else {
        $sql .= " ORDER BY z.$sort_column $sort_dir";
    }
    
    return mysqli_query($connection, $sql);
} 