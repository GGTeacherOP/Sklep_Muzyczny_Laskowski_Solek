<?php
/**
 * Pomocnicze funkcje dla sortowania w panelu administratora
 */

/**
 * Generuje link do sortowania dla kolumny
 * 
 * @param string $column Nazwa kolumny
 * @param string $current_sort Aktualnie sortowana kolumna
 * @param string $current_dir Aktualny kierunek sortowania
 * @param string $base_url Podstawowy URL, np. "?view=orders"
 * @return string Link do sortowania
 */
function getSortLink($column, $current_sort, $current_dir, $base_url) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "{$base_url}&sort={$column}&dir={$dir}";
}

/**
 * Generuje ikonę sortowania dla kolumny
 * 
 * @param string $column Nazwa kolumny
 * @param string $current_sort Aktualnie sortowana kolumna
 * @param string $current_dir Aktualny kierunek sortowania
 * @return string Kod HTML z ikoną sortowania
 */
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

/**
 * Generuje nagłówek kolumny z sortowaniem
 * 
 * @param string $column Nazwa kolumny
 * @param string $label Etykieta do wyświetlenia
 * @param string $current_sort Aktualnie sortowana kolumna
 * @param string $current_dir Aktualny kierunek sortowania
 * @param string $base_url Podstawowy URL, np. "?view=orders"
 * @return string Kod HTML nagłówka kolumny
 */
function getSortableHeader($column, $label, $current_sort, $current_dir, $base_url) {
    $link = getSortLink($column, $current_sort, $current_dir, $base_url);
    $icon = getSortIcon($column, $current_sort, $current_dir);
    
    return '<a href="' . $link . '" class="sort-link">' . $label . ' ' . $icon . '</a>';
} 