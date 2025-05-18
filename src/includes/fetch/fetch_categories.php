<?php
/**
 * Funkcje pobierające dane dotyczące kategorii
 */

/**
 * Pobiera listę kategorii z bazy danych
 * 
 * @param mysqli $connection Połączenie z bazą danych
 * @param string $sort_column Kolumna, według której sortujemy
 * @param string $sort_dir Kierunek sortowania (asc/desc)
 * @return mysqli_result Wynik zapytania zawierający dane kategorii
 */
function fetchCategories($connection, $sort_column = 'nazwa', $sort_dir = 'asc') {
    // Zabezpieczenie przed SQL Injection przy sortowaniu
    if (!in_array($sort_column, CATEGORY_SORT_COLUMNS)) {
        $sort_column = DEFAULT_CATEGORY_SORT_COLUMN;
    }

    $sort_dir = strtolower($sort_dir);
    if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
        $sort_dir = DEFAULT_CATEGORY_SORT_DIRECTION;
    }

    // Pobranie kategorii z liczbą instrumentów
    if ($sort_column === 'liczba_instrumentow') {
        $sql = "SELECT k.*, COUNT(i.id) as liczba_instrumentow 
                FROM kategorie_instrumentow k 
                LEFT JOIN instrumenty i ON k.id = i.kategoria_id 
                GROUP BY k.id 
                ORDER BY COUNT(i.id) $sort_dir, k.nazwa ASC";
    } else {
        $sql = "SELECT k.*, COUNT(i.id) as liczba_instrumentow 
                FROM kategorie_instrumentow k 
                LEFT JOIN instrumenty i ON k.id = i.kategoria_id 
                GROUP BY k.id 
                ORDER BY k.$sort_column $sort_dir";
    }
    
    return mysqli_query($connection, $sql);
} 