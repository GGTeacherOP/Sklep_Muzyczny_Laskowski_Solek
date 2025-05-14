<?php
/**
 * Funkcje pomocnicze do sortowania kategorii
 */

/**
 * Generuje link do sortowania
 * 
 * @param string $column Kolumna, według której ma być sortowanie
 * @param string $current_sort Aktualna kolumna sortowania
 * @param string $current_dir Aktualny kierunek sortowania
 * @return string Link do sortowania
 */
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=categories&sort={$column}&dir={$dir}";
}

/**
 * Generuje ikonę sortowania
 * 
 * @param string $column Kolumna, dla której generowana jest ikona
 * @param string $current_sort Aktualna kolumna sortowania
 * @param string $current_dir Aktualny kierunek sortowania
 * @return string HTML z ikoną sortowania
 */
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
} 